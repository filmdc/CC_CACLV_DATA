<?php

namespace App\Notifications;

use App\Models\Requirement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Phase 3A: in-app ping when a requirement gets assigned to a user.
 *
 * Phase 3B Step 2: adds mail channel under the assignment_email opt-out
 * (which also gates RequirementUpstreamUploaded — both are
 * "your-work" notifications and share a single user toggle).
 *
 * Self-assignment suppression is handled at the dispatch site
 * (Requirement::assign).
 */
class RequirementAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Requirement $requirement) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable instanceof User && $notifiable->shouldEmailFor('assignment_email')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $job = $this->requirement->housingJob;
        $url = $job ? route('project.management', $job->id) : url('/');
        $jobLabel = $job ? "{$job->job_title} (#{$job->job_number})" : 'a project';
        $typeLabel = $this->requirement->type === Requirement::TYPE_DOCUMENT ? 'document' : 'task';

        return (new MailMessage)
            ->subject("New {$typeLabel} assigned: {$this->requirement->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A {$typeLabel} on {$jobLabel} has been assigned to you.")
            ->line("**{$this->requirement->title}**")
            ->lineIf(! empty($this->requirement->description), $this->requirement->description ?? '')
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
            'job_id' => $job?->id,
            'job_number' => $job?->job_number,
            'job_title' => $job?->job_title,
            'kind' => 'assigned',
            'url' => $job ? route('project.management', $job->id) : null,
        ];
    }
}
