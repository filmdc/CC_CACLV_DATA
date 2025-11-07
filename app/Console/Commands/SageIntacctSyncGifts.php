<?php

namespace App\Console\Commands;

use App\Models\SageIntacctSetting;
use App\Services\SageIntacctApiService;
use App\Services\SageIntacctSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SageIntacctSyncGifts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intacct:sync-gifts
                            {--user-id= : User ID whose OAuth token to use for sync}
                            {--start-date= : Start date for sync (YYYY-MM-DD)}
                            {--end-date= : End date for sync (YYYY-MM-DD)}
                            {--retry-failed : Retry previously failed syncs}
                            {--dry-run : Run without actually syncing}
                            {--force : Force sync even in production environment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync DonorPerfect gifts to Sage Intacct';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sage Intacct Gift Sync');
        $this->info('======================');
        $this->newLine();

        // Get active settings
        $setting = SageIntacctSetting::getActive();

        if (! $setting) {
            $this->error('No active Sage Intacct configuration found.');
            $this->info('Please configure Sage Intacct settings first.');

            return Command::FAILURE;
        }

        if (! $setting->isConfigured()) {
            $this->error('Sage Intacct is not fully configured.');
            $this->info('Please complete the configuration in the admin panel.');

            return Command::FAILURE;
        }

        // Get user token for OAuth authentication
        $userId = $this->option('user-id');

        if (! $userId) {
            $this->error('User ID is required for OAuth-based sync.');
            $this->info('Usage: php artisan intacct:sync-gifts --user-id=1');

            return Command::FAILURE;
        }

        $user = \App\Models\User::find($userId);

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

        $this->info('Using OAuth token for user: '.$user->name.' (ID: '.$userId.')');
        $this->newLine();

        // Sandbox safety check
        if (! $userToken->isSandbox() && ! $this->option('force')) {
            $this->warn('WARNING: You are syncing to a PRODUCTION environment!');
            $this->warn('Company ID: '.$userToken->company_id);
            $this->newLine();

            if (! $this->confirm('Are you sure you want to continue?', false)) {
                $this->info('Sync cancelled.');

                return Command::SUCCESS;
            }
        } elseif ($userToken->isSandbox()) {
            $this->info('✓ Using sandbox environment: '.$userToken->company_id);
        }

        $this->newLine();

        // Dry run notice
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No actual changes will be made');
            $this->newLine();
        }

        // Determine sync mode
        if ($this->option('retry-failed')) {
            return $this->retryFailedSync($setting, $userToken);
        } else {
            return $this->syncDateRange($setting, $userToken);
        }
    }

    /**
     * Sync gifts for a specific date range
     */
    private function syncDateRange(SageIntacctSetting $setting, $userToken): int
    {
        // Get date range
        $startDate = $this->option('start-date')
            ? Carbon::parse($this->option('start-date'))
            : Carbon::today()->subDays(30);

        $endDate = $this->option('end-date')
            ? Carbon::parse($this->option('end-date'))
            : Carbon::today();

        $this->info('Syncing gifts from '.$startDate->toDateString().' to '.$endDate->toDateString());
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('Would sync gifts in this date range.');

            return Command::SUCCESS;
        }

        // Initialize services
        $apiService = new SageIntacctApiService($userToken, $setting);
        $syncService = new SageIntacctSyncService($apiService, $setting, $userToken);

        // Test connection first
        $this->info('Testing connection...');
        $connectionResult = $apiService->testConnection();

        if (! $connectionResult['success']) {
            $this->error('Connection test failed: '.$connectionResult['message']);

            return Command::FAILURE;
        }

        $this->info('✓ Connection successful');
        $this->newLine();

        // Perform sync
        $this->info('Starting sync...');
        $this->withProgressBar(1, function () {
            // Note: This is a simplified progress bar.
            // The actual sync happens inside syncGifts
        });

        $result = $syncService->syncGifts($startDate, $endDate, $userToken->user_id);

        $this->newLine(2);

        if ($result['success']) {
            $this->info('✓ Sync completed successfully');
            $this->newLine();

            // Display statistics
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Records', $result['total'] ?? 0],
                    ['Successfully Synced', $result['synced'] ?? 0],
                    ['Failed', $result['failed'] ?? 0],
                ]
            );

            return Command::SUCCESS;
        } else {
            $this->error('✗ Sync failed: '.($result['error'] ?? 'Unknown error'));

            return Command::FAILURE;
        }
    }

    /**
     * Retry failed syncs
     */
    private function retryFailedSync(SageIntacctSetting $setting, $userToken): int
    {
        $this->info('Retrying failed sync items...');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('Would retry failed sync items.');

            return Command::SUCCESS;
        }

        // Initialize services
        $apiService = new SageIntacctApiService($userToken, $setting);
        $syncService = new SageIntacctSyncService($apiService, $setting, $userToken);

        // Test connection first
        $this->info('Testing connection...');
        $connectionResult = $apiService->testConnection();

        if (! $connectionResult['success']) {
            $this->error('Connection test failed: '.$connectionResult['message']);

            return Command::FAILURE;
        }

        $this->info('✓ Connection successful');
        $this->newLine();

        // Retry failed syncs
        $this->info('Starting retry...');
        $result = $syncService->syncUnsyncedGifts($userToken->user_id);

        $this->newLine();

        if ($result['success']) {
            $this->info('✓ Retry completed successfully');
            $this->newLine();

            // Display statistics
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Retried', $result['total'] ?? 0],
                    ['Successfully Synced', $result['synced'] ?? 0],
                    ['Still Failed', $result['failed'] ?? 0],
                ]
            );

            return Command::SUCCESS;
        } else {
            $this->error('✗ Retry failed: '.($result['error'] ?? 'Unknown error'));

            return Command::FAILURE;
        }
    }
}
