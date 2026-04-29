<?php

namespace App\Services;

use App\Models\FundCode;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use Illuminate\Support\Collection;

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
