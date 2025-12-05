<?php

namespace App\Services;

use App\Models\DonorPerfectSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DonorPerfectApiService
{
    private DonorPerfectSetting $setting;

    /**
     * Constructor - accepts DonorPerfectSetting instance
     */
    public function __construct(DonorPerfectSetting $setting)
    {
        $this->setting = $setting;
    }

    /**
     * Test API connectivity
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

            // Test with a simple SELECT query
            $testQuery = 'SELECT TOP 1 donor_id FROM dp';

            // Build URL manually to avoid double-encoding the API key
            $url = $this->setting->api_url . '?apikey=' . $this->setting->api_key . '&action=' . urlencode($testQuery);

            Log::info('DonorPerfect API: Testing connection', [
                'url_preview' => substr($url, 0, 100) . '...'
            ]);

            // Make API call
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                Log::error('DonorPerfect API connection test failed', [
                    'status' => $response->status(),
                    'response' => substr($response->body(), 0, 500)
                ]);

                return [
                    'success' => false,
                    'message' => 'API request failed: ' . $response->status()
                ];
            }

            // Parse XML response
            $result = $this->parseXmlResponse($response->body());

            if (isset($result['error']) || isset($result['auth_error'])) {
                return [
                    'success' => false,
                    'message' => $result['error'] ?? 'Unknown error'
                ];
            }

            return [
                'success' => true,
                'message' => 'Connection successful'
            ];

        } catch (\Exception $e) {
            Log::error('DonorPerfect API connection test exception', [
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
     * Get gifts from DonorPerfect API within date range
     *
     * @param string $startDate Date in Y-m-d format
     * @param string $endDate Date in Y-m-d format
     * @param array $fields Optional array of field names to retrieve
     * @return array Array of gift records or error information
     */
    public function getGifts(string $startDate, string $endDate, array $fields = []): array
    {
        try {
            if (!$this->setting->isConfigured()) {
                throw new \Exception('API credentials are not fully configured');
            }

            // Use provided fields or fall back to configured/default fields
            $fieldsToRetrieve = !empty($fields) ? $fields : $this->setting->getSelectedFields();

            // Map fields to their table sources and aliases
            $giftFields = [];
            $donorFields = [];
            $udfFields = [];
            $needsUdfJoin = false;
            $needsDonorJoin = false;

            // Known gift table fields (from dpgift) - normalized to lowercase
            $knownGiftFields = array_map('strtolower', [
                'gift_id', 'donor_id', 'record_type', 'gift_date', 'amount', 'gl_code',
                'solicit_code', 'sub_solicit_code', 'campaign', 'gift_type', 'split_gift',
                'pledge_payment', 'first_gift', 'reference', 'memory_honor', 'gfname', 'glname',
                'fmv', 'batch_no', 'gift_narrative', 'ty_letter_no', 'glink', 'plink', 'nocalc',
                'receipt', 'ty_date', 'start_date', 'total', 'bill', 'balance', 'delinquent',
                'initial_payment', 'frequency', 'reminder', 'last_bill_date', 'last_paid_date',
                'writeoff_date', 'writeoff_amount', 'created_by', 'created_date', 'modified_by',
                'modified_date', 'membership_type', 'membership_level', 'membership_enr_date',
                'membership_exp_date', 'address_id', 'alink', 'batch_gift_id', 'tlink',
                'RCPT_TYPE', 'RCPT_STATUS', 'RCPT_DATE', 'RCPT_NUM', 'TRIBUTE_START_DATE',
                'TRIBUTE_end_DATE', 'TRIBUTE_MAIN_CONTACT', 'tribute_description', 'line_id',
                'gift_aid_date', 'batch_id_temp', 'gift_aid_amt', 'gift_aid_eligible_g',
                'currency', 'vault_id', 'transaction_id', 'PAYMENT_TYPE', 'PROCESSING_METHOD',
                'ACKNOWLEDGEPREF', 'OFFSET_FEE_AMOUNT', 'SETTLEMENT_ID', 'SETTLEMENT_DATE'
            ]);

            // Known donor table fields (from dp) - normalized to lowercase
            $knownDonorFields = array_map('strtolower', [
                'first_name', 'last_name', 'middle_name', 'suffix', 'title', 'salutation',
                'prof_title', 'opt_line', 'address', 'address2', 'address3', 'address4',
                'city', 'state', 'zip', 'country', 'address_type', 'home_phone', 'business_phone',
                'fax_phone', 'mobile_phone', 'email', 'org_rec', 'donor_type', 'nomail',
                'nomail_reason', 'narrative', 'tag_date', 'initial_gift_date', 'last_contrib_date',
                'last_contrib_amt', 'ytd', 'gift_total', 'gifts', 'max_date', 'max_amt', 'avg_amt',
                'yrs_donated', 'donor_rcpt_type', 'receipt_delivery', 'no_email', 'no_email_reason'
            ]);

            foreach ($fieldsToRetrieve as $field) {
                $fieldLower = strtolower($field);

                // Fields that exist in dpgift table
                if (in_array($fieldLower, $knownGiftFields)) {
                    $giftFields[] = "g.$field";
                }
                // Fields that exist in dp (donor) table
                elseif (in_array($fieldLower, $knownDonorFields)) {
                    $donorFields[] = "d.$field";
                    $needsDonorJoin = true;
                }
                // Special case: phone field maps to home_phone
                elseif ($fieldLower === 'phone' || $fieldLower === 'home_phone') {
                    $donorFields[] = "d.home_phone as phone";
                    $needsDonorJoin = true;
                }
                // Any other field - assume it's a UDF field
                else {
                    $udfFields[] = "u.$field";
                    $needsUdfJoin = true;
                }
            }

            // Combine all fields
            $allFields = array_merge($giftFields, $donorFields, $udfFields);
            $fieldsList = implode(', ', $allFields);

            // Convert date format from Y-m-d to M/D/YYYY for DonorPerfect
            $dpStartDate = date('n/j/Y', strtotime($startDate));
            $dpEndDate = date('n/j/Y', strtotime($endDate));

            // Build JOIN query with necessary tables
            $fromClause = "FROM dpgift g";
            if ($needsDonorJoin) {
                $fromClause .= " INNER JOIN dp d ON g.donor_id = d.donor_id";
            }
            if ($needsUdfJoin) {
                $fromClause .= " LEFT JOIN dpgiftudf u ON g.gift_id = u.gift_id";
            }

            $selectQuery = "SELECT {$fieldsList} {$fromClause} WHERE g.gift_date BETWEEN '{$dpStartDate}' AND '{$dpEndDate}' ORDER BY g.gift_date DESC";

            // Build URL manually to avoid double-encoding the API key (which may already be URL-encoded)
            $url = $this->setting->api_url . '?apikey=' . $this->setting->api_key . '&action=' . urlencode($selectQuery);

            Log::info('DonorPerfect API: Fetching gifts', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'dp_start_date' => $dpStartDate,
                'dp_end_date' => $dpEndDate,
                'fields_count' => count($fieldsToRetrieve),
                'needs_donor_join' => $needsDonorJoin,
                'needs_udf_join' => $needsUdfJoin,
                'full_query' => $selectQuery
            ]);

            // Make API call
            $response = Http::timeout(60)->get($url);

            if (!$response->successful()) {
                $statusCode = $response->status();
                $responseBody = $response->body();

                Log::error('DonorPerfect API gifts request failed', [
                    'status' => $statusCode,
                    'response' => substr($responseBody, 0, 500)
                ]);

                // Check if it's an authentication error
                if (in_array($statusCode, [401, 403])) {
                    throw new \Exception('Authentication failed: Invalid API credentials. Please check your API Key in the configuration.', 401);
                }

                throw new \Exception('API request failed with status: ' . $statusCode);
            }

            // Log the raw response for debugging
            $responseBody = $response->body();
            Log::info('DonorPerfect API: Raw response preview', [
                'status' => $response->status(),
                'preview' => substr($responseBody, 0, 500),
                'length' => strlen($responseBody)
            ]);

            // Parse XML response to array
            $result = $this->parseXmlResponse($responseBody);

            if (isset($result['error'])) {
                throw new \Exception($result['error']);
            }

            if (isset($result['auth_error']) && $result['auth_error']) {
                throw new \Exception($result['error'] ?? 'Authentication failed', 401);
            }

            Log::info('DonorPerfect API: Gifts retrieved successfully', [
                'count' => count($result['gifts'] ?? []),
                'result_keys' => array_keys($result)
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('DonorPerfect API getGifts exception', [
                'error' => $e->getMessage(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'error' => $e->getMessage(),
                'auth_error' => $e->getCode() === 401,
                'gifts' => []
            ];
        }
    }

    /**
     * Build XML for authentication test
     */
    private function buildAuthTestXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
               xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <soap:Body>
        <dpLogin>
            <user>' . htmlspecialchars($this->setting->api_login) . '</user>
            <pass>' . htmlspecialchars($this->setting->api_password) . '</pass>
            <apikey>' . htmlspecialchars($this->setting->api_key) . '</apikey>
        </dpLogin>
    </soap:Body>
</soap:Envelope>';
    }

    /**
     * Build XML for gifts request
     */
    private function buildGiftsRequestXml(string $startDate, string $endDate, array $fields): string
    {
        $fieldsXml = implode(',', array_map('htmlspecialchars', $fields));

        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
               xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xmlns:xsd="http://www.w3.org/2001/XMLSchema">
    <soap:Body>
        <dpGetGiftList>
            <user>' . htmlspecialchars($this->setting->api_login) . '</user>
            <pass>' . htmlspecialchars($this->setting->api_password) . '</pass>
            <apikey>' . htmlspecialchars($this->setting->api_key) . '</apikey>
            <startDate>' . htmlspecialchars($startDate) . '</startDate>
            <endDate>' . htmlspecialchars($endDate) . '</endDate>
            <fields>' . $fieldsXml . '</fields>
        </dpGetGiftList>
    </soap:Body>
</soap:Envelope>';
    }

    /**
     * Parse XML response to array
     * Handles DonorPerfect XML format and converts to PHP array
     */
    private function parseXmlResponse(string $xmlString): array
    {
        try {
            // Suppress XML errors and use internal error handling
            $previousErrorSetting = libxml_use_internal_errors(true);

            $xml = simplexml_load_string($xmlString);

            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                libxml_use_internal_errors($previousErrorSetting);

                $errorMessage = 'XML parsing failed';
                if (!empty($errors)) {
                    $errorMessage .= ': ' . $errors[0]->message;
                }

                return ['error' => $errorMessage];
            }

            libxml_use_internal_errors($previousErrorSetting);

            // Register SOAP namespace
            $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');

            // Check for DonorPerfect field-based error response format
            // Format: <result><field name='success' reason='login failed' id='success' value='false'/></result>
            $resultFields = $xml->xpath('//result/field[@name="success"][@value="false"]');
            if (!empty($resultFields)) {
                $reason = (string) $resultFields[0]['reason'] ?? 'Unknown error';

                Log::warning('DonorPerfect API returned failure', [
                    'reason' => $reason,
                    'field_attributes' => (array) $resultFields[0]->attributes()
                ]);

                // Check if SQL statement not allowed error
                $sqlErrors = $xml->xpath('//error');
                $sqlError = !empty($sqlErrors) ? (string) $sqlErrors[0] : '';

                if (str_contains($sqlError, 'SQL statement not allowed') || str_contains($reason, 'not authorized')) {
                    return [
                        'error' => 'Query not authorized: Some fields or table combinations are not accessible with your API key. ' . ucfirst($reason),
                        'auth_error' => true,
                        'sql_error' => $sqlError
                    ];
                }

                // This is always an authentication error when success=false
                return [
                    'error' => 'Authentication failed: ' . ucfirst($reason) . '. Please verify your API Key in the configuration.',
                    'auth_error' => true
                ];
            }

            // Check for SOAP fault
            $faults = $xml->xpath('//soap:Fault');
            if (!empty($faults)) {
                $faultString = (string) $faults[0]->faultstring ?? 'Unknown SOAP fault';

                // Check if it's an authentication error
                if ($this->isAuthenticationError($faultString)) {
                    return [
                        'error' => 'Authentication failed: Invalid API credentials. Please check your API Key, Login, and Password in the configuration.',
                        'auth_error' => true
                    ];
                }

                return ['error' => $faultString];
            }

            // Check for DonorPerfect error response
            $dpErrors = $xml->xpath('//error');
            if (!empty($dpErrors)) {
                $errorMessage = (string) $dpErrors[0];

                // Check if it's an authentication error
                if ($this->isAuthenticationError($errorMessage)) {
                    return [
                        'error' => 'Authentication failed: Invalid API credentials. Please check your API Key, Login, and Password in the configuration.',
                        'auth_error' => true
                    ];
                }

                return ['error' => $errorMessage];
            }

            // Extract gift records
            // DonorPerfect format: <field name='gift_id' id='gift_id' value='195382'/>
            $gifts = [];
            $records = $xml->xpath('//record');

            // UDF fields that need case normalization (API returns uppercase, code expects mixed case)
            $udfFieldMap = [
                'DP_CHECKSCAN_BATCH' => 'DP_CheckScan_Batch',
                'IMAGE_CHECK' => 'IMAGE_CHECK', // Keep as-is
            ];

            foreach ($records as $record) {
                $gift = [];
                foreach ($record->children() as $fieldElement) {
                    // Get attributes from the <field> element
                    $attributes = $fieldElement->attributes();
                    $fieldName = (string) ($attributes['name'] ?? '');
                    $fieldValue = (string) ($attributes['value'] ?? '');

                    if (!empty($fieldName)) {
                        // Normalize UDF field names to expected case
                        $normalizedName = $udfFieldMap[$fieldName] ?? $fieldName;
                        $gift[$normalizedName] = $fieldValue;
                    }
                }
                $gifts[] = $gift;
            }

            Log::info('DonorPerfect API: Parsed gift records', [
                'total_records' => count($gifts),
                'first_gift_keys' => !empty($gifts) ? array_keys($gifts[0]) : [],
                'first_gift_sample' => !empty($gifts) ? array_slice($gifts[0], 0, 5) : []
            ]);

            return [
                'success' => true,
                'gifts' => $gifts,
                'count' => count($gifts)
            ];

        } catch (\Exception $e) {
            Log::error('XML parsing exception', [
                'error' => $e->getMessage(),
                'xml_preview' => substr($xmlString, 0, 500)
            ]);

            return [
                'error' => 'XML parsing exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if an error message indicates an authentication problem
     */
    private function isAuthenticationError(string $errorMessage): bool
    {
        $authKeywords = [
            'authentication',
            'auth',
            'credentials',
            'login',
            'password',
            'unauthorized',
            'api key',
            'apikey',
            'invalid user',
            'access denied',
            'permission denied',
            'not authorized',
        ];

        $lowerError = strtolower($errorMessage);

        foreach ($authKeywords as $keyword) {
            if (str_contains($lowerError, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available fields from DonorPerfect tables
     * Returns array of field names organized by table
     */
    public function getAvailableFields(): array
    {
        try {
            $tables = ['dpgift', 'dp', 'dpgiftudf'];
            $allFields = [];

            foreach ($tables as $tableName) {
                // Query to get one record with all fields
                $query = "SELECT TOP 1 * FROM {$tableName}";
                $url = $this->setting->api_url . '?apikey=' . $this->setting->api_key . '&action=' . urlencode($query);

                $response = Http::timeout(15)->get($url);

                if (!$response->successful()) {
                    Log::warning("Could not fetch fields for table: {$tableName}");
                    continue;
                }

                $xmlBody = $response->body();
                $xml = simplexml_load_string($xmlBody);
                if ($xml === false) {
                    continue;
                }

                // Check for API error
                $errorFields = $xml->xpath('//field[@name="success"][@value="false"]');
                if (!empty($errorFields)) {
                    Log::warning("Access denied for table: {$tableName}");
                    continue;
                }

                $record = $xml->xpath('//record');
                if (empty($record)) {
                    continue;
                }

                $fields = [];
                foreach ($record[0]->children() as $fieldElement) {
                    $fieldName = (string)($fieldElement->attributes()['name'] ?? '');
                    if (!empty($fieldName)) {
                        $fields[] = $fieldName;
                    }
                }

                $allFields[$tableName] = $fields;
            }

            return [
                'success' => true,
                'tables' => $allFields
            ];

        } catch (\Exception $e) {
            Log::error('Error getting available fields', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tables' => []
            ];
        }
    }

    /**
     * Get the current setting instance
     */
    public function getSetting(): DonorPerfectSetting
    {
        return $this->setting;
    }
}
