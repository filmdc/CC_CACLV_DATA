<?php

namespace App\Services;

use App\Models\ProjectMilestone;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3A scope addition: validates that a proposed dependency edge
 * set on a milestone does not introduce a cycle and stays scoped to a
 * single project. Edits replace the milestone's dependency set
 * wholesale, so cycle detection runs against the existing graph
 * EXCLUDING the milestone being edited (otherwise we false-positive on
 * edges that are about to be removed).
 *
 * The validator is a separate class — non-trivial graph algorithm
 * deserves its own home and is independently unit-testable.
 */
class MilestoneDependencyValidator
{
    /**
     * Throws \DomainException if assigning $proposedDependencyIds as the
     * dependency set of $milestone would introduce a self-reference,
     * a cycle (direct or transitive), or include a milestone from a
     * different project.
     *
     * @param  array<int, int|string>  $proposedDependencyIds
     */
    public function validate(ProjectMilestone $milestone, array $proposedDependencyIds): void
    {
        $proposed = array_values(array_unique(array_map('intval', $proposedDependencyIds)));

        if (in_array($milestone->id, $proposed, true)) {
            throw new \DomainException('A milestone cannot depend on itself.');
        }

        $sameProjectIds = ProjectMilestone::query()
            ->where('housing_job_id', $milestone->housing_job_id)
            ->where('id', '!=', $milestone->id)
            ->pluck('id')
            ->all();

        $crossProject = array_diff($proposed, $sameProjectIds);

        if (! empty($crossProject)) {
            throw new \DomainException(
                'Milestone dependencies must live on the same project. '.
                'Cross-project edges are not allowed: '.implode(', ', $crossProject)
            );
        }

        // Build the existing graph EXCLUDING edges out of $milestone, so
        // edits that swap out a dependency don't false-positive against
        // the to-be-removed edge.
        $edges = $this->loadEdges($milestone->housing_job_id, $excludeFrom = $milestone->id);

        // Pretend-add proposed edges and run a reachability check from
        // each candidate back to $milestone->id. A path means the new
        // edge closes a cycle.
        foreach ($proposed as $candidateId) {
            if ($this->hasPath($edges, $candidateId, $milestone->id)) {
                throw new \DomainException(
                    "Adding a dependency on milestone {$candidateId} would create a cycle."
                );
            }
        }
    }

    /**
     * Adjacency map: from-id => list of to-ids, scoped to one project,
     * with all outbound edges from $excludeFrom omitted.
     *
     * @return array<int, array<int, int>>
     */
    protected function loadEdges(int $housingJobId, ?int $excludeFrom = null): array
    {
        $rows = DB::table('milestone_dependencies as md')
            ->join('project_milestones as pm', 'pm.id', '=', 'md.milestone_id')
            ->where('pm.housing_job_id', $housingJobId)
            ->select('md.milestone_id', 'md.depends_on_milestone_id')
            ->get();

        $edges = [];

        foreach ($rows as $row) {
            $from = (int) $row->milestone_id;
            $to = (int) $row->depends_on_milestone_id;

            if ($excludeFrom !== null && $from === $excludeFrom) {
                continue;
            }

            $edges[$from][] = $to;
        }

        return $edges;
    }

    /**
     * DFS from $start through the directed graph; returns true if we
     * can reach $target.
     */
    protected function hasPath(array $edges, int $start, int $target): bool
    {
        if ($start === $target) {
            return true;
        }

        $visited = [];
        $stack = [$start];

        while (! empty($stack)) {
            $node = array_pop($stack);

            if (isset($visited[$node])) {
                continue;
            }
            $visited[$node] = true;

            foreach ($edges[$node] ?? [] as $next) {
                if ($next === $target) {
                    return true;
                }
                if (! isset($visited[$next])) {
                    $stack[] = $next;
                }
            }
        }

        return false;
    }
}
