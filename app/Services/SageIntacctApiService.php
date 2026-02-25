<?php

namespace App\Services;

use App\Models\UserSageIntacctToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Hybrid Sage Intacct API Service
 *
 * Supports BOTH XML Web Services and OAuth 2.0 REST API authentication methods.
 * Authentication method is determined by $setting->auth_method:
 * - 'xml': Uses XML Web Services with session-based authentication
 * - 'oauth': Uses OAuth 2.0 with Bearer token authentication
 */
class SageIntacctApiService
{
    private $setting;

    private ?UserSageIntacctToken $userToken = null;

    private ?string $sessionId = null;

    private ?string $accessToken = null;

    /**
     * Constructor
     *
     * @param  mixed  $setting  SageIntacctSetting instance
     * @param  UserSageIntacctToken|null  $userToken  Required for OAuth, not needed for XML
     */
    public function __construct($setting, ?UserSageIntacctToken $userToken = null)
    {
        $this->setting = $setting;
        $this->userToken = $userToken;

        // Initialize OAuth access token if provided
        if ($userToken) {
            $this->accessToken = $userToken->access_token;
        }

        Log::debug('Sage Intacct API Service initialized', [
            'auth_method' => $this->setting->auth_method ?? 'xml',
            'has_user_token' => $userToken !== null,
        ]);
    }

    /**
     * Main authentication method - routes to appropriate auth handler
     */
    public function authenticate(): array
    {
        if ($this->setting->isXmlApi()) {
            return $this->authenticateXml();
        } else {
            return $this->authenticateOAuth();
        }
    }

    /**
     * Test API connectivity
     */
    public function testConnection(): array
    {
        if ($this->setting->isXmlApi()) {
            return $this->testConnectionXml();
        } else {
            return $this->testConnectionOAuth();
        }
    }

    /**
     * Create a record in a custom object
     */
    public function createCustomObjectRecord(string $objectName, array $fieldData): array
    {
        if ($this->setting->isXmlApi()) {
            return $this->createCustomObjectRecordXml($objectName, $fieldData);
        } else {
            return $this->createCustomObjectRecordOAuth($objectName, $fieldData);
        }
    }

    /**
     * Batch create records in a custom object (up to 100 records per batch)
     */
    public function batchCreateRecords(string $objectName, array $records): array
    {
        if ($this->setting->isXmlApi()) {
            return $this->batchCreateRecordsXml($objectName, $records);
        } else {
            return $this->batchCreateRecordsOAuth($objectName, $records);
        }
    }

    // ====================================================================
    // XML WEB SERVICES AUTHENTICATION & METHODS
    // ====================================================================

