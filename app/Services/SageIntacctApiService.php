<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sage Intacct REST API Service
 *
 * Handles authentication and API communication with Sage Intacct REST API.
 * Uses OAuth 2.0 for authentication and JSON for data exchange.
 */
class SageIntacctApiService
{
    private $setting;

    private ?string $accessToken = null;

    private const TOKEN_CACHE_KEY_PREFIX = 'sage_intacct_token_';

    /**
     * Constructor - accepts SageIntacctSetting instance
     */
    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    /**
     * Authenticate with Sage Intacct and get OAuth access token
     *
     * @return array Result with success boolean and optional message
     */
    public function authenticate(): array
    {
        try {
            if (! $this->setting->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Sage Intacct credentials are not fully configured',
                ];
            }

            // Check if we should use REST API
            if (! $this->setting->isRestApi()) {
                return [
                    'success' => false,
                    'message' => 'REST API credentials (Client ID and Client Secret) are required',
                ];
            }

            // Check cache first
            $cacheKey = self::TOKEN_CACHE_KEY_PREFIX.$this->setting->id;
            $cachedToken = Cache::get($cacheKey);

            if ($cachedToken) {
                $this->accessToken = $cachedToken;
                Log::info('Sage Intacct API: Using cached access token');

                return [
                    'success' => true,
                    'access_token' => $this->accessToken,
                    'message' => 'Using cached authentication token',
                ];
            }

            Log::info('Sage Intacct API: Authenticating with OAuth 2.0', [
                'client_id' => substr($this->setting->client_id, 0, 10).'...',
                'company_id' => $this->setting->company_id,
            ]);

            // Get OAuth token
            $tokenResult = $this->fetchOAuthToken();

            if (! $tokenResult['success']) {
                return [
                    'success' => false,
                    'message' => $tokenResult['message'] ?? 'Authentication failed',
                ];
            }

            $this->accessToken = $tokenResult['access_token'];

            // Cache the token (expires in ~1 hour, cache for 55 minutes to be safe)
            $expiresIn = $tokenResult['expires_in'] ?? 3300;
            Cache::put($cacheKey, $this->accessToken, now()->addSeconds($expiresIn - 300));

            Log::info('Sage Intacct API: Authentication successful', [
                'expires_in' => $expiresIn,
            ]);

            return [
                'success' => true,
                'access_token' => $this->accessToken,
                'expires_in' => $expiresIn,
                'message' => 'Authentication successful',
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct authentication exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Fetch OAuth 2.0 access token from Sage Intacct
     *
     * @return array Result with access_token and expires_in
     */
    private function fetchOAuthToken(): array
    {
        try {
            $tokenUrl = rtrim($this->setting->getEndpointUrl(), '/').'/oauth/token';

            Log::debug('Sage Intacct: Requesting OAuth token', [
                'url' => $tokenUrl,
                'company_id' => $this->setting->company_id,
            ]);

            $response = Http::asForm()
                ->timeout(30)
                ->post($tokenUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->setting->client_id,
                    'client_secret' => $this->setting->client_secret,
                    'company_id' => $this->setting->company_id,
                ]);

            if (! $response->successful()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error_description'] ?? $errorBody['error'] ?? 'Token request failed';

                Log::error('Sage Intacct OAuth token request failed', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'OAuth failed: '.$errorMessage,
                ];
            }

            $data = $response->json();

            if (empty($data['access_token'])) {
                return [
                    'success' => false,
                    'message' => 'No access token in response',
                ];
            }

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'] ?? 3600,
                'token_type' => $data['token_type'] ?? 'Bearer',
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct getAccessToken exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Test API connectivity by authenticating and making a simple API call
     *
     * @return array Result with success boolean and message
     */
    public function testConnection(): array
    {
        try {
            if (! $this->setting->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'API credentials are not fully configured',
                ];
            }

            // Test authentication
            $authResult = $this->authenticate();

            if (! $authResult['success']) {
                return [
                    'success' => false,
                    'message' => $authResult['message'],
                ];
            }

            // Verify company access by checking company info endpoint
            $apiUrl = rtrim($this->setting->getEndpointUrl(), '/').'/v1/companies/'.urlencode($this->setting->company_id);

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
                'company_id' => $this->setting->company_id,
                'company_name' => $companyName,
            ]);

            return [
                'success' => true,
                'message' => 'Connection successful to '.$companyName,
                'company_name' => $companyName,
                'is_sandbox' => $this->setting->isSandbox(),
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct connection test exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Create a record in a custom object
     *
     * @param  string  $objectName  Custom object name in Sage Intacct
     * @param  array  $fieldData  Associative array of field names and values
     * @return array Result with success boolean and optional error or record key
     */
    public function createCustomObjectRecord(string $objectName, array $fieldData): array
    {
        try {
            // Sandbox safety check
            if (! $this->setting->isSandbox()) {
                Log::warning('Sage Intacct: Creating record in non-sandbox environment', [
                    'company_id' => $this->setting->company_id,
                    'object_name' => $objectName,
                ]);
            }

            // Ensure we have a valid token
            if (! $this->accessToken) {
                $authResult = $this->authenticate();
                if (! $authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                    ];
                }
            }

            $apiUrl = rtrim($this->setting->getEndpointUrl(), '/').
                      '/v1/companies/'.urlencode($this->setting->company_id).
                      '/objects/'.urlencode($objectName);

            Log::info('Sage Intacct API: Creating custom object record', [
                'object_name' => $objectName,
                'company_id' => $this->setting->company_id,
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

                // Include validation errors if present
                if (isset($errorData['errors']) && is_array($errorData['errors'])) {
                    $errorMessage .= ': '.implode(', ', $errorData['errors']);
                }

                Log::error('Sage Intacct record creation failed', [
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
                'object_name' => $objectName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Batch create records in a custom object (up to 100 records per batch)
     *
     * @param  string  $objectName  Custom object name in Sage Intacct
     * @param  array  $records  Array of associative arrays, each representing a record
     * @return array Result with success boolean, created count, and failed records
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
            if (! $this->setting->isSandbox()) {
                Log::warning('Sage Intacct: Batch creating records in non-sandbox environment', [
                    'company_id' => $this->setting->company_id,
                    'object_name' => $objectName,
                    'record_count' => count($records),
                ]);
            }

            // Ensure we have a valid token
            if (! $this->accessToken) {
                $authResult = $this->authenticate();
                if (! $authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                    ];
                }
            }

            $apiUrl = rtrim($this->setting->getEndpointUrl(), '/').
                      '/v1/companies/'.urlencode($this->setting->company_id).
                      '/objects/'.urlencode($objectName).'/batch';

            Log::info('Sage Intacct API: Batch creating records', [
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

            // Parse batch response results
            $created = $result['created'] ?? $result['success_count'] ?? 0;
            $failedRecords = $result['failed'] ?? $result['errors'] ?? [];

            Log::info('Sage Intacct API: Batch create completed', [
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
                'object_name' => $objectName,
                'record_count' => count($records),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
     * Refresh the access token
     *
     * @return array Result with success boolean
     */
    public function refreshToken(): array
    {
        // Clear cached token
        $cacheKey = self::TOKEN_CACHE_KEY_PREFIX.$this->setting->id;
        Cache::forget($cacheKey);

        // Get new token
        return $this->authenticate();
    }

    /**
     * Get the current access token (authenticate if not already done)
     */
    public function getAccessToken(): ?string
    {
        if (! $this->accessToken) {
            $result = $this->authenticate();
            if (! $result['success']) {
                return null;
            }
        }

        return $this->accessToken;
    }

    /**
     * Get the current setting instance
     */
    public function getSetting()
    {
        return $this->setting;
    }

    /**
     * Clear the cached token for this setting
     */
    public function clearTokenCache(): void
    {
        $cacheKey = self::TOKEN_CACHE_KEY_PREFIX.$this->setting->id;
        Cache::forget($cacheKey);
    }
}
