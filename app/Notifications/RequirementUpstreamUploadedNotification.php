<?php

namespace App\Notifications;

use App\Models\Requirement;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Phase 3B (Step 1 stub, Step 3 implementation): notifies the owner of
 * a downstream requirement when an attachment lands on an upstream
 * requirement whose source milestone is depended on by the owner's
 * milestone.
 *
 * Step 1 carries the queueable shell + database channel only. Step 3
 * adds the milestone-graph traversal at the upload site, the toMail()
 * body, and the assignment_email opt-out check.
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
        return ['database'];
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
