<?php

namespace App\Notifications;

use App\Models\Requirement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Phase 3A: in-app ping when a requirement closes out.
 *
 * Phase 3B retrofit (Step 1): implements ShouldQueue so dispatch goes
 * through the database queue worker. Step 2 swaps the recipient from
 * "project manager" to "owner-of-record at completion time" and adds
 * the mail channel under the completion_email opt-out.
 *
 * Self-completion suppression is handled at the dispatch site.
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
        return ['database'];
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
