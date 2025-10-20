<?php

namespace App\Console\Commands;

use App\Models\SageIntacctSetting;
use App\Services\DonorPerfectGiftService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DiscoverDonorPerfectCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dp:discover-codes {--days=90 : Number of days to look back}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover and sync DonorPerfect codes for Sage Intacct mapping';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('DonorPerfect Code Discovery');
        $this->newLine();

        try {
            // Check if code mapping is enabled
            $intacctSettings = SageIntacctSetting::getActive();

            if (!$intacctSettings) {
                $this->info('Sage Intacct is not configured. Code mapping is not enabled.');
                Log::info('Code discovery skipped: No active Sage Intacct configuration');
                return Command::SUCCESS;
            }

            if (!$intacctSettings->code_mapping_enabled) {
                $this->info('Code mapping is not enabled in Sage Intacct settings.');
                $this->info('Enable code mapping in Sage Intacct settings to use this feature.');
                Log::info('Code discovery skipped: Code mapping not enabled');
                return Command::SUCCESS;
            }

            // Get days parameter
            $days = (int) $this->option('days');

            if ($days <= 0) {
                $this->error('Days must be a positive number');
                return Command::FAILURE;
            }

            // Display discovery configuration
            $this->info("Discovering codes from last {$days} days...");
            $this->newLine();

            // Call service to discover and sync codes
            $giftService = app(DonorPerfectGiftService::class);
            $results = $giftService->discoverAndSyncCodes($days);

            if (!$results['success']) {
                $this->error('Code discovery failed: ' . ($results['error'] ?? 'Unknown error'));
                Log::error('DonorPerfect code discovery failed', [
                    'error' => $results['error'] ?? 'Unknown error',
                    'days' => $days,
                ]);
                return Command::FAILURE;
            }

            // Display results in a table
            $this->info('Discovery Results:');
            $this->newLine();

            $tableData = [];
            foreach ($results['by_type'] as $fieldType => $data) {
                $tableData[] = [
                    $this->formatFieldType($fieldType),
                    $data['total'] ?? 0,
                    $data['new'] ?? 0,
                ];
            }

            $this->table(
                ['Field Type', 'Total Codes', 'New Codes'],
                $tableData
            );

            // Display summary
            $this->newLine();
            $totalNew = $results['new'] ?? 0;
            $this->info("Discovery complete. Found {$totalNew} new code(s).");

            // Log results
            Log::info('DonorPerfect code discovery completed successfully', [
                'days' => $days,
                'total_new_codes' => $totalNew,
                'total_codes' => $results['total'] ?? 0,
                'by_type' => $results['by_type'],
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('An error occurred during code discovery: ' . $e->getMessage());
            Log::error('DonorPerfect code discovery exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Format field type for display
     */
    private function formatFieldType(string $fieldType): string
    {
        return match($fieldType) {
            'gl_code' => 'GL Code',
            'campaign' => 'Campaign',
            'solicit_code' => 'Solicit Code',
            'sub_solicit_code' => 'Sub-Solicit Code',
            default => ucwords(str_replace('_', ' ', $fieldType)),
        };
    }
}
