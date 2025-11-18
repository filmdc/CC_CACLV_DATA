<?php

namespace App\Console\Commands;

use App\Models\SageIntacctSetting;
use App\Services\SageIntacctApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class QueryDimensionRelationships extends Command
{
    protected $signature = 'intacct:query-relationships {--output=intacct-dimension-relationships.md}';

    protected $description = 'Query Sage Intacct dimensional relationships and restrictions';

    public function handle()
    {
        $this->info('Querying Sage Intacct dimension relationships...');

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
        $this->info('Authenticating...');
        $authResult = $apiService->authenticate();

        if (!$authResult['success']) {
            $this->error('Authentication failed: ' . ($authResult['message'] ?? 'Unknown'));
            return 1;
        }

        $this->info('✓ Authenticated');

        // Query dimension restrictions
        $restrictions = $this->queryDimensionRestrictions($apiService);

        // Generate markdown
        $markdown = $this->generateMarkdown($restrictions);
        $outputFile = $this->option('output');
        File::put($outputFile, $markdown);

        $this->info("✓ Documentation saved to: {$outputFile}");

        return 0;
    }

    private function queryDimensionRestrictions(SageIntacctApiService $apiService): array
    {
        $sessionId = $apiService->getSessionId();
        $setting = $apiService->getSetting();

        $restrictions = [];

        // Query DIMENSIONRESTRICTION object
        $this->info('Querying dimension restrictions...');

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$setting->sender_id}</senderid>
        <password>{$setting->sender_password}</password>
        <controlid>query-restrictions</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <sessionid>{$sessionId}</sessionid>
        </authentication>
        <content>
            <function controlid="func-restrictions">
                <get_list object="dimensionrestriction" maxitems="5000" />
            </function>
        </content>
    </operation>
</request>
XML;

        $reflection = new \ReflectionClass($apiService);
        $method = $reflection->getMethod('sendXmlRequest');
        $method->setAccessible(true);

        $response = $method->invoke($apiService, $xml);

        if (!$response['success']) {
            $this->warn('Failed to query dimension restrictions');
            return [];
        }

        $xmlObj = simplexml_load_string($response['xml']);

        // Check for errors
        $errors = $xmlObj->xpath('//errormessage');
        if (!empty($errors)) {
            $errorText = (string)($errors[0]->error->description2 ?? $errors[0]->error->description ?? 'Unknown');
            $this->warn("API Error: {$errorText}");

            // Try alternate approach - query through getDimensionRestrictionReport
            return $this->queryAlternateMethod($apiService);
        }

        // Parse restrictions
        $restrictionNodes = $xmlObj->xpath('//dimensionrestriction');

        foreach ($restrictionNodes as $node) {
            $restrictions[] = [
                'name' => (string)$node->name ?? '',
                'description' => (string)$node->description ?? '',
                'dimension1' => (string)$node->dimension1 ?? '',
                'dimension2' => (string)$node->dimension2 ?? '',
                'status' => (string)$node->status ?? '',
            ];
        }

        $this->info('  Found ' . count($restrictions) . ' restriction rules');

        return $restrictions;
    }

    private function queryAlternateMethod(SageIntacctApiService $apiService): array
    {
        $this->info('Trying alternate query method...');

        // Try querying LOCATIONDEPTRESTRICT for Location-Department relationships
        $sessionId = $apiService->getSessionId();
        $setting = $apiService->getSetting();

        $results = [];

        // Common restriction objects to try
        $restrictionObjects = [
            'LOCATIONDEPTRESTRICT',
            'LOCATIONPROJRESTRICT',
            'DEPTPROJRESTRICT',
            'RESTRICTEDDIMENSION',
        ];

        foreach ($restrictionObjects as $objName) {
            $this->line("  Trying {$objName}...");

            $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$setting->sender_id}</senderid>
        <password>{$setting->sender_password}</password>
        <controlid>query-{$objName}</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <sessionid>{$sessionId}</sessionid>
        </authentication>
        <content>
            <function controlid="func-{$objName}">
                <get_list object="{$objName}" maxitems="1000" />
            </function>
        </content>
    </operation>
</request>
XML;

            $reflection = new \ReflectionClass($apiService);
            $method = $reflection->getMethod('sendXmlRequest');
            $method->setAccessible(true);

            $response = $method->invoke($apiService, $xml);

            if ($response['success']) {
                $xmlObj = simplexml_load_string($response['xml']);
                $errors = $xmlObj->xpath('//errormessage');

                if (empty($errors)) {
                    $records = $xmlObj->xpath("//".strtolower($objName));
                    if (!empty($records)) {
                        $this->info("    ✓ Found " . count($records) . " records");
                        $results[$objName] = $records;
                    }
                }
            }
        }

        return $results;
    }

    private function generateMarkdown(array $restrictions): string
    {
        $markdown = "# Sage Intacct Dimension Relationships\n\n";
        $markdown .= "_Generated on: " . now()->format('Y-m-d H:i:s') . "_\n\n";
        $markdown .= "This document shows dimensional relationship restrictions in Sage Intacct.\n\n";
        $markdown .= "---\n\n";

        if (empty($restrictions)) {
            $markdown .= "## No Dimension Restrictions Found\n\n";
            $markdown .= "**Note:** Your Sage Intacct instance may not have dimension restrictions configured,\n";
            $markdown .= "or they may be queried through a different API endpoint.\n\n";
            $markdown .= "**Common restriction types:**\n";
            $markdown .= "- Location-Department restrictions (which Services work with which Programs)\n";
            $markdown .= "- Location-Project restrictions (which Funds work with which Programs)\n";
            $markdown .= "- Department-Project restrictions (which Funds work with which Services)\n\n";
            $markdown .= "**To configure in Sage Intacct:**\n";
            $markdown .= "1. Go to Company → Setup → Dimension Relationships\n";
            $markdown .= "2. Configure allowed combinations\n";
            $markdown .= "3. Re-run this command to document them\n\n";
        } else {
            foreach ($restrictions as $objName => $records) {
                $markdown .= "## {$objName}\n\n";
                $markdown .= "| Dimension 1 | Dimension 2 | Status |\n";
                $markdown .= "|---|---|---|\n";

                foreach ($records as $record) {
                    if (is_array($record)) {
                        $dim1 = $record['dimension1'] ?? 'N/A';
                        $dim2 = $record['dimension2'] ?? 'N/A';
                        $status = $record['status'] ?? 'N/A';
                        $markdown .= "| {$dim1} | {$dim2} | {$status} |\n";
                    }
                }

                $markdown .= "\n";
            }
        }

        // Add practical guidance
        $markdown .= "---\n\n";
        $markdown .= "## For Other Receipts: Valid Dimension Combinations\n\n";
        $markdown .= "Based on your current mappings and test results:\n\n";

        $markdown .= "### Known Working Combinations:\n\n";
        $markdown .= "```\n";
        $markdown .= "Location (Program) + Department (Service) + Project (Fund) + Class (Restriction)\n";
        $markdown .= "─────────────────────────────────────────────────────────────────────────────\n";
        $markdown .= "P1000 (ADMIN)      + S10999 (AS999)       + F8000           + 0 (Unrestricted)\n";
        $markdown .= "```\n\n";

        $markdown .= "### Campaign Override System:\n\n";
        $markdown .= "The system supports campaign-based dimension overrides via `campaign_service_overrides` table.\n\n";
        $markdown .= "**Example:** When Location = P1400 (Second Harvest Food Bank):\n";
        $markdown .= "- Force Service: S14999 (SHFB999-Undesignated Service)\n";
        $markdown .= "- Force Fund: F8000\n\n";

        $markdown .= "This ensures dimensional compatibility for specific programs.\n\n";

        return $markdown;
    }
}
