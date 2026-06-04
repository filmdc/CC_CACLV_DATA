<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Participant;
use App\Models\ServiceEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Merges one participant ("loser") into another ("survivor"), reassigning every
 * associated record to the survivor before deleting the loser.
 *
 * This is a true record↔record merge. It is distinct from the duplicate-resolution
 * "merge" in App\Livewire\DuplicateResolutionManagement, which only copies detected
 * import data (proposed_data) onto a single participant and never reassigns
 * associated records.
 *
 * Tables that reference participant_id (see CLAUDE.md schema notes):
 *   program_enrollments, service_enrollments, properties, housing_jobs,
 *   communication_logs (SET NULL by default — must be reassigned explicitly),
 *   potential_duplicates.matched_participant_id, and the polymorphic taggables pivot.
 *
 * Records that also carry the loser's household_id (properties, housing_jobs,
 * communication_logs) are repointed to the survivor's household so they are not
 * cascade-deleted if the loser's now-empty household is cleaned up.
 */
class ParticipantMergeService
{
    /**
     * @param  array<string,string>  $fieldChoices  Optional per-field overrides:
     *                                              ['field' => 'use_loser'] forces the
     *                                              loser's value onto the survivor.
     * @return array<string,mixed> Summary of what was reassigned.
     */
    public function merge(Participant $survivor, Participant $loser, array $fieldChoices = [], ?int $userId = null): array
    {
        if ($survivor->id === $loser->id) {
            throw new \InvalidArgumentException('Cannot merge a participant into itself.');
        }

        return DB::transaction(function () use ($survivor, $loser, $fieldChoices, $userId) {
            $survivor->refresh();
            $loser->refresh();

            $loserHouseholdId = $loser->household_id;
            $survivorHouseholdId = $survivor->household_id;

            $summary = [
                'survivor_id' => $survivor->id,
                'loser_id' => $loser->id,
                'program_enrollments_reassigned' => 0,
                'program_enrollments_deduped' => 0,
                'service_enrollments_reassigned' => 0,
                'service_enrollments_deduped' => 0,
                'properties_reassigned' => 0,
                'housing_jobs_reassigned' => 0,
                'communication_logs_reassigned' => 0,
                'tags_moved' => 0,
                'household_emptied' => false,
                'head_promoted_participant_id' => null,
                'snapshot_path' => null,
            ];

            // Snapshot the loser before any destructive change, for recoverability.
            $summary['snapshot_path'] = $this->writeLoserSnapshot($survivor, $loser, $userId);

            $this->mergeEnrollments($survivor, $loser, $summary);
            $this->reassignHouseholdScopedRecords($survivor, $loser, $loserHouseholdId, $survivorHouseholdId, $summary);
            $this->mergeTags($survivor, $loser, $summary);

            // Detection artifacts pointing at the loser are no longer meaningful.
            DB::table('potential_duplicates')->where('matched_participant_id', $loser->id)->delete();

            $this->mergeFields($survivor, $loser, $fieldChoices);

            // Record the merge on the survivor's notes JSON for traceability.
            $this->appendMergeNote($survivor, $loser);

            // Handle the loser's household before deleting the loser row.
            $this->handleLoserHousehold($survivor, $loser, $loserHouseholdId, $survivorHouseholdId, $summary);

            $loser->delete();

            activity('participant_merge')
                ->performedOn($survivor)
                ->causedBy($userId ? \App\Models\User::find($userId) : auth()->user())
                ->withProperties($summary)
                ->log("Merged participant #{$loser->id} ({$loser->full_name}) into #{$survivor->id} ({$survivor->full_name})");

            Log::info('Participant merge completed', $summary);

            return $summary;
        });
    }

