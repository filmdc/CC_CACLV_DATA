<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ContractUtilizationThresholdNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Contract $contract;

    protected int $threshold;

    protected Collection $recentExpenses;

    protected ?User $originalRecipient;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        Contract $contract,
        int $threshold,
        Collection $recentExpenses,
        ?User $originalRecipient = null
    ) {
        $this->contract = $contract;
        $this->threshold = $threshold;
        $this->recentExpenses = $recentExpenses;
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
        $useCustomTemplate = ! empty(SiteSetting::get('utilization_email_template'));

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
        $subject = SiteSetting::get(
            'utilization_email_subject',
            '[{{urgency}}] Contract Budget Alert: {{contract_title}} - {{threshold}}% Remaining'
        );
        $template = SiteSetting::get('utilization_email_template', '');

        // Replace variables
        $variables = $this->getTemplateVariables($notifiable);

        foreach ($variables as $key => $value) {
            $subject = str_replace($key, $value, $subject);
            $template = str_replace($key, $value, $template);
        }

        // Handle recent expenses placeholder
        if (str_contains($template, '{{#if recent_expenses}}')) {
            if ($this->recentExpenses->isNotEmpty()) {
                $expensesList = $this->formatRecentExpenses();
                $template = preg_replace(
                    '/\{\{#if recent_expenses\}\}(.*?)\{\{\/if\}\}/s',
                    '$1',
                    $template
                );
                $template = str_replace('{{recent_expenses}}', $expensesList, $template);
            } else {
                $template = preg_replace(
                    '/\{\{#if recent_expenses\}\}.*?\{\{\/if\}\}/s',
                    '',
                    $template
                );
            }
        }

        // Add test mode prefix if applicable
        if (SiteSetting::get('utilization_test_mode', '0') === '1') {
            $subject = '[TEST] '.$subject;
        }

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting($this->getGreeting());

        // Parse template - handle HTML properly
        $mailMessage->line(new \Illuminate\Support\HtmlString($template));

        $mailMessage->action('View Contract Utilization', $this->getDashboardUrl());

        return $mailMessage;
    }

    /**
     * Build default email template
     */
    private function buildDefaultEmail(object $notifiable): MailMessage
    {
        $subject = $this->getSubject();
        $urgencyLevel = $this->getUrgencyLevel();

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting($this->getGreeting())
            ->line($this->getMainMessage())
            ->line('')
            ->line('**Contract Details:**')
            ->line("• **Contract:** {$this->contract->title}")
            ->line("• **Contract Number:** ".($this->contract->contract_number ?? 'N/A'))
            ->line("• **Grant Code:** ".($this->contract->grant_code ?? 'N/A'))
            ->line("• **Program:** ".($this->contract->program->name ?? 'N/A'))
            ->line("• **Funder:** ".($this->contract->funder->name ?? 'N/A'))
            ->line('')
            ->line('**Budget Status:**')
            ->line('• **Total Budget:** $'.number_format($this->contract->amount, 2))
            ->line('• **Total Spent:** $'.number_format($this->contract->total_spent, 2))
            ->line('• **Remaining:** $'.number_format($this->contract->remaining_budget, 2))
            ->line("• **Utilization:** {$this->contract->utilization_percentage}%")
            ->line("• **Threshold Crossed:** {$this->threshold}% remaining");

        // Add recent expenses if available
        if ($this->recentExpenses->isNotEmpty()) {
            $mailMessage->line('')
                ->line('**Recent Expenses:**');

            foreach ($this->recentExpenses->take(5) as $expense) {
                $date = $expense->expense_date?->format('M j, Y') ?? 'N/A';
                $amount = '$'.number_format($expense->amount, 2);
                $desc = $expense->description ?? 'No description';
                $mailMessage->line("• {$date}: {$amount} - ".substr($desc, 0, 50));
            }
        }

        $mailMessage->line('')
            ->line($this->getActionMessage())
            ->action('View Contract Utilization', $this->getDashboardUrl());

        // Add urgency styling based on threshold
        if ($urgencyLevel === 'critical') {
            $mailMessage->line('')
                ->line('🔴 **CRITICAL** - Contract funds nearly exhausted!');
        } elseif ($urgencyLevel === 'high') {
            $mailMessage->line('')
                ->line('🟠 **HIGH PRIORITY** - Immediate attention required!');
        } elseif ($urgencyLevel === 'medium') {
            $mailMessage->line('')
                ->line('🟡 **MEDIUM PRIORITY** - Please review budget status.');
        }

        return $mailMessage;
    }

    /**
     * Get template variables for replacement
     */
    private function getTemplateVariables(object $notifiable): array
    {
        $recipientName = $this->originalRecipient
            ? $this->originalRecipient->name
            : $notifiable->name;

        $urgency = $this->getUrgencyLabel();
        $urgencyMessage = $this->getUrgencyMessage();
        $remainingColor = $this->threshold <= 10 ? '#dc2626' : ($this->threshold <= 25 ? '#f59e0b' : '#16a34a');

        return [
            '{{recipient_name}}' => $recipientName,
            '{{contract_title}}' => $this->contract->title,
            '{{contract_number}}' => $this->contract->contract_number ?? 'N/A',
            '{{grant_code}}' => $this->contract->grant_code ?? 'N/A',
            '{{program}}' => $this->contract->program->name ?? 'N/A',
            '{{funder}}' => $this->contract->funder->name ?? 'N/A',
            '{{threshold}}' => (string) $this->threshold,
            '{{utilization_percentage}}' => (string) $this->contract->utilization_percentage,
            '{{total_budget}}' => number_format($this->contract->amount, 2),
            '{{total_spent}}' => number_format($this->contract->total_spent, 2),
            '{{remaining_amount}}' => number_format($this->contract->remaining_budget, 2),
            '{{remaining_color}}' => $remainingColor,
            '{{urgency}}' => $urgency,
            '{{urgency_message}}' => $urgencyMessage,
            '{{site_name}}' => SiteSetting::get('site_name', 'CAP TRACK'),
        ];
    }

    /**
     * Format recent expenses as HTML list items
     */
    private function formatRecentExpenses(): string
    {
        $items = [];
        foreach ($this->recentExpenses->take(5) as $expense) {
            $date = $expense->expense_date?->format('M j, Y') ?? 'N/A';
            $amount = '$'.number_format($expense->amount, 2);
            $desc = $expense->description ?? 'No description';
            $items[] = "<li>{$date}: {$amount} - ".htmlspecialchars(substr($desc, 0, 50)).'</li>';
        }

        return implode("\n", $items);
    }

    /**
     * Get the subject line for the email
     */
    private function getSubject(): string
    {
        $urgency = $this->getUrgencyIndicator();
        $testPrefix = SiteSetting::get('utilization_test_mode', '0') === '1' ? '[TEST] ' : '';

        if ($this->threshold <= 0) {
            return "{$testPrefix}{$urgency}DEPLETED: Contract Budget - {$this->contract->title}";
        }

        if ($this->threshold <= 5) {
            return "{$testPrefix}{$urgency}CRITICAL: Contract Budget at {$this->threshold}% - {$this->contract->title}";
        }

        return "{$testPrefix}{$urgency}Contract Budget Alert - {$this->contract->title} ({$this->threshold}% Remaining)";
    }

    /**
     * Get the greeting for the email
     */
    private function getGreeting(): string
    {
        if ($this->threshold <= 5) {
            return 'URGENT NOTICE';
        }

        if ($this->threshold <= 25) {
            return 'Important Alert';
        }

        return 'Budget Alert';
    }

    /**
     * Get the main message content
     */
    private function getMainMessage(): string
    {
        if ($this->threshold <= 0) {
            return "**The contract \"{$this->contract->title}\" has been fully utilized (100% of budget spent).**";
        }

        if ($this->threshold <= 5) {
            return "**CRITICAL: The contract \"{$this->contract->title}\" has only {$this->threshold}% of its budget remaining!**";
        }

        if ($this->threshold <= 25) {
            return "**WARNING: The contract \"{$this->contract->title}\" has reached {$this->contract->utilization_percentage}% utilization with only {$this->threshold}% remaining.**";
        }

        return "The contract \"{$this->contract->title}\" has reached a budget threshold. Currently at {$this->contract->utilization_percentage}% utilization with {$this->threshold}% remaining.";
    }

    /**
     * Get the action message
     */
    private function getActionMessage(): string
    {
        if ($this->threshold <= 5) {
            return 'Please review remaining budget allocations immediately.';
        }

        if ($this->threshold <= 25) {
            return 'Please review budget utilization and plan accordingly.';
        }

        return 'Please monitor spending on this contract.';
    }

    /**
     * Get the urgency level
     */
    private function getUrgencyLevel(): string
    {
        if ($this->threshold <= 5) {
            return 'critical';
        }

        if ($this->threshold <= 10) {
            return 'high';
        }

        if ($this->threshold <= 25) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get urgency label for template
     */
    private function getUrgencyLabel(): string
    {
        return match ($this->getUrgencyLevel()) {
            'critical' => 'CRITICAL',
            'high' => 'HIGH PRIORITY',
            'medium' => 'WARNING',
            default => 'INFO',
        };
    }

    /**
     * Get urgency message for template
     */
    private function getUrgencyMessage(): string
    {
        return match ($this->getUrgencyLevel()) {
            'critical' => 'This contract requires immediate attention - budget nearly exhausted!',
            'high' => 'This contract is approaching budget limits and requires prompt review.',
            'medium' => 'This contract has crossed a budget threshold and should be reviewed.',
            default => 'This is an informational notice about contract budget utilization.',
        };
    }

    /**
     * Get urgency indicator for subject
     */
    private function getUrgencyIndicator(): string
    {
        return match ($this->getUrgencyLevel()) {
            'critical' => '[CRITICAL] ',
            'high' => '[URGENT] ',
            'medium' => '[WARNING] ',
            default => '',
        };
    }

    /**
     * Get the URL to the utilization dashboard
     */
    private function getDashboardUrl(): string
    {
        return url('/contract-utilization');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contract_id' => $this->contract->id,
            'contract_title' => $this->contract->title,
            'threshold' => $this->threshold,
            'utilization_percentage' => $this->contract->utilization_percentage,
            'remaining_amount' => $this->contract->remaining_budget,
            'urgency_level' => $this->getUrgencyLevel(),
        ];
    }
}
