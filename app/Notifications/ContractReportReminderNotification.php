<?php

namespace App\Notifications;

use App\Models\ContractReportDueDate;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractReportReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $dueDate;
    protected $daysUntilDue;
    protected $originalRecipient;

    /**
     * Create a new notification instance.
     */
    public function __construct(ContractReportDueDate $dueDate, int $daysUntilDue, ?User $originalRecipient = null)
    {
        $this->dueDate = $dueDate;
        $this->daysUntilDue = $daysUntilDue;
        $this->originalRecipient = $originalRecipient; // For test mode
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
        // Check if we should use custom template
        $useCustomTemplate = !empty(SiteSetting::get('contract_reminder_email_template'));
        
        if ($useCustomTemplate) {
            return $this->buildCustomEmail($notifiable);
        }
        
        // Fallback to default template
        return $this->buildDefaultEmail($notifiable);
    }
    
    /**
     * Build email using custom template from settings
     */
    private function buildCustomEmail(object $notifiable): MailMessage
    {
        $subject = SiteSetting::get('contract_reminder_email_subject', 'Contract Report Reminder: {{report_name}} - Due in {{days_until}} days');
        $template = SiteSetting::get('contract_reminder_email_template', '');
        
        // Replace variables
        $variables = $this->getTemplateVariables($notifiable);
        
        foreach ($variables as $key => $value) {
            $subject = str_replace($key, $value, $subject);
            $template = str_replace($key, $value, $template);
        }
        
        // Add urgency prefix
        $subject = $this->getUrgencyIndicator() . $subject;
        
        // Add test mode prefix if applicable
        if (SiteSetting::get('contract_reminder_test_mode', '0') === '1') {
            $subject = '[TEST] ' . $subject;
        }
        
        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting($this->getGreeting());
            
        // Parse template into lines
        $lines = explode("\n", $template);
        foreach ($lines as $line) {
            $mailMessage->line($line);
        }
        
        $mailMessage->action('View Contract Management', $this->getContractUrl());
        
        return $mailMessage;
    }
    
    /**
     * Build default email template
     */
    private function buildDefaultEmail(object $notifiable): MailMessage
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
            ->line("• **Report Name:** " . strip_tags($this->dueDate->report_name ?? ucfirst($this->dueDate->report_type) . ' Report'))
            ->line("• **Contract:** {$this->dueDate->contract->title}")
            ->line("• **Program:** {$this->dueDate->contract->program->name ?? 'N/A'}")
            ->line("• **Funder:** {$this->dueDate->contract->funder->name ?? 'N/A'}")
            ->line("• **Due Date:** {$this->dueDate->due_date->format('F j, Y')}")
            ->line("• **Days Remaining:** {$this->daysUntilDue}");

        if ($this->dueDate->description) {
            $mailMessage->line("• **Description:** " . strip_tags($this->dueDate->description));
        }

        $mailMessage->line('')
            ->line($this->getActionMessage())
            ->action('View Contract Management', $this->getContractUrl())
            ->line('')
            ->line('If you have already submitted this report, please mark it as completed in the system.')
            ->line('')
            ->line('Thank you for your attention to this matter.');

        // Add urgency styling based on days remaining
        if ($urgencyLevel === 'high' || $urgencyLevel === 'critical') {
            $mailMessage->line('')
                ->line('🔴 **HIGH PRIORITY** - Immediate attention required!');
        } elseif ($urgencyLevel === 'medium') {
            $mailMessage->line('')
                ->line('🟡 **MEDIUM PRIORITY** - Please prioritize this task.');
        }

        return $mailMessage;
    }
    
    /**
     * Get template variables for replacement
     */
    private function getTemplateVariables(object $notifiable): array
    {
        // Use original recipient info if in test mode
        $recipientName = $this->originalRecipient ? $this->originalRecipient->name : $notifiable->name;
        
        return [
            '{{recipient_name}}' => $recipientName,
            '{{report_name}}' => strip_tags($this->dueDate->report_name ?? ucfirst($this->dueDate->report_type) . ' Report'),
            '{{contract_title}}' => $this->dueDate->contract->title,
            '{{program}}' => $this->dueDate->contract->program->name ?? 'N/A',
            '{{funder}}' => $this->dueDate->contract->funder->name ?? 'N/A',
            '{{due_date}}' => $this->dueDate->due_date->format('F j, Y'),
            '{{days_until}}' => (string) $this->daysUntilDue,
            '{{description}}' => strip_tags($this->dueDate->description ?? ''),
            '{{site_name}}' => SiteSetting::get('site_name', 'CAP TRACK'),
        ];
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