    /**
     * Authenticate with Sage Intacct XML API and create a session
     */
    private function authenticateXml(): array
    {
        try {
            if (! $this->setting->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Sage Intacct credentials are not fully configured',
                ];
            }

            // Build authentication request
            $xmlRequest = $this->buildAuthenticationXml();

            Log::info('Sage Intacct XML API: Authenticating', [
                'sender_id' => $this->setting->sender_id,
                'company_id' => $this->setting->company_id,
                'user_id' => $this->setting->web_services_user_id,
            ]);

            // Send authentication request
            $response = $this->sendXmlRequest($xmlRequest);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? 'Authentication failed',
                ];
            }

            // Extract session ID from response
            $xml = simplexml_load_string($response['xml']);
            if ($xml === false) {
                return [
                    'success' => false,
                    'message' => 'Failed to parse authentication response',
                ];
            }

            // Check for API errors
            $errormessage = $xml->xpath('//errormessage');
            if (! empty($errormessage)) {
                $errorText = (string) $errormessage[0]->error->description2 ?? (string) $errormessage[0]->error->description ?? 'Authentication failed';

                Log::error('Sage Intacct XML authentication failed', [
                    'error' => $errorText,
                ]);

                return [
                    'success' => false,
                    'message' => 'Authentication failed: '.$errorText,
                ];
            }

            // Get session ID from getAPISession response
            // Try multiple possible locations for session ID
            $sessionId = $xml->xpath('//result/data/api/sessionid');
            if (empty($sessionId)) {
                $sessionId = $xml->xpath('//data/api/sessionid');
            }
            if (empty($sessionId)) {
                $sessionId = $xml->xpath('//sessionid');
            }
            if (empty($sessionId)) {
                // Log the response for debugging
                Log::error('Sage Intacct: No session ID found in response', [
                    'response_preview' => substr($response['xml'], 0, 1000),
                ]);

                return [
                    'success' => false,
                    'message' => 'No session ID returned from authentication',
                ];
            }

            $this->sessionId = (string) $sessionId[0];

            Log::info('Sage Intacct XML API: Authentication successful', [
                'session_id' => substr($this->sessionId, 0, 20).'...',
            ]);

            return [
                'success' => true,
                'session_id' => $this->sessionId,
                'message' => 'Authentication successful',
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct XML authentication exception', [
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
     * Test XML API connectivity by authenticating
     */
    private function testConnectionXml(): array
    {
        try {
            if (! $this->setting->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'API credentials are not fully configured',
                ];
            }

            // Test authentication
            $authResult = $this->authenticateXml();

            if (! $authResult['success']) {
                return [
                    'success' => false,
                    'message' => $authResult['message'],
                ];
            }

            // Try to read a simple function to verify access
            $testXml = $this->buildXmlRequest('getUserPermissions', []);
            $response = $this->sendXmlRequest($testXml);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => 'Connection test failed: '.($response['error'] ?? 'Unknown error'),
                ];
            }

            return [
                'success' => true,
                'message' => 'Connection successful',
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct XML connection test exception', [
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
     * Create a record in a custom object (XML method)
     */
    private function createCustomObjectRecordXml(string $objectName, array $fieldData): array
    {
        try {
            // Ensure we have a session
            if (! $this->sessionId) {
                $authResult = $this->authenticateXml();
                if (! $authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                    ];
                }
            }

            // Build create request
            $parameters = [
                'CUSTOMOBJECT' => $objectName,
                'CUSTOMOBJECTFIELDS' => $fieldData,
            ];

            $xmlRequest = $this->buildXmlRequest('create', $parameters);

            Log::info('Sage Intacct XML API: Creating custom object record', [
                'object_name' => $objectName,
                'fields' => array_keys($fieldData),
            ]);

            // Send request
            $response = $this->sendXmlRequest($xmlRequest);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Failed to create record',
                ];
            }

            // Parse response
            $result = $this->parseXmlResponse($response['xml']);

            if (! $result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to parse response',
                ];
            }

            Log::info('Sage Intacct XML API: Record created successfully', [
                'object_name' => $objectName,
                'record_key' => $result['key'] ?? 'unknown',
            ]);

            return [
                'success' => true,
                'key' => $result['key'] ?? null,
                'data' => $result['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct XML createCustomObjectRecord exception', [
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
     * Inspect custom object definition to see available fields
     *
     * @param  string  $objectName  Custom object name
     * @return array Result with field definitions
     */
    public function inspectObjectDefinition(string $objectName): array
    {
        if (! $this->setting->isXmlApi()) {
            return [
                'success' => false,
                'error' => 'Inspect is only supported in XML API mode',
            ];
        }

        try {
            // Ensure we have a session
            if (! $this->sessionId) {
                $authResult = $this->authenticateXml();
                if (! $authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                    ];
                }
            }

            Log::info('Sage Intacct XML: Inspecting object definition', [
                'object_name' => $objectName,
            ]);

            // Use lookup function to get object metadata
            $xmlRequest = $this->buildLookupXml($objectName);

            // Send request
            $response = $this->sendXmlRequest($xmlRequest);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Lookup failed',
                ];
            }

            // Parse to get field list
            $xml = simplexml_load_string($response['xml']);

            // Extract all field names from the Type definition
            $fields = [];
            $typeNodes = $xml->xpath('//Type');

            if (! empty($typeNodes)) {
                foreach ($typeNodes[0]->children() as $field) {
                    $fieldName = $field->getName();
                    $fields[] = [
                        'name' => $fieldName,
                        'type' => (string) $field['type'] ?? 'unknown',
                    ];
                }
            }

            Log::info('Sage Intacct XML: Object inspection completed', [
                'object_name' => $objectName,
                'field_count' => count($fields),
            ]);

            return [
                'success' => true,
                'object_name' => $objectName,
                'fields' => $fields,
                'raw_xml' => $response['xml'],
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct inspectObjectDefinition exception', [
                'object_name' => $objectName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create an Other Receipt in Sage Intacct
     *
     * @param  array  $receiptData  Other Receipt data
     * @return array Result with success status and record key
     */
    public function createOtherReceipt(array $receiptData): array
    {
        if (! $this->setting->isXmlApi()) {
            return [
                'success' => false,
                'error' => 'Other Receipts are only supported in XML API mode',
            ];
        }

        try {
            // Ensure we have a session
            if (! $this->sessionId) {
                $authResult = $this->authenticateXml();
                if (! $authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                    ];
                }
            }

            Log::info('Sage Intacct XML API: Creating Other Receipt', [
                'refid' => $receiptData['refid'] ?? 'unknown',
                'amount' => $receiptData['amount'] ?? 'unknown',
                'paymentmethod' => $receiptData['paymentmethod'] ?? 'unknown',
            ]);

            // Build Other Receipt XML
            $xmlRequest = $this->buildOtherReceiptXml($receiptData);

            // Send request
            $response = $this->sendXmlRequest($xmlRequest);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Failed to create Other Receipt',
                ];
            }

            // Parse response
            $result = $this->parseXmlResponse($response['xml']);

            if (! $result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to parse response',
                ];
            }

            Log::info('Sage Intacct XML API: Other Receipt created successfully', [
                'refid' => $receiptData['refid'] ?? 'unknown',
                'record_key' => $result['key'] ?? 'unknown',
            ]);

            return [
                'success' => true,
                'key' => $result['key'] ?? null,
                'data' => $result['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct createOtherReceipt exception', [
                'refid' => $receiptData['refid'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Batch create multiple Other Receipts
     *
     * @param  array  $receipts  Array of Other Receipt data
     * @return array Result with created/failed counts
     */
    public function batchCreateOtherReceipts(array $receipts): array
    {
        if (! $this->setting->isXmlApi()) {
            return [
                'success' => false,
                'error' => 'Other Receipts are only supported in XML API mode',
            ];
        }

        try {
            // Ensure we have a session
            if (! $this->sessionId) {
                $authResult = $this->authenticateXml();
                if (! $authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                    ];
                }
            }

            Log::info('Sage Intacct XML API: Batch creating Other Receipts', [
                'record_count' => count($receipts),
            ]);

            // Build batch request with multiple receipt functions
            $functionContent = '';
            foreach ($receipts as $index => $receiptData) {
                $functionContent .= $this->buildOtherReceiptFunctionContent($receiptData, $index);
            }

            $xmlRequest = $this->buildXmlRequest('batch', [], $functionContent);

            // Log complete XML for first receipt for debugging
            if (count($receipts) > 0) {
                $firstFunction = $this->buildOtherReceiptFunctionContent($receipts[0], 0);
                Log::debug('Sage Intacct XML API: Complete first receipt XML', [
                    'receipt_data' => $receipts[0],
                    'xml_function' => $firstFunction,
                ]);
            }

            // Send request
            $response = $this->sendXmlRequest($xmlRequest);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Batch creation failed',
                ];
            }

            // Parse batch response
            $created = 0;
            $failed = 0;
            $details = [];

            $xml = simplexml_load_string($response['xml']);

            // Check for control-level failure first
            $controlStatus = (string) $xml->control->status;
            if ($controlStatus === 'failure') {
                $errorMsg = (string) $xml->errormessage->error->description2
                    ?? (string) $xml->errormessage->error->description
                    ?? 'Unknown control-level error';
                $errorCode = (string) $xml->errormessage->error->errorno ?? 'N/A';

                Log::error('Sage Intacct XML API: Control-level error in batch', [
                    'error_code' => $errorCode,
                    'error_message' => $errorMsg,
                    'full_response' => substr($response['xml'], 0, 2000),
                ]);

                return [
                    'success' => false,
                    'error' => "Control-level error [{$errorCode}]: {$errorMsg}",
                ];
            }

            $results = $xml->xpath('//result');

            foreach ($results as $index => $result) {
                $status = (string) $result->status;
                if ($status === 'success') {
                    $created++;

                    // Try multiple possible XML paths for RECORDNO (Intacct uses different node names)
                    $key = null;
                    if (isset($result->data->otherreceipt)) {
                        $key = (string) $result->data->otherreceipt->RECORDNO;
                    } elseif (isset($result->data->othercashreceipt)) {
                        $key = (string) $result->data->othercashreceipt->RECORDNO;
                    } elseif (isset($result->data)) {
                        // Try to find any RECORDNO in data
                        $recordnos = $result->data->xpath('.//RECORDNO');
                        if (! empty($recordnos)) {
                            $key = (string) $recordnos[0];
                        }
                    }

                    if ($key) {
                        Log::debug('Sage Intacct: Other Receipt created', [
                            'index' => $index,
                            'recordno' => $key,
                            'data_structure' => isset($result->data->otherreceipt) ? 'otherreceipt' : (isset($result->data->othercashreceipt) ? 'othercashreceipt' : 'unknown'),
                        ]);
                    } else {
                        // No RECORDNO found - log the actual XML structure
                        Log::warning('Sage Intacct: Other Receipt created but RECORDNO not found in response', [
                            'index' => $index,
                            'result_xml' => $result->asXML(),
                        ]);
                    }

                    $details[] = [
                        'success' => true,
                        'key' => $key,
                        'index' => $index,
                    ];
                } else {
                    $failed++;
                    $errorMsg = (string) $result->errormessage->error->description2
                        ?? (string) $result->errormessage->error->description
                        ?? 'Unknown error';
                    $errorCode = (string) $result->errormessage->error->errorno ?? 'N/A';
                    $errorCorrection = (string) $result->errormessage->error->correction ?? '';

                    // Get the receipt data that failed
                    $failedReceipt = $receipts[$index] ?? null;

                    Log::warning('Sage Intacct XML API: Other Receipt creation failed', [
                        'index' => $index,
                        'error_code' => $errorCode,
                        'error_message' => $errorMsg,
                        'error_correction' => $errorCorrection,
                        'gift_id' => $failedReceipt['refid'] ?? null,
                        'dimensions' => [
                            'locationid' => $failedReceipt['receiptitems'][0]['locationid'] ?? null,
                            'departmentid' => $failedReceipt['receiptitems'][0]['departmentid'] ?? null,
                            'projectid' => $failedReceipt['receiptitems'][0]['projectid'] ?? null,
                            'classid' => $failedReceipt['receiptitems'][0]['classid'] ?? null,
                        ],
                        'accounts' => [
                            'glaccountno' => $failedReceipt['receiptitems'][0]['glaccountno'] ?? null,
                            'undepglaccountno' => $failedReceipt['undepglaccountno'] ?? null,
                            'bankaccountid' => $failedReceipt['bankaccountid'] ?? null,
                        ],
                        'payment_method' => $failedReceipt['paymentmethod'] ?? null,
                        'supdocid' => $failedReceipt['supdocid'] ?? null,
                        'full_error_xml' => $result->asXML(),
                    ]);

                    $details[] = [
                        'success' => false,
                        'error' => $errorMsg,
                        'index' => $index,
                    ];
                }
            }

            // Log errors if any failures occurred
            if ($failed > 0) {
                Log::warning('Sage Intacct XML API: Batch had failures', [
                    'total' => count($receipts),
                    'created' => $created,
                    'failed' => $failed,
                    'failure_details' => array_filter($details, fn ($d) => ! $d['success']),
                ]);
            }

            Log::info('Sage Intacct XML API: Batch create Other Receipts completed', [
                'total' => count($receipts),
                'created' => $created,
                'failed' => $failed,
            ]);

            return [
                'success' => true,
                'created' => $created,
                'failed' => $failed,
                'total' => count($receipts),
                'details' => $details,
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct batchCreateOtherReceipts exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Query custom object records (XML method)
     *
     * @param  string  $objectName  Custom object name
     * @param  array  $giftIds  Array of gift IDs to query
     * @return array Result with existing records keyed by gift_id
     */
    public function queryCustomObjectRecords(string $objectName, array $giftIds): array
    {
        if (! $this->setting->isXmlApi()) {
            return [
                'success' => false,
                'error' => 'Query is only supported in XML API mode',
            ];
        }

        try {
            // Ensure we have a session
            if (! $this->sessionId) {
                $authResult = $this->authenticateXml();
                if (! $authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                    ];
                }
            }

            if (empty($giftIds)) {
                return ['success' => true, 'records' => []];
            }

            // Build query filter for donor_perfect_gift_id IN (...)
            $giftIdList = implode(',', array_map('intval', $giftIds));
            $query = "donor_perfect_gift_id IN ({$giftIdList})";

            Log::info('Sage Intacct XML: Querying existing records', [
                'object_name' => $objectName,
                'gift_count' => count($giftIds),
                'query' => $query,
            ]);

            // Build readByQuery XML request
            $parameters = [
                'object' => $objectName,
                'query' => $query,
                'fields' => '*', // Get all fields
                'pagesize' => 1000,
            ];

            $xmlRequest = $this->buildReadByQueryXml($objectName, $query);

            // Send request
            $response = $this->sendXmlRequest($xmlRequest);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Query failed',
                ];
            }

            // Parse response
            $parseResult = $this->parseQueryResponse($response['xml']);

            if (! $parseResult['success']) {
                return [
                    'success' => false,
                    'error' => $parseResult['error'] ?? 'Failed to parse query response',
                ];
            }

            Log::info('Sage Intacct XML: Query completed', [
                'object_name' => $objectName,
                'records_found' => count($parseResult['records']),
            ]);

            return [
                'success' => true,
                'records' => $parseResult['records'],
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct queryCustomObjectRecords exception', [
                'object_name' => $objectName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing custom object record (XML method)
     *
     * @param  string  $objectName  Custom object name
     * @param  string  $recordKey  The RECORDNO or key of the record to update
     * @param  array  $fieldData  Fields to update
     * @return array Result with success status
     */
    public function updateCustomObjectRecord(string $objectName, string $recordKey, array $fieldData): array
    {
        if (! $this->setting->isXmlApi()) {
            return [
                'success' => false,
                'error' => 'Update is only supported in XML API mode',
            ];
        }

        try {
            // Ensure we have a session
            if (! $this->sessionId) {
                $authResult = $this->authenticateXml();
                if (! $authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                    ];
                }
            }

            Log::info('Sage Intacct XML: Updating custom object record', [
                'object_name' => $objectName,
                'record_key' => $recordKey,
                'fields' => array_keys($fieldData),
            ]);

            // Build update request
            $xmlRequest = $this->buildUpdateXml($objectName, $recordKey, $fieldData);

            // Send request
            $response = $this->sendXmlRequest($xmlRequest);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Update failed',
                ];
            }

            // Parse response
            $parseResult = $this->parseXmlResponse($response['xml']);

            if (! $parseResult['success']) {
                return [
                    'success' => false,
                    'error' => $parseResult['error'] ?? 'Failed to parse update response',
                ];
            }

            Log::info('Sage Intacct XML: Record updated successfully', [
                'object_name' => $objectName,
                'record_key' => $recordKey,
            ]);

            return [
                'success' => true,
                'key' => $recordKey,
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct updateCustomObjectRecord exception', [
                'object_name' => $objectName,
                'record_key' => $recordKey,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Batch create records in a custom object (XML method)
     */
    private function batchCreateRecordsXml(string $objectName, array $records): array
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

            // Ensure we have a session
            if (! $this->sessionId) {
                $authResult = $this->authenticateXml();
                if (! $authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                    ];
                }
            }

            // Build batch create request with multiple functions
            $xmlRequest = $this->buildBatchCreateXml($objectName, $records);

            Log::info('Sage Intacct XML API: Batch creating records', [
                'object_name' => $objectName,
                'record_count' => count($records),
            ]);

            // Send request
            $response = $this->sendXmlRequest($xmlRequest);

            if (! $response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Batch create failed',
                    'created' => 0,
                    'failed' => $records,
                ];
            }

            // Parse batch response
            $result = $this->parseBatchResponse($response['xml']);

            // Check for control-level error from parseBatchResponse
            if (isset($result['error'])) {
                Log::error('Sage Intacct XML API: Batch create failed with control-level error', [
                    'object_name' => $objectName,
                    'error' => $result['error'],
                ]);

                return [
                    'success' => false,
                    'error' => $result['error'],
                    'created' => 0,
                    'failed' => count($records),
                    'details' => [],
                ];
            }

            Log::info('Sage Intacct XML API: Batch create completed', [
                'object_name' => $objectName,
                'total' => count($records),
                'created' => $result['created'],
                'failed' => count($result['failed']),
            ]);

            return [
                'success' => true,
                'created' => $result['created'],
                'failed' => count($result['failed']),
                'details' => $result['details'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct XML batchCreateRecords exception', [
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

    // ====================================================================
    // XML HELPER METHODS
    // ====================================================================

    /**
     * Build XML request structure for Sage Intacct API
     */
    private function buildXmlRequest(string $functionName, array $parameters, ?string $preBuildFunctionContent = null): string
    {
        $controlId = $this->generateControlId();
        $functionId = $this->generateControlId();

        // Build authentication section
        if ($this->sessionId) {
            $authSection = '<sessionid>'.htmlspecialchars($this->sessionId).'</sessionid>';
        } else {
            $authSection = '<login>
                <userid>'.htmlspecialchars($this->setting->web_services_user_id).'</userid>
                <companyid>'.htmlspecialchars($this->setting->company_id).'</companyid>
                <password>'.htmlspecialchars($this->setting->web_services_password).'</password>
            </login>';
        }

        // Build function content - use pre-built content if provided
        // For batch operations, pre-built content already contains <function> tags
        if ($preBuildFunctionContent !== null) {
            $contentSection = $preBuildFunctionContent;
        } else {
            $functionContent = $this->buildFunctionContent($functionName, $parameters);
            $contentSection = '<function controlid="'.$functionId.'">
                '.$functionContent.'
            </function>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>'.htmlspecialchars($this->setting->sender_id).'</senderid>
        <password>'.htmlspecialchars($this->setting->sender_password).'</password>
        <controlid>'.$controlId.'</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            '.$authSection.'
        </authentication>
        <content>
            '.$contentSection.'
        </content>
    </operation>
</request>';

        return $xml;
    }

    /**
     * Send HTTP request to Sage Intacct XML API
     */
    private function sendXmlRequest(string $xmlPayload): array
    {
        try {
            $apiUrl = $this->setting->endpoint_url ?? 'https://api.intacct.com/ia/xml/xmlgw.phtml';

            Log::debug('Sage Intacct XML API: Sending request', [
                'url' => $apiUrl,
                'xml_preview' => substr($xmlPayload, 0, 500).'...',
            ]);

            // Send POST request with XML payload
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/xml',
                ])
                ->withBody($xmlPayload, 'application/xml')
                ->post($apiUrl);

            if (! $response->successful()) {
                Log::error('Sage Intacct XML API request failed', [
                    'status' => $response->status(),
                    'response' => substr($response->body(), 0, 500),
                ]);

                return [
                    'success' => false,
                    'error' => 'API request failed with status: '.$response->status(),
                ];
            }

            $responseBody = $response->body();

            Log::debug('Sage Intacct XML API: Response received', [
                'status' => $response->status(),
                'preview' => substr($responseBody, 0, 500).'...',
            ]);

            return [
                'success' => true,
                'xml' => $responseBody,
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct XML API sendRequest exception', [
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
     * Parse XML response and validate
     */
    private function parseXmlResponse(string $xmlResponse): array
    {
        try {
            // Suppress XML errors and use internal error handling
            $previousErrorSetting = libxml_use_internal_errors(true);

            $xml = simplexml_load_string($xmlResponse);

            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                libxml_use_internal_errors($previousErrorSetting);

                $errorMessage = 'XML parsing failed';
                if (! empty($errors)) {
                    $errorMessage .= ': '.$errors[0]->message;
                }

                return [
                    'success' => false,
                    'error' => $errorMessage,
                ];
            }

            libxml_use_internal_errors($previousErrorSetting);

            // Check for operation errors
            $operationResult = $xml->xpath('//operation/result/status');
            if (! empty($operationResult)) {
                $status = (string) $operationResult[0];

                if ($status === 'failure') {
                    $errorMessages = $xml->xpath('//operation/result/errormessage/error');
                    $errorText = 'Operation failed';

                    if (! empty($errorMessages)) {
                        $errorDetails = [];
                        foreach ($errorMessages as $error) {
                            $description = (string) ($error->description2 ?? $error->description ?? 'Unknown error');
                            $errorDetails[] = $description;
                        }
                        $errorText .= ': '.implode('; ', $errorDetails);
                    }

                    return [
                        'success' => false,
                        'error' => $errorText,
                    ];
                }
            }

            // Check for function result
            $functionResult = $xml->xpath('//function/result/status');
            if (! empty($functionResult)) {
                $status = (string) $functionResult[0];

                if ($status === 'failure') {
                    $errorMessages = $xml->xpath('//function/result/errormessage/error');
                    $errorText = 'Function failed';

                    if (! empty($errorMessages)) {
                        $errorDetails = [];
                        foreach ($errorMessages as $error) {
                            $description = (string) ($error->description2 ?? $error->description ?? 'Unknown error');
                            $correction = (string) ($error->correction ?? '');
                            $errorDetails[] = $description.($correction ? ' ('.$correction.')' : '');
                        }
                        $errorText .= ': '.implode('; ', $errorDetails);
                    }

                    return [
                        'success' => false,
                        'error' => $errorText,
                    ];
                }

                // Extract key if present (for create operations)
                $key = $xml->xpath('//function/result/key');
                $data = $xml->xpath('//function/result/data');

                return [
                    'success' => true,
                    'key' => ! empty($key) ? (string) $key[0] : null,
                    'data' => ! empty($data) ? $this->xmlToArray($data[0]) : [],
                ];
            }

            // Success - return raw XML structure as array
            return [
                'success' => true,
                'data' => $this->xmlToArray($xml),
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct XML parsing exception', [
                'error' => $e->getMessage(),
                'xml_preview' => substr($xmlResponse, 0, 500),
            ]);

            return [
                'success' => false,
                'error' => 'XML parsing exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Build authentication XML
     */
    private function buildAuthenticationXml(): string
    {
        $controlId = $this->generateControlId();

        return '<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>'.htmlspecialchars($this->setting->sender_id).'</senderid>
        <password>'.htmlspecialchars($this->setting->sender_password).'</password>
        <controlid>'.$controlId.'</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <login>
                <userid>'.htmlspecialchars($this->setting->web_services_user_id).'</userid>
                <companyid>'.htmlspecialchars($this->setting->company_id).'</companyid>
                <password>'.htmlspecialchars($this->setting->web_services_password).'</password>
            </login>
        </authentication>
        <content>
            <function controlid="'.$this->generateControlId().'">
                <getAPISession/>
            </function>
        </content>
    </operation>
</request>';
    }

    /**
     * Build function content based on function name and parameters
     */
    private function buildFunctionContent(string $functionName, array $parameters): string
    {
        if ($functionName === 'create' && isset($parameters['CUSTOMOBJECT'])) {
            $objectName = $parameters['CUSTOMOBJECT'];
            $fields = $parameters['CUSTOMOBJECTFIELDS'] ?? [];

            // Sanitize object name for XML element (replace spaces with underscores, remove invalid chars)
            $sanitizedObjectName = preg_replace('/[^A-Za-z0-9_-]/', '_', $objectName);

            $fieldsXml = '';
            foreach ($fields as $fieldName => $fieldValue) {
                // Sanitize field names for XML elements
                $sanitizedFieldName = preg_replace('/[^A-Za-z0-9_-]/', '_', $fieldName);
                $fieldsXml .= '<'.$sanitizedFieldName.'>'.htmlspecialchars((string) $fieldValue).'</'.$sanitizedFieldName.'>'."\n";
            }

            return '<create>
                <'.$sanitizedObjectName.'>
                    '.$fieldsXml.'
                </'.$sanitizedObjectName.'>
            </create>';
        }

        // Default: just return function name as empty element
        return '<'.htmlspecialchars($functionName).'/>';
    }

    /**
     * Build XML for lookup request (inspect object definition)
     */
    private function buildLookupXml(string $objectName): string
    {
        $controlId = $this->generateControlId();
        $functionId = $this->generateControlId();

        $authSection = $this->sessionId
            ? '<sessionid>'.htmlspecialchars($this->sessionId).'</sessionid>'
            : '<login>
                <userid>'.htmlspecialchars($this->setting->web_services_user_id).'</userid>
                <companyid>'.htmlspecialchars($this->setting->company_id).'</companyid>
                <password>'.htmlspecialchars($this->setting->web_services_password).'</password>
            </login>';

        $sanitizedObjectName = htmlspecialchars($objectName);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$this->setting->sender_id}</senderid>
        <password>{$this->setting->sender_password}</password>
        <controlid>{$controlId}</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            {$authSection}
        </authentication>
        <content>
            <function controlid="{$functionId}">
                <lookup>
                    <object>{$sanitizedObjectName}</object>
                </lookup>
            </function>
        </content>
    </operation>
</request>
XML;
    }

    /**
     * Build XML for readByQuery request
     */
    private function buildReadByQueryXml(string $objectName, string $query, int $pagesize = 1000): string
    {
        $controlId = $this->generateControlId();
        $functionId = $this->generateControlId();

        $authSection = $this->sessionId
            ? '<sessionid>'.htmlspecialchars($this->sessionId).'</sessionid>'
            : '<login>
                <userid>'.htmlspecialchars($this->setting->web_services_user_id).'</userid>
                <companyid>'.htmlspecialchars($this->setting->company_id).'</companyid>
                <password>'.htmlspecialchars($this->setting->web_services_password).'</password>
            </login>';

        $sanitizedObjectName = htmlspecialchars($objectName);
        $sanitizedQuery = htmlspecialchars($query);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$this->setting->sender_id}</senderid>
        <password>{$this->setting->sender_password}</password>
        <controlid>{$controlId}</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            {$authSection}
        </authentication>
        <content>
            <function controlid="{$functionId}">
                <readByQuery>
                    <object>{$sanitizedObjectName}</object>
                    <query>{$sanitizedQuery}</query>
                    <fields>*</fields>
                    <pagesize>{$pagesize}</pagesize>
                </readByQuery>
            </function>
        </content>
    </operation>
</request>
XML;
    }

    /**
     * Build XML for update request
     */
    private function buildUpdateXml(string $objectName, string $recordKey, array $fieldData): string
    {
        $controlId = $this->generateControlId();
        $functionId = $this->generateControlId();

        $authSection = $this->sessionId
            ? '<sessionid>'.htmlspecialchars($this->sessionId).'</sessionid>'
            : '<login>
                <userid>'.htmlspecialchars($this->setting->web_services_user_id).'</userid>
                <companyid>'.htmlspecialchars($this->setting->company_id).'</companyid>
                <password>'.htmlspecialchars($this->setting->web_services_password).'</password>
            </login>';

        $sanitizedObjectName = htmlspecialchars($objectName);
        $sanitizedRecordKey = htmlspecialchars($recordKey);

        $fieldsXml = '';
        foreach ($fieldData as $fieldName => $fieldValue) {
            $fieldsXml .= '<'.htmlspecialchars($fieldName).'>'.htmlspecialchars($fieldValue).'</'.htmlspecialchars($fieldName).'>'."\n";
        }

        // Note: Custom objects use <id> instead of <RECORDNO>
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$this->setting->sender_id}</senderid>
        <password>{$this->setting->sender_password}</password>
        <controlid>{$controlId}</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            {$authSection}
        </authentication>
        <content>
            <function controlid="{$functionId}">
                <update>
                    <{$sanitizedObjectName}>
                        <id>{$sanitizedRecordKey}</id>
                        {$fieldsXml}
                    </{$sanitizedObjectName}>
                </update>
            </function>
        </content>
    </operation>
</request>
XML;

        Log::debug('Sage Intacct: Built update XML', [
            'record_key' => $recordKey,
            'sanitized_record_key' => $sanitizedRecordKey,
            'object_name' => $objectName,
            'fields' => array_keys($fieldData),
            'xml_sample' => substr($xml, strpos($xml, '<update>'), 500),
        ]);

        return $xml;
    }

    /**
     * Parse query response and extract records
     */
    private function parseQueryResponse(string $xmlResponse): array
    {
        try {
            $xml = simplexml_load_string($xmlResponse);
            if ($xml === false) {
                return ['success' => false, 'error' => 'XML parsing failed'];
            }

            // Check for errors
            $errormessage = $xml->xpath('//errormessage');
            if (! empty($errormessage)) {
                $errorText = (string) ($errormessage[0]->error->description2 ?? $errormessage[0]->error->description ?? 'Query failed');

                return ['success' => false, 'error' => $errorText];
            }

            // Extract records from response
            $recordNodes = $xml->xpath('//data/'.$this->setting->custom_object_name);

            $records = [];
            foreach ($recordNodes as $record) {
                $recordData = [];
                foreach ($record->children() as $field) {
                    $fieldName = $field->getName();
                    $fieldValue = (string) $field;
                    $recordData[$fieldName] = $fieldValue;
                }

                // Key by donor_perfect_gift_id for easy lookup
                if (isset($recordData['donor_perfect_gift_id'])) {
                    $records[$recordData['donor_perfect_gift_id']] = $recordData;
                }
            }

            return [
                'success' => true,
                'records' => $records,
            ];

        } catch (\Exception $e) {
            Log::error('Parse query response exception', [
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build batch create XML with multiple functions
     */
    private function buildBatchCreateXml(string $objectName, array $records): string
    {
        $controlId = $this->generateControlId();

        // Sanitize object name for XML element (replace spaces with underscores, remove invalid chars)
        $sanitizedObjectName = preg_replace('/[^A-Za-z0-9_-]/', '_', $objectName);

        // Build authentication section
        if ($this->sessionId) {
            $authSection = '<sessionid>'.htmlspecialchars($this->sessionId).'</sessionid>';
        } else {
            $authSection = '<login>
                <userid>'.htmlspecialchars($this->setting->web_services_user_id).'</userid>
                <companyid>'.htmlspecialchars($this->setting->company_id).'</companyid>
                <password>'.htmlspecialchars($this->setting->web_services_password).'</password>
            </login>';
        }

        // Build function for each record
        $functionsXml = '';
        foreach ($records as $index => $record) {
            $functionId = $this->generateControlId();

            $fieldsXml = '';
            foreach ($record as $fieldName => $fieldValue) {
                // Sanitize field names for XML elements
                $sanitizedFieldName = preg_replace('/[^A-Za-z0-9_-]/', '_', $fieldName);
                $fieldsXml .= '<'.$sanitizedFieldName.'>'.htmlspecialchars((string) $fieldValue).'</'.$sanitizedFieldName.'>'."\n";
            }

            $functionsXml .= '<function controlid="'.$functionId.'">
                <create>
                    <'.$sanitizedObjectName.'>
                        '.$fieldsXml.'
                    </'.$sanitizedObjectName.'>
                </create>
            </function>'."\n";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>'.htmlspecialchars($this->setting->sender_id).'</senderid>
        <password>'.htmlspecialchars($this->setting->sender_password).'</password>
        <controlid>'.$controlId.'</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            '.$authSection.'
        </authentication>
        <content>
            '.$functionsXml.'
        </content>
    </operation>
</request>';
    }

    /**
     * Parse batch response
     */
    private function parseBatchResponse(string $xmlResponse): array
    {
        try {
            $xml = simplexml_load_string($xmlResponse);
            if ($xml === false) {
                return [
                    'created' => 0,
                    'failed' => [],
                    'details' => [],
                    'error' => 'Failed to parse XML response',
                ];
            }

            $created = 0;
            $failed = [];
            $details = [];

            // Check for control-level failure first
            $controlStatus = (string) ($xml->control->status ?? '');
            if ($controlStatus === 'failure') {
                $errorMessages = $xml->xpath('//errormessage/error');
                $errorText = 'Control-level failure';

                if (! empty($errorMessages)) {
                    $errorDetails = [];
                    foreach ($errorMessages as $error) {
                        $description = (string) ($error->description2 ?? $error->description ?? '');
                        if ($description) {
                            $errorDetails[] = $description;
                        }
                    }
                    if (! empty($errorDetails)) {
                        $errorText = implode('; ', $errorDetails);
                    }
                }

                Log::error('Sage Intacct: Control-level failure in batch response', [
                    'error' => $errorText,
                    'xml_preview' => substr($xmlResponse, 0, 2000),
                ]);

                return [
                    'created' => 0,
                    'failed' => [],
                    'details' => [],
                    'error' => $errorText,
                ];
            }

            // Get all function results (try both paths)
            $functionResults = $xml->xpath('//operation/result');
            if (empty($functionResults)) {
                $functionResults = $xml->xpath('//function/result');
            }

            // Debug: Log if no results found
            if (empty($functionResults)) {
                Log::warning('Sage Intacct: No function results found in batch response', [
                    'xml_preview' => substr($xmlResponse, 0, 2000),
                ]);
            }

            foreach ($functionResults as $index => $result) {
                $status = (string) $result->status;

                if ($status === 'success') {
                    $created++;
                    $key = (string) ($result->key ?? '');
                    $details[] = [
                        'index' => $index,
                        'success' => true,
                        'key' => $key,
                    ];
                } else {
                    // Try multiple error paths - Sage Intacct uses different structures
                    $errorText = '';

                    // Try errormessage/error path
                    $errorMessages = $result->xpath('errormessage/error');
                    if (! empty($errorMessages)) {
                        $errorDetails = [];
                        foreach ($errorMessages as $error) {
                            // Get description2 first (more detailed), then description
                            $desc2 = trim((string) ($error->description2 ?? ''));
                            $desc1 = trim((string) ($error->description ?? ''));
                            $correction = trim((string) ($error->correction ?? ''));
                            $errorno = trim((string) ($error->errorno ?? ''));

                            // Build error message from available parts
                            $msg = $desc2 ?: $desc1;
                            if (empty($msg) && $errorno) {
                                $msg = "Error code: {$errorno}";
                            }
                            if ($correction) {
                                $msg = $msg ? "{$msg} - {$correction}" : $correction;
                            }
                            if ($msg) {
                                $errorDetails[] = $msg;
                            }
                        }
                        $errorText = implode('; ', $errorDetails);
                    }

                    // If still empty, log the raw XML and use a fallback message
                    if (empty($errorText) || $errorText === 'Unknown error') {
                        // Log the raw result XML for debugging (only log first few failures to avoid spam)
                        if ($index < 3) {
                            Log::warning('Sage Intacct: Empty error message from API', [
                                'index' => $index,
                                'status' => $status,
                                'result_xml' => $result->asXML(),
                            ]);
                        }
                        $errorText = $errorText ?: 'API returned failure without error details (check Sage Intacct audit logs)';
                    }

                    $failed[] = [
                        'index' => $index,
                        'error' => $errorText,
                    ];

                    $details[] = [
                        'index' => $index,
                        'success' => false,
                        'error' => $errorText,
                    ];
                }
            }

            return [
                'created' => $created,
                'failed' => $failed,
                'details' => $details,
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct batch response parsing exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'created' => 0,
                'failed' => [],
                'details' => [],
            ];
        }
    }

    /**
     * Upload an attachment to Sage Intacct using create_supdoc (XML method only)
     *
     * @param  string  $fileName  Original filename (without extension)
     * @param  string  $fileContents  Binary file contents
     * @param  string  $fileExtension  File extension (without dot, e.g., 'jpg', 'pdf')
     * @param  string  $description  Description for the attachment
     * @param  string  $folderName  Sage Intacct folder name (default: from env)
     * @return array Result with success status and SUPDOCID
     */
    public function createAttachment(
        string $fileName,
        string $fileContents,
        string $fileExtension,
        string $description = '',
        string $folderName = ''
    ): array {
        // Only XML API supports attachment uploads via this method
        if (! $this->setting->isXmlApi()) {
            return [
                'success' => false,
                'error' => 'Attachment uploads are only supported in XML API mode',
            ];
        }

        try {
            // Ensure we have a valid session
            if (! $this->sessionId) {
                $authResult = $this->authenticateXml();
                if (! $authResult['success']) {
                    return $authResult;
                }
            }

            // Base64 encode file contents for XML transmission
            $base64Contents = base64_encode($fileContents);

            // Validate file size (Intacct recommends keeping under 10MB for performance)
            $sizeMB = strlen($base64Contents) / 1024 / 1024;
            $maxSizeMB = env('INTACCT_ATTACHMENT_MAX_SIZE_MB', 10);

            if ($sizeMB > $maxSizeMB) {
                return [
                    'success' => false,
                    'error' => 'Attachment too large: '.round($sizeMB, 2)."MB (max {$maxSizeMB}MB)",
                ];
            }

            // Use folder from parameter or fall back to env config
            if (empty($folderName)) {
                $folderName = env('INTACCT_ATTACHMENT_FOLDER', 'Gift_Check_Images');
            }

            // Generate unique SUPDOCID (max 20 characters per Intacct requirements)
            // Format: CHK-{gift_id}-{timestamp_last_6_digits}
            // Example: CHK-196375-891696
            $timestamp = substr(time(), -6); // Last 6 digits of timestamp

            // Extract gift ID from filename (format: Gift_{giftid}_{name}.ext)
            preg_match('/Gift_(\d+)_/', $fileName, $matches);
            $giftIdForSupdoc = $matches[1] ?? substr(preg_replace('/[^A-Za-z0-9]/', '', $fileName), 0, 6);

            $supdocId = 'CHK-'.$giftIdForSupdoc.'-'.$timestamp;

            // Enforce 20 character limit - truncate gift ID portion if needed
            if (strlen($supdocId) > 20) {
                // CHK- (4) + giftId + - (1) + timestamp (6) = 11 + giftId
                // Max giftId length = 20 - 11 = 9
                $maxGiftIdLen = 20 - 4 - 1 - strlen($timestamp);
                $giftIdForSupdoc = substr($giftIdForSupdoc, 0, $maxGiftIdLen);
                $supdocId = 'CHK-'.$giftIdForSupdoc.'-'.$timestamp;
            }

            Log::info('Sage Intacct XML: Creating SUPDOC attachment', [
                'supdocid' => $supdocId,
                'supdocid_length' => strlen($supdocId),
                'filename' => $fileName,
                'extension' => $fileExtension,
                'folder' => $folderName,
                'size_mb' => round($sizeMB, 2),
            ]);

            // Build XML request for create_supdoc
            $xmlRequest = $this->buildSupdocXml(
                $supdocId,
                $fileName,
                $fileExtension,
                $base64Contents,
                $folderName,
                $description
            );

            // Send request
            $response = $this->sendXmlRequest($xmlRequest);

            if (! $response['success']) {
                Log::error('Sage Intacct SUPDOC creation failed', [
                    'supdocid' => $supdocId,
                    'error' => $response['error'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Failed to create SUPDOC',
                ];
            }

            // Parse response
            $parseResult = $this->parseXmlResponse($response['xml']);

            if (! $parseResult['success']) {
                return [
                    'success' => false,
                    'error' => $parseResult['error'] ?? 'Failed to parse SUPDOC response',
                ];
            }

            // Extract SUPDOCID from response
            $supdocid = $parseResult['key'] ?? null;

            if (empty($supdocid)) {
                // Try to extract from nested data structure
                $supdocid = $parseResult['data']['operation']['result']['key'] ?? null;
            }

            if (empty($supdocid)) {
                // Try to extract from XML directly
                $xml = simplexml_load_string($response['xml']);
                $keyNodes = $xml->xpath('//result/key');
                if (! empty($keyNodes)) {
                    $supdocid = (string) $keyNodes[0];
                }
            }

            // If still no SUPDOCID, the creation failed
            if (empty($supdocid)) {
                Log::error('Sage Intacct XML: SUPDOC creation failed - no SUPDOCID returned', [
                    'requested_supdocid' => $supdocId,
                    'parse_result' => $parseResult,
                    'response_preview' => substr($response['xml'], 0, 1000),
                ]);

                return [
                    'success' => false,
                    'error' => 'SUPDOC creation failed - no SUPDOCID returned. Check XML format and field names.',
                ];
            }

            Log::info('Sage Intacct XML: SUPDOC created successfully', [
                'requested_supdocid' => $supdocId,
                'returned_supdocid' => $supdocid,
                'using_for_linking' => $supdocId,
            ]);

            // Use the requested SUPDOCID (not the returned one) for linking
            // Sage Intacct requires referencing the SUPDOCID you specified in create_supdoc
            return [
                'success' => true,
                'supdocid' => $supdocId,
                'message' => 'SUPDOC created successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct SUPDOC creation exception', [
                'filename' => $fileName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Link SUPDOC to a gift record (XML method only)
     *
     * @param  string  $supdocId  The SUPDOCID to update
     * @param  string  $recordKey  The RECORDNO of the gift to link to
     * @return array Result with success status
     */
    public function linkSupdocToRecord(string $supdocId, string $recordKey): array
    {
        // Only XML API supports this operation
        if (! $this->setting->isXmlApi()) {
            return [
                'success' => false,
                'error' => 'SUPDOC linking is only supported in XML API mode',
            ];
        }

        try {
            // Ensure we have a valid session
            if (! $this->sessionId) {
                $authResult = $this->authenticateXml();
                if (! $authResult['success']) {
                    return $authResult;
                }
            }

            Log::info('Sage Intacct XML: Linking SUPDOC to record', [
                'supdocid' => $supdocId,
                'record_key' => $recordKey,
            ]);

            // Build update_supdoc XML request
            $xmlRequest = $this->buildSupdocLinkXml($supdocId, $recordKey);

            // Send request
            $response = $this->sendXmlRequest($xmlRequest);

            if (! $response['success']) {
                Log::error('Sage Intacct SUPDOC linking failed', [
                    'supdocid' => $supdocId,
                    'record_key' => $recordKey,
                    'error' => $response['error'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Failed to link SUPDOC',
                ];
            }

            // Parse response
            $parseResult = $this->parseXmlResponse($response['xml']);

            if (! $parseResult['success']) {
                return [
                    'success' => false,
                    'error' => $parseResult['error'] ?? 'Failed to parse SUPDOC link response',
                ];
            }

            Log::info('Sage Intacct XML: SUPDOC linked successfully', [
                'supdocid' => $supdocId,
                'record_key' => $recordKey,
            ]);

            return [
                'success' => true,
                'message' => 'SUPDOC linked successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct SUPDOC linking exception', [
                'supdocid' => $supdocId,
                'record_key' => $recordKey,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build XML for linking SUPDOC to a record
     *
     * @param  string  $supdocId  The SUPDOCID to update
     * @param  string  $recordKey  The RECORDNO to link to
     * @return string XML request
     */
    private function buildSupdocLinkXml(string $supdocId, string $recordKey): string
    {
        $controlId = 'supdoc-link-'.time().'-'.uniqid();
        $functionId = 'func-'.time().'-'.uniqid();

        $sanitizedSupdocId = htmlspecialchars($supdocId, ENT_XML1, 'UTF-8');
        $sanitizedRecordKey = htmlspecialchars($recordKey, ENT_XML1, 'UTF-8');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$this->setting->sender_id}</senderid>
        <password>{$this->setting->sender_password}</password>
        <controlid>{$controlId}</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <sessionid>{$this->sessionId}</sessionid>
        </authentication>
        <content>
            <function controlid="{$functionId}">
                <update_supdoc key="{$sanitizedSupdocId}">
                    <supdockey>{$sanitizedRecordKey}</supdockey>
                </update_supdoc>
            </function>
        </content>
    </operation>
</request>
XML;
    }

    /**
     * Build XML for SUPDOC creation
     *
     * @param  string  $supdocId  Unique ID for the SUPDOC (max 20 characters)
     * @param  string  $fileName  File name without extension
     * @param  string  $fileExtension  File extension without dot
     * @param  string  $base64Contents  Base64 encoded file contents
     * @param  string  $folderName  Intacct folder name
     * @param  string  $description  Description for the SUPDOC
     * @return string XML request
     */
    private function buildSupdocXml(
        string $supdocId,
        string $fileName,
        string $fileExtension,
        string $base64Contents,
        string $folderName,
        string $description
    ): string {
        $controlId = 'supdoc-'.time().'-'.uniqid();
        $functionId = 'func-'.time().'-'.uniqid();

        // Sanitize XML content
        $sanitizedSupdocId = htmlspecialchars($supdocId, ENT_XML1, 'UTF-8');
        $sanitizedFolderName = htmlspecialchars($folderName, ENT_XML1, 'UTF-8');
        $sanitizedDescription = htmlspecialchars($description ?: 'Check image', ENT_XML1, 'UTF-8');
        $sanitizedFileName = htmlspecialchars($fileName, ENT_XML1, 'UTF-8');
        $sanitizedExtension = htmlspecialchars($fileExtension, ENT_XML1, 'UTF-8');

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$this->setting->sender_id}</senderid>
        <password>{$this->setting->sender_password}</password>
        <controlid>{$controlId}</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <sessionid>{$this->sessionId}</sessionid>
        </authentication>
        <content>
            <function controlid="{$functionId}">
                <create_supdoc>
                    <supdocid>{$sanitizedSupdocId}</supdocid>
                    <supdocname>{$sanitizedFileName}.{$sanitizedExtension}</supdocname>
                    <supdocfoldername>{$sanitizedFolderName}</supdocfoldername>
                    <supdocdescription>{$sanitizedDescription}</supdocdescription>
                    <attachments>
                        <attachment>
                            <attachmentname>{$sanitizedFileName}.{$sanitizedExtension}</attachmentname>
                            <attachmenttype>{$sanitizedExtension}</attachmenttype>
                            <attachmentdata>{$base64Contents}</attachmentdata>
                        </attachment>
                    </attachments>
                </create_supdoc>
            </function>
        </content>
    </operation>
</request>
XML;
    }

    /**
     * Build Other Receipt XML request
     *
     * @param  array  $receiptData  Other Receipt data
     * @return string XML request
     */
    private function buildOtherReceiptXml(array $receiptData): string
    {
        $controlId = 'receipt-'.time().'-'.uniqid();
        $functionId = 'func-'.time().'-'.uniqid();

        $functionContent = $this->buildOtherReceiptFunctionContent($receiptData, 0);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$this->setting->sender_id}</senderid>
        <password>{$this->setting->sender_password}</password>
        <controlid>{$controlId}</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <sessionid>{$this->sessionId}</sessionid>
        </authentication>
        <content>
            {$functionContent}
        </content>
    </operation>
</request>
XML;
    }

    /**
     * Build Other Receipt function content for batch or single request
     *
     * @param  array  $receiptData  Other Receipt data
     * @param  int  $index  Index for control ID
     * @return string XML function content
     */
    private function buildOtherReceiptFunctionContent(array $receiptData, int $index): string
    {
        $controlId = 'receipt-'.$index.'-'.uniqid();

        // Parse dates to year/month/day structure
        $paymentDate = $this->parseDateToArray($receiptData['paymentdate']);
        $receivedDate = $this->parseDateToArray($receiptData['receiveddate']);
        $depositDate = isset($receiptData['depositdate']) ? $this->parseDateToArray($receiptData['depositdate']) : null;

        // Sanitize required fields
        $payee = htmlspecialchars($receiptData['payee'], ENT_XML1, 'UTF-8');
        $paymentMethod = htmlspecialchars($receiptData['paymentmethod'], ENT_XML1, 'UTF-8');
        $refId = htmlspecialchars($receiptData['refid'] ?? '', ENT_XML1, 'UTF-8');
        $description = htmlspecialchars($receiptData['description'] ?? '', ENT_XML1, 'UTF-8');

        // Build date XML
        $paymentDateXml = "<paymentdate><year>{$paymentDate['year']}</year><month>{$paymentDate['month']}</month><day>{$paymentDate['day']}</day></paymentdate>";
        $receivedDateXml = "<receiveddate><year>{$receivedDate['year']}</year><month>{$receivedDate['month']}</month><day>{$receivedDate['day']}</day></receiveddate>";

        // Build bank account or undeposited funds field
        $accountXml = '';
        if (! empty($receiptData['bankaccountid'])) {
            $bankAccountId = htmlspecialchars($receiptData['bankaccountid'], ENT_XML1, 'UTF-8');
            $accountXml = "<bankaccountid>{$bankAccountId}</bankaccountid>";

            // Add deposit date if using bank account
            if ($depositDate) {
                $accountXml .= "<depositdate><year>{$depositDate['year']}</year><month>{$depositDate['month']}</month><day>{$depositDate['day']}</day></depositdate>";
            }
        } elseif (! empty($receiptData['undepglaccountno'])) {
            $undepAccount = htmlspecialchars($receiptData['undepglaccountno'], ENT_XML1, 'UTF-8');
            $accountXml = "<undepglaccountno>{$undepAccount}</undepglaccountno>";
        }

        // Build line items
        $lineItemsXml = '';
        $lineItems = $receiptData['receiptitems'] ?? [];
        foreach ($lineItems as $lineItem) {
            $glAccountNo = htmlspecialchars($lineItem['glaccountno'], ENT_XML1, 'UTF-8');
            // Strip commas to handle formatted numbers like "1,000.00"
            $amountRaw = str_replace(',', '', $lineItem['amount'] ?? 0);
            $amount = number_format((float) $amountRaw, 2, '.', '');
            $memo = isset($lineItem['memo']) ? '<memo>'.htmlspecialchars($lineItem['memo'], ENT_XML1, 'UTF-8').'</memo>' : '';

            // Add dimension fields if provided
            $locationXml = isset($lineItem['locationid']) ? '<locationid>'.htmlspecialchars($lineItem['locationid'], ENT_XML1, 'UTF-8').'</locationid>' : '';
            $projectXml = isset($lineItem['projectid']) ? '<projectid>'.htmlspecialchars($lineItem['projectid'], ENT_XML1, 'UTF-8').'</projectid>' : '';
            $itemXml = isset($lineItem['itemid']) ? '<itemid>'.htmlspecialchars($lineItem['itemid'], ENT_XML1, 'UTF-8').'</itemid>' : '';
            $departmentXml = isset($lineItem['departmentid']) ? '<departmentid>'.htmlspecialchars($lineItem['departmentid'], ENT_XML1, 'UTF-8').'</departmentid>' : '';
            $classXml = isset($lineItem['classid']) ? '<classid>'.htmlspecialchars($lineItem['classid'], ENT_XML1, 'UTF-8').'</classid>' : '';

            $lineItemsXml .= <<<LINEITEM
                <lineitem>
                    <glaccountno>{$glAccountNo}</glaccountno>
                    <amount>{$amount}</amount>
                    {$memo}
                    {$locationXml}
                    {$departmentXml}
                    {$projectXml}
                    {$itemXml}
                    {$classXml}
                </lineitem>
LINEITEM;
        }

        // Add optional SUPDOC attachment if provided
        $supdocXml = '';
        if (! empty($receiptData['supdocid'])) {
            $supdocid = htmlspecialchars($receiptData['supdocid'], ENT_XML1, 'UTF-8');
            $supdocXml = "<supdocid>{$supdocid}</supdocid>";
        }

        // Build custom fields XML
        $customFieldsXml = '';
        $customFieldItems = '';

        // Add custom fields from the _custom_fields array
        // This allows the sync service to pass custom fields with configurable names
        if (! empty($receiptData['_custom_fields']) && is_array($receiptData['_custom_fields'])) {
            foreach ($receiptData['_custom_fields'] as $fieldName => $fieldValue) {
                if (! empty($fieldValue)) {
                    $sanitizedFieldName = htmlspecialchars($fieldName, ENT_XML1, 'UTF-8');
                    $sanitizedFieldValue = htmlspecialchars($fieldValue, ENT_XML1, 'UTF-8');
                    $customFieldItems .= <<<CUSTOMFIELD
                        <customfield>
                            <customfieldname>{$sanitizedFieldName}</customfieldname>
                            <customfieldvalue>{$sanitizedFieldValue}</customfieldvalue>
                        </customfield>
CUSTOMFIELD;
                }
            }
        }

        // Wrap custom fields if any exist
        if (! empty($customFieldItems)) {
            $customFieldsXml = <<<CUSTOMFIELDS
                    <customfields>
{$customFieldItems}
                    </customfields>
CUSTOMFIELDS;
        }

        return <<<XML
            <function controlid="{$controlId}">
                <record_otherreceipt>
                    {$paymentDateXml}
                    <payee>{$payee}</payee>
                    {$receivedDateXml}
                    <paymentmethod>{$paymentMethod}</paymentmethod>
                    {$accountXml}
                    <refid>{$refId}</refid>
                    <description>{$description}</description>
                    {$supdocXml}
                    {$customFieldsXml}
                    <receiptitems>
                        {$lineItemsXml}
                    </receiptitems>
                </record_otherreceipt>
            </function>
XML;
    }

    /**
     * Parse date string to array with year/month/day
     *
     * @param  string  $dateString  Date string (Y-m-d format)
     * @return array Array with year, month, day keys
     */
    private function parseDateToArray(string $dateString): array
    {
        $date = \Carbon\Carbon::parse($dateString);

        return [
            'year' => $date->year,
            'month' => str_pad($date->month, 2, '0', STR_PAD_LEFT),
            'day' => str_pad($date->day, 2, '0', STR_PAD_LEFT),
        ];
    }

    // ====================================================================
    // OAUTH 2.0 AUTHENTICATION & METHODS
    // ====================================================================

    /**
     * Authenticate with OAuth 2.0 (ensure we have a valid access token)
     */
    private function authenticateOAuth(): array
    {
        try {
            // Validate we have a user token
            if (! $this->userToken) {
                return [
                    'success' => false,
                    'message' => 'OAuth authentication requires a user token',
                    'requires_reauth' => true,
                ];
            }

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
            Log::error('Sage Intacct OAuth authentication check exception', [
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

            Log::debug('Sage Intacct OAuth: Refreshing access token', [
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

                Log::error('Sage Intacct OAuth token refresh failed', [
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
            // Note: Sage Intacct tokens typically expire in 300 seconds (5 minutes)
            $this->userToken->updateToken(
                $data['access_token'],
                $data['expires_in'] ?? 300,
                $data['refresh_token'] ?? null
            );

            // Update local reference
            $this->accessToken = $data['access_token'];

            Log::info('Sage Intacct OAuth: Access token refreshed successfully', [
                'user_id' => $this->userToken->user_id,
            ]);

            return [
                'success' => true,
                'message' => 'Token refreshed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Sage Intacct OAuth token refresh exception', [
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
     * Test OAuth API connectivity
     */
    private function testConnectionOAuth(): array
    {
        try {
            // Validate we have a user token
            if (! $this->userToken) {
                return [
                    'success' => false,
                    'message' => 'OAuth authentication requires a user token',
                ];
            }

            // Ensure token is valid
            $authResult = $this->authenticateOAuth();
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

                Log::error('Sage Intacct OAuth company verification failed', [
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

            Log::info('Sage Intacct OAuth connection test successful', [
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
            Log::error('Sage Intacct OAuth connection test exception', [
                'user_id' => $this->userToken->user_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Create a record in a custom object (OAuth method)
     */
    private function createCustomObjectRecordOAuth(string $objectName, array $fieldData): array
    {
        try {
            // Validate we have a user token
            if (! $this->userToken) {
                return [
                    'success' => false,
                    'error' => 'OAuth authentication requires a user token',
                ];
            }

            // Sandbox safety check
            if (! $this->userToken->isSandbox()) {
                Log::warning('Sage Intacct OAuth: Creating record in non-sandbox environment', [
                    'user_id' => $this->userToken->user_id,
                    'company_id' => $this->userToken->company_id,
                    'object_name' => $objectName,
                ]);
            }

            // Ensure valid token
            $authResult = $this->authenticateOAuth();
            if (! $authResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                ];
            }

            $apiUrl = 'https://api.intacct.com/ia/api/v1/companies/'.urlencode($this->userToken->company_id).
                      '/objects/'.urlencode($objectName);

            Log::info('Sage Intacct OAuth API: Creating custom object record', [
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

                Log::error('Sage Intacct OAuth record creation failed', [
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

            Log::info('Sage Intacct OAuth API: Record created successfully', [
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
            Log::error('Sage Intacct OAuth createCustomObjectRecord exception', [
                'user_id' => $this->userToken->user_id ?? null,
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
     * Batch create records in a custom object (OAuth method)
     */
    private function batchCreateRecordsOAuth(string $objectName, array $records): array
    {
        try {
            // Validate we have a user token
            if (! $this->userToken) {
                return [
                    'success' => false,
                    'error' => 'OAuth authentication requires a user token',
                ];
            }

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
                Log::warning('Sage Intacct OAuth: Batch creating records in non-sandbox environment', [
                    'user_id' => $this->userToken->user_id,
                    'company_id' => $this->userToken->company_id,
                    'object_name' => $objectName,
                    'record_count' => count($records),
                ]);
            }

            // Ensure valid token
            $authResult = $this->authenticateOAuth();
            if (! $authResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                ];
            }

            $apiUrl = 'https://api.intacct.com/ia/api/v1/companies/'.urlencode($this->userToken->company_id).
                      '/objects/'.urlencode($objectName).'/batch';

            Log::info('Sage Intacct OAuth API: Batch creating records', [
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

                Log::error('Sage Intacct OAuth batch create failed', [
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

            Log::info('Sage Intacct OAuth API: Batch create completed', [
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
            Log::error('Sage Intacct OAuth batchCreateRecords exception', [
                'user_id' => $this->userToken->user_id ?? null,
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

    // ====================================================================
    // OTHER RECEIPT QUERY METHODS
    // ====================================================================

    /**
     * Query Other Receipts by their RECORDNO values to get deposit information
     * Used for syncing deposit dates back to DonorPerfect
     *
     * @param  array  $recordNos  Array of Sage Intacct RECORDNO values
     * @return array Result with receipts data including deposit status
     */
    public function queryOtherReceiptsByRecordNos(array $recordNos): array
    {
        if (empty($recordNos)) {
            return [
                'success' => true,
                'receipts' => [],
                'count' => 0,
            ];
        }

        if ($this->setting->isXmlApi()) {
            return $this->queryOtherReceiptsByRecordNosXml($recordNos);
        } else {
            return $this->queryOtherReceiptsByRecordNosOAuth($recordNos);
        }
    }

    /**
     * Query Other Receipts using XML Web Services
     * Uses <read> by keys instead of <readByQuery> to access line-item dimensions
     * (PROJECTID/DEPARTMENTID) which are flattened into the header by read with fields=*
     */
    private function queryOtherReceiptsByRecordNosXml(array $recordNos): array
    {
        try {
            // Ensure we have a session
            if (! $this->sessionId) {
                $authResult = $this->authenticate();
                if (! $authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                        'receipts' => [],
                    ];
                }
            }

            Log::info('Sage Intacct: Reading Other Receipts by keys', [
                'record_count' => count($recordNos),
            ]);

            // Sage read supports max 100 keys per call, process in chunks
            $allReceipts = [];
            $chunks = array_chunk($recordNos, 100);

            foreach ($chunks as $chunk) {
                $xmlRequest = $this->buildOtherReceiptReadXml($chunk);
                $response = $this->sendXmlRequest($xmlRequest);

                if (! $response['success']) {
                    return [
                        'success' => false,
                        'error' => $response['error'] ?? 'Read failed',
                        'receipts' => [],
                    ];
                }

                $receipts = $this->parseOtherReceiptQueryResponse($response['xml']);
                $allReceipts = array_merge($allReceipts, $receipts);
            }

            Log::info('Sage Intacct: Other Receipts read completed', [
                'queried' => count($recordNos),
                'found' => count($allReceipts),
            ]);

            return [
                'success' => true,
                'receipts' => $allReceipts,
                'count' => count($allReceipts),
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct queryOtherReceiptsByRecordNosXml exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Exception: '.$e->getMessage(),
                'receipts' => [],
            ];
        }
    }

    /**
     * Query Other Receipts using OAuth REST API
     */
    private function queryOtherReceiptsByRecordNosOAuth(array $recordNos): array
    {
        try {
            if (! $this->userToken) {
                return [
                    'success' => false,
                    'error' => 'OAuth authentication requires a user token',
                    'receipts' => [],
                ];
            }

            // Ensure valid token
            $authResult = $this->authenticateOAuth();
            if (! $authResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Authentication failed: '.($authResult['message'] ?? 'Unknown error'),
                    'receipts' => [],
                ];
            }

            // Build filter for REST API
            $recordNoList = implode(',', array_map('intval', $recordNos));
            $filter = urlencode("RECORDNO in ({$recordNoList})");

            $apiUrl = 'https://api.intacct.com/ia/api/v1/companies/'.urlencode($this->userToken->company_id).
                      '/objects/OTHERRECEIPTS?filter='.$filter.
                      '&fields=RECORDNO,DESCRIPTION2,DOCNUMBER,WHENPAID,WHENCREATED,DEPOSITDATE,BANKACCOUNTID,STATE,TOTALENTERED,PROJECTID,DEPARTMENTID';

            Log::info('Sage Intacct OAuth: Querying Other Receipts', [
                'record_count' => count($recordNos),
            ]);

            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$this->accessToken,
                    'Accept' => 'application/json',
                ])
                ->get($apiUrl);

            if (! $response->successful()) {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Query failed';

                Log::error('Sage Intacct OAuth Other Receipt query failed', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'receipts' => [],
                ];
            }

            $result = $response->json();
            $receipts = $result['data'] ?? $result['items'] ?? $result;

            // Normalize receipt data
            $normalizedReceipts = [];
            foreach ($receipts as $receipt) {
                $normalizedReceipts[] = [
                    'recordno' => $receipt['RECORDNO'] ?? $receipt['recordno'] ?? null,
                    'payee' => $receipt['PAYEE'] ?? $receipt['payee'] ?? null,
                    'refid' => $receipt['REFID'] ?? $receipt['refid'] ?? null,
                    'paymentdate' => $receipt['PAYMENTDATE'] ?? $receipt['paymentdate'] ?? null,
                    'receiveddate' => $receipt['RECEIVEDDATE'] ?? $receipt['receiveddate'] ?? null,
                    'depositdate' => $receipt['DEPOSITDATE'] ?? $receipt['depositdate'] ?? null,
                    'bankaccountid' => $receipt['BANKACCOUNTID'] ?? $receipt['bankaccountid'] ?? null,
                    'undepglaccountno' => $receipt['UNDEPGLACCOUNTNO'] ?? $receipt['undepglaccountno'] ?? null,
                    'state' => $receipt['STATE'] ?? $receipt['state'] ?? null,
                    'totalentered' => $receipt['TOTALENTERED'] ?? $receipt['totalentered'] ?? null,
                    'projectid' => $receipt['PROJECTID'] ?? $receipt['projectid'] ?? null,
                    'departmentid' => $receipt['DEPARTMENTID'] ?? $receipt['departmentid'] ?? null,
                ];
            }

            Log::info('Sage Intacct OAuth: Other Receipts query completed', [
                'queried' => count($recordNos),
                'found' => count($normalizedReceipts),
            ]);

            return [
                'success' => true,
                'receipts' => $normalizedReceipts,
                'count' => count($normalizedReceipts),
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct queryOtherReceiptsByRecordNosOAuth exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Exception: '.$e->getMessage(),
                'receipts' => [],
            ];
        }
    }

    /**
     * Build XML request to read Other Receipts by keys
     * Uses <read> instead of <readByQuery> to access line-item dimensions
     * (PROJECTID/DEPARTMENTID are flattened from the first line item into the header)
     *
     * @param  array  $keys  Array of RECORDNO values (max 100 per Sage API limit)
     */
    private function buildOtherReceiptReadXml(array $keys): string
    {
        $controlId = $this->generateControlId();
        $functionId = $this->generateControlId();

        $authSection = $this->sessionId
            ? "<sessionid>{$this->sessionId}</sessionid>"
            : "<login>
                    <userid>{$this->setting->web_services_user_id}</userid>
                    <companyid>{$this->setting->company_id}</companyid>
                    <password>{$this->setting->web_services_password}</password>
                </login>";

        $keysList = implode(',', array_map('intval', $keys));

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$this->setting->sender_id}</senderid>
        <password>{$this->setting->sender_password}</password>
        <controlid>{$controlId}</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
        <includewhitespace>false</includewhitespace>
    </control>
    <operation>
        <authentication>
            {$authSection}
        </authentication>
        <content>
            <function controlid="{$functionId}">
                <read>
                    <object>OTHERRECEIPTS</object>
                    <keys>{$keysList}</keys>
                    <fields>*</fields>
                </read>
            </function>
        </content>
    </operation>
</request>
XML;
    }

    /**
     * Parse the response from Other Receipt query
     */
    private function parseOtherReceiptQueryResponse(string $xmlString): array
    {
        try {
            $xml = simplexml_load_string($xmlString);
            if ($xml === false) {
                Log::error('Sage Intacct: Failed to parse Other Receipt query response');

                return [];
            }

            $receipts = [];

            // Try different possible response structures
            // Sage Intacct returns <otherreceipts> nodes (lowercase) for the OTHERRECEIPTS object
            $dataNodes = $xml->xpath('//result/data/otherreceipts');
            if (empty($dataNodes)) {
                $dataNodes = $xml->xpath('//result/data/OTHERRECEIPTS');
            }
            if (empty($dataNodes)) {
                $dataNodes = $xml->xpath('//data/otherreceipts');
            }
            if (empty($dataNodes)) {
                $dataNodes = $xml->xpath('//data/OTHERRECEIPTS');
            }

            foreach ($dataNodes as $node) {
                // Normalize deposit date to Y-m-d format
                // Sage returns MM/DD/YYYY from readByQuery but YYYY-MM-DD from read
                $rawDepositDate = (string) ($node->DEPOSITDATE ?? '');
                if (! empty($rawDepositDate)) {
                    $parsedDate = date_create($rawDepositDate);
                    if ($parsedDate) {
                        $rawDepositDate = $parsedDate->format('Y-m-d');
                    }
                }

                $receipt = [
                    'recordno' => (string) ($node->RECORDNO ?? ''),
                    'payee' => (string) ($node->DESCRIPTION2 ?? $node->PAYEE ?? ''),
                    'refid' => (string) ($node->DOCNUMBER ?? $node->REFID ?? ''),
                    'paymentdate' => (string) ($node->WHENPAID ?? $node->PAYMENTDATE ?? ''),
                    'receiveddate' => (string) ($node->WHENCREATED ?? $node->RECEIVEDDATE ?? ''),
                    'depositdate' => $rawDepositDate,
                    'bankaccountid' => (string) ($node->BANKACCOUNTID ?? ''),
                    'undepglaccountno' => (string) ($node->UNDEPACCTNO ?? $node->UNDEPGLACCOUNTNO ?? ''),
                    'state' => (string) ($node->STATE ?? ''),
                    'totalentered' => (string) ($node->TOTALENTERED ?? ''),
                    'projectid' => (string) ($node->PROJECTID ?? ''),
                    'departmentid' => (string) ($node->DEPARTMENTID ?? ''),
                ];

                // Only include if we have a valid recordno
                if (! empty($receipt['recordno'])) {
                    $receipts[] = $receipt;
                }
            }

            return $receipts;

        } catch (\Exception $e) {
            Log::error('Sage Intacct parseOtherReceiptQueryResponse exception', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    // ====================================================================
    // SHARED HELPER METHODS
    // ====================================================================

    /**
     * Convert SimpleXMLElement to array
     */
    private function xmlToArray(\SimpleXMLElement $xml): array
    {
        $json = json_encode($xml);

        return json_decode($json, true);
    }

    /**
     * Generate unique control ID
     */
    private function generateControlId(): string
    {
        return 'CAPTRACK_'.time().'_'.uniqid();
    }

    /**
     * Get the current setting instance
     */
    public function getSetting()
    {
        return $this->setting;
    }

    /**
     * Get the current session ID (XML API only)
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * Get the user token (OAuth only)
     */
    public function getUserToken(): ?UserSageIntacctToken
    {
        return $this->userToken;
    }
}
