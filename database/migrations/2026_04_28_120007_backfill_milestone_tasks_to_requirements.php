<?php

use App\Support\Phase3ABackfillLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * P-18 (Phase 3A): copy each existing milestone_tasks row into the
     * new requirements table as source_type=milestone, type=task.
     *
     * Status mapping:
     *   pending      → outstanding
     *   in_progress  → outstanding
     *   completed    → complete (carry completed_date / approved_at /
     *                            approved_by where present)
     *   overdue      → outstanding (overdue is derived; not stored on
     *                              the new model)
     *
     * Owner resolution: legacy.assigned_to ?? milestone.assigned_to_user_id
     *   ?? housing_job.project_manager_id.
     */
    public function up(): void
    {
        $legacy = DB::table('milestone_tasks as mt')
            ->join('project_milestones as pm', 'pm.id', '=', 'mt.milestone_id')
            ->join('housing_jobs as hj', 'hj.id', '=', 'pm.housing_job_id')
            ->select(
                'mt.id as legacy_id',
                'mt.milestone_id',
                'mt.name',
                'mt.description',
                'mt.status',
                'mt.assigned_to',
                'mt.deadline',
                'mt.approved_at',
                'mt.approved_by',
                'mt.completed_date',
                'mt.created_by',
                'mt.created_at',
                'mt.updated_at',
                'pm.assigned_to_user_id as milestone_assignee',
                'pm.housing_job_id',
                'hj.project_manager_id'
            )
            ->orderBy('mt.id')
            ->get();

        $insertedIds = [];
        $now = now();

        foreach ($legacy as $row) {
            $reqStatus = $row->status === 'completed' ? 'complete' : 'outstanding';

            $owner = $row->assigned_to
                ?? $row->milestone_assignee
                ?? $row->project_manager_id;

            $completedAt = null;
            $completedBy = null;
            $completionNote = null;

            if ($reqStatus === 'complete') {
                $completedAt = $row->approved_at
                    ?? ($row->completed_date ? $row->completed_date.' 00:00:00' : null)
                    ?? $row->updated_at;
                $completedBy = $row->approved_by ?? $row->assigned_to ?? $row->created_by;
                $completionNote = "[BACKFILL-3A] Backfilled from completed milestone_task #{$row->legacy_id}";
            }

            $id = DB::table('requirements')->insertGetId([
                'housing_job_id' => $row->housing_job_id,
                'type' => 'task',
                'title' => $row->name,
                'description' => $row->description,
                'status' => $reqStatus,
                'owner_user_id' => $owner,
                'source_type' => 'milestone',
                'source_id' => $row->milestone_id,
                'source_template_id' => null,
                'source_label_snapshot' => $row->name,
                'milestone_gate_text' => null,
                'completed_at' => $completedAt,
                'completed_by_user_id' => $completedBy,
                'completion_note' => $completionNote,
                'override_reason' => null,
                'overridden_by_user_id' => null,
                'created_by' => $row->created_by,
                'updated_by' => null,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $now,
            ]);

            $insertedIds[] = $id;
        }

        (new Phase3ABackfillLog)->write('milestone_tasks_to_requirements', [
            'requirement_ids' => $insertedIds,
            'count' => count($insertedIds),
        ]);
    }

    public function down(): void
    {
        $section = (new Phase3ABackfillLog)->section('milestone_tasks_to_requirements');

        if ($section === null) {
            throw new \RuntimeException(
                'Phase 3A backfill log is missing milestone_tasks_to_requirements section. '.
                'Restore database/seeders/phase_3a_backfill_ids.json from git before rollback.'
            );
        }

        $ids = $section['requirement_ids'] ?? [];

        if (! empty($ids)) {
            DB::table('requirements')->whereIn('id', $ids)->delete();
        }
    }
};
