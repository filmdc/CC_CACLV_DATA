#!/usr/bin/env php
<?php

/**
 * Sage Intacct MCP Server
 *
 * Model Context Protocol server for querying Sage Intacct API.
 * Bootstraps Laravel to reuse existing SageIntacctApiService, models, and credentials.
 *
 * Usage in Claude Desktop config:
 * {
 *   "mcpServers": {
 *     "sage-intacct": {
 *       "command": "php",
 *       "args": ["/var/www/CC_CAP_TRACK/mcp-servers/sage-intacct-mcp.php"]
 *     }
 *   }
 * }
 */

// Prevent any output before MCP protocol starts
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/sage-intacct-mcp.log');

// Bootstrap Laravel
$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SageIntacctSetting;
use App\Models\SageIntacctSyncHistory;
use App\Models\SageIntacctSyncItem;
use App\Models\SageIntacctFieldMapping;
use App\Models\UserSageIntacctToken;
use App\Services\SageIntacctApiService;

// ============================================================================
// MCP Protocol Implementation
// ============================================================================

class SageIntacctMcpServer
{
    private ?SageIntacctApiService $apiService = null;
    private ?SageIntacctSetting $setting = null;
    private bool $authenticated = false;

    public function __construct()
    {
        $this->setting = SageIntacctSetting::getActive();
    }

    /**
     * Main event loop - reads JSON-RPC messages from stdin
     */
    public function run(): void
    {
        $buffer = '';

        while (true) {
            $line = fgets(STDIN);

            if ($line === false) {
                break; // EOF
            }

            $buffer .= $line;

            // Try to parse complete JSON-RPC messages
            // MCP uses Content-Length headers or newline-delimited JSON
            $decoded = json_decode(trim($buffer), true);
            if ($decoded !== null) {
                $buffer = '';
                $this->handleMessage($decoded);
            }
        }
    }

    /**
     * Handle a single JSON-RPC message
     */
    private function handleMessage(array $message): void
    {
        $method = $message['method'] ?? null;
        $id = $message['id'] ?? null;
        $params = $message['params'] ?? [];

        // Notifications (no id) don't need a response
        if ($id === null && $method !== null) {
            // Handle notifications like 'initialized', 'notifications/initialized'
            return;
        }

        $result = match ($method) {
            'initialize' => $this->handleInitialize($params),
            'tools/list' => $this->handleToolsList(),
            'tools/call' => $this->handleToolCall($params),
            'ping' => ['pong' => true],
            default => null,
        };

        if ($result === null && $id !== null) {
            $this->sendError($id, -32601, "Method not found: {$method}");
            return;
        }

        if ($id !== null) {
            $this->sendResponse($id, $result);
        }
    }

