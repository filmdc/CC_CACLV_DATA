<?php

namespace App\Console\Commands;

use App\Models\SageIntacctSetting;
use App\Services\SageIntacctApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class QueryIntacctDimensions extends Command
{
    protected $signature = 'intacct:query-dimensions {--output=intacct-dimensions.md} {--debug}';

    protected $description = 'Query Sage Intacct for all dimension values and create markdown documentation';

    public function handle()
    {
        $this->info('Querying Sage Intacct dimensions...');

        // Get active setting
        $setting = SageIntacctSetting::where('is_active', true)->first();

        if (!$setting) {
            $this->error('No active Sage Intacct settings found');
            return 1;
        }

        if (!$setting->isXmlApi()) {
            $this->error('This command only works with XML API settings');
            return 1;
        }

        $apiService = new SageIntacctApiService($setting);

        // Authenticate
        $this->info('Authenticating with Sage Intacct...');
        $authResult = $apiService->authenticate();

        if (!$authResult['success']) {
            $this->error('Authentication failed: ' . ($authResult['message'] ?? 'Unknown error'));
            return 1;
        }

        $this->info('✓ Authenticated successfully');

        // Query each dimension type
        $dimensions = [
            'LOCATION' => 'Locations',
            'DEPARTMENT' => 'Departments',
            'PROJECT' => 'Projects',
            'CLASS' => 'Classes (Service)',
        ];

        $results = [];

        foreach ($dimensions as $objectType => $displayName) {
            $this->info("Querying {$displayName}...");
            $result = $this->queryDimension($apiService, $objectType);

            if ($result['success']) {
                $results[$objectType] = $result['records'];
                $this->info("  ✓ Found " . count($result['records']) . " records");
            } else {
                $this->warn("  ✗ Failed: " . ($result['error'] ?? 'Unknown error'));
                $results[$objectType] = [];
            }
        }

        // Generate markdown
        $outputFile = $this->option('output');
        $markdown = $this->generateMarkdown($results);

        File::put($outputFile, $markdown);

        $this->info('');
        $this->info("✓ Documentation saved to: {$outputFile}");
        $this->info('');

        // Display summary
        $this->table(
            ['Dimension', 'Count'],
            collect($results)->map(fn($records, $type) => [
                $dimensions[$type] ?? $type,
                count($records)
            ])->values()
        );

        return 0;
    }

    private function queryDimension(SageIntacctApiService $apiService, string $objectType): array
    {
        try {
            $xmlRequest = $this->buildReadAllXml($apiService, $objectType);
            $response = $this->sendRequest($apiService, $xmlRequest);

            if (!$response['success']) {
                return ['success' => false, 'error' => $response['error'] ?? 'Query failed'];
            }

            // Debug output
            if ($this->option('debug')) {
                $debugFile = storage_path("logs/intacct-{$objectType}-response.xml");
                file_put_contents($debugFile, $response['xml']);
                $this->line("  Debug: Response saved to {$debugFile}");
                $this->line("  Response preview: " . substr($response['xml'], 0, 500));
            }

            $records = $this->parseRecords($response['xml'], $objectType);

            return ['success' => true, 'records' => $records];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function buildReadAllXml(SageIntacctApiService $apiService, string $objectType): string
    {
        $sessionId = $apiService->getSessionId();
        $setting = $apiService->getSetting();
        $controlId = 'query-' . strtolower($objectType) . '-' . time();
        $functionId = 'func-' . time() . '-' . uniqid();

        // Use get_list for standard dimension objects
        $listObject = strtolower($objectType);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$setting->sender_id}</senderid>
        <password>{$setting->sender_password}</password>
        <controlid>{$controlId}</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <sessionid>{$sessionId}</sessionid>
        </authentication>
        <content>
            <function controlid="{$functionId}">
                <get_list object="{$listObject}" maxitems="1000" />
            </function>
        </content>
    </operation>
</request>
XML;
    }

    private function sendRequest(SageIntacctApiService $apiService, string $xmlRequest): array
    {
        // Use reflection to access private sendXmlRequest method
        $reflection = new \ReflectionClass($apiService);
        $method = $reflection->getMethod('sendXmlRequest');
        $method->setAccessible(true);

        return $method->invoke($apiService, $xmlRequest);
    }

    private function parseRecords(string $xmlResponse, string $objectType): array
    {
        $xml = simplexml_load_string($xmlResponse);
        if ($xml === false) {
            $this->warn("Failed to parse XML for {$objectType}");
            return [];
        }

        // Check for errors
        $errormessage = $xml->xpath('//errormessage');
        if (!empty($errormessage)) {
            $errorText = (string)($errormessage[0]->error->description2 ?? $errormessage[0]->error->description ?? 'Unknown error');
            $this->warn("API Error for {$objectType}: {$errorText}");
            return [];
        }

        // Extract records - object names are lowercase in XML
        $objectLower = strtolower($objectType);
        $recordNodes = $xml->xpath("//data/{$objectLower}");
        if (empty($recordNodes)) {
            $recordNodes = $xml->xpath("//{$objectLower}");
        }

        // Debug: Show what we found
        if ($this->option('verbose')) {
            $this->line("  Found " . count($recordNodes) . " XML nodes for {$objectType}");
            if (count($recordNodes) > 0) {
                $this->line("  First record preview: " . substr(json_encode($recordNodes[0]), 0, 200));
            }
        }

        $records = [];
        foreach ($recordNodes as $record) {
            $recordData = [];
            foreach ($record->children() as $field) {
                $fieldName = $field->getName();
                $fieldValue = (string) $field;
                $recordData[$fieldName] = $fieldValue;
            }

            if (!empty($recordData)) {
                $records[] = $recordData;
            }
        }

        return $records;
    }

    private function generateMarkdown(array $results): string
    {
        $markdown = "# Sage Intacct Dimensions Reference\n\n";
        $markdown .= "_Generated on: " . now()->format('Y-m-d H:i:s') . "_\n\n";
        $markdown .= "This document lists all active dimension values in your Sage Intacct instance.\n\n";
        $markdown .= "---\n\n";

        // Locations
        if (!empty($results['LOCATION'])) {
            $markdown .= "## Locations\n\n";
            $markdown .= "**API Field:** `locationid`\n\n";
            $markdown .= "| ID | Name | Status | Parent |\n";
            $markdown .= "|---|---|---|---|\n";

            foreach ($results['LOCATION'] as $record) {
                $id = $record['LOCATIONID'] ?? $record['locationid'] ?? 'N/A';
                $name = $record['NAME'] ?? $record['name'] ?? '';
                $status = $record['STATUS'] ?? $record['status'] ?? '';
                $parent = $record['PARENTID'] ?? $record['parentid'] ?? '';

                $markdown .= "| `{$id}` | {$name} | {$status} | {$parent} |\n";
            }
            $markdown .= "\n";
        }

        // Departments
        if (!empty($results['DEPARTMENT'])) {
            $markdown .= "## Departments\n\n";
            $markdown .= "**API Field:** `departmentid`\n\n";
            $markdown .= "⚠️ **Note:** Departments are NOT supported in Other Receipt line items.\n\n";
            $markdown .= "| ID | Name | Status | Parent |\n";
            $markdown .= "|---|---|---|---|\n";

            foreach ($results['DEPARTMENT'] as $record) {
                $id = $record['DEPARTMENTID'] ?? $record['departmentid'] ?? 'N/A';
                $name = $record['TITLE'] ?? $record['title'] ?? $record['NAME'] ?? $record['name'] ?? '';
                $status = $record['STATUS'] ?? $record['status'] ?? '';
                $parent = $record['PARENTID'] ?? $record['parentid'] ?? '';

                $markdown .= "| `{$id}` | {$name} | {$status} | {$parent} |\n";
            }
            $markdown .= "\n";
        }

        // Projects
        if (!empty($results['PROJECT'])) {
            $markdown .= "## Projects\n\n";
            $markdown .= "**API Field:** `projectid`\n\n";
            $markdown .= "_May appear as 'Program' in your UI_\n\n";
            $markdown .= "| ID | Name | Status | Category |\n";
            $markdown .= "|---|---|---|---|\n";

            foreach ($results['PROJECT'] as $record) {
                $id = $record['PROJECTID'] ?? $record['projectid'] ?? 'N/A';
                $name = $record['NAME'] ?? $record['name'] ?? '';
                $status = $record['STATUS'] ?? $record['status'] ?? '';
                $category = $record['PROJECTCATEGORY'] ?? $record['projectcategory'] ?? '';

                $markdown .= "| `{$id}` | {$name} | {$status} | {$category} |\n";
            }
            $markdown .= "\n";
        }

        // Classes (Service)
        if (!empty($results['CLASS'])) {
            $markdown .= "## Classes (Service Dimension)\n\n";
            $markdown .= "**API Field:** `classid`\n\n";
            $markdown .= "✅ **Your UI Label:** 'Service'\n\n";
            $markdown .= "⚠️ **Critical:** Even though you renamed this to 'Service' in the UI, the API field is still `classid`, NOT `departmentid`.\n\n";
            $markdown .= "| ID | Name | Status | Parent |\n";
            $markdown .= "|---|---|---|---|\n";

            foreach ($results['CLASS'] as $record) {
                $id = $record['CLASSID'] ?? $record['classid'] ?? 'N/A';
                $name = $record['NAME'] ?? $record['name'] ?? '';
                $status = $record['STATUS'] ?? $record['status'] ?? '';
                $parent = $record['PARENTID'] ?? $record['parentid'] ?? '';

                $markdown .= "| `{$id}` | {$name} | {$status} | {$parent} |\n";
            }
            $markdown .= "\n";
        }

        // Summary section
        $markdown .= "---\n\n";
        $markdown .= "## Dimension Field Mapping for Other Receipts\n\n";
        $markdown .= "| DonorPerfect Field | Intacct Dimension | API Field | Supported in Other Receipts |\n";
        $markdown .= "|---|---|---|---|\n";
        $markdown .= "| `campaign` | Location | `locationid` | ✅ Yes |\n";
        $markdown .= "| `solicit_code` | Project (Program) | `projectid` | ✅ Yes |\n";
        $markdown .= "| `sub_solicit_code` | Class (Service) | `classid` | ✅ Yes |\n";
        $markdown .= "| N/A | Department | `departmentid` | ❌ No |\n";
        $markdown .= "| N/A | Item | `itemid` | ✅ Yes (optional) |\n";
        $markdown .= "\n";

        // Code mapping counts
        $markdown .= "## Current Code Mappings in Database\n\n";

        $mappingCounts = \DB::table('donor_perfect_code_mappings')
            ->selectRaw('field_type, COUNT(*) as count')
            ->groupBy('field_type')
            ->pluck('count', 'field_type');

        if ($mappingCounts->isNotEmpty()) {
            $markdown .= "| Field Type | Mapped Count |\n";
            $markdown .= "|---|---|\n";
            foreach ($mappingCounts as $fieldType => $count) {
                $markdown .= "| `{$fieldType}` | {$count} |\n";
            }
        } else {
            $markdown .= "_No code mappings found in database._\n";
        }

        return $markdown;
    }
}
