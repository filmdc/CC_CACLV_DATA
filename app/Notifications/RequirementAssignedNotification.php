<?php

namespace App\Notifications;

use App\Models\Requirement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Phase 3A: in-app ping when a requirement gets assigned to a user.
 *
 * Phase 3B retrofit (Step 1): implements ShouldQueue so dispatch goes
 * through the database queue worker (deploy/systemd/cc_caclv_data-queue.service).
 * The mail channel is wired in Step 2 alongside the per-user opt-out.
 *
 * Suppression of self-pings is handled at the dispatch site
 * (Requirement::assign) — this class assumes it should fire if
 * dispatched.
 */
class RequirementAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
