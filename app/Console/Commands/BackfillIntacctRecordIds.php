<?php

namespace App\Console\Commands;

use App\Models\SageIntacctSetting;
use App\Models\SageIntacctSyncItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BackfillIntacctRecordIds extends Command
{
    protected $signature = 'intacct:backfill-record-ids
                            {--dry-run : Preview what would be updated without making changes}
                            {--start=0 : Starting RECORDNO to scan from (0 = auto-detect)}
                            {--end=0 : Ending RECORDNO to scan to (0 = auto-detect)}';

    protected $description = 'Backfill missing intacct_record_id values by scanning Sage Intacct receipts and matching DOCNUMBER to gift IDs';

    public function handle()
    {
        $this->info('Backfill Missing Sage Intacct Record IDs');
        $this->info('=========================================');
        $this->newLine();

        $sageSetting = SageIntacctSetting::getActive();
        if (! $sageSetting) {
            $this->error('No active Sage Intacct configuration found.');

            return Command::FAILURE;
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No database changes will be made');
            $this->newLine();
        }

        // Get all gift_ids that need backfilling
        $missingItems = SageIntacctSyncItem::where('status', 'success')
            ->whereNull('intacct_record_id')
            ->where('environment', 'production')
            ->pluck('gift_id')
            ->unique()
            ->flip() // gift_id => index (for fast lookup)
            ->toArray();

        $total = count($missingItems);
        $this->info("Found {$total} unique gift IDs with missing intacct_record_id");

        if ($total === 0) {
            $this->info('Nothing to backfill.');

            return Command::SUCCESS;
        }

        // Determine RECORDNO scan range
        $knownMin = SageIntacctSyncItem::where('status', 'success')
            ->whereNotNull('intacct_record_id')
            ->where('environment', 'production')
            ->min('intacct_record_id');

        $knownMax = SageIntacctSyncItem::where('status', 'success')
            ->whereNotNull('intacct_record_id')
            ->where('environment', 'production')
            ->max('intacct_record_id');

        // Scan a range wider than known: 1000 before earliest known to 1000 after latest
        $start = (int) $this->option('start') ?: max(1, (int) $knownMin - 1000);
        $end = (int) $this->option('end') ?: (int) $knownMax + 2000;

        $this->info("Known RECORDNO range: {$knownMin} to {$knownMax}");
        $this->info("Scanning RECORDNO range: {$start} to {$end}");
        $this->newLine();

        $apiUrl = $sageSetting->endpoint_url ?? 'https://api.intacct.com/ia/xml/xmlgw.phtml';
        $updated = 0;
        $scanned = 0;

        // Scan in chunks of 100 (Sage <read> max per call)
        $totalKeys = $end - $start + 1;
        $progressBar = $this->output->createProgressBar($totalKeys);
        $progressBar->start();

        for ($cursor = $start; $cursor <= $end; $cursor += 100) {
            $keys = range($cursor, min($cursor + 99, $end));
            $keysStr = implode(',', $keys);

            $xml = $this->buildReadXml($sageSetting, $keysStr);

            try {
                $response = Http::timeout(60)
                    ->withHeaders(['Content-Type' => 'application/xml'])
                    ->withBody($xml, 'application/xml')
                    ->post($apiUrl);

                if (! $response->successful()) {
                    $progressBar->advance(count($keys));

                    continue;
                }

                $body = $response->body();
                $doc = @simplexml_load_string($body);

                if (! $doc) {
                    $progressBar->advance(count($keys));

                    continue;
                }

                // Parse receipts from response
                $dataNodes = $doc->xpath('//data/*');
                foreach ($dataNodes as $node) {
                    $recordNo = (string) ($node->RECORDNO ?? '');
                    $docNumber = (string) ($node->DOCNUMBER ?? '');
                    $scanned++;

                    // Check if this DOCNUMBER matches a gift_id we're looking for
                    if ($recordNo && $docNumber && isset($missingItems[$docNumber])) {
                        if (! $dryRun) {
                            DB::table('sage_intacct_sync_items')
                                ->where('status', 'success')
                                ->where('gift_id', $docNumber)
                                ->whereNull('intacct_record_id')
                                ->where('environment', 'production')
                                ->update([
                                    'intacct_record_id' => $recordNo,
                                    'updated_at' => now(),
                                ]);
                        }
                        $updated++;
                        unset($missingItems[$docNumber]);

                        // If we've found all missing items, stop early
                        if (empty($missingItems)) {
                            $progressBar->finish();
                            break 2;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Continue on error
            }

            $progressBar->advance(count($keys));
        }

        $progressBar->finish();
        $this->newLine(2);

        $notFound = count($missingItems);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total with missing IDs', $total],
                ['Receipts scanned in Sage', $scanned],
                ['Matched & updated', $updated],
                ['Still not found', $notFound],
            ]
        );

        if ($dryRun && $updated > 0) {
            $this->warn('Dry run complete. Run without --dry-run to apply changes.');
        }

        if ($notFound > 0) {
            $this->warn("{$notFound} items could not be matched. They may have been created outside the scanned range, or deleted from Sage.");
        }

        return Command::SUCCESS;
    }

    private function buildReadXml(SageIntacctSetting $setting, string $keys): string
    {
        $controlId = 'BACKFILL_'.time().'_'.bin2hex(random_bytes(4));

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
            <login>
                <userid>{$setting->web_services_user_id}</userid>
                <companyid>{$setting->company_id}</companyid>
                <password>{$setting->web_services_password}</password>
            </login>
        </authentication>
        <content>
            <function controlid="backfill_read">
                <read>
                    <object>OTHERRECEIPTS</object>
                    <keys>{$keys}</keys>
                    <fields>RECORDNO,DOCNUMBER</fields>
                </read>
            </function>
        </content>
    </operation>
</request>
XML;
    }
}
