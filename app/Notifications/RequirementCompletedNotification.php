<?php

namespace App\Notifications;

use App\Models\Requirement;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * P-17 (Phase 3A): in-app ping to the project manager when a
 * requirement on her project closes out. Database channel only;
 * Phase 3B adds 'mail' to via().
 *
 * Self-completion suppression (manager closes her own item) is handled
 * at the dispatch site (Requirement::notifyProjectManagerOfCompletion).
 */
class RequirementCompletedNotification extends Notification
{
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
