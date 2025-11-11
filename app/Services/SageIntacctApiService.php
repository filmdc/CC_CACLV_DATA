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

            Log::info('Sage Intacct XML API: Batch create completed', [
                'object_name' => $objectName,
                'total' => count($records),
                'created' => $result['created'],
                'failed' => count($result['failed']),
            ]);

            return [
                'success' => true,
                'created' => $result['created'],
                'failed' => $result['failed'],
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
    private function buildXmlRequest(string $functionName, array $parameters): string
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

        // Build function content
        $functionContent = $this->buildFunctionContent($functionName, $parameters);

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
            <function controlid="'.$functionId.'">
                '.$functionContent.'
            </function>
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

            $fieldsXml = '';
            foreach ($fields as $fieldName => $fieldValue) {
                $fieldsXml .= '<'.htmlspecialchars($fieldName).'>'.htmlspecialchars($fieldValue).'</'.htmlspecialchars($fieldName).'>'."\n";
            }

            return '<create>
                <'.htmlspecialchars($objectName).'>
                    '.$fieldsXml.'
                </'.htmlspecialchars($objectName).'>
            </create>';
        }

        // Default: just return function name as empty element
        return '<'.htmlspecialchars($functionName).'/>';
    }

    /**
     * Build batch create XML with multiple functions
     */
    private function buildBatchCreateXml(string $objectName, array $records): string
    {
        $controlId = $this->generateControlId();

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
                $fieldsXml .= '<'.htmlspecialchars($fieldName).'>'.htmlspecialchars($fieldValue).'</'.htmlspecialchars($fieldName).'>'."\n";
            }

            $functionsXml .= '<function controlid="'.$functionId.'">
                <create>
                    <'.htmlspecialchars($objectName).'>
                        '.$fieldsXml.'
                    </'.htmlspecialchars($objectName).'>
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
                ];
            }

            $created = 0;
            $failed = [];
            $details = [];

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
                    $errorMessages = $result->xpath('errormessage/error');
                    $errorText = 'Unknown error';

                    if (! empty($errorMessages)) {
                        $errorDetails = [];
                        foreach ($errorMessages as $error) {
                            $description = (string) ($error->description2 ?? $error->description ?? 'Unknown error');
                            $errorDetails[] = $description;
                        }
                        $errorText = implode('; ', $errorDetails);
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

            // Generate unique SUPDOC name
            $supdocName = 'CheckImage_'.$fileName.'_'.time();

            Log::info('Sage Intacct XML: Creating SUPDOC attachment', [
                'supdoc_name' => $supdocName,
                'filename' => $fileName,
                'extension' => $fileExtension,
                'folder' => $folderName,
                'size_mb' => round($sizeMB, 2),
            ]);

            // Build XML request for create_supdoc
            $xmlRequest = $this->buildSupdocXml(
                $supdocName,
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
                    'supdoc_name' => $supdocName,
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
                // Try to extract from data
                $xml = simplexml_load_string($response['xml']);
                $supdocidNodes = $xml->xpath('//supdocid');
                if (! empty($supdocidNodes)) {
                    $supdocid = (string) $supdocidNodes[0];
                }
            }

            Log::info('Sage Intacct XML: SUPDOC created successfully', [
                'supdoc_name' => $supdocName,
                'supdocid' => $supdocid,
            ]);

            return [
                'success' => true,
                'supdocid' => $supdocid,
                'supdoc_name' => $supdocName,
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
     * Build XML for SUPDOC creation
     *
     * @param  string  $supdocName  Unique name for the SUPDOC
     * @param  string  $fileName  File name without extension
     * @param  string  $fileExtension  File extension without dot
     * @param  string  $base64Contents  Base64 encoded file contents
     * @param  string  $folderName  Intacct folder name
     * @param  string  $description  Description for the SUPDOC
     * @return string XML request
     */
    private function buildSupdocXml(
        string $supdocName,
        string $fileName,
        string $fileExtension,
        string $base64Contents,
        string $folderName,
        string $description
    ): string {
        $controlId = 'supdoc-'.time().'-'.uniqid();
        $functionId = 'func-'.time().'-'.uniqid();

        // Sanitize XML content
        $sanitizedSupdocName = htmlspecialchars($supdocName, ENT_XML1, 'UTF-8');
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
                    <supdocname>{$sanitizedSupdocName}</supdocname>
                    <supdocfoldername>{$sanitizedFolderName}</supdocfoldername>
                    <supdocdescription>{$sanitizedDescription}</supdocdescription>
                    <attachments>
                        <attachment>
                            <attachmentname>{$sanitizedFileName}</attachmentname>
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
