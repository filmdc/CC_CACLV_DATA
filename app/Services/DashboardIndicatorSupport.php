<?php

namespace App\Services;

use App\Models\FundCode;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use Illuminate\Support\Collection;

/**
 * TODO(3B): date-driven notifications live here, not on the Requirement
 * model. A scheduled job (probably daily) walks the indicator helpers
 * below to find fund_codes crossing the urgent (≤30d) or expired
 * threshold and project_milestones crossing the approaching (≤7d) or
 * overdue threshold, dispatching a notification to the project
 * manager when a row first crosses a new bucket. The dashboard
 * surfaces the same signals visually in 3A; 3B adds the email push.
 */
/**
 * Phase 3A / 7.5: dashboard indicator precomputes.
 *
 * The "blocked milestone" signal would naively iterate each visible
 * requirement → load its milestone → load that milestone's
 * dependencies → load each dependency's requirements to check
 * completion. With 50–100 requirements visible at once, that's
 * hundreds of queries per render. This service computes the set of
 * blocked-milestone IDs in a single query, regardless of how many
 * requirements the dashboard renders.
 *
 * Step 9 (dashboard) calls these helpers once per render and uses
 * O(1) in_array / map lookups per row.
 */
class DashboardIndicatorSupport
{
    /**
     * Returns the set of milestone IDs that are currently "blocked":
     * at least one of their dependencies has at least one requirement
     * whose status is not in (complete, not_applicable). Scoped to
     * milestones on the given housing_jobs.
     *
     * @param  array<int, int>  $housingJobIds
     * @return array<int, int>
     */
    public function blockedMilestoneIds(array $housingJobIds): array
    {
        if (empty($housingJobIds)) {
            return [];
        }

        return ProjectMilestone::query()
            ->whereIn('housing_job_id', $housingJobIds)
            ->whereHas('dependencies.requirements', function ($q) {
                $q->whereNotIn('status', [
                    Requirement::STATUS_COMPLETE,
                    Requirement::STATUS_NOT_APPLICABLE,
                ]);
            })
            ->pluck('id')
            ->all();
    }

    /**
     * Returns the set of fund_code_requirements.id values where
     * is_hard_requirement = true, scoped to fund codes attached to any
     * of the given housing_jobs. The dashboard uses this to surface a
     * "hard outstanding" badge per project in O(1) per requirement
     * row, rather than dereferencing each requirement's
     * source_template_id one-by-one.
     *
     * @param  array<int, int>  $housingJobIds
     * @return array<int, int>
     */
    public function hardRequirementTemplateIds(array $housingJobIds): array
    {
        if (empty($housingJobIds)) {
            return [];
        }

        return \App\Models\FundCodeRequirement::query()
            ->where('is_hard_requirement', true)
            ->whereIn('fund_code_id', function ($q) use ($housingJobIds) {
                $q->select('fund_code_id')
                    ->from('fund_code_housing_job')
                    ->whereIn('housing_job_id', $housingJobIds);
            })
            ->pluck('id')
            ->all();
    }

    /**
     * Returns FundCode rows attached to any of the given housing_jobs,
     * keyed by id. Used by the dashboard to map a requirement's
     * source_id (when source_type=fund_code) to expiration metadata
     * in O(1) per row.
     *
     * @param  array<int, int>  $housingJobIds
     * @return Collection<int, FundCode>
     */
    public function fundCodesAttachedToProjects(array $housingJobIds): Collection
    {
        if (empty($housingJobIds)) {
            return collect();
        }

        return FundCode::query()
            ->whereIn('id', function ($q) use ($housingJobIds) {
                $q->select('fund_code_id')
                    ->from('fund_code_housing_job')
                    ->whereIn('housing_job_id', $housingJobIds);
            })
            ->get(['id', 'code', 'description', 'is_active', 'starts_at', 'expires_at'])
            ->keyBy('id');
    }
}
