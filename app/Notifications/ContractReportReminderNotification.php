<?php

namespace App\Notifications;

use App\Models\ContractReportDueDate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractReportReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $dueDate;
    protected $daysUntilDue;

    /**
     * Create a new notification instance.
     */
    public function __construct(ContractReportDueDate $dueDate, int $daysUntilDue)
    {
        $this->dueDate = $dueDate;
        $this->daysUntilDue = $daysUntilDue;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->getSubject();
        $greeting = $this->getGreeting();
        $urgencyLevel = $this->getUrgencyLevel();
        
        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($this->getMainMessage())
            ->line('')
            ->line('**Report Details:**')
            ->line("• **Report Name:** {$this->dueDate->report_name}")
            ->line("• **Contract:** {$this->dueDate->contract->title}")
            ->line("• **Program:** {$this->dueDate->contract->program->name ?? 'N/A'}")
            ->line("• **Funder:** {$this->dueDate->contract->funder->name ?? 'N/A'}")
            ->line("• **Due Date:** {$this->dueDate->due_date->format('F j, Y')}")
            ->line("• **Days Remaining:** {$this->daysUntilDue}");

        if ($this->dueDate->description) {
            $mailMessage->line("• **Description:** {$this->dueDate->description}");
        }

        $mailMessage->line('')
            ->line($this->getActionMessage())
            ->action('View Contract Management', $this->getContractUrl())
            ->line('')
            ->line('If you have already submitted this report, please mark it as completed in the system.')
            ->line('')
            ->line('Thank you for your attention to this matter.');

        // Add urgency styling based on days remaining
        if ($urgencyLevel === 'high') {
            $mailMessage->line('')
                ->line('🔴 **HIGH PRIORITY** - Immediate attention required!');
        } elseif ($urgencyLevel === 'medium') {
            $mailMessage->line('')
                ->line('🟡 **MEDIUM PRIORITY** - Please prioritize this task.');
        }

        return $mailMessage;
    }

    /**
     * Get the subject line for the email
     */
    private function getSubject(): string
    {
        $urgency = $this->getUrgencyIndicator();
        
        if ($this->daysUntilDue <= 0) {
            return "{$urgency}OVERDUE: Contract Report - {$this->dueDate->report_name}";
        } elseif ($this->daysUntilDue === 1) {
            return "{$urgency}URGENT: Contract Report Due Tomorrow - {$this->dueDate->report_name}";
        } else {
            return "{$urgency}Contract Report Reminder - {$this->dueDate->report_name} (Due in {$this->daysUntilDue} days)";
        }
    }

    /**
     * Get the greeting for the email
     */
    private function getGreeting(): string
    {
        if ($this->daysUntilDue <= 0) {
            return 'URGENT NOTICE';
        } elseif ($this->daysUntilDue <= 3) {
            return 'Important Reminder';
        } else {
            return 'Hello!';
        }
    }

    /**
     * Get the main message content
     */
    private function getMainMessage(): string
    {
        if ($this->daysUntilDue <= 0) {
            return "**The following contract report is now OVERDUE and requires immediate attention:**";
        } elseif ($this->daysUntilDue === 1) {
            return "**The following contract report is due TOMORROW and requires your immediate attention:**";
        } elseif ($this->daysUntilDue <= 3) {
            return "**The following contract report is due soon and requires your attention:**";
        } else {
            return "This is a friendly reminder that the following contract report is due in {$this->daysUntilDue} days:";
        }
    }

    /**
     * Get the action message
     */
    private function getActionMessage(): string
    {
        if ($this->daysUntilDue <= 0) {
            return 'Please submit this report immediately and update the status in the system.';
        } elseif ($this->daysUntilDue <= 3) {
            return 'Please prioritize the completion of this report.';
        } else {
            return 'Please plan accordingly to ensure timely submission.';
        }
    }

    /**
     * Get the urgency level
     */
    private function getUrgencyLevel(): string
    {
        if ($this->daysUntilDue <= 0) {
            return 'critical';
        } elseif ($this->daysUntilDue <= 3) {
            return 'high';
        } elseif ($this->daysUntilDue <= 7) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get urgency indicator for subject
     */
    private function getUrgencyIndicator(): string
    {
        if ($this->daysUntilDue <= 0) {
            return '[OVERDUE] ';
        } elseif ($this->daysUntilDue <= 1) {
            return '[URGENT] ';
        } elseif ($this->daysUntilDue <= 3) {
            return '[HIGH PRIORITY] ';
        } else {
            return '';
        }
    }

    /**
     * Get the URL to the contract management page
     */
    private function getContractUrl(): string
    {
        return url('/contract-management');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contract_report_due_date_id' => $this->dueDate->id,
            'report_name' => $this->dueDate->report_name,
            'contract_title' => $this->dueDate->contract->title,
            'due_date' => $this->dueDate->due_date->toDateString(),
            'days_until_due' => $this->daysUntilDue,
            'urgency_level' => $this->getUrgencyLevel(),
        ];
    }
}