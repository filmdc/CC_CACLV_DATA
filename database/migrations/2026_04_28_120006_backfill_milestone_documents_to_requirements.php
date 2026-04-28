<?php

use App\Support\Phase3ABackfillLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * P-18 (Phase 3A): copy each existing milestone_documents row into
     * the new requirements table as source_type=milestone, type=document.
     *
     * Status mapping:
     *   not_uploaded     → outstanding
     *   uploaded         → outstanding   (file exists in legacy media but
     *                                    the new model treats user-driven
     *                                    mark-complete as the close path)
     *   pending_review   → outstanding
     *   approved         → complete      (carry approved_at, approved_by)
     *   rejected         → outstanding   (rejection text appended to
     *                                    description for audit)
     *
     * Owner resolution falls through:
     *   legacy.assigned_to
     *     ?? milestone.assigned_to_user_id
     *     ?? housing_job.project_manager_id
     *     ?? null (admin assigns later)
     *
     * Spatie media on the legacy MilestoneDocument is NOT migrated; the
     * old rows stay populated for audit, and 'uploaded'/'pending_review'/
     * 'rejected' requirement rows reset to outstanding so the user
     * explicitly closes them via the new flow (with override or upload).
     */
    public function up(): void
    {
        $legacy = DB::table('milestone_documents as md')
            ->join('project_milestones as pm', 'pm.id', '=', 'md.milestone_id')
            ->join('housing_jobs as hj', 'hj.id', '=', 'pm.housing_job_id')
            ->select(
                'md.id as legacy_id',
                'md.milestone_id',
                'md.document_type',
                'md.description',
                'md.status',
                'md.assigned_to',
                'md.uploaded_date',
                'md.approved_at',
                'md.approved_by',
                'md.rejection_reason',
                'md.created_by',
                'md.created_at',
                'md.updated_at',
                'pm.assigned_to_user_id as milestone_assignee',
                'pm.housing_job_id',
                'hj.project_manager_id'
            )
            ->orderBy('md.id')
            ->get();

        $insertedIds = [];
        $now = now();

        foreach ($legacy as $row) {
            $reqStatus = match ($row->status) {
                'approved' => 'complete',
                default => 'outstanding',
            };

            $description = $row->description;

            if ($row->status === 'rejected' && ! empty(trim((string) $row->rejection_reason))) {
                $note = "[REJECTED: {$row->rejection_reason}]";
                $description = $description ? trim($description)."\n\n".$note : $note;
            }

            $owner = $row->assigned_to
                ?? $row->milestone_assignee
                ?? $row->project_manager_id;

            $completedAt = null;
            $completedBy = null;
            $completionNote = null;

            if ($reqStatus === 'complete') {
                $completedAt = $row->approved_at ?? $row->uploaded_date ?? $row->updated_at;
                $completedBy = $row->approved_by ?? $row->created_by;
                $completionNote = "[BACKFILL-3A] Backfilled from approved milestone_document #{$row->legacy_id}";
            }

            $title = $row->document_type ?: 'Document';

            $id = DB::table('requirements')->insertGetId([
                'housing_job_id' => $row->housing_job_id,
                'type' => 'document',
                'title' => $title,
                'description' => $description,
                'status' => $reqStatus,
                'owner_user_id' => $owner,
                'source_type' => 'milestone',
                'source_id' => $row->milestone_id,
                'source_template_id' => null,
                'source_label_snapshot' => $title,
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

        (new Phase3ABackfillLog)->write('milestone_documents_to_requirements', [
            'requirement_ids' => $insertedIds,
            'count' => count($insertedIds),
        ]);
    }

    public function down(): void
    {
        $section = (new Phase3ABackfillLog)->section('milestone_documents_to_requirements');

        if ($section === null) {
            throw new \RuntimeException(
                'Phase 3A backfill log is missing milestone_documents_to_requirements section. '.
                'Restore database/seeders/phase_3a_backfill_ids.json from git before rollback.'
            );
        }

        $ids = $section['requirement_ids'] ?? [];

        if (! empty($ids)) {
            DB::table('requirements')->whereIn('id', $ids)->delete();
        }
    }
};