    /**
     * Reassign program enrollments, deduping when the survivor is already enrolled in
     * the same program (avoids two active rows for one program), then reassign every
     * service enrollment — in either branch — deduping on service_id so the survivor
     * never ends up with two enrollments for the same service.
     */
    private function mergeEnrollments(Participant $survivor, Participant $loser, array &$summary): void
    {
        $survivorServiceIds = $survivor->serviceEnrollments()->pluck('service_id')->all();
        $survivorByProgram = $survivor->programEnrollments()->get()->keyBy('program_id');

        foreach ($loser->programEnrollments()->get() as $pe) {
            $existing = $survivorByProgram->get($pe->program_id);

            if ($existing) {
                // Survivor already enrolled in this program: move the loser's service
                // enrollments under the survivor's enrollment (deduping), then drop
                // the duplicate program enrollment.
                foreach (ServiceEnrollment::where('program_enrollment_id', $pe->id)->get() as $se) {
                    $this->placeServiceEnrollment($se, $survivor, $existing->id, $survivorServiceIds, $summary);
                }
                $pe->delete();
                $summary['program_enrollments_deduped']++;
            } else {
                $pe->update(['participant_id' => $survivor->id]);
                $survivorByProgram->put($pe->program_id, $pe);
                $summary['program_enrollments_reassigned']++;
            }
        }

        // Any service enrollments still owned by the loser (those under program
        // enrollments that were reassigned, not deduped) get reassigned too.
        foreach ($loser->serviceEnrollments()->get() as $se) {
            $this->placeServiceEnrollment($se, $survivor, $se->program_enrollment_id, $survivorServiceIds, $summary);
        }
    }

    /**
     * Move one service enrollment to the survivor, or drop it if the survivor already
     * has an enrollment for that service.
     */
    private function placeServiceEnrollment(ServiceEnrollment $se, Participant $survivor, $targetProgramEnrollmentId, array &$survivorServiceIds, array &$summary): void
    {
        if (in_array($se->service_id, $survivorServiceIds, true)) {
            $se->delete();
            $summary['service_enrollments_deduped']++;
        } else {
            $se->update([
                'participant_id' => $survivor->id,
                'program_enrollment_id' => $targetProgramEnrollmentId,
            ]);
            $survivorServiceIds[] = $se->service_id;
            $summary['service_enrollments_reassigned']++;
        }
    }

    /**
     * Reassign properties, housing jobs and communication logs to the survivor.
     * Where the record carried the loser's household_id, repoint it to the
     * survivor's household so it survives any later cleanup of the empty household.
     */
    private function reassignHouseholdScopedRecords(
        Participant $survivor,
        Participant $loser,
        ?int $loserHouseholdId,
        ?int $survivorHouseholdId,
        array &$summary
    ): void {
        $summary['properties_reassigned'] = $this->reassign('properties', $loser, $survivor, $loserHouseholdId, $survivorHouseholdId);
        $summary['housing_jobs_reassigned'] = $this->reassign('housing_jobs', $loser, $survivor, $loserHouseholdId, $survivorHouseholdId);
        $summary['communication_logs_reassigned'] = $this->reassign('communication_logs', $loser, $survivor, $loserHouseholdId, $survivorHouseholdId);
    }

    private function reassign(string $table, Participant $loser, Participant $survivor, ?int $loserHouseholdId, ?int $survivorHouseholdId): int
    {
        $count = DB::table($table)->where('participant_id', $loser->id)->update(['participant_id' => $survivor->id]);

        if ($loserHouseholdId && $survivorHouseholdId && $loserHouseholdId !== $survivorHouseholdId) {
            DB::table($table)
                ->where('participant_id', $survivor->id)
                ->where('household_id', $loserHouseholdId)
                ->update(['household_id' => $survivorHouseholdId]);
        }

        return $count;
    }

    /**
     * Move the loser's polymorphic tag links to the survivor, skipping any tag the
     * survivor already carries (the taggables unique index would otherwise collide).
     */
    private function mergeTags(Participant $survivor, Participant $loser, array &$summary): void
    {
        $type = Participant::class;
        $survivorTagIds = DB::table('taggables')
            ->where('taggable_type', $type)
            ->where('taggable_id', $survivor->id)
            ->pluck('tag_id')
            ->all();

        $loserLinks = DB::table('taggables')
            ->where('taggable_type', $type)
            ->where('taggable_id', $loser->id)
            ->get();

        foreach ($loserLinks as $link) {
            if (in_array($link->tag_id, $survivorTagIds, true)) {
                DB::table('taggables')->where('id', $link->id)->delete();
            } else {
                DB::table('taggables')->where('id', $link->id)->update(['taggable_id' => $survivor->id]);
                $summary['tags_moved']++;
            }
        }
    }

