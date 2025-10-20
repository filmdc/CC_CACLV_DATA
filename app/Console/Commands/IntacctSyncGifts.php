<?php

namespace App\Console\Commands;

use App\Models\DonorPerfectGiftCache;
use App\Models\SageIntacctSetting;
use App\Services\SageIntacctSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IntacctSyncGifts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intacct:sync-gifts
                            {--dry-run : Preview sync without actually sending to Intacct}
                            {--start-date= : Start date for sync (Y-m-d format)}
                            {--end-date= : End date for sync (Y-m-d format)}
                            {--unsynced-only : Only sync gifts not previously synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize DonorPerfect gifts to Sage Intacct';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Sage Intacct gift synchronization...');

        // Check if Intacct is configured
        $intacctSettings = SageIntacctSetting::getActive();

        if (!$intacctSettings) {
            $this->error('Sage Intacct is not configured. Please configure Sage Intacct settings first.');
            Log::error('Intacct sync failed: No active configuration found');
            return Command::FAILURE;
        }

        if (!$intacctSettings->isConfigured()) {
            $this->error('Sage Intacct configuration is incomplete. Please ensure all required credentials are set.');
            Log::error('Intacct sync failed: Configuration incomplete');
            return Command::FAILURE;
        }

        // Parse command options
        $dryRun = $this->option('dry-run');
        $unsyncedOnly = $this->option('unsynced-only');
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');

        // Set default date range (last 7 days) if not provided
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        }

        if (!$endDate) {
            $endDate = Carbon::now()->format('Y-m-d');
        }

        // Validate date format
        try {
            $startDateObj = Carbon::createFromFormat('Y-m-d', $startDate);
            $endDateObj = Carbon::createFromFormat('Y-m-d', $endDate);

            if ($endDateObj->lt($startDateObj)) {
                $this->error('End date must be after start date');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Invalid date format. Please use Y-m-d format (e.g., 2025-01-15)');
            Log::error('Intacct sync failed: Invalid date format', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be sent to Sage Intacct');
        }

        // Display sync configuration
        $this->newLine();
        $this->info('Sync Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Start Date', $startDate],
                ['End Date', $endDate],
                ['Unsynced Only', $unsyncedOnly ? 'Yes' : 'No'],
                ['Dry Run', $dryRun ? 'Yes' : 'No'],
                ['Intacct Company', $intacctSettings->company_id ?? 'N/A'],
            ]
        );

        // Get gifts to sync
        $this->info('Retrieving gifts from cache...');

        try {
            $gifts = $this->getGiftsToSync($startDate, $endDate, $unsyncedOnly);

            if (empty($gifts)) {
                $this->warn('No gifts found for the specified date range.');
                Log::info('Intacct sync completed: No gifts to sync', [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'unsynced_only' => $unsyncedOnly,
                ]);
                return Command::SUCCESS;
            }

            $totalGifts = count($gifts);
            $this->info("Found {$totalGifts} gift(s) to sync");

        } catch (\Exception $e) {
            $this->error('Failed to retrieve gifts: ' . $e->getMessage());
            Log::error('Intacct sync failed: Error retrieving gifts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }

        // Ask for confirmation unless dry-run
        if (!$dryRun) {
            if (!$this->confirm("Proceed with syncing {$totalGifts} gift(s) to Sage Intacct?")) {
                $this->warn('Sync cancelled by user');
                return Command::SUCCESS;
            }
        }

        // Perform sync
        $this->newLine();
        $this->info($dryRun ? 'Previewing sync...' : 'Syncing gifts to Sage Intacct...');

        try {
            $syncService = app(SageIntacctSyncService::class);

            // Initialize progress tracking
            $successCount = 0;
            $failedCount = 0;
            $errors = [];

            // Create progress bar
            $progressBar = $this->output->createProgressBar($totalGifts);
            $progressBar->start();

            foreach ($gifts as $gift) {
                try {
                    if ($dryRun) {
                        // In dry-run mode, just validate the data
                        $this->validateGiftData($gift);
                        $successCount++;
                    } else {
                        // Actually sync the gift
                        $result = $syncService->syncGift($gift);

                        if ($result['success']) {
                            $successCount++;
                        } else {
                            $failedCount++;
                            $errors[] = [
                                'gift_id' => $gift['gift_id'] ?? 'Unknown',
                                'error' => $result['error'] ?? 'Unknown error',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = [
                        'gift_id' => $gift['gift_id'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to sync individual gift', [
                        'gift_id' => $gift['gift_id'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                    ]);
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Display summary
            $this->displaySyncSummary($totalGifts, $successCount, $failedCount, $errors, $dryRun);

            // Log summary
            Log::info('Intacct sync completed', [
                'total_gifts' => $totalGifts,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'dry_run' => $dryRun,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            return $failedCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            Log::error('Intacct sync failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Get gifts to sync from cache
     */
    private function getGiftsToSync(string $startDate, string $endDate, bool $unsyncedOnly): array
    {
        // Get all cached gifts for the date range
        $cacheEntries = DonorPerfectGiftCache::where('start_date', '>=', $startDate)
            ->where('end_date', '<=', $endDate)
            ->where('expires_at', '>', now())
            ->get();

        if ($cacheEntries->isEmpty()) {
            return [];
        }

        $gifts = [];
        foreach ($cacheEntries as $entry) {
            if (!empty($entry->gift_data) && is_array($entry->gift_data)) {
                $gifts = array_merge($gifts, $entry->gift_data);
            }
        }

        // Filter for unsynced gifts if requested
        if ($unsyncedOnly) {
            $gifts = array_filter($gifts, function($gift) {
                return empty($gift['intacct_synced_at']);
            });
        }

        return $gifts;
    }

    /**
     * Validate gift data structure
     */
    private function validateGiftData(array $gift): void
    {
        $requiredFields = ['gift_id', 'gift_date', 'amount'];

        foreach ($requiredFields as $field) {
            if (!isset($gift[$field])) {
                throw new \Exception("Gift is missing required field: {$field}");
            }
        }
    }

    /**
     * Display sync summary
     */
    private function displaySyncSummary(int $total, int $success, int $failed, array $errors, bool $dryRun): void
    {
        $this->info('Sync Summary:');
        $this->newLine();

        $summaryData = [
            ['Total Gifts', $total],
            ['Successful', $success],
            ['Failed', $failed],
        ];

        if ($dryRun) {
            $summaryData[] = ['Status', 'DRY RUN - No data sent'];
        }

        $this->table(['Metric', 'Count'], $summaryData);

        // Display errors if any
        if (!empty($errors)) {
            $this->newLine();
            $this->error("Failed to sync {$failed} gift(s):");

            $errorData = array_slice($errors, 0, 10); // Show first 10 errors
            $this->table(
                ['Gift ID', 'Error'],
                array_map(fn($err) => [$err['gift_id'], $err['error']], $errorData)
            );

            if (count($errors) > 10) {
                $this->warn('... and ' . (count($errors) - 10) . ' more errors. Check logs for details.');
            }
        } else {
            $this->newLine();
            $this->info($dryRun ? 'All gifts validated successfully!' : 'All gifts synced successfully!');
        }
    }
}
