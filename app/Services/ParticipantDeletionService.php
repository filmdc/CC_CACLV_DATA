<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Hard-deletes a participant and every record associated with them, after writing a
 * complete JSON snapshot to storage and an activity-log entry so the action is
 * auditable and recoverable by re-import.
 *
 * Most associated tables cascade on delete (program_enrollments, service_enrollments,
 * properties, housing_jobs + their children, potential_duplicates). The two exceptions
 * are handled explicitly here:
 *   - communication_logs reference participant_id with SET NULL, so they are deleted
 *     explicitly (their children cascade).
 *   - taggables is a polymorphic pivot with no FK, so the participant's tag links are
 *     removed explicitly.
 *
 * Co-member safety: if the participant heads a household that still has other members,
 * the caller must resolve the situation by passing one of:
 *   - ['promote_to_head_id' => <member id>] to promote a member before deleting, or
 *   - ['force' => true] to delete anyway and leave the household without a head.
 * Otherwise a HouseholdDecisionRequiredException is thrown so the UI can prompt.
 */
class ParticipantDeletionService
{
    /**
     * @return array{
     *     household_action: string,
     *     other_members: \Illuminate\Support\Collection,
     *     is_head: bool
     * }
     *
     * Inspect the household impact before deleting, so the UI can decide whether to prompt.
     */
    public function inspect(Participant $participant): array
    {
        $otherMembers = $participant->household_id
            ? Participant::where('household_id', $participant->household_id)
                ->where('id', '!=', $participant->id)
                ->get()
            : collect();

        $needsDecision = $participant->is_head_of_household && $otherMembers->isNotEmpty();

        return [
            'is_head' => (bool) $participant->is_head_of_household,
            'other_members' => $otherMembers,
            'needs_decision' => $needsDecision,
        ];
    }

    /**
     * Count every record that will be removed, for a pre-delete preview.
     *
     * @return array<string,int>
     */
    public function previewCounts(Participant $participant): array
    {
        $id = $participant->id;

        return [
            'program_enrollments' => DB::table('program_enrollments')->where('participant_id', $id)->count(),
            'service_enrollments' => DB::table('service_enrollments')->where('participant_id', $id)->count(),
            'properties' => DB::table('properties')->where('participant_id', $id)->count(),
            'housing_jobs' => DB::table('housing_jobs')->where('participant_id', $id)->count(),
            'communication_logs' => DB::table('communication_logs')->where('participant_id', $id)->count(),
            'potential_duplicates' => DB::table('potential_duplicates')->where('matched_participant_id', $id)->count(),
            'tags' => DB::table('taggables')->where('taggable_type', Participant::class)->where('taggable_id', $id)->count(),
        ];
    }

    /**
     * @param  array{promote_to_head_id?:int|null, force?:bool}  $options
     * @return array<string,mixed> Summary including the snapshot path.
     */
    public function delete(Participant $participant, array $options = [], ?int $userId = null): array
    {
        $inspection = $this->inspect($participant);

        if ($inspection['needs_decision']) {
            $promoteId = $options['promote_to_head_id'] ?? null;
            $force = $options['force'] ?? false;

            if (! $promoteId && ! $force) {
                throw new HouseholdDecisionRequiredException(
                    'This participant heads a household with other members. Choose to promote a new head, delete anyway, or cancel.'
                );
            }
        }

        return DB::transaction(function () use ($participant, $options, $userId) {
            $snapshot = $this->buildSnapshot($participant);
            $snapshotPath = $this->writeSnapshot($participant, $snapshot, $userId);

            $householdId = $participant->household_id;

            // Promote a new head before deletion if requested.
            $promoteId = $options['promote_to_head_id'] ?? null;
            if ($promoteId) {
                $newHead = Participant::where('household_id', $householdId)
                    ->where('id', $promoteId)
                    ->where('id', '!=', $participant->id)
                    ->first();

                if (! $newHead) {
                    throw new \InvalidArgumentException('Selected member to promote is not part of this household.');
                }

                $newHead->update(['is_head_of_household' => true]);
            }

            // Explicitly remove the records that would not cascade-delete on their own.
            $deletedCommLogs = $this->deleteCommunicationLogs($participant);
            $this->deleteTagLinks($participant);

            // The participant row cascades to enrollments, properties, housing jobs
            // (and their children), and potential_duplicates.
            $participant->delete();

            // Clean up the household if it is now empty.
            $householdDeleted = false;
            if ($householdId && Participant::where('household_id', $householdId)->doesntExist()) {
                DB::table('taggables')->where('taggable_type', Household::class)->where('taggable_id', $householdId)->delete();
                Household::where('id', $householdId)->delete();
                $householdDeleted = true;
            }

            $summary = [
                'participant_id' => $snapshot['participant']['id'],
                'participant_name' => trim(($snapshot['participant']['first_name'] ?? '').' '.($snapshot['participant']['last_name'] ?? '')),
                'counts' => $snapshot['counts'],
                'communication_logs_deleted' => $deletedCommLogs,
                'household_deleted' => $householdDeleted,
                'head_promoted_participant_id' => $promoteId,
                'snapshot_path' => $snapshotPath,
            ];

            activity('participant_delete')
                ->causedBy($userId ? \App\Models\User::find($userId) : auth()->user())
                ->withProperties($summary)
                ->log("Deleted participant #{$summary['participant_id']} ({$summary['participant_name']}) and all associated records");

            Log::warning('Participant hard-deleted with associated records', $summary);

            return $summary;
        });
    }

