<?php

namespace App\Notifications;

use App\Models\Requirement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Phase 3A: in-app ping when a requirement closes out.
 *
 * Phase 3B Step 2 — subscriber-semantics refinement: the recipient is
 * now the requirement's owner-of-record at completion time (not the
 * project manager), and mail is gated on the completion_email opt-out.
 * Self-completion suppression is handled at the dispatch site
 * (Requirement::notifyOwnerOfCompletion).
 */
class RequirementCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Requirement $requirement,
        public User $completer,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->shouldEmailFor('completion_email')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->requirement->housingJob;
        $url = $job ? route('project.management', $job->id) : url('/');
        $jobLabel = $job ? "{$job->job_title} (#{$job->job_number})" : 'a project';
        $statusLabel = $this->requirement->status === Requirement::STATUS_NOT_APPLICABLE
            ? 'marked not applicable'
            : 'completed';
        $overrideNote = $this->requirement->wasOverridden()
            ? ' (closed without an upload via override)'
            : '';

        return (new MailMessage)
            ->subject("{$this->requirement->title} was {$statusLabel}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->completer->name} {$statusLabel} **{$this->requirement->title}** on {$jobLabel}{$overrideNote}.")
            ->action('Open project', $url)
            ->line('You can opt out of these emails in Settings → Notifications.');
    }

    public function toArray(object $notifiable): array
    {
        $job = $this->requirement->housingJob;

        return [
            'requirement_id' => $this->requirement->id,
            'title' => $this->requirement->title,
            'type' => $this->requirement->type,
            'status' => $this->requirement->status,
            'completer_id' => $this->completer->id,
            'completer_name' => $this->completer->name,
            'overridden' => $this->requirement->wasOverridden(),
            'job_id' => $job?->id,
            'job_number' => $job?->job_number,
            'job_title' => $job?->job_title,
            'kind' => 'completed',
            'url' => $job ? route('project.management', $job->id) : null,
        ];
    }
}
