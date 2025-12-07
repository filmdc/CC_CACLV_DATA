<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractUtilizationNotification;
use App\Models\SiteSetting;
use App\Services\ContractUtilizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckContractUtilizationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contract:check-utilization
                            {--dry-run : Run without sending notifications}
                            {--contract-id= : Only process a specific contract}
                            {--test-mode : Send all emails to test recipient}
                            {--test-email= : Test recipient email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check contract budget utilization and send threshold notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $contractId = $this->option('contract-id');
        $testMode = $this->option('test-mode');
        $testEmail = $this->option('test-email');

        // Check if notifications are enabled
        if (SiteSetting::get('utilization_notification_enabled', '1') !== '1') {
            $this->warn('Utilization notifications are disabled in settings.');

            return Command::SUCCESS;
        }

        // Check for global test mode
        $globalTestMode = SiteSetting::get('utilization_test_mode', '0') === '1';
        $globalTestEmail = SiteSetting::get('utilization_test_email');

        if ($globalTestMode && ! $testMode) {
            $testMode = true;
            $testEmail = $testEmail ?: $globalTestEmail;
            $this->warn('GLOBAL TEST MODE is enabled in settings');
        }

        $this->info('Starting contract utilization check...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No notifications will be sent');
        }

        if ($testMode) {
            if (! $testEmail) {
                $this->error('Test mode requires --test-email parameter or global test email setting');

                return Command::FAILURE;
            }
            $this->warn("TEST MODE - All emails will be sent to: {$testEmail}");

            // Temporarily set the test settings
            SiteSetting::set('utilization_test_mode', '1', 'boolean');
            SiteSetting::set('utilization_test_email', $testEmail, 'text');
        }

        // Get thresholds from settings
        $thresholds = ContractUtilizationService::getThresholds();
        $this->info('Checking thresholds: '.implode('%, ', $thresholds).'%');

        // Query active contracts with budget set
        $query = Contract::active()
            ->where('amount', '>', 0)
            ->with(['program', 'funder', 'projectExpenses']);

        if ($contractId) {
            $query->where('id', $contractId);
            $this->info("Filtering for contract ID: {$contractId}");
        }

        $contracts = $query->get();
        $this->info("Found {$contracts->count()} contracts to check");

        $totalNotifications = 0;
        $contractsProcessed = 0;

        foreach ($contracts as $contract) {
            $remainingPercent = $contract->remaining_percentage;
            $utilizationPercent = $contract->utilization_percentage;

            // Get thresholds that need notification
            $crossedThresholds = ContractUtilizationNotification::getAllCrossedThresholds(
                $contract->id,
                $remainingPercent,
                $thresholds
            );

            if (empty($crossedThresholds)) {
                continue;
            }

            $contractsProcessed++;

            foreach ($crossedThresholds as $threshold) {
                $this->line(sprintf(
                    'Contract: %s | Utilization: %.1f%% | Threshold: %d%% remaining',
                    $contract->title,
                    $utilizationPercent,
                    $threshold
                ));

                if (! $dryRun) {
                    $sent = ContractUtilizationService::sendThresholdNotification(
                        $contract,
                        $threshold,
                        'scheduled'
                    );
                    $totalNotifications += $sent;

                    $this->line("  → Sent {$sent} notification(s)");
                } else {
                    $recipients = $contract->getUtilizationNotificationRecipients();
                    $this->line("  → Would send to {$recipients->count()} recipient(s)");
                    $totalNotifications += $recipients->count();
                }
            }
        }

        // Reset test mode if we set it
        if ($testMode && ! $globalTestMode) {
            SiteSetting::set('utilization_test_mode', '0', 'boolean');
        }

        $this->newLine();
        $this->info(sprintf(
            'Utilization check completed. Contracts processed: %d | Notifications sent: %d',
            $contractsProcessed,
            $totalNotifications
        ));

        Log::info('Contract utilization check completed', [
            'contracts_processed' => $contractsProcessed,
            'notifications_sent' => $totalNotifications,
            'dry_run' => $dryRun,
            'contract_id' => $contractId,
            'test_mode' => $testMode,
        ]);

        return Command::SUCCESS;
    }
}
