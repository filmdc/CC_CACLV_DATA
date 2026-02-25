<?php

namespace App\Console\Commands;

use App\Models\SageIntacctSetting;
use App\Models\User;
use App\Models\UserSageIntacctToken;
use App\Services\DepositDateSyncService;
use Illuminate\Console\Command;

class SyncDepositDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intacct:sync-deposit-dates
                            {--user-id= : User ID whose OAuth token to use (required for OAuth auth)}
                            {--batch-size=100 : Number of items to process per batch}
                            {--dry-run : Run without actually updating DonorPerfect}
                            {--retry-failed : Retry previously failed deposit date syncs}
                            {--stats : Show statistics only, do not sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync deposit dates from Sage Intacct back to DonorPerfect';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Deposit Date Sync: Sage Intacct → DonorPerfect');
        $this->info('=============================================');
        $this->newLine();

        // Get active Sage settings
        $sageSetting = SageIntacctSetting::getActive();

        if (! $sageSetting) {
            $this->error('No active Sage Intacct configuration found.');

            return Command::FAILURE;
        }

        // Determine if we need OAuth token
        $userToken = null;
        if (! $sageSetting->isXmlApi()) {
            $userId = $this->option('user-id');

            if ($userId) {
                $user = User::find($userId);

                if (! $user) {
                    $this->error('User not found with ID: '.$userId);

                    return Command::FAILURE;
                }

                $userToken = $user->sageIntacctToken;
            } else {
                // Auto-detect: find any user with a valid OAuth token
                $userToken = UserSageIntacctToken::whereNotNull('access_token')
                    ->whereNotNull('company_id')
                    ->latest('updated_at')
                    ->first();

                if ($userToken) {
                    $this->info('Auto-detected OAuth token for user ID: '.$userToken->user_id);
                }
            }

            if (! $userToken || ! $userToken->hasValidToken()) {
                $this->error('No valid Sage Intacct OAuth token available.');
                $this->info('Either pass --user-id=N or ensure a user has authorized via the web interface.');

                return Command::FAILURE;
            }

            $this->info('Using OAuth token for user: '.($userToken->user->name ?? 'ID '.$userToken->user_id));
        } else {
            $this->info('Using XML Web Services authentication');
        }

        $this->newLine();

        // Initialize the service
        $service = new DepositDateSyncService($userToken);
        $this->info('Environment: '.$service->getEnvironment());

        if (! $service->isConfigured()) {
            $this->error('Deposit date sync service is not properly configured.');
            $this->table(
                ['Component', 'Status'],
                collect($service->getConfigurationStatus())->map(function ($status, $key) {
                    return [$key, $status ? '✓ Ready' : '✗ Not Ready'];
                })->toArray()
            );

            return Command::FAILURE;
        }

        // Stats only mode
        if ($this->option('stats')) {
            $this->showStatistics($service);

            return Command::SUCCESS;
        }

        // Retry failed mode
        if ($this->option('retry-failed')) {
            return $this->retryFailed($service);
        }

        // Main sync
        return $this->runSync($service);
    }

    /**
     * Show deposit date sync statistics
     */
    private function showStatistics(DepositDateSyncService $service): void
    {
        $stats = $service->getStatistics();

        $this->info('Deposit Date Sync Statistics');
        $this->info('----------------------------');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total gifts synced to Sage', $stats['total_synced_to_sage']],
                ['Pending deposit check (need to query Sage)', $stats['pending_deposit_check']],
                ['Have deposit date from Sage', $stats['has_deposit_date']],
                ['Pending DP update (have deposit date, not synced)', $stats['pending_dp_update']],
                ['Successfully synced to DP', $stats['synced_to_dp']],
                ['With sync errors', $stats['with_errors']],
            ]
        );
    }

    /**
     * Run the main deposit date sync
     */
    private function runSync(DepositDateSyncService $service): int
    {
        $batchSize = (int) $this->option('batch-size');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to DonorPerfect');
            $this->newLine();
        }

        $pendingCount = $service->getPendingCount();
        $this->info("Found {$pendingCount} items pending deposit date sync");

        if ($pendingCount === 0) {
            $this->info('Nothing to sync.');

            return Command::SUCCESS;
        }

        $this->info("Processing all {$pendingCount} items in batches of {$batchSize}...");
        $this->newLine();

        // Show progress bar for total pending items
        $progressBar = $this->output->createProgressBar($pendingCount);
        $progressBar->start();

        // Loop through all pending items in batches
        $totals = [
            'total_pending' => 0,
            'sage_queried' => 0,
            'deposited_found' => 0,
            'fund_codes_found' => 0,
            'service_codes_found' => 0,
            'dp_updated' => 0,
            'dp_failed' => 0,
            'errors' => [],
            'batches' => 0,
        ];
        $hasErrors = false;

        while (true) {
            $result = $service->sync($batchSize, $dryRun);
            $totals['batches']++;
            $totals['total_pending'] += $result['total_pending'];
            $totals['sage_queried'] += $result['sage_queried'];
            $totals['deposited_found'] += $result['deposited_found'];
            $totals['fund_codes_found'] += $result['fund_codes_found'] ?? 0;
            $totals['service_codes_found'] += $result['service_codes_found'] ?? 0;
            $totals['dp_updated'] += $result['dp_updated'];
            $totals['dp_failed'] += $result['dp_failed'];
            $totals['errors'] = array_merge($totals['errors'], $result['errors'] ?? []);

            if (! $result['success']) {
                $hasErrors = true;
            }

            $progressBar->advance($result['total_pending']);

            // Stop if this batch had no items (we've processed everything)
            if ($result['total_pending'] === 0) {
                break;
            }

            // Stop if we got fewer items than the batch size (last batch)
            if ($result['total_pending'] < $batchSize) {
                break;
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        if (! $hasErrors) {
            $this->info('Sync completed successfully!');
        } else {
            $this->error('Sync completed with errors.');
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total processed', $totals['total_pending']],
                ['Batches run', $totals['batches']],
                ['Queried from Sage', $totals['sage_queried']],
                ['Found with deposit date', $totals['deposited_found']],
                ['Found with fund code', $totals['fund_codes_found']],
                ['Found with service code', $totals['service_codes_found']],
                ['Updated in DonorPerfect', $totals['dp_updated']],
                ['Failed to update', $totals['dp_failed']],
            ]
        );

        if (! empty($totals['errors'])) {
            $this->newLine();
            $this->warn('Errors:');
            foreach (array_slice($totals['errors'], 0, 10) as $error) {
                $this->line('  - '.$error);
            }
            if (count($totals['errors']) > 10) {
                $this->line('  ... and '.(count($totals['errors']) - 10).' more errors');
            }
        }

        return ! $hasErrors ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Retry failed deposit date syncs
     */
    private function retryFailed(DepositDateSyncService $service): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to DonorPerfect');
            $this->newLine();
        }

        $errorItems = $service->getItemsWithErrors();
        $this->info("Found {$errorItems->count()} items with errors to retry");

        if ($errorItems->isEmpty()) {
            $this->info('Nothing to retry.');

            return Command::SUCCESS;
        }

        $this->newLine();

        // Run retry
        $result = $service->retryFailed($dryRun);

        // Display results
        if ($result['success']) {
            $this->info('Retry completed!');
        } else {
            $this->error('Retry completed with errors.');
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total with errors', $result['total_errors']],
                ['Retried', $result['retried']],
                ['Succeeded', $result['succeeded']],
                ['Failed', $result['failed']],
            ]
        );

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->warn('Errors:');
            foreach (array_slice($result['errors'], 0, 10) as $error) {
                $this->line('  - '.$error);
            }
        }

        return $result['success'] ? Command::SUCCESS : Command::FAILURE;
    }
}
