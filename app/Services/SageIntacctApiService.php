<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SageIntacctApiService
{
    private $setting;
    private ?string $sessionId = null;

    /**
     * Constructor - accepts SageIntacctSetting instance
     */
    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    /**
     * Authenticate with Sage Intacct and create a session
     * Returns array with success boolean and optional session ID or error message
     */
    public function authenticate(): array
    {
        try {
            if (!$this->setting->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'Sage Intacct credentials are not fully configured'
                ];
            }

            // Build authentication request
            $xmlRequest = $this->buildAuthenticationXml();

            Log::info('Sage Intacct API: Authenticating', [
                'sender_id' => $this->setting->sender_id,
                'company_id' => $this->setting->company_id,
                'user_id' => $this->setting->web_services_user_id
            ]);

            // Send authentication request
            $response = $this->sendRequest($xmlRequest);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? 'Authentication failed'
                ];
            }

            // Extract session ID from response
            $xml = simplexml_load_string($response['xml']);
            if ($xml === false) {
                return [
                    'success' => false,
                    'message' => 'Failed to parse authentication response'
                ];
            }

            // Check for API errors
            $errormessage = $xml->xpath('//errormessage');
            if (!empty($errormessage)) {
                $errorText = (string) $errormessage[0]->error->description2 ?? (string) $errormessage[0]->error->description ?? 'Authentication failed';

                Log::error('Sage Intacct authentication failed', [
                    'error' => $errorText
                ]);

                return [
                    'success' => false,
                    'message' => 'Authentication failed: ' . $errorText
                ];
            }

            // Get session ID
            $sessionId = $xml->xpath('//authentication/sessionid');
            if (empty($sessionId)) {
                return [
                    'success' => false,
                    'message' => 'No session ID returned from authentication'
                ];
            }

            $this->sessionId = (string) $sessionId[0];

            Log::info('Sage Intacct API: Authentication successful', [
                'session_id' => substr($this->sessionId, 0, 20) . '...'
            ]);

            return [
                'success' => true,
                'session_id' => $this->sessionId,
                'message' => 'Authentication successful'
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct authentication exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test API connectivity by authenticating
     * Returns array with success boolean and optional error message
     */
    public function testConnection(): array
    {
        try {
            if (!$this->setting->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'API credentials are not fully configured'
                ];
            }

            // Test authentication
            $authResult = $this->authenticate();

            if (!$authResult['success']) {
                return [
                    'success' => false,
                    'message' => $authResult['message']
                ];
            }

            // Try to read a simple function to verify access
            $testXml = $this->buildXmlRequest('getUserPermissions', []);
            $response = $this->sendRequest($testXml);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'Connection test failed: ' . ($response['error'] ?? 'Unknown error')
                ];
            }

            return [
                'success' => true,
                'message' => 'Connection successful'
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct connection test exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a record in a custom object
     *
     * @param string $objectName Custom object name in Sage Intacct
     * @param array $fieldData Associative array of field names and values
     * @return array Result with success boolean and optional error or record key
     */
    public function createCustomObjectRecord(string $objectName, array $fieldData): array
    {
        try {
            // Ensure we have a session
            if (!$this->sessionId) {
                $authResult = $this->authenticate();
                if (!$authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: ' . ($authResult['message'] ?? 'Unknown error')
                    ];
                }
            }

            // Build create request
            $parameters = [
                'CUSTOMOBJECT' => $objectName,
                'CUSTOMOBJECTFIELDS' => $fieldData
            ];

            $xmlRequest = $this->buildXmlRequest('create', $parameters);

            Log::info('Sage Intacct API: Creating custom object record', [
                'object_name' => $objectName,
                'fields' => array_keys($fieldData)
            ]);

            // Send request
            $response = $this->sendRequest($xmlRequest);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Failed to create record'
                ];
            }

            // Parse response
            $result = $this->parseXmlResponse($response['xml']);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to parse response'
                ];
            }

            Log::info('Sage Intacct API: Record created successfully', [
                'object_name' => $objectName,
                'record_key' => $result['key'] ?? 'unknown'
            ]);

            return [
                'success' => true,
                'key' => $result['key'] ?? null,
                'data' => $result['data'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct createCustomObjectRecord exception', [
                'object_name' => $objectName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Batch create records in a custom object (up to 100 records per batch)
     *
     * @param string $objectName Custom object name in Sage Intacct
     * @param array $records Array of associative arrays, each representing a record
     * @return array Result with success boolean, created count, and failed records
     */
    public function batchCreateRecords(string $objectName, array $records): array
    {
        try {
            // Validate batch size
            if (count($records) > 100) {
                return [
                    'success' => false,
                    'error' => 'Batch size exceeds limit of 100 records. Provided: ' . count($records)
                ];
            }

            if (empty($records)) {
                return [
                    'success' => true,
                    'created' => 0,
                    'failed' => [],
                    'message' => 'No records to create'
                ];
            }

            // Ensure we have a session
            if (!$this->sessionId) {
                $authResult = $this->authenticate();
                if (!$authResult['success']) {
                    return [
                        'success' => false,
                        'error' => 'Authentication failed: ' . ($authResult['message'] ?? 'Unknown error')
                    ];
                }
            }

            // Build batch create request with multiple functions
            $xmlRequest = $this->buildBatchCreateXml($objectName, $records);

            Log::info('Sage Intacct API: Batch creating records', [
                'object_name' => $objectName,
                'record_count' => count($records)
            ]);

            // Send request
            $response = $this->sendRequest($xmlRequest);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Batch create failed',
                    'created' => 0,
                    'failed' => $records
                ];
            }

            // Parse batch response
            $result = $this->parseBatchResponse($response['xml']);

            Log::info('Sage Intacct API: Batch create completed', [
                'object_name' => $objectName,
                'total' => count($records),
                'created' => $result['created'],
                'failed' => count($result['failed'])
            ]);

            return [
                'success' => true,
                'created' => $result['created'],
                'failed' => $result['failed'],
                'details' => $result['details'] ?? []
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct batchCreateRecords exception', [
                'object_name' => $objectName,
                'record_count' => count($records),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'created' => 0,
                'failed' => $records
            ];
        }
    }

    /**
     * Build XML request structure for Sage Intacct API
     *
     * @param string $functionName The function to call (e.g., 'create', 'read', 'update')
     * @param array $parameters Function parameters
     * @return string XML request string
     */
    public function buildXmlRequest(string $functionName, array $parameters): string
    {
        $controlId = $this->generateControlId();
        $functionId = $this->generateControlId();

        // Build authentication section
        if ($this->sessionId) {
            $authSection = '<sessionid>' . htmlspecialchars($this->sessionId) . '</sessionid>';
        } else {
            $authSection = '<login>
                <userid>' . htmlspecialchars($this->setting->web_services_user_id) . '</userid>
                <companyid>' . htmlspecialchars($this->setting->company_id) . '</companyid>
                <password>' . htmlspecialchars($this->setting->web_services_password) . '</password>
            </login>';
        }

        // Build function content
        $functionContent = $this->buildFunctionContent($functionName, $parameters);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>' . htmlspecialchars($this->setting->sender_id) . '</senderid>
        <password>' . htmlspecialchars($this->setting->sender_password) . '</password>
        <controlid>' . $controlId . '</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            ' . $authSection . '
        </authentication>
        <content>
            <function controlid="' . $functionId . '">
                ' . $functionContent . '
            </function>
        </content>
    </operation>
</request>';

        return $xml;
    }

    /**
     * Send HTTP request to Sage Intacct API
     *
     * @param string $xmlPayload XML request payload
     * @return array Response with success boolean and xml or error
     */
    public function sendRequest(string $xmlPayload): array
    {
        try {
            $apiUrl = $this->setting->api_url ?? 'https://api.intacct.com/ia/xml/xmlgw.phtml';

            Log::debug('Sage Intacct API: Sending request', [
                'url' => $apiUrl,
                'xml_preview' => substr($xmlPayload, 0, 500) . '...'
            ]);

            // Send POST request with XML payload
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/xml',
                ])
                ->withBody($xmlPayload, 'application/xml')
                ->post($apiUrl);

            if (!$response->successful()) {
                Log::error('Sage Intacct API request failed', [
                    'status' => $response->status(),
                    'response' => substr($response->body(), 0, 500)
                ]);

                return [
                    'success' => false,
                    'error' => 'API request failed with status: ' . $response->status()
                ];
            }

            $responseBody = $response->body();

            Log::debug('Sage Intacct API: Response received', [
                'status' => $response->status(),
                'preview' => substr($responseBody, 0, 500) . '...'
            ]);

            return [
                'success' => true,
                'xml' => $responseBody
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct API sendRequest exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parse XML response and validate
     *
     * @param string $xmlResponse XML response string
     * @return array Parsed response with success boolean and data or error
     */
    public function parseXmlResponse(string $xmlResponse): array
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
                if (!empty($errors)) {
                    $errorMessage .= ': ' . $errors[0]->message;
                }

                return [
                    'success' => false,
                    'error' => $errorMessage
                ];
            }

            libxml_use_internal_errors($previousErrorSetting);

            // Check for operation errors
            $operationResult = $xml->xpath('//operation/result/status');
            if (!empty($operationResult)) {
                $status = (string) $operationResult[0];

                if ($status === 'failure') {
                    $errorMessages = $xml->xpath('//operation/result/errormessage/error');
                    $errorText = 'Operation failed';

                    if (!empty($errorMessages)) {
                        $errorDetails = [];
                        foreach ($errorMessages as $error) {
                            $description = (string) ($error->description2 ?? $error->description ?? 'Unknown error');
                            $errorDetails[] = $description;
                        }
                        $errorText .= ': ' . implode('; ', $errorDetails);
                    }

                    return [
                        'success' => false,
                        'error' => $errorText
                    ];
                }
            }

            // Check for function result
            $functionResult = $xml->xpath('//function/result/status');
            if (!empty($functionResult)) {
                $status = (string) $functionResult[0];

                if ($status === 'failure') {
                    $errorMessages = $xml->xpath('//function/result/errormessage/error');
                    $errorText = 'Function failed';

                    if (!empty($errorMessages)) {
                        $errorDetails = [];
                        foreach ($errorMessages as $error) {
                            $description = (string) ($error->description2 ?? $error->description ?? 'Unknown error');
                            $correction = (string) ($error->correction ?? '');
                            $errorDetails[] = $description . ($correction ? ' (' . $correction . ')' : '');
                        }
                        $errorText .= ': ' . implode('; ', $errorDetails);
                    }

                    return [
                        'success' => false,
                        'error' => $errorText
                    ];
                }

                // Extract key if present (for create operations)
                $key = $xml->xpath('//function/result/key');
                $data = $xml->xpath('//function/result/data');

                return [
                    'success' => true,
                    'key' => !empty($key) ? (string) $key[0] : null,
                    'data' => !empty($data) ? $this->xmlToArray($data[0]) : []
                ];
            }

            // Success - return raw XML structure as array
            return [
                'success' => true,
                'data' => $this->xmlToArray($xml)
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct XML parsing exception', [
                'error' => $e->getMessage(),
                'xml_preview' => substr($xmlResponse, 0, 500)
            ]);

            return [
                'success' => false,
                'error' => 'XML parsing exception: ' . $e->getMessage()
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
        <senderid>' . htmlspecialchars($this->setting->sender_id) . '</senderid>
        <password>' . htmlspecialchars($this->setting->sender_password) . '</password>
        <controlid>' . $controlId . '</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <login>
                <userid>' . htmlspecialchars($this->setting->web_services_user_id) . '</userid>
                <companyid>' . htmlspecialchars($this->setting->company_id) . '</companyid>
                <password>' . htmlspecialchars($this->setting->web_services_password) . '</password>
            </login>
        </authentication>
        <content>
            <function controlid="' . $this->generateControlId() . '">
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
                $fieldsXml .= '<' . htmlspecialchars($fieldName) . '>' . htmlspecialchars($fieldValue) . '</' . htmlspecialchars($fieldName) . '>' . "\n";
            }

            return '<create>
                <' . htmlspecialchars($objectName) . '>
                    ' . $fieldsXml . '
                </' . htmlspecialchars($objectName) . '>
            </create>';
        }

        // Default: just return function name as empty element
        return '<' . htmlspecialchars($functionName) . '/>';
    }

    /**
     * Build batch create XML with multiple functions
     */
    private function buildBatchCreateXml(string $objectName, array $records): string
    {
        $controlId = $this->generateControlId();

        // Build authentication section
        if ($this->sessionId) {
            $authSection = '<sessionid>' . htmlspecialchars($this->sessionId) . '</sessionid>';
        } else {
            $authSection = '<login>
                <userid>' . htmlspecialchars($this->setting->web_services_user_id) . '</userid>
                <companyid>' . htmlspecialchars($this->setting->company_id) . '</companyid>
                <password>' . htmlspecialchars($this->setting->web_services_password) . '</password>
            </login>';
        }

        // Build function for each record
        $functionsXml = '';
        foreach ($records as $index => $record) {
            $functionId = $this->generateControlId();

            $fieldsXml = '';
            foreach ($record as $fieldName => $fieldValue) {
                $fieldsXml .= '<' . htmlspecialchars($fieldName) . '>' . htmlspecialchars($fieldValue) . '</' . htmlspecialchars($fieldName) . '>' . "\n";
            }

            $functionsXml .= '<function controlid="' . $functionId . '">
                <create>
                    <' . htmlspecialchars($objectName) . '>
                        ' . $fieldsXml . '
                    </' . htmlspecialchars($objectName) . '>
                </create>
            </function>' . "\n";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>' . htmlspecialchars($this->setting->sender_id) . '</senderid>
        <password>' . htmlspecialchars($this->setting->sender_password) . '</password>
        <controlid>' . $controlId . '</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            ' . $authSection . '
        </authentication>
        <content>
            ' . $functionsXml . '
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
                    'details' => []
                ];
            }

            $created = 0;
            $failed = [];
            $details = [];

            // Get all function results
            $functionResults = $xml->xpath('//function/result');

            foreach ($functionResults as $index => $result) {
                $status = (string) $result->status;

                if ($status === 'success') {
                    $created++;
                    $key = (string) ($result->key ?? '');
                    $details[] = [
                        'index' => $index,
                        'success' => true,
                        'key' => $key
                    ];
                } else {
                    $errorMessages = $result->xpath('errormessage/error');
                    $errorText = 'Unknown error';

                    if (!empty($errorMessages)) {
                        $errorDetails = [];
                        foreach ($errorMessages as $error) {
                            $description = (string) ($error->description2 ?? $error->description ?? 'Unknown error');
                            $errorDetails[] = $description;
                        }
                        $errorText = implode('; ', $errorDetails);
                    }

                    $failed[] = [
                        'index' => $index,
                        'error' => $errorText
                    ];

                    $details[] = [
                        'index' => $index,
                        'success' => false,
                        'error' => $errorText
                    ];
                }
            }

            return [
                'created' => $created,
                'failed' => $failed,
                'details' => $details
            ];

        } catch (\Exception $e) {
            Log::error('Sage Intacct batch response parsing exception', [
                'error' => $e->getMessage()
            ]);

            return [
                'created' => 0,
                'failed' => [],
                'details' => []
            ];
        }
    }

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
        return 'CAPTRACK_' . time() . '_' . uniqid();
    }

    /**
     * Get the current setting instance
     */
    public function getSetting()
    {
        return $this->setting;
    }

    /**
     * Get the current session ID
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }
}
