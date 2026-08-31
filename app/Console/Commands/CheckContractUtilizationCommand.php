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

        // Master switch off: keep evaluating overspend and keep recording which
        // thresholds were crossed, but withhold the email. Recording as we go
        // is what stops a backlog burst when the switch is turned back on.
        $notificationsEnabled = ContractUtilizationService::notificationsEnabled();

        if (! $notificationsEnabled) {
            $this->warn('Utilization notifications are DISABLED. Thresholds will be evaluated and recorded, but no email will be sent.');
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

        // A test redirect still delivers while the master switch is off, so
        // reporting keys off this rather than the switch alone.
        $deliveringMail = $notificationsEnabled || ($testMode && $testEmail);

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
        $totalSuppressed = 0;
        $contractsProcessed = 0;

        try {
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

                        if ($deliveringMail) {
                            $totalNotifications += $sent;
                            $this->line("  → Sent {$sent} notification(s)");
                        } else {
                            $totalSuppressed++;
                            $this->line('  → Recorded without sending (notifications disabled)');
                        }
                    } elseif (! $deliveringMail) {
                        $totalSuppressed++;
                        $this->line('  → Would record without sending (notifications disabled)');
                    } else {
                        $recipients = $contract->getUtilizationNotificationRecipients();
                        $this->line("  → Would send to {$recipients->count()} recipient(s)");
                        $totalNotifications += $recipients->count();
                    }
                }
            }
        } finally {
            // Always released, even if a contract blows up mid-run: a latched
            // test-mode flag would silently divert every later alert.
            if ($testMode && ! $globalTestMode) {
                SiteSetting::set('utilization_test_mode', '0', 'boolean');
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Utilization check completed. Contracts processed: %d | Notifications sent: %d | Suppressed: %d',
            $contractsProcessed,
            $totalNotifications,
            $totalSuppressed
        ));

        Log::info('Contract utilization check completed', [
            'contracts_processed' => $contractsProcessed,
            'notifications_sent' => $totalNotifications,
            'suppressed' => $totalSuppressed,
            'notifications_enabled' => $notificationsEnabled,
            'dry_run' => $dryRun,
            'contract_id' => $contractId,
            'test_mode' => $testMode,
        ]);

        return Command::SUCCESS;
    }
}
