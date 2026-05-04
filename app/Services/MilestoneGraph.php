<?php

namespace App\Services;

use App\Models\ProjectMilestone;
use App\Models\Requirement;
use Illuminate\Support\Collection;

/**
 * Phase 3B Step 3: read-side graph helper for the upstream-upload
 * notification trigger.
 *
 * The trigger fires when an attachment is added to an upstream
 * requirement; recipients are owners of open requirements on milestones
 * that depend on the upstream's source milestone.
 *
 * Single-hop only — direct dependents, not transitive. Phase 4 may
 * extend to transitive dependents if Kaitlyn surfaces a use case; it's
 * a one-line BFS swap if so.
 *
 * Cycle detection is MilestoneDependencyValidator's job; this service
 * trusts the persisted edges are well-formed because edits go through
 * that validator before they land.
 */
class MilestoneGraph
{
    /**
     * Resolve the (downstream-requirement, owner) pairs that should be
     * notified when an attachment is added to $upstream.
     *
     * Returns an empty Collection when:
     *   - $upstream is not source_type='milestone' (fund-code/ad-hoc
     *     have no graph),
     *   - $upstream's source_id is null (the milestone was deleted via
     *     the keep-branch — see PHASE_3A_NOTES.md),
     *   - $upstream's source milestone has no dependents,
     *   - none of the dependent milestones have open requirements with
     *     non-null owners.
     *
     * Each returned row is shaped as ['requirement' => Requirement,
     * 'owner' => User]. Callers are responsible for filtering out the
     * uploader (no self-ping) and respecting per-user opt-outs (handled
     * inside the notification's via() against User::shouldEmailFor).
     *
     * @return Collection<int, array{requirement: Requirement, owner: \App\Models\User}>
     */
    public function downstreamOwnersFor(Requirement $upstream): Collection
    {
        if ($upstream->source_type !== Requirement::SOURCE_MILESTONE) {
            return collect();
        }

        if ($upstream->source_id === null) {
            return collect();
        }

        $sourceMilestone = ProjectMilestone::find($upstream->source_id);

        if ($sourceMilestone === null) {
            // Defensive — source_id present but milestone hard-deleted.
            // 3A's keep-branch nulls source_id, so this branch is rare,
            // but a stale id would have us traverse a ghost.
            return collect();
        }

        $dependentMilestoneIds = $sourceMilestone->dependents()
            ->pluck('project_milestones.id');

        if ($dependentMilestoneIds->isEmpty()) {
            return collect();
        }

        return Requirement::query()
            ->whereIn('source_id', $dependentMilestoneIds)
            ->where('source_type', Requirement::SOURCE_MILESTONE)
            ->where('status', Requirement::STATUS_OUTSTANDING)
            ->whereNotNull('owner_user_id')
            ->with('owner')
            ->get()
            ->filter(fn (Requirement $req) => $req->owner !== null)
            ->map(fn (Requirement $req) => [
                'requirement' => $req,
                'owner' => $req->owner,
            ])
            ->values();
    }
}
