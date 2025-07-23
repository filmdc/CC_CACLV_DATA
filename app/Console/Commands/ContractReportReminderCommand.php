<?php

namespace App\Console\Commands;

use App\Models\ContractReportDueDate;
use App\Models\SiteSetting;
use App\Notifications\ContractReportReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ContractReportReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contract:send-reminders 
                            {--dry-run : Run without sending emails}
                            {--days= : Check for specific days until due date}
                            {--contract-id= : Only process a specific contract}
                            {--test-mode : Send all emails to test recipient}
                            {--test-email= : Test recipient email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automated reminder emails for contract report due dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificDays = $this->option('days');
        $contractId = $this->option('contract-id');
        $testMode = $this->option('test-mode');
        $testEmail = $this->option('test-email');
        
        // Check if test mode is enabled in settings
        $globalTestMode = (bool) SiteSetting::get('contract_reminder_test_mode', '0');
        $globalTestEmail = SiteSetting::get('contract_reminder_test_email');
        
        // Override with global test mode if enabled
        if ($globalTestMode && !$testMode) {
            $testMode = true;
            $testEmail = $testEmail ?: $globalTestEmail;
            $this->warn('GLOBAL TEST MODE is enabled in settings');
        }
        
        $this->info('Starting contract report reminder check...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No emails will be sent');
        }
        
        if ($testMode) {
            if (!$testEmail) {
                $this->error('Test mode requires --test-email parameter or global test email setting');
                return Command::FAILURE;
            }
            $this->warn("TEST MODE - All emails will be sent to: {$testEmail}");
        }

        // Get all active due dates that haven't been completed
        $query = ContractReportDueDate::with(['contract.program', 'contract.funder', 'assignedUsers'])
            ->where('active', true)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date');
            
        // Filter by contract if specified
        if ($contractId) {
            $query->where('contract_id', $contractId);
            $this->info("Filtering for contract ID: {$contractId}");
        }
        
        $dueDates = $query->get();

        $totalReminders = 0;
        $todayDate = now()->startOfDay();

        // Get global reminder intervals
        $reminderIntervals = json_decode(SiteSetting::get('contract_reminder_intervals', '[30,15,7,3,1,0]'), true);
        
        foreach ($dueDates as $dueDate) {
            $daysUntilDue = $todayDate->diffInDays($dueDate->due_date, false);
            
            // If specific days filter is provided, only check for that interval
            if ($specificDays !== null && $daysUntilDue != $specificDays) {
                continue;
            }

            // Check if this day interval is in our reminder schedule
            if (in_array($daysUntilDue, $reminderIntervals)) {
                $remindersSent = $this->sendRemindersForDueDate($dueDate, $daysUntilDue, $dryRun, $testMode, $testEmail);
                $totalReminders += $remindersSent;
                
                $this->line(sprintf(
                    'Due Date: %s (%d days) - %s - Sent %d reminders',
                    strip_tags($dueDate->report_name ?? ucfirst($dueDate->report_type) . ' Report'),
                    $daysUntilDue,
                    $dueDate->contract->title ?? 'N/A',
                    $remindersSent
                ));
            }
        }

        $this->info(sprintf(
            'Reminder check completed. Total reminders sent: %d',
            $totalReminders
        ));

        Log::info('Contract report reminders processed', [
            'total_reminders' => $totalReminders,
            'dry_run' => $dryRun,
            'specific_days' => $specificDays,
            'contract_id' => $contractId,
            'test_mode' => $testMode,
        ]);

        return Command::SUCCESS;
    }

    /**
     * Send reminders for a specific due date
     */
    private function sendRemindersForDueDate(ContractReportDueDate $dueDate, int $daysUntilDue, bool $dryRun = false, bool $testMode = false, ?string $testEmail = null): int
    {
        $remindersSent = 0;
        $assignedUsers = $dueDate->assignedUsers()->where('active', true)->get();

        if ($assignedUsers->isEmpty()) {
            $this->warn("No active users assigned to due date: {$dueDate->report_name}");
            return 0;
        }

        foreach ($assignedUsers as $user) {
            // Check if reminder was already sent for this interval
            $emailRecord = $dueDate->dueDateEmails()
                ->where('user_id', $user->id)
                ->first();

            if ($emailRecord && $this->wasReminderAlreadySent($emailRecord, $daysUntilDue)) {
                continue;
            }

            if (!$dryRun) {
                try {
                    // Send the notification
                    if ($testMode && $testEmail) {
                        // In test mode, create a temporary user object with test email
                        $testUser = new \App\Models\User();
                        $testUser->email = $testEmail;
                        $testUser->name = "Test Recipient (for {$user->name})";
                        $testUser->notify(new ContractReportReminderNotification($dueDate, $daysUntilDue, $user));
                        $this->line("  [TEST] Sent to test recipient: {$testEmail} (instead of {$user->email})");
                    } else {
                        $user->notify(new ContractReportReminderNotification($dueDate, $daysUntilDue));
                    }
                    
                    // Update email record
                    $this->updateEmailRecord($emailRecord, $daysUntilDue);
                    
                    $remindersSent++;
                    
                } catch (\Exception $e) {
                    $this->error("Failed to send reminder to {$user->email}: " . $e->getMessage());
                    Log::error('Contract reminder email failed', [
                        'user_id' => $user->id,
                        'due_date_id' => $dueDate->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $prefix = $testMode ? "[DRY RUN][TEST]" : "[DRY RUN]";
                $recipient = $testMode ? $testEmail : "{$user->name} ({$user->email})";
                $this->line("  {$prefix} Would send reminder to: {$recipient}");
                $remindersSent++;
            }
        }

        return $remindersSent;
    }

    /**
     * Check if reminder was already sent for this interval
     */
    private function wasReminderAlreadySent($emailRecord, int $daysUntilDue): bool
    {
        if (!$emailRecord || !$emailRecord->reminders_sent) {
            return false;
        }

        $remindersSent = is_array($emailRecord->reminders_sent) 
            ? $emailRecord->reminders_sent 
            : json_decode($emailRecord->reminders_sent, true) ?? [];

        return in_array($daysUntilDue, $remindersSent);
    }

    /**
     * Update email record with sent reminder
     */
    private function updateEmailRecord($emailRecord, int $daysUntilDue): void
    {
        $remindersSent = is_array($emailRecord->reminders_sent) 
            ? $emailRecord->reminders_sent 
            : json_decode($emailRecord->reminders_sent, true) ?? [];

        if (!in_array($daysUntilDue, $remindersSent)) {
            $remindersSent[] = $daysUntilDue;
            $emailRecord->update([
                'reminders_sent' => $remindersSent,
                'last_reminder_sent' => now(),
            ]);
        }
    }
}