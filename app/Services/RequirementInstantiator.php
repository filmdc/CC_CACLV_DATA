<?php

namespace App\Services;

use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\User;
use App\Notifications\RequirementAssignedNotification;
use Illuminate\Support\Collection;

/**
 * Phase 3A: single entry point for creating Requirement rows, regardless
 * of source. Each method picks safe defaults for owner_user_id, fires a
 * RequirementAssignedNotification if the chosen owner is not the actor,
 * and idempotency-guards where instantiation is template-driven so
 * repeat hooks don't create duplicates.
 *
 * Copy-on-instantiate is enforced here: source_label_snapshot captures
 * the template's label at the moment of creation. Future edits to the
 * upstream template do not propagate.
 */
class RequirementInstantiator
{
    /**
     * For every FundCodeRequirement template on the fund code, create
     * one Requirement on the project. Idempotent: skips templates that
     * already have a corresponding row on this project.
     *
     * Owner defaults to the project's project_manager_id. The actor is
     * the user who triggered instantiation (e.g., the admin attaching
     * the fund code) — used solely for self-notification suppression.
     *
     * @return Collection<int, Requirement>
     */
    public function forFundCode(HousingJob $job, FundCode $fundCode, ?User $actor = null): Collection
    {
        $existingTemplateIds = Requirement::query()
            ->where('housing_job_id', $job->id)
            ->where('source_type', Requirement::SOURCE_FUND_CODE)
            ->whereNotNull('source_template_id')
            ->pluck('source_template_id')
            ->all();

        $created = collect();

        // P-39b (addendum D-1): project-execution requirements draw only
        // from project-phase templates. Eligibility-phase templates are
        // intake-only and flow through IntakeRequirementInstantiator.
        foreach ($fundCode->requirements()->where('phase', 'project')->ordered()->get() as $template) {
            if (in_array($template->id, $existingTemplateIds, true)) {
                continue;
            }

            $req = $this->insert($job, [
                'type' => $template->type ?? Requirement::TYPE_TASK,
                'title' => $template->label,
                'description' => $template->description,
                'source_type' => Requirement::SOURCE_FUND_CODE,
                'source_id' => $fundCode->id,
                'source_template_id' => $template->id,
                'source_label_snapshot' => $template->label,
                'owner_user_id' => $job->project_manager_id,
            ], $actor);

            $created->push($req);
        }

        return $created;
    }

    /**
     * Create a single milestone-bound requirement. Used by the milestone
     * tab's task / document creation UI (Step 6) and by tests. Owner
     * cascades milestone.assigned_to_user_id ?? job.project_manager_id
     * if not given.
     */
    public function forMilestone(
        ProjectMilestone $milestone,
        string $type,
        string $title,
        User $actor,
        ?User $owner = null,
        array $extras = []
    ): Requirement {
        $job = $milestone->housingJob;

        $resolvedOwnerId = $owner?->id
            ?? $milestone->assigned_to_user_id
            ?? $job->project_manager_id;

        return $this->insert($job, array_merge([
            'type' => $type,
            'title' => $title,
            'source_type' => Requirement::SOURCE_MILESTONE,
            'source_id' => $milestone->id,
            'source_label_snapshot' => $title,
            'owner_user_id' => $resolvedOwnerId,
        ], $extras), $actor);
    }

    /**
     * Create a freestanding ad-hoc requirement. Owner defaults to the
     * project manager unless explicitly given.
     */
    public function adHoc(
        HousingJob $job,
        string $type,
        string $title,
        User $actor,
        ?User $owner = null,
        array $extras = []
    ): Requirement {
        $resolvedOwnerId = $owner?->id ?? $job->project_manager_id;

        return $this->insert($job, array_merge([
            'type' => $type,
            'title' => $title,
            'source_type' => Requirement::SOURCE_AD_HOC,
            'source_id' => null,
            'source_template_id' => null,
            'source_label_snapshot' => null,
            'owner_user_id' => $resolvedOwnerId,
        ], $extras), $actor);
    }

    /**
     * Shared insert path. Sets default status, stamps created_by /
     * updated_by from the actor, and fires the assignment notification
     * if the resolved owner is not the actor.
     */
    protected function insert(HousingJob $job, array $attributes, ?User $actor): Requirement
    {
        $req = new Requirement(array_merge([
            'status' => Requirement::STATUS_OUTSTANDING,
        ], $attributes));

        $req->housing_job_id = $job->id;
        $req->created_by = $actor?->id;
        $req->updated_by = $actor?->id;
        $req->save();

        $this->maybeNotifyAssignment($req, $actor);

        return $req;
    }

    protected function maybeNotifyAssignment(Requirement $req, ?User $actor): void
    {
        if ($req->owner_user_id === null) {
            return;
        }

        if ($actor !== null && $actor->id === $req->owner_user_id) {
            return;
        }

        $owner = $req->owner;

        if ($owner === null) {
            return;
        }

        $owner->notify(new RequirementAssignedNotification($req));
    }
}