    private function deleteCommunicationLogs(Participant $participant): int
    {
        $logIds = DB::table('communication_logs')->where('participant_id', $participant->id)->pluck('id')->all();

        if (empty($logIds)) {
            return 0;
        }

        // Children of communication_logs cascade; delete the parents directly.
        return DB::table('communication_logs')->whereIn('id', $logIds)->delete();
    }

    private function deleteTagLinks(Participant $participant): void
    {
        DB::table('taggables')
            ->where('taggable_type', Participant::class)
            ->where('taggable_id', $participant->id)
            ->delete();
    }

    /**
     * Capture the participant and every associated record as a recoverable JSON blob.
     */
    private function buildSnapshot(Participant $participant): array
    {
        $id = $participant->id;

        $housingJobs = DB::table('housing_jobs')->where('participant_id', $id)->get();
        $housingJobIds = $housingJobs->pluck('id')->all();

        $childTable = function (string $table, string $column, array $ids) {
            return empty($ids) ? [] : DB::table($table)->whereIn($column, $ids)->get()->toArray();
        };

        $commLogs = DB::table('communication_logs')->where('participant_id', $id)->get();
        $commLogIds = $commLogs->pluck('id')->all();

        return [
            'participant' => (array) DB::table('participants')->where('id', $id)->first(),
            'household' => $participant->household_id
                ? (array) DB::table('households')->where('id', $participant->household_id)->first()
                : null,
            'program_enrollments' => DB::table('program_enrollments')->where('participant_id', $id)->get()->toArray(),
            'service_enrollments' => DB::table('service_enrollments')->where('participant_id', $id)->get()->toArray(),
            'properties' => DB::table('properties')->where('participant_id', $id)->get()->toArray(),
            'housing_jobs' => $housingJobs->toArray(),
            'housing_job_children' => [
                'project_milestones' => $childTable('project_milestones', 'housing_job_id', $housingJobIds),
                'project_budgets' => $childTable('project_budgets', 'housing_job_id', $housingJobIds),
                'project_expenses' => $childTable('project_expenses', 'housing_job_id', $housingJobIds),
                'project_contacts' => $childTable('project_contacts', 'housing_job_id', $housingJobIds),
                'job_document_checklists' => $childTable('job_document_checklists', 'housing_job_id', $housingJobIds),
                'job_contact_ledgers' => $childTable('job_contact_ledgers', 'housing_job_id', $housingJobIds),
            ],
            'communication_logs' => $commLogs->toArray(),
            'intake_requirements' => $childTable('intake_requirements', 'communication_log_id', $commLogIds),
            'potential_duplicates' => DB::table('potential_duplicates')->where('matched_participant_id', $id)->get()->toArray(),
            'tags' => DB::table('taggables')->where('taggable_type', Participant::class)->where('taggable_id', $id)->get()->toArray(),
            'counts' => $this->previewCounts($participant),
            'snapshot_taken_at' => now()->toISOString(),
        ];
    }

    private function writeSnapshot(Participant $participant, array $snapshot, ?int $userId): string
    {
        $path = sprintf(
            'deletion-snapshots/participant-%d-%s.json',
            $participant->id,
            now()->format('Ymd-His')
        );

        $snapshot['deleted_by_user_id'] = $userId ?? auth()->id();

        Storage::disk('local')->put($path, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }
}
