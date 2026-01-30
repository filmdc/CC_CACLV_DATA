<?php

namespace App\Console\Commands;

use App\Models\SageIntacctSetting;
use App\Models\User;
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

            if (! $userId) {
                $this->error('User ID is required for OAuth-based authentication.');
                $this->info('Usage: php artisan intacct:sync-deposit-dates --user-id=1');

                return Command::FAILURE;
            }

            $user = User::find($userId);

            if (! $user) {
                $this->error('User not found with ID: '.$userId);

                return Command::FAILURE;
            }

            $userToken = $user->sageIntacctToken;

            if (! $userToken || ! $userToken->hasValidToken()) {
                $this->error('User does not have a valid Sage Intacct OAuth token.');
                $this->info('The user must authorize access via the web interface first.');

                return Command::FAILURE;
            }

            $this->info('Using OAuth token for user: '.$user->name);
        } else {
            $this->info('Using XML Web Services authentication');
        }

        $this->newLine();

        // Initialize the service
        $service = new DepositDateSyncService($userToken);

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

        $this->info("Processing up to {$batchSize} items...");
        $this->newLine();

        // Show progress bar
        $progressBar = $this->output->createProgressBar(min($batchSize, $pendingCount));
        $progressBar->start();

        // Run sync
        $result = $service->sync($batchSize, $dryRun);

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        if ($result['success']) {
            $this->info('Sync completed successfully!');
        } else {
            $this->error('Sync completed with errors.');
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total pending', $result['total_pending']],
                ['Queried from Sage', $result['sage_queried']],
                ['Found with deposit date', $result['deposited_found']],
                ['Updated in DonorPerfect', $result['dp_updated']],
                ['Failed to update', $result['dp_failed']],
            ]
        );

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->warn('Errors:');
            foreach (array_slice($result['errors'], 0, 10) as $error) {
                $this->line('  - '.$error);
            }
            if (count($result['errors']) > 10) {
                $this->line('  ... and '.(count($result['errors']) - 10).' more errors');
            }
        }

        return $result['success'] ? Command::SUCCESS : Command::FAILURE;
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
