<?php

namespace App\Console\Commands;

use App\Models\ContractReportDueDate;
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
                            {--days= : Check for specific days until due date}';

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
        
        $this->info('Starting contract report reminder check...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No emails will be sent');
        }

        // Get all active due dates that haven't been completed
        $dueDates = ContractReportDueDate::with(['contract.program', 'contract.funder', 'assignedUsers'])
            ->where('active', true)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->get();

        $totalReminders = 0;
        $todayDate = now()->startOfDay();

        foreach ($dueDates as $dueDate) {
            $daysUntilDue = $todayDate->diffInDays($dueDate->due_date, false);
            
            // If specific days filter is provided, only check for that interval
            if ($specificDays !== null && $daysUntilDue != $specificDays) {
                continue;
            }

            // Check if we should send reminder for this due date
            if ($dueDate->shouldSendReminder($daysUntilDue)) {
                $remindersSent = $this->sendRemindersForDueDate($dueDate, $daysUntilDue, $dryRun);
                $totalReminders += $remindersSent;
                
                $this->line(sprintf(
                    'Due Date: %s (%d days) - %s - Sent %d reminders',
                    $dueDate->report_name,
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
        ]);

        return Command::SUCCESS;
    }

    /**
     * Send reminders for a specific due date
     */
    private function sendRemindersForDueDate(ContractReportDueDate $dueDate, int $daysUntilDue, bool $dryRun = false): int
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
                    $user->notify(new ContractReportReminderNotification($dueDate, $daysUntilDue));
                    
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
                $this->line("  [DRY RUN] Would send reminder to: {$user->name} ({$user->email})");
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