    /**
     * Survivor wins. Fill the survivor's empty fields from the loser, and honour any
     * explicit per-field "use_loser" overrides.
     */
    private function mergeFields(Participant $survivor, Participant $loser, array $fieldChoices): void
    {
        $protected = ['id', 'household_id', 'created_at', 'updated_at', 'notes', 'is_head_of_household'];
        $updates = [];

        foreach ($survivor->getFillable() as $field) {
            if (in_array($field, $protected, true)) {
                continue;
            }

            $forceLoser = ($fieldChoices[$field] ?? null) === 'use_loser';
            $survivorEmpty = $this->isEmptyValue($survivor->$field);

            if ($forceLoser && ! $this->isEmptyValue($loser->$field)) {
                $updates[$field] = $loser->$field;
            } elseif ($survivorEmpty && ! $this->isEmptyValue($loser->$field)) {
                $updates[$field] = $loser->$field;
            }
        }

        if (! empty($updates)) {
            $survivor->update($updates);
        }
    }

    private function isEmptyValue($value): bool
    {
        if (is_array($value)) {
            return count($value) === 0;
        }

        return $value === null || $value === '';
    }

    private function appendMergeNote(Participant $survivor, Participant $loser): void
    {
        $notes = $survivor->notes ? (json_decode($survivor->notes, true) ?: []) : [];
        if (! is_array($notes)) {
            $notes = ['legacy_notes' => $survivor->notes];
        }

        $notes['merge_history'][] = [
            'merged_participant_id' => $loser->id,
            'merged_participant_name' => $loser->full_name,
            'merged_at' => now()->toISOString(),
            'merged_by' => auth()->id(),
        ];

        $survivor->update(['notes' => json_encode($notes)]);
    }

    /**
     * After reassigning the loser's records, tidy its household:
     * - Same household as the survivor: if the loser was the head, the survivor
     *   inherits headship (the head row must not be left dangling).
     * - Different household: if the loser was head and members remain, promote the
     *   longest-standing remaining member; if the household is left empty, delete it
     *   (its addresses cascade).
     */
    private function handleLoserHousehold(Participant $survivor, Participant $loser, ?int $loserHouseholdId, ?int $survivorHouseholdId, array &$summary): void
    {
        if (! $loserHouseholdId) {
            return;
        }

        if ($loserHouseholdId === $survivorHouseholdId) {
            if ($loser->is_head_of_household && ! $survivor->is_head_of_household) {
                $survivor->update(['is_head_of_household' => true]);
                $summary['head_promoted_participant_id'] = $survivor->id;
            }

            return;
        }

        $remaining = Participant::where('household_id', $loserHouseholdId)
            ->where('id', '!=', $loser->id)
            ->orderBy('created_at')
            ->get();

        if ($remaining->isEmpty()) {
            Household::where('id', $loserHouseholdId)->delete();
            $summary['household_emptied'] = true;

            return;
        }

        if ($loser->is_head_of_household && ! $remaining->contains('is_head_of_household', true)) {
            $newHead = $remaining->first();
            $newHead->update(['is_head_of_household' => true]);
            $summary['head_promoted_participant_id'] = $newHead->id;
        }
    }

    /**
     * Write a JSON snapshot of the loser (and the merge context) so the merged-away
     * record's data is recoverable, mirroring the delete-path snapshot.
     */
    private function writeLoserSnapshot(Participant $survivor, Participant $loser, ?int $userId): string
    {
        $path = sprintf('merge-snapshots/participant-%d-into-%d-%s.json', $loser->id, $survivor->id, now()->format('Ymd-His'));

        Storage::disk('local')->put($path, json_encode([
            'merged_at' => now()->toISOString(),
            'merged_by_user_id' => $userId ?? auth()->id(),
            'survivor_id' => $survivor->id,
            'loser' => $loser->getAttributes(),
            'loser_program_enrollments' => DB::table('program_enrollments')->where('participant_id', $loser->id)->get()->toArray(),
            'loser_service_enrollments' => DB::table('service_enrollments')->where('participant_id', $loser->id)->get()->toArray(),
            'loser_properties' => DB::table('properties')->where('participant_id', $loser->id)->get()->toArray(),
            'loser_housing_jobs' => DB::table('housing_jobs')->where('participant_id', $loser->id)->get()->toArray(),
            'loser_communication_logs' => DB::table('communication_logs')->where('participant_id', $loser->id)->get()->toArray(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }
}
