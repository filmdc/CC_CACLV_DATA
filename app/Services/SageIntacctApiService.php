<?php

namespace App\Services;

use App\Models\UserSageIntacctToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sage Intacct REST API Service
 *
 * Handles API communication with Sage Intacct REST API using user OAuth tokens.
 */
class SageIntacctApiService
{
    private UserSageIntacctToken $userToken;

    private $setting;

    private ?string $accessToken = null;

    /**
     * Constructor - accepts user token and settings
     */
    public function __construct(UserSageIntacctToken $userToken, $setting)
    {
        $this->userToken = $userToken;
        $this->setting = $setting;
        $this->accessToken = $userToken->access_token;
    }

    /**
     * Ensure we have a valid access token (refresh if needed)
     */
    public function authenticate(): array
    {
        try {
            // Check if token is expired
            if ($this->userToken->isExpired()) {
                return [
                    'success' => false,
                    'message' => 'Token expired - user must re-authorize',
                    'requires_reauth' => true,
                ];
            }

            // Refresh if needed (expires in < 5 minutes)
            if ($this->userToken->needsRefresh()) {
                return $this->refreshAccessToken();
            }

            // Token is valid
            $this->accessToken = $this->userToken->access_token;

            return [
                'success' => true,
                'message' => 'Token is valid',
            ];
        } catch (\Exception $e) {
            Log::error('Sage Intacct authentication check exception', [
                'user_id' => $this->userToken->user_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Refresh the user's access token using refresh token
     */
    private function refreshAccessToken(): array
    {
        try {
            if (! $this->userToken->refresh_token) {
                return [
                    'success' => false,
                    'message' => 'No refresh token available - user must re-authorize',
                    'requires_reauth' => true,
                ];
            }

            $tokenUrl = 'https://api.intacct.com/ia/api/v1/oauth2/token';

            Log::debug('Sage Intacct: Refreshing access token', [
                'user_id' => $this->userToken->user_id,
            ]);

            $response = Http::asForm()
                ->timeout(30)
                ->post($tokenUrl, [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->userToken->refresh_token,
                    'client_id' => $this->setting->client_id,
                    'client_secret' => $this->setting->client_secret,
                ]);

            if (! $response->successful()) {
                $error = $response->json();

                Log::error('Sage Intacct token refresh failed', [
                    'user_id' => $this->userToken->user_id,
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                return [
                    'success' => false,
                    'message' => 'Token refresh failed - user must re-authorize',
                    'requires_reauth' => true,
                ];
            }

            $data = $response->json();

            // Update token in database
            $this->userToken->updateToken(
                $data['access_token'],
                $data['expires_in'] ?? 3600,
                $data['refresh_token'] ?? null
            );

            // Update local reference
            $this->accessToken = $data['access_token'];

            Log::info('Sage Intacct: Access token refreshed successfully', [
                'user_id' => $this->userToken->user_id,
            ]);

            return [
                'success' => true,
                'message' => 'Token refreshed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Sage Intacct token refresh exception', [
                'user_id' => $this->userToken->user_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Test API connectivity
     */
    public function testConnection(): array
    {
        try {
            // Ensure token is valid
            $authResult = $this->authenticate();
            if (! $authResult['success']) {
                return $authResult;
            }

            // Verify company access
            $apiUrl = 'https://api.intacct.com/ia/api/v1/companies/'.urlencode($this->userToken->company_id);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->accessToken,
                    'Accept' => 'application/json',
                ])
                ->get($apiUrl);

            if (! $response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Company verification failed';

                Log::error('Sage Intacct company verification failed', [
                    'user_id' => $this->userToken->user_id,
                    'status' => $response->status(),
                    'error' => $errorMessage,
                ]);

                return [
                    'success' => false,
                    'message' => 'Connection test failed: '.$errorMessage,
                ];
            }

            $companyData = $response->json();
            $companyName = $companyData['name'] ?? $companyData['companyName'] ?? 'Unknown';

            Log::info('Sage Intacct connection test successful', [
                'user_id' => $this->userToken->user_id,
                'company_id' => $this->userToken->company_id,
                'company_name' => $companyName,
            ]);

            return [
                'success' => true,
                'message' => 'Connection successful to '.$companyName,
                'company_name' => $companyName,
                'is_sandbox' => $this->userToken->isSandbox(),
            ];
        } catch (\Exception $e) {
            Log::error('Sage Intacct connection test exception', [
                'user_id' => $this->userToken->user_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Create a record in a custom object
     */
    public function createCustomObjectRecord(string $objectName, array $fieldData): array
    {
        try {
            // Sandbox safety check
            if (! $this->userToken->isSandbox()) {
                Log::warning('Sage Intacct: Creating record in non-sandbox environment', [
                    'user_id' => $this->userToken->user_id,
                    'company_id' => $this->userToken->company_id,
                    'object_name' => $objectName,
                ]);
            }

            // Ensure valid token
            $authResult = $this->authenticate();
            if (! $authResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                ];
            }

            $apiUrl = 'https://api.intacct.com/ia/api/v1/companies/'.urlencode($this->userToken->company_id).
                      '/objects/'.urlencode($objectName);

            Log::info('Sage Intacct API: Creating custom object record', [
                'user_id' => $this->userToken->user_id,
                'object_name' => $objectName,
                'company_id' => $this->userToken->company_id,
                'fields' => array_keys($fieldData),
            ]);

            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->accessToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($apiUrl, $fieldData);

            if (! $response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Failed to create record';

                if (isset($errorData['errors']) && is_array($errorData['errors'])) {
                    $errorMessage .= ': '.implode(', ', $errorData['errors']);
                }

                Log::error('Sage Intacct record creation failed', [
                    'user_id' => $this->userToken->user_id,
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'object_name' => $objectName,
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                ];
            }

            $result = $response->json();
            $recordKey = $result['id'] ?? $result['key'] ?? $result['recordno'] ?? null;

            Log::info('Sage Intacct API: Record created successfully', [
                'user_id' => $this->userToken->user_id,
                'object_name' => $objectName,
                'record_key' => $recordKey,
            ]);

            return [
                'success' => true,
                'key' => $recordKey,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Sage Intacct createCustomObjectRecord exception', [
                'user_id' => $this->userToken->user_id,
                'object_name' => $objectName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Batch create records in a custom object (up to 100 records per batch)
     */
    public function batchCreateRecords(string $objectName, array $records): array
    {
        try {
            // Validate batch size
            if (count($records) > 100) {
                return [
                    'success' => false,
                    'error' => 'Batch size exceeds limit of 100 records. Provided: '.count($records),
                ];
            }

            if (empty($records)) {
                return [
                    'success' => true,
                    'created' => 0,
                    'failed' => [],
                    'message' => 'No records to create',
                ];
            }

            // Sandbox safety check
            if (! $this->userToken->isSandbox()) {
                Log::warning('Sage Intacct: Batch creating records in non-sandbox environment', [
                    'user_id' => $this->userToken->user_id,
                    'company_id' => $this->userToken->company_id,
                    'object_name' => $objectName,
                    'record_count' => count($records),
                ]);
            }

            // Ensure valid token
            $authResult = $this->authenticate();
            if (! $authResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                ];
            }

            $apiUrl = 'https://api.intacct.com/ia/api/v1/companies/'.urlencode($this->userToken->company_id).
                      '/objects/'.urlencode($objectName).'/batch';

            Log::info('Sage Intacct API: Batch creating records', [
                'user_id' => $this->userToken->user_id,
                'object_name' => $objectName,
                'record_count' => count($records),
            ]);

            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->accessToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($apiUrl, ['records' => $records]);

            if (! $response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Batch create failed';

                Log::error('Sage Intacct batch create failed', [
                    'user_id' => $this->userToken->user_id,
                    'status' => $response->status(),
                    'error' => $errorMessage,
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'created' => 0,
                    'failed' => $records,
                ];
            }

            $result = $response->json();

            $created = $result['created'] ?? $result['success_count'] ?? 0;
            $failedRecords = $result['failed'] ?? $result['errors'] ?? [];

            Log::info('Sage Intacct API: Batch create completed', [
                'user_id' => $this->userToken->user_id,
                'object_name' => $objectName,
                'total' => count($records),
                'created' => $created,
                'failed' => count($failedRecords),
            ]);

            return [
                'success' => true,
                'created' => $created,
                'failed' => $failedRecords,
                'details' => $result['details'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Sage Intacct batchCreateRecords exception', [
                'user_id' => $this->userToken->user_id,
                'object_name' => $objectName,
                'record_count' => count($records),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Exception: '.$e->getMessage(),
                'created' => 0,
                'failed' => $records,
            ];
        }
    }

    /**
     * Get the user token
     */
    public function getUserToken(): UserSageIntacctToken
    {
        return $this->userToken;
    }

    /**
     * Get the setting instance
     */
    public function getSetting()
    {
        return $this->setting;
    }
}
