<?php

namespace App\Notifications;

use App\Models\Requirement;
use Illuminate\Notifications\Notification;

/**
 * P-17 (Phase 3A): in-app ping when a requirement gets assigned to a
 * user. Database channel only for 3A; Phase 3B adds 'mail' to via().
 *
 * Suppression of self-pings is handled at the dispatch site
 * (Requirement::assign) — this class assumes it should fire if
 * dispatched.
 */
class RequirementAssignedNotification extends Notification
{
    public function __construct(public Requirement $requirement) {}

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
            'job_id' => $job?->id,
            'job_number' => $job?->job_number,
            'job_title' => $job?->job_title,
            'kind' => 'assigned',
            'url' => $job ? route('project.management', $job->id) : null,
        ];
    }
}
