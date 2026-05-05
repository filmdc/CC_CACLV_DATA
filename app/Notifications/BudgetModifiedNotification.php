<?php

namespace App\Notifications;

use App\Models\BudgetModification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Phase 3B Step 7 — fires when a ProjectBudget.budgeted_amount is
 * modified (increase or decrease).
 *
 * Recipients come from the over_budget_email_recipients table; each
 * recipient is a User. Delivery routing:
 *   - database: ALWAYS fires for users on the recipient list. The bell
 *     icon surfaces these to anyone who logs in.
 *   - mail: gated on the recipient's over_budget_email category opt-
 *     out via User::shouldEmailFor.
 *
 * The opt-out for non-recipients is a no-op (they never get pinged
 * regardless), as documented on the settings page.
 */
class BudgetModifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BudgetModification $modification) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->shouldEmailFor('over_budget_email')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $budget = $this->modification->budget;
        $job = $budget?->housingJob;
        $url = $job ? route('project.management', $job->id) : url('/');
        $jobLabel = $job ? "{$job->job_title} (#{$job->job_number})" : 'a project';
        $direction = $this->modification->direction();
        $directionLabel = $direction === 'increase' ? 'increased' : ($direction === 'decrease' ? 'decreased' : 'modified');
        $modifierName = $this->modification->modifier?->name ?? 'A user';
        $oldFormatted = '$'.number_format((float) $this->modification->old_amount, 2);
        $newFormatted = '$'.number_format((float) $this->modification->new_amount, 2);
        $category = $budget?->category ?? 'a budget category';

        return (new MailMessage)
            ->subject("Budget {$directionLabel}: {$category} on {$jobLabel}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$modifierName} {$directionLabel} the budget for **{$category}** on {$jobLabel}.")
            ->line("**Previous:** {$oldFormatted}")
            ->line("**New:** {$newFormatted}")
            ->line("**Comment:** {$this->modification->comment}")
            ->action('Open project', $url)
            ->line('You are receiving this because you are on the over-budget recipient list. You can opt out of these emails in Settings → Notifications.');
    }

    public function toArray(object $notifiable): array
    {
        $budget = $this->modification->budget;
        $job = $budget?->housingJob;

        return [
            'kind' => 'budget_modified',
            'modification_id' => $this->modification->id,
            'project_budget_id' => $this->modification->project_budget_id,
            'category' => $budget?->category,
            'old_amount' => (float) $this->modification->old_amount,
            'new_amount' => (float) $this->modification->new_amount,
            'comment' => $this->modification->comment,
            'modifier_id' => $this->modification->modified_by_user_id,
            'modifier_name' => $this->modification->modifier?->name,
            'job_id' => $job?->id,
            'job_number' => $job?->job_number,
            'job_title' => $job?->job_title,
            'url' => $job ? route('project.management', $job->id) : null,
        ];
    }
}
