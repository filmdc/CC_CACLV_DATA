<?php

namespace App\Notifications;

use App\Models\Requirement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Phase 3B Step 3: notifies the owner of a downstream open requirement
 * when an attachment lands on an upstream requirement whose source
 * milestone is depended on by the owner's milestone.
 *
 * Mail channel is gated on the recipient's assignment_email opt-out
 * — uploads upstream of "your" assigned work share the same toggle as
 * new assignments, since both are "your-work" notifications.
 *
 * The trigger site (Requirement::notifyDownstreamOfUpload) handles
 * the graph traversal, status filter, and self-ping suppression.
 */
class RequirementUpstreamUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Requirement $upstreamRequirement,
        public Requirement $downstreamRequirement,
        public User $uploader,
    ) {}

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
        $job = $this->downstreamRequirement->housingJob;
        $url = $job ? route('project.management', $job->id) : url('/');
        $jobLabel = $job ? "{$job->job_title} (#{$job->job_number})" : 'a project';

        return (new MailMessage)
            ->subject("Upstream upload: {$this->upstreamRequirement->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->uploader->name} added an attachment to **{$this->upstreamRequirement->title}** on {$jobLabel}.")
            ->line("This is upstream of your open requirement **{$this->downstreamRequirement->title}**.")
            ->action('Open project', $url)
            ->line('You can opt out of these emails in Settings → Notifications.');
    }

    public function toArray(object $notifiable): array
    {
        $job = $this->downstreamRequirement->housingJob;

        return [
            'kind' => 'upstream_uploaded',
            'upstream_requirement_id' => $this->upstreamRequirement->id,
            'upstream_title' => $this->upstreamRequirement->title,
            'requirement_id' => $this->downstreamRequirement->id,
            'title' => $this->downstreamRequirement->title,
            'uploader_id' => $this->uploader->id,
            'uploader_name' => $this->uploader->name,
            'job_id' => $job?->id,
            'job_number' => $job?->job_number,
            'job_title' => $job?->job_title,
            'url' => $job ? route('project.management', $job->id) : null,
        ];
    }
}