    /**
     * Handle initialize request
     */
    private function handleInitialize(array $params): array
    {
        return [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => [
                    'listChanged' => false,
                ],
            ],
            'serverInfo' => [
                'name' => 'sage-intacct',
                'version' => '1.0.0',
            ],
        ];
    }

    /**
     * Return available tools
     */
    private function handleToolsList(): array
    {
        return [
            'tools' => [
                [
                    'name' => 'test_connection',
                    'description' => 'Test the connection to Sage Intacct API. Returns connection status and company info.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => new \stdClass(),
                    ],
                ],
                [
                    'name' => 'get_configuration',
                    'description' => 'Get the current Sage Intacct configuration including company ID, auth method, endpoint, dimension defaults, and sync settings. Does NOT return credentials.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => new \stdClass(),
                    ],
                ],
                [
                    'name' => 'query_objects',
                    'description' => 'Query any Sage Intacct object using readByQuery. Supports standard objects (VENDOR, CUSTOMER, GLACCOUNT, APBILL, ARBILL, ARINVOICE, JOURNALENTRY, etc.) and custom objects. Use an empty query string to return all records.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'object' => [
                                'type' => 'string',
                                'description' => 'Object name to query (e.g., GLACCOUNT, VENDOR, CUSTOMER, APBILL, ARINVOICE, JOURNALENTRY, LOCATION, DEPARTMENT, PROJECT, CLASS, or custom object names)',
                            ],
                            'query' => [
                                'type' => 'string',
                                'description' => 'Query filter string using Intacct query syntax (e.g., "STATUS = \'active\'", "TOTALDUE > 1000", "VENDORID = \'V-001\'"). Leave empty to return all records.',
                            ],
                            'fields' => [
                                'type' => 'string',
                                'description' => 'Comma-separated list of fields to return (e.g., "RECORDNO,VENDORID,NAME"). Use "*" for all fields. Default: "*"',
                            ],
                            'pagesize' => [
                                'type' => 'integer',
                                'description' => 'Maximum number of records to return (1-1000). Default: 100',
                            ],
                        ],
                        'required' => ['object'],
                    ],
                ],
                [
                    'name' => 'read_by_id',
                    'description' => 'Read one or more Sage Intacct records by their RECORDNO or key values.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'object' => [
                                'type' => 'string',
                                'description' => 'Object name (e.g., GLACCOUNT, VENDOR, CUSTOMER)',
                            ],
                            'keys' => [
                                'type' => 'string',
                                'description' => 'Comma-separated record keys/IDs to read (e.g., "1,2,3" or "V-001,V-002")',
                            ],
                            'fields' => [
                                'type' => 'string',
                                'description' => 'Comma-separated list of fields to return. Use "*" for all fields. Default: "*"',
                            ],
                        ],
                        'required' => ['object', 'keys'],
                    ],
                ],
                [
                    'name' => 'inspect_object',
                    'description' => 'Inspect a Sage Intacct object to discover its fields, types, and structure. Useful for understanding available fields before querying.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'object' => [
                                'type' => 'string',
                                'description' => 'Object name to inspect (e.g., GLACCOUNT, VENDOR, CUSTOMER, APBILL)',
                            ],
                        ],
                        'required' => ['object'],
                    ],
                ],
                [
                    'name' => 'list_dimensions',
                    'description' => 'List all values for a Sage Intacct dimension (Location, Department, Project, or Class). Returns IDs, names, and status.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'dimension' => [
                                'type' => 'string',
                                'enum' => ['LOCATION', 'DEPARTMENT', 'PROJECT', 'CLASS'],
                                'description' => 'The dimension type to list',
                            ],
                        ],
                        'required' => ['dimension'],
                    ],
                ],
                [
                    'name' => 'list_gl_accounts',
                    'description' => 'List GL (General Ledger) accounts from Sage Intacct. Can filter by status or account type.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type' => 'string',
                                'enum' => ['active', 'inactive', 'all'],
                                'description' => 'Filter by account status. Default: "active"',
                            ],
                            'account_type' => [
                                'type' => 'string',
                                'description' => 'Filter by account type (e.g., "balancesheet", "incomestatement"). Leave empty for all types.',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'list_vendors',
                    'description' => 'List vendors from Sage Intacct. Can filter by status or search by name.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type' => 'string',
                                'enum' => ['active', 'inactive', 'all'],
                                'description' => 'Filter by vendor status. Default: "active"',
                            ],
                            'name_contains' => [
                                'type' => 'string',
                                'description' => 'Filter vendors whose name contains this string',
                            ],
                            'pagesize' => [
                                'type' => 'integer',
                                'description' => 'Maximum number of records. Default: 100',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'list_customers',
                    'description' => 'List customers from Sage Intacct. Can filter by status or search by name.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type' => 'string',
                                'enum' => ['active', 'inactive', 'all'],
                                'description' => 'Filter by customer status. Default: "active"',
                            ],
                            'name_contains' => [
                                'type' => 'string',
                                'description' => 'Filter customers whose name contains this string',
                            ],
                            'pagesize' => [
                                'type' => 'integer',
                                'description' => 'Maximum number of records. Default: 100',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'get_sync_history',
                    'description' => 'Get the DonorPerfect-to-Sage Intacct sync history from the local database. Shows sync jobs with status, record counts, and timing.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Number of recent sync records to return. Default: 20',
                            ],
                            'status' => [
                                'type' => 'string',
                                'enum' => ['completed', 'failed', 'in_progress', 'all'],
                                'description' => 'Filter by sync status. Default: "all"',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'get_sync_items',
                    'description' => 'Get individual gift sync item details for a specific sync history record.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'sync_history_id' => [
                                'type' => 'integer',
                                'description' => 'The sync history ID to get items for',
                            ],
                            'status' => [
                                'type' => 'string',
                                'enum' => ['success', 'failed', 'skipped', 'filtered', 'pending', 'all'],
                                'description' => 'Filter items by status. Default: "all"',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Maximum items to return. Default: 50',
                            ],
                        ],
                        'required' => ['sync_history_id'],
                    ],
                ],
                [
                    'name' => 'get_field_mappings',
                    'description' => 'Get the configured field mappings between DonorPerfect and Sage Intacct.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => new \stdClass(),
                    ],
                ],
                [
                    'name' => 'run_raw_query',
                    'description' => 'Execute a raw Sage Intacct XML API function. For advanced users who need to run specific API operations not covered by other tools. The function XML is wrapped in the authentication envelope automatically.',
                    'inputSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'function_xml' => [
                                'type' => 'string',
                                'description' => 'The XML function body to execute (everything inside <function> tags). Example: "<readByQuery><object>GLACCOUNT</object><query></query><fields>*</fields><pagesize>10</pagesize></readByQuery>"',
                            ],
                        ],
                        'required' => ['function_xml'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Handle tool execution
     */
    private function handleToolCall(array $params): array
    {
        $toolName = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        try {
            $result = match ($toolName) {
                'test_connection' => $this->toolTestConnection(),
                'get_configuration' => $this->toolGetConfiguration(),
                'query_objects' => $this->toolQueryObjects($arguments),
                'read_by_id' => $this->toolReadById($arguments),
                'inspect_object' => $this->toolInspectObject($arguments),
                'list_dimensions' => $this->toolListDimensions($arguments),
                'list_gl_accounts' => $this->toolListGlAccounts($arguments),
                'list_vendors' => $this->toolListVendors($arguments),
                'list_customers' => $this->toolListCustomers($arguments),
                'get_sync_history' => $this->toolGetSyncHistory($arguments),
                'get_sync_items' => $this->toolGetSyncItems($arguments),
                'get_field_mappings' => $this->toolGetFieldMappings(),
                'run_raw_query' => $this->toolRunRawQuery($arguments),
                default => ['error' => "Unknown tool: {$toolName}"],
            };

            $isError = isset($result['error']);

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'isError' => $isError,
            ];
        } catch (\Exception $e) {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode([
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ], JSON_PRETTY_PRINT),
                    ],
                ],
                'isError' => true,
            ];
        }
    }

    // ========================================================================
    // Tool Implementations
    // ========================================================================

    private function ensureApiService(): SageIntacctApiService
    {
        if (!$this->setting) {
            $this->setting = SageIntacctSetting::getActive();
        }

        if (!$this->setting) {
            throw new \RuntimeException('No active Sage Intacct configuration found. Configure settings in the CAP TRACK admin panel first.');
        }

        if (!$this->apiService) {
            $userToken = null;
            if ($this->setting->isRestApi()) {
                $userToken = UserSageIntacctToken::whereNotNull('access_token')->first();
                if (!$userToken) {
                    throw new \RuntimeException('No OAuth token found. A user must authorize via the CAP TRACK web interface first.');
                }
            }
            $this->apiService = new SageIntacctApiService($this->setting, $userToken);
        }

        if (!$this->authenticated) {
            $authResult = $this->apiService->authenticate();
            if (!$authResult['success']) {
                throw new \RuntimeException('Authentication failed: ' . ($authResult['message'] ?? 'Unknown error'));
            }
            $this->authenticated = true;
        }

        return $this->apiService;
    }

    private function toolTestConnection(): array
    {
        if (!$this->setting) {
            return ['error' => 'No active Sage Intacct configuration found'];
        }

        $apiService = $this->ensureApiService();
        $result = $apiService->testConnection();

        return [
            'connection_status' => $result['success'] ? 'connected' : 'failed',
            'message' => $result['message'] ?? ($result['success'] ? 'Successfully connected' : 'Connection failed'),
            'company_id' => $this->setting->company_id,
            'auth_method' => $this->setting->auth_method ?? 'xml',
            'is_sandbox' => $this->setting->isSandbox(),
            'endpoint' => $this->setting->getEndpointUrl(),
        ];
    }

    private function toolGetConfiguration(): array
    {
        if (!$this->setting) {
            return ['error' => 'No active Sage Intacct configuration found'];
        }

        return [
            'company_id' => $this->setting->company_id,
            'auth_method' => $this->setting->auth_method ?? 'xml',
            'is_sandbox' => $this->setting->isSandbox(),
            'endpoint_url' => $this->setting->getEndpointUrl(),
            'is_configured' => $this->setting->isConfigured(),
            'is_active' => $this->setting->is_active,
            'custom_object_name' => $this->setting->custom_object_name,
            'code_mapping_enabled' => $this->setting->isCodeMappingEnabled(),
            'defaults' => [
                'location_id' => $this->setting->location_id,
                'department_id' => $this->setting->department_id,
                'class_id' => $this->setting->class_id,
                'gl_account' => $this->setting->default_gl_account,
                'payment_method' => $this->setting->default_payment_method,
                'bank_account_id' => $this->setting->bank_account_id,
            ],
            'receipt_settings' => [
                'use_other_receipts' => $this->setting->use_other_receipts,
                'use_undeposited_funds' => $this->setting->use_undeposited_funds,
                'undeposited_gl_account' => $this->setting->undeposited_gl_account,
            ],
            'last_sync_at' => $this->setting->last_sync_at?->toISOString(),
        ];
    }

    private function toolQueryObjects(array $args): array
    {
        $objectName = $args['object'] ?? null;
        if (!$objectName) {
            return ['error' => 'object parameter is required'];
        }

        $query = $args['query'] ?? '';
        $fields = $args['fields'] ?? '*';
        $pagesize = min(max((int)($args['pagesize'] ?? 100), 1), 1000);

        $apiService = $this->ensureApiService();

        // Build and execute readByQuery
        $xmlRequest = $this->buildReadByQueryXml($objectName, $query, $fields, $pagesize);
        $response = $this->sendXmlRequest($apiService, $xmlRequest);

        if (!$response['success']) {
            return ['error' => $response['error'] ?? 'Query failed'];
        }

        $records = $this->parseGenericQueryResponse($response['xml'], $objectName);

        return [
            'object' => $objectName,
            'query' => $query ?: '(all records)',
            'record_count' => count($records),
            'records' => $records,
        ];
    }

    private function toolReadById(array $args): array
    {
        $objectName = $args['object'] ?? null;
        $keys = $args['keys'] ?? null;
        $fields = $args['fields'] ?? '*';

        if (!$objectName || !$keys) {
            return ['error' => 'object and keys parameters are required'];
        }

        $apiService = $this->ensureApiService();

        $xmlRequest = $this->buildReadXml($objectName, $keys, $fields);
        $response = $this->sendXmlRequest($apiService, $xmlRequest);

        if (!$response['success']) {
            return ['error' => $response['error'] ?? 'Read failed'];
        }

        $records = $this->parseGenericQueryResponse($response['xml'], $objectName);

        return [
            'object' => $objectName,
            'keys' => $keys,
            'record_count' => count($records),
            'records' => $records,
        ];
    }

    private function toolInspectObject(array $args): array
    {
        $objectName = $args['object'] ?? null;
        if (!$objectName) {
            return ['error' => 'object parameter is required'];
        }

        $apiService = $this->ensureApiService();

        // Use the existing inspect method for XML API
        if ($this->setting->isXmlApi()) {
            $result = $apiService->inspectObjectDefinition($objectName);
            if (!$result['success']) {
                return ['error' => $result['error'] ?? 'Inspection failed'];
            }
            return [
                'object' => $objectName,
                'fields' => $result['fields'] ?? [],
                'field_count' => count($result['fields'] ?? []),
            ];
        }

        return ['error' => 'Object inspection requires XML API mode'];
    }

    private function toolListDimensions(array $args): array
    {
        $dimension = strtoupper($args['dimension'] ?? '');
        if (!in_array($dimension, ['LOCATION', 'DEPARTMENT', 'PROJECT', 'CLASS'])) {
            return ['error' => 'dimension must be one of: LOCATION, DEPARTMENT, PROJECT, CLASS'];
        }

        $apiService = $this->ensureApiService();

        // Use get_list for dimension objects
        $xmlRequest = $this->buildGetListXml(strtolower($dimension));
        $response = $this->sendXmlRequest($apiService, $xmlRequest);

        if (!$response['success']) {
            return ['error' => $response['error'] ?? 'Query failed'];
        }

        $records = $this->parseListResponse($response['xml'], strtolower($dimension));

        return [
            'dimension' => $dimension,
            'record_count' => count($records),
            'records' => $records,
        ];
    }

    private function toolListGlAccounts(array $args): array
    {
        $status = $args['status'] ?? 'active';
        $accountType = $args['account_type'] ?? '';

        $query = '';
        $conditions = [];

        if ($status !== 'all') {
            $conditions[] = "STATUS = '{$status}'";
        }
        if (!empty($accountType)) {
            $conditions[] = "ACCOUNTTYPE = '{$accountType}'";
        }

        $query = implode(' AND ', $conditions);

        $apiService = $this->ensureApiService();

        $xmlRequest = $this->buildReadByQueryXml('GLACCOUNT', $query, '*', 1000);
        $response = $this->sendXmlRequest($apiService, $xmlRequest);

        if (!$response['success']) {
            return ['error' => $response['error'] ?? 'Query failed'];
        }

        $records = $this->parseGenericQueryResponse($response['xml'], 'GLACCOUNT');

        return [
            'filter' => ['status' => $status, 'account_type' => $accountType ?: 'all'],
            'record_count' => count($records),
            'accounts' => $records,
        ];
    }

    private function toolListVendors(array $args): array
    {
        $status = $args['status'] ?? 'active';
        $nameContains = $args['name_contains'] ?? '';
        $pagesize = min(max((int)($args['pagesize'] ?? 100), 1), 1000);

        $conditions = [];
        if ($status !== 'all') {
            $conditions[] = "STATUS = '{$status}'";
        }
        if (!empty($nameContains)) {
            $conditions[] = "NAME LIKE '%{$nameContains}%'";
        }

        $query = implode(' AND ', $conditions);

        $apiService = $this->ensureApiService();

        $xmlRequest = $this->buildReadByQueryXml('VENDOR', $query, '*', $pagesize);
        $response = $this->sendXmlRequest($apiService, $xmlRequest);

        if (!$response['success']) {
            return ['error' => $response['error'] ?? 'Query failed'];
        }

        $records = $this->parseGenericQueryResponse($response['xml'], 'VENDOR');

        return [
            'filter' => ['status' => $status, 'name_contains' => $nameContains ?: null],
            'record_count' => count($records),
            'vendors' => $records,
        ];
    }

    private function toolListCustomers(array $args): array
    {
        $status = $args['status'] ?? 'active';
        $nameContains = $args['name_contains'] ?? '';
        $pagesize = min(max((int)($args['pagesize'] ?? 100), 1), 1000);

        $conditions = [];
        if ($status !== 'all') {
            $conditions[] = "STATUS = '{$status}'";
        }
        if (!empty($nameContains)) {
            $conditions[] = "NAME LIKE '%{$nameContains}%'";
        }

        $query = implode(' AND ', $conditions);

        $apiService = $this->ensureApiService();

        $xmlRequest = $this->buildReadByQueryXml('CUSTOMER', $query, '*', $pagesize);
        $response = $this->sendXmlRequest($apiService, $xmlRequest);

        if (!$response['success']) {
            return ['error' => $response['error'] ?? 'Query failed'];
        }

        $records = $this->parseGenericQueryResponse($response['xml'], 'CUSTOMER');

        return [
            'filter' => ['status' => $status, 'name_contains' => $nameContains ?: null],
            'record_count' => count($records),
            'customers' => $records,
        ];
    }

    private function toolGetSyncHistory(array $args): array
    {
        $limit = min(max((int)($args['limit'] ?? 20), 1), 100);
        $status = $args['status'] ?? 'all';

        $query = SageIntacctSyncHistory::orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $records = $query->limit($limit)->get();

        return [
            'filter' => ['status' => $status, 'limit' => $limit],
            'record_count' => $records->count(),
            'sync_history' => $records->map(function ($record) {
                return [
                    'id' => $record->id,
                    'status' => $record->status,
                    'total_records' => $record->total_records,
                    'successful_records' => $record->successful_records,
                    'failed_records' => $record->failed_records,
                    'skipped_records' => $record->skipped_records,
                    'environment' => $record->environment ?? 'unknown',
                    'started_at' => $record->started_at,
                    'completed_at' => $record->completed_at,
                    'error_message' => $record->error_message,
                    'created_at' => $record->created_at?->toISOString(),
                ];
            })->toArray(),
        ];
    }

    private function toolGetSyncItems(array $args): array
    {
        $syncHistoryId = (int)($args['sync_history_id'] ?? 0);
        if (!$syncHistoryId) {
            return ['error' => 'sync_history_id is required'];
        }

        $status = $args['status'] ?? 'all';
        $limit = min(max((int)($args['limit'] ?? 50), 1), 500);

        $query = SageIntacctSyncItem::where('sync_history_id', $syncHistoryId)
            ->orderBy('id', 'asc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $items = $query->limit($limit)->get();

        return [
            'sync_history_id' => $syncHistoryId,
            'filter' => ['status' => $status, 'limit' => $limit],
            'item_count' => $items->count(),
            'items' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'gift_id' => $item->gift_id,
                    'status' => $item->status,
                    'intacct_record_id' => $item->intacct_record_id,
                    'error_message' => $item->error_message,
                    'sage_deposit_date' => $item->sage_deposit_date,
                    'sage_fund_code' => $item->sage_fund_code,
                    'sage_service_code' => $item->sage_service_code,
                    'environment' => $item->environment,
                    'synced_at' => $item->synced_at,
                ];
            })->toArray(),
        ];
    }

    private function toolGetFieldMappings(): array
    {
        if (!$this->setting) {
            return ['error' => 'No active Sage Intacct configuration found'];
        }

        $mappings = SageIntacctFieldMapping::where('setting_id', $this->setting->id)
            ->orderBy('id')
            ->get();

        return [
            'setting_id' => $this->setting->id,
            'mapping_count' => $mappings->count(),
            'mappings' => $mappings->map(function ($mapping) {
                return [
                    'id' => $mapping->id,
                    'source_field' => $mapping->source_field,
                    'target_field' => $mapping->target_field,
                    'default_value' => $mapping->default_value,
                    'is_required' => $mapping->is_required ?? false,
                    'transformation' => $mapping->transformation,
                ];
            })->toArray(),
        ];
    }

    private function toolRunRawQuery(array $args): array
    {
        $functionXml = $args['function_xml'] ?? null;
        if (!$functionXml) {
            return ['error' => 'function_xml parameter is required'];
        }

        $apiService = $this->ensureApiService();

        $xmlRequest = $this->buildRawFunctionXml($functionXml);
        $response = $this->sendXmlRequest($apiService, $xmlRequest);

        if (!$response['success']) {
            return ['error' => $response['error'] ?? 'Request failed'];
        }

        // Parse the response generically
        $xml = simplexml_load_string($response['xml']);
        if ($xml === false) {
            return ['error' => 'Failed to parse response XML'];
        }

        // Check for errors
        $errors = $xml->xpath('//errormessage/error');
        if (!empty($errors)) {
            $errorTexts = [];
            foreach ($errors as $error) {
                $errorTexts[] = (string)($error->description2 ?? $error->description ?? 'Unknown error');
            }
            return ['error' => implode('; ', $errorTexts)];
        }

        // Convert full response to array
        $resultArray = $this->xmlToArray($xml);

        return [
            'success' => true,
            'response' => $resultArray,
        ];
    }

    // ========================================================================
    // XML Building Helpers
    // ========================================================================

    private function buildReadByQueryXml(string $object, string $query, string $fields = '*', int $pagesize = 100): string
    {
        $controlId = 'MCP_' . time() . '_' . uniqid();
        $functionId = 'func_' . time() . '_' . uniqid();
        $sessionId = $this->apiService->getSessionId();

        $authSection = $sessionId
            ? '<sessionid>' . htmlspecialchars($sessionId) . '</sessionid>'
            : '<login>
                <userid>' . htmlspecialchars($this->setting->web_services_user_id) . '</userid>
                <companyid>' . htmlspecialchars($this->setting->company_id) . '</companyid>
                <password>' . htmlspecialchars($this->setting->web_services_password) . '</password>
            </login>';

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
                    <object>{$object}</object>
                    <query>{$query}</query>
                    <fields>{$fields}</fields>
                    <pagesize>{$pagesize}</pagesize>
                </readByQuery>
            </function>
        </content>
    </operation>
</request>
XML;
    }

    private function buildReadXml(string $object, string $keys, string $fields = '*'): string
    {
        $controlId = 'MCP_' . time() . '_' . uniqid();
        $functionId = 'func_' . time() . '_' . uniqid();
        $sessionId = $this->apiService->getSessionId();

        $authSection = $sessionId
            ? '<sessionid>' . htmlspecialchars($sessionId) . '</sessionid>'
            : '<login>
                <userid>' . htmlspecialchars($this->setting->web_services_user_id) . '</userid>
                <companyid>' . htmlspecialchars($this->setting->company_id) . '</companyid>
                <password>' . htmlspecialchars($this->setting->web_services_password) . '</password>
            </login>';

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
                <read>
                    <object>{$object}</object>
                    <keys>{$keys}</keys>
                    <fields>{$fields}</fields>
                </read>
            </function>
        </content>
    </operation>
</request>
XML;
    }

    private function buildGetListXml(string $objectLower): string
    {
        $controlId = 'MCP_' . time() . '_' . uniqid();
        $functionId = 'func_' . time() . '_' . uniqid();
        $sessionId = $this->apiService->getSessionId();

        $authSection = $sessionId
            ? '<sessionid>' . htmlspecialchars($sessionId) . '</sessionid>'
            : '<login>
                <userid>' . htmlspecialchars($this->setting->web_services_user_id) . '</userid>
                <companyid>' . htmlspecialchars($this->setting->company_id) . '</companyid>
                <password>' . htmlspecialchars($this->setting->web_services_password) . '</password>
            </login>';

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
                <get_list object="{$objectLower}" maxitems="1000" />
            </function>
        </content>
    </operation>
</request>
XML;
    }

    private function buildRawFunctionXml(string $functionBody): string
    {
        $controlId = 'MCP_' . time() . '_' . uniqid();
        $functionId = 'func_' . time() . '_' . uniqid();
        $sessionId = $this->apiService->getSessionId();

        $authSection = $sessionId
            ? '<sessionid>' . htmlspecialchars($sessionId) . '</sessionid>'
            : '<login>
                <userid>' . htmlspecialchars($this->setting->web_services_user_id) . '</userid>
                <companyid>' . htmlspecialchars($this->setting->company_id) . '</companyid>
                <password>' . htmlspecialchars($this->setting->web_services_password) . '</password>
            </login>';

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
                {$functionBody}
            </function>
        </content>
    </operation>
</request>
XML;
    }

    // ========================================================================
    // XML Sending & Parsing
    // ========================================================================

    private function sendXmlRequest(SageIntacctApiService $apiService, string $xmlPayload): array
    {
        // Use reflection to access the private sendXmlRequest method
        $reflection = new \ReflectionClass($apiService);
        $method = $reflection->getMethod('sendXmlRequest');
        $method->setAccessible(true);

        return $method->invoke($apiService, $xmlPayload);
    }

    private function parseGenericQueryResponse(string $xmlResponse, string $objectName): array
    {
        $xml = simplexml_load_string($xmlResponse);
        if ($xml === false) {
            return [];
        }

        // Check for errors first
        $errors = $xml->xpath('//errormessage/error');
        if (!empty($errors)) {
            return [];
        }

        // Try to find records in the data section
        // Intacct uses the object name (uppercase or lowercase) as the element name
        $recordNodes = $xml->xpath("//data/{$objectName}");
        if (empty($recordNodes)) {
            $recordNodes = $xml->xpath("//data/" . strtolower($objectName));
        }
        if (empty($recordNodes)) {
            $recordNodes = $xml->xpath("//data/" . strtoupper($objectName));
        }

        // Fallback: try to get any child elements of data
        if (empty($recordNodes)) {
            $dataNodes = $xml->xpath('//data');
            if (!empty($dataNodes)) {
                $recordNodes = $dataNodes[0]->children();
            }
        }

        $records = [];
        foreach ($recordNodes as $record) {
            $recordData = $this->xmlElementToArray($record);
            if (!empty($recordData)) {
                $records[] = $recordData;
            }
        }

        return $records;
    }

    private function parseListResponse(string $xmlResponse, string $objectLower): array
    {
        $xml = simplexml_load_string($xmlResponse);
        if ($xml === false) {
            return [];
        }

        $recordNodes = $xml->xpath("//data/{$objectLower}");
        if (empty($recordNodes)) {
            $recordNodes = $xml->xpath("//{$objectLower}");
        }

        $records = [];
        foreach ($recordNodes as $record) {
            $recordData = [];
            foreach ($record->children() as $field) {
                $recordData[$field->getName()] = (string)$field;
            }
            if (!empty($recordData)) {
                $records[] = $recordData;
            }
        }

        return $records;
    }

    private function xmlElementToArray(\SimpleXMLElement $element): array
    {
        $result = [];
        foreach ($element->children() as $child) {
            $name = $child->getName();
            if ($child->count() > 0) {
                // Nested element - recurse
                $result[$name] = $this->xmlElementToArray($child);
            } else {
                $result[$name] = (string)$child;
            }
        }
        return $result;
    }

    private function xmlToArray(\SimpleXMLElement $xml): array
    {
        $json = json_encode($xml);
        return json_decode($json, true);
    }

    // ========================================================================
    // JSON-RPC Response Helpers
    // ========================================================================

    private function sendResponse(int|string $id, array $result): void
    {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];

        $json = json_encode($response, JSON_UNESCAPED_SLASHES);
        fwrite(STDOUT, $json . "\n");
        fflush(STDOUT);
    }

    private function sendError(int|string $id, int $code, string $message): void
    {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        $json = json_encode($response, JSON_UNESCAPED_SLASHES);
        fwrite(STDOUT, $json . "\n");
        fflush(STDOUT);
    }
}

// ============================================================================
// Run the server
// ============================================================================

$server = new SageIntacctMcpServer();
$server->run();
