<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Merges one call/intake log (the "duplicate") into another (the "survivor").
 *
 * The housing project manager sometimes logs the same homeowner twice — a
 * second call comes in before she finds the first entry, and now the person's
 * history is split across two records. Merging folds the duplicate into the
 * record she keeps: its timeline entries, its own root call, its documents and
 * requirements, its counties, fund codes and attachments all land on the
 * survivor, and the duplicate is soft-deleted with a tombstone
 * (merged_into_id) saying where its contents went.
 *
 * This is a true record↔record merge, in the same spirit as
 * ParticipantMergeService: nothing is copied and left behind in two places.
 * Rows are re-pointed, so attachments, uploaders and timestamps keep their
 * original identity and the survivor reads as one continuous history.
 *
 * The survivor wins every field it already has a value for; its blanks are
 * filled from the duplicate. The one thing that is NOT re-pointed is the
 * duplicate's own activity_log history — that stays on the (soft-deleted) row,
 * because rewriting it would claim edits were made to a record they were
 * never made to.
 *
 * Ordering rule, mirroring HousingRecordDeleter: a log that was promoted into
 * a live project cannot be merged away. That project's origin is this record;
 * folding it into another would leave the project pointing at a deleted log.
 */
class CommunicationLogMergeService
{
    /**
     * Why this pair cannot be merged, or null when it can.
     * Phrased for the project manager, not for a developer.
     */
    public function blockedReason(CommunicationLog $survivor, CommunicationLog $duplicate): ?string
    {
        if ($survivor->id === $duplicate->id) {
            return 'That is the same entry — pick the other record you want to fold in.';
        }

        if ($survivor->trashed()) {
            return 'This entry is deleted. Restore it first, then merge into it.';
        }

        if ($duplicate->trashed()) {
            return 'That entry is already deleted, so there is nothing to merge.';
        }

        if ($duplicate->merged_into_id !== null) {
            return 'That entry has already been merged into COMM-'.$duplicate->merged_into_id.'.';
        }

        $job = $this->liveJobFor($duplicate);

        if ($job !== null) {
            return "That entry was converted into project {$job->job_number}, so it is the origin of live work. "
                .'Merge the other direction, or delete that project first.';
        }

        return null;
    }

    public function canMerge(CommunicationLog $survivor, CommunicationLog $duplicate): bool
    {
        return $this->blockedReason($survivor, $duplicate) === null;
    }

    /**
     * What the merge will move, counted before anything changes — the numbers
     * the confirmation screen shows so she is never guessing.
     *
     * @return array<string,int|string|null>
     */
    public function preview(CommunicationLog $survivor, CommunicationLog $duplicate): array
    {
        $requirements = IntakeRequirement::where('communication_log_id', $duplicate->id)->get();
        $signatures = $this->requirementSignatures($survivor);

        $moved = $requirements->reject(fn (IntakeRequirement $r) => isset($signatures[$this->requirementSignature($r)]));

        return [
            // +1: the duplicate's own root call becomes a timeline entry too.
            'timeline_entries' => $duplicate->updates()->count() + 1,
            'requirements' => $moved->count(),
            'requirements_deduped' => $requirements->count() - $moved->count(),
            'files' => $requirements->sum(fn (IntakeRequirement $r) => $r->getMedia('documents')->count()),
            'counties' => $this->missingIds(
                'communication_log_housing_services', 'housing_service_id', $survivor, $duplicate
            )->count(),
            'fund_codes' => $this->missingIds(
                'communication_log_anticipated_fund_codes', 'fund_code_id', $survivor, $duplicate
            )->count(),
            'services' => $this->missingIds(
                'communication_log_service', 'service_id', $survivor, $duplicate
            )->count(),
            'income_assessment' => $this->incomeAssessmentDisposition($survivor, $duplicate),
            'follow_up_adopted' => $this->adoptsFollowUp($survivor, $duplicate),
        ];
    }

    /**
     * Fold $duplicate into $survivor. Everything happens in one transaction —
     * a half-merged pair would be worse than either duplicate.
     *
     * @return array<string,mixed> Summary of what moved.
     */
    public function merge(CommunicationLog $survivor, CommunicationLog $duplicate, ?User $actor = null): array
    {
        $reason = $this->blockedReason($survivor, $duplicate);

        if ($reason !== null) {
            throw new RuntimeException($reason);
        }

        return DB::transaction(function () use ($survivor, $duplicate, $actor) {
            $survivor->refresh();
            $duplicate->refresh();

            $actorId = $actor?->id ?? auth()->id();

            $summary = [
                'survivor_id' => $survivor->id,
                'duplicate_id' => $duplicate->id,
                'timeline_entries_moved' => 0,
                'requirements_moved' => 0,
                'requirements_deduped' => 0,
                'files_moved' => 0,
                'counties_moved' => 0,
                'fund_codes_moved' => 0,
                'services_moved' => 0,
                'pins_moved' => 0,
                'tags_moved' => 0,
                'income_assessment_moved' => false,
                'follow_up_adopted' => false,
                'fields_filled' => [],
                'snapshot_path' => null,
            ];

            // Snapshot first: the duplicate row is about to stop being the
            // owner of anything, so capture it while it still is.
            $summary['snapshot_path'] = $this->writeSnapshot($survivor, $duplicate, $actorId);

            $this->moveTimeline($survivor, $duplicate, $actorId, $summary);
            $this->moveRequirements($survivor, $duplicate, $actorId, $summary);
            $this->moveIncomeAssessment($survivor, $duplicate, $summary);
            $this->moveAttachments($survivor, $duplicate, $actorId, $summary);
            $this->movePins($survivor, $duplicate, $summary);
            $this->moveTags($survivor, $duplicate, $summary);
            $this->adoptFollowUp($survivor, $duplicate, $actorId, $summary);
            $this->fillBlankFields($survivor, $duplicate, $summary);

            // Tombstone, then soft-delete. deleted_by is stamped by
            // TracksSoftDeleter from the authenticated user.
            $duplicate->merged_into_id = $survivor->id;
            $duplicate->merged_at = now();
            $duplicate->merged_by = $actorId;
            $duplicate->save();
            $duplicate->delete();

            // Requirements arriving from the duplicate can flip the Phase-1
            // gate in either direction — recompute the same way every other
            // requirement transition does.
            $survivor->refresh()->applyAutoCategory();

            activity('comm_log_merge')
                ->performedOn($survivor)
                ->causedBy($actor ?? ($actorId ? User::find($actorId) : null))
                ->withProperties($summary)
                ->log("Merged communication log #{$duplicate->id} into #{$survivor->id}");

            return $summary;
        });
    }

    // ── Movers ────────────────────────────────────────────────────────

    /**
     * Re-point the duplicate's update rows, then write the duplicate's own
     * root call as one more entry on the survivor.
     *
     * The root entry is backdated to the call it describes rather than stamped
     * "now": the survivor's timeline is read newest-first as a chronology, and
     * a three-week-old call sitting at the top of it would be a lie.
     */
    protected function moveTimeline(CommunicationLog $survivor, CommunicationLog $duplicate, ?int $actorId, array &$summary): void
    {
        $summary['timeline_entries_moved'] = DB::table('communication_log_updates')
            ->where('communication_log_id', $duplicate->id)
            ->update(['communication_log_id' => $survivor->id]);

        $when = $duplicate->communication_date ?? $duplicate->created_at ?? now();

        $what = trim((string) ($duplicate->summary ?: $duplicate->notes ?: $duplicate->subject));
        $type = ucfirst(str_replace('_', ' ', (string) $duplicate->communication_type));
        $direction = ucfirst((string) $duplicate->direction);

        $header = trim("{$type} · {$direction}", ' ·');
        $content = "[Merged from COMM-{$duplicate->id}] {$header}";

        if ($what !== '') {
            $content .= " — {$what}";
        }

        // created_at is set explicitly (and is therefore dirty, so Eloquent
        // leaves it alone) — it is what the timeline sorts on.
        $root = $survivor->updates()->make([
            'update_content' => $content,
            'update_type' => 'note',
            'update_date' => $when,
            'created_by' => $duplicate->created_by ?? $actorId,
        ]);
        $root->created_at = $when;
        $root->save();

        // And a status-change entry at the top, so the merge itself is visible
        // where she is looking rather than buried in the chronology.
        $survivor->addUpdate(
            "Merged in the duplicate entry COMM-{$duplicate->id}"
                .($duplicate->contact_full_name !== '' ? " ({$duplicate->contact_full_name})" : '')
                .'. Its calls, documents and requirements are now on this record.',
            'status_change',
            $actorId
        );
    }

    /**
     * Move the duplicate's intake requirements. A requirement the survivor
     * already carries is not duplicated — the two rows are reconciled into the
     * survivor's, which inherits the better status and any files the duplicate
     * had collected against it.
     */
    protected function moveRequirements(CommunicationLog $survivor, CommunicationLog $duplicate, ?int $actorId, array &$summary): void
    {
        $existing = $this->requirementSignatures($survivor);

        foreach (IntakeRequirement::where('communication_log_id', $duplicate->id)->get() as $req) {
            $match = $existing[$this->requirementSignature($req)] ?? null;

            if ($match === null) {
                $req->communication_log_id = $survivor->id;
                $req->updated_by_user_id = $actorId;
                $req->save();

                $existing[$this->requirementSignature($req)] = $req;
                $summary['requirements_moved']++;

                continue;
            }

            $summary['files_moved'] += $this->repointMedia($req, $match);
            $this->reconcileRequirement($match, $req, $actorId);

            $req->delete();
            $summary['requirements_deduped']++;
        }
    }

    /**
     * Fold the duplicate's copy of a requirement into the survivor's copy:
     * a met status beats an unmet one, an un-orphaned row beats an orphaned
     * one, `via` sources are unioned so later detach reconciliation still
     * knows every reason the row exists, and a worker note fills a blank.
     */
    protected function reconcileRequirement(IntakeRequirement $keep, IntakeRequirement $drop, ?int $actorId): void
    {
        $dirty = false;

        if (! $keep->isMet() && $drop->isMet()) {
            $keep->status = $drop->status;
            $keep->received_at = $drop->received_at;
            $keep->received_by_user_id = $drop->received_by_user_id;
            $keep->status_changed_at = $drop->status_changed_at;
            $keep->status_changed_by_user_id = $drop->status_changed_by_user_id;
            $dirty = true;
        }

        if ($keep->is_orphaned && ! $drop->is_orphaned) {
            $keep->is_orphaned = false;
            $dirty = true;
        }

        $via = array_values(array_unique(array_merge(
            is_array($keep->via) ? $keep->via : [],
            is_array($drop->via) ? $drop->via : [],
        )));

        if ($via !== (is_array($keep->via) ? $keep->via : [])) {
            $keep->via = $via;
            $dirty = true;
        }

        if (trim((string) $keep->worker_note) === '' && trim((string) $drop->worker_note) !== '') {
            $keep->worker_note = $drop->worker_note;
            $dirty = true;
        }

        if ($dirty) {
            $keep->updated_by_user_id = $actorId;
            $keep->save();
        }
    }

    /**
     * The figures behind the income-eligibility determination. One row per log
     * (unique index), so when both sides have one the survivor's stands — it
     * is the determination attached to the record she is keeping — and the
     * duplicate's is left on the snapshot rather than silently overwriting it.
     */
    protected function moveIncomeAssessment(CommunicationLog $survivor, CommunicationLog $duplicate, array &$summary): void
    {
        if ($this->incomeAssessmentDisposition($survivor, $duplicate) !== 'move') {
            return;
        }

        DB::table('intake_income_assessments')
            ->where('communication_log_id', $duplicate->id)
            ->update(['communication_log_id' => $survivor->id]);

        $summary['income_assessment_moved'] = true;
    }

    /** Counties, anticipated fund codes and services the survivor is missing. */
    protected function moveAttachments(CommunicationLog $survivor, CommunicationLog $duplicate, ?int $actorId, array &$summary): void
    {
        $counties = $this->missingIds('communication_log_housing_services', 'housing_service_id', $survivor, $duplicate);
        foreach ($counties as $id) {
            DB::table('communication_log_housing_services')->insert([
                'communication_log_id' => $survivor->id,
                'housing_service_id' => $id,
                'created_by_user_id' => $actorId,
                'created_at' => now(),
            ]);
        }
        $summary['counties_moved'] = $counties->count();

        $fundCodes = $this->missingIds('communication_log_anticipated_fund_codes', 'fund_code_id', $survivor, $duplicate);
        foreach ($fundCodes as $id) {
            DB::table('communication_log_anticipated_fund_codes')->insert([
                'communication_log_id' => $survivor->id,
                'fund_code_id' => $id,
                'created_by_user_id' => $actorId,
                'created_at' => now(),
            ]);
        }
        $summary['fund_codes_moved'] = $fundCodes->count();

        $services = $this->missingIds('communication_log_service', 'service_id', $survivor, $duplicate);
        foreach ($services as $id) {
            DB::table('communication_log_service')->insert([
                'communication_log_id' => $survivor->id,
                'service_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $summary['services_moved'] = $services->count();
    }

    /**
     * Whoever had the duplicate pinned keeps a pin — on the record that now
     * holds the history — unless they had already pinned the survivor too.
     */
    protected function movePins(CommunicationLog $survivor, CommunicationLog $duplicate, array &$summary): void
    {
        $moved = 0;

        $survivorPinners = DB::table('communication_log_user_pins')
            ->where('communication_log_id', $survivor->id)
            ->pluck('user_id')
            ->all();

        $moved += DB::table('communication_log_user_pins')
            ->where('communication_log_id', $duplicate->id)
            ->whereNotIn('user_id', $survivorPinners ?: [0])
            ->update(['communication_log_id' => $survivor->id]);

        $homesPinners = DB::table('homes_pins')
            ->where('pin_type', 'call')
            ->where('pinnable_id', $survivor->id)
            ->pluck('user_id')
            ->all();

        $moved += DB::table('homes_pins')
            ->where('pin_type', 'call')
            ->where('pinnable_id', $duplicate->id)
            ->whereNotIn('user_id', $homesPinners ?: [0])
            ->update(['pinnable_id' => $survivor->id]);

        $summary['pins_moved'] = $moved;
    }

    /** Polymorphic tag links, skipping any tag the survivor already carries. */
    protected function moveTags(CommunicationLog $survivor, CommunicationLog $duplicate, array &$summary): void
    {
        $type = CommunicationLog::class;

        $survivorTagIds = DB::table('taggables')
            ->where('taggable_type', $type)
            ->where('taggable_id', $survivor->id)
            ->pluck('tag_id')
            ->all();

        $summary['tags_moved'] = DB::table('taggables')
            ->where('taggable_type', $type)
            ->where('taggable_id', $duplicate->id)
            ->whereNotIn('tag_id', $survivorTagIds ?: [0])
            ->update(['taggable_id' => $survivor->id]);
    }

    /**
     * An open follow-up on the duplicate is the one piece of forward-looking
     * state a merge could silently drop — she scheduled a callback and the
     * record holding it just disappeared. It moves only when the survivor has
     * nothing open of its own; otherwise it becomes a timeline note so the
     * commitment is still visible.
     */
    protected function adoptFollowUp(CommunicationLog $survivor, CommunicationLog $duplicate, ?int $actorId, array &$summary): void
    {
        if (! $this->hasOpenFollowUp($duplicate)) {
            return;
        }

        if ($this->adoptsFollowUp($survivor, $duplicate)) {
            $survivor->follow_up_date = $duplicate->follow_up_date;
            $survivor->follow_up_action = $duplicate->follow_up_action;
            $survivor->follow_up_completed = false;
            $survivor->updated_by = $actorId;
            $survivor->save();

            $summary['follow_up_adopted'] = true;

            return;
        }

        $survivor->addUpdate(
            'Follow-up carried over from COMM-'.$duplicate->id.', due '
                .$duplicate->follow_up_date?->format('M j, Y').': '
                .($duplicate->follow_up_action ?: 'follow up')
                .'. This entry already had its own follow-up, so check both.',
            'follow_up',
            $actorId
        );
    }

    /**
     * Survivor wins. Its blanks — a phone number she only captured the second
     * time, an email, a city — are filled from the duplicate rather than lost.
     */
    protected function fillBlankFields(CommunicationLog $survivor, CommunicationLog $duplicate, array &$summary): void
    {
        $fillable = [
            'participant_id', 'household_id', 'property_id', 'contractor_id',
            'contact_first_name', 'contact_last_name', 'contact_phone',
            'contact_email', 'contact_address', 'contact_city', 'contact_state',
            'contact_zip', 'contact_organization', 'contact_role',
            'summary', 'notes', 'outcome',
        ];

        $updates = [];

        foreach ($fillable as $field) {
            $current = $survivor->$field;
            $incoming = $duplicate->$field;

            if (($current === null || $current === '') && $incoming !== null && $incoming !== '') {
                $updates[$field] = $incoming;
            }
        }

        if ($updates === []) {
            return;
        }

        $updates['updated_by'] = auth()->id() ?? $survivor->updated_by;
        $survivor->update($updates);

        $summary['fields_filled'] = array_keys(array_diff_key($updates, ['updated_by' => null]));
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * How one requirement is recognised as "the same requirement" on the other
     * record. Library atoms carry a stable key; template- and definition-sourced
     * rows fall back to their source id; ad-hoc rows can only match on label.
     */
    protected function requirementSignature(IntakeRequirement $req): string
    {
        if (trim((string) $req->key) !== '') {
            return 'key:'.strtolower(trim($req->key));
        }

        if ($req->source_definition_id) {
            return 'def:'.$req->source_definition_id;
        }

        if ($req->source_template_id) {
            return 'tpl:'.$req->source_template_id;
        }

        return 'label:'.strtolower(trim((string) ($req->label ?? $req->source_label_snapshot)));
    }

    /** @return array<string,IntakeRequirement> */
    protected function requirementSignatures(CommunicationLog $log): array
    {
        $map = [];

        foreach (IntakeRequirement::where('communication_log_id', $log->id)->get() as $req) {
            $map[$this->requirementSignature($req)] = $req;
        }

        return $map;
    }

    /** Re-point a deduped requirement's files onto the row that is being kept. */
    protected function repointMedia(IntakeRequirement $from, IntakeRequirement $to): int
    {
        $moved = 0;

        foreach ($from->media as $media) {
            $media->model_id = $to->id;
            $media->save();
            $moved++;
        }

        return $moved;
    }

    /**
     * Ids present on the duplicate's pivot but not the survivor's.
     *
     * @return \Illuminate\Support\Collection<int,int>
     */
    protected function missingIds(string $table, string $column, CommunicationLog $survivor, CommunicationLog $duplicate)
    {
        $have = DB::table($table)->where('communication_log_id', $survivor->id)->pluck($column)->all();

        return DB::table($table)
            ->where('communication_log_id', $duplicate->id)
            ->whereNotIn($column, $have ?: [0])
            ->pluck($column);
    }

    /** move | keep_survivor | none */
    protected function incomeAssessmentDisposition(CommunicationLog $survivor, CommunicationLog $duplicate): string
    {
        $duplicateHas = DB::table('intake_income_assessments')
            ->where('communication_log_id', $duplicate->id)->exists();

        if (! $duplicateHas) {
            return 'none';
        }

        $survivorHas = DB::table('intake_income_assessments')
            ->where('communication_log_id', $survivor->id)->exists();

        return $survivorHas ? 'keep_survivor' : 'move';
    }

    protected function hasOpenFollowUp(CommunicationLog $log): bool
    {
        return $log->follow_up_date !== null && ! $log->follow_up_completed;
    }

    protected function adoptsFollowUp(CommunicationLog $survivor, CommunicationLog $duplicate): bool
    {
        return $this->hasOpenFollowUp($duplicate) && ! $this->hasOpenFollowUp($survivor);
    }

    /** The non-deleted project this log was promoted into, if any. */
    protected function liveJobFor(CommunicationLog $log): ?HousingJob
    {
        $jobId = $log->converted_to_job_id ?? $log->housing_job_id;

        if ($jobId === null) {
            return null;
        }

        return HousingJob::find($jobId);
    }

    /**
     * A JSON snapshot of the duplicate as it stood, mirroring the merge- and
     * delete-path snapshots elsewhere. The row itself survives (soft delete),
     * so this exists for the associations that are about to be re-pointed away
     * from it and could not otherwise be reconstructed.
     */
    protected function writeSnapshot(CommunicationLog $survivor, CommunicationLog $duplicate, ?int $actorId): string
    {
        $path = sprintf(
            'merge-snapshots/comm-log-%d-into-%d-%s.json',
            $duplicate->id,
            $survivor->id,
            now()->format('Ymd-His')
        );

        Storage::disk('local')->put($path, json_encode([
            'merged_at' => now()->toISOString(),
            'merged_by_user_id' => $actorId,
            'survivor_id' => $survivor->id,
            'survivor' => $survivor->getAttributes(),
            'duplicate' => $duplicate->getAttributes(),
            'duplicate_updates' => DB::table('communication_log_updates')
                ->where('communication_log_id', $duplicate->id)->get()->toArray(),
            'duplicate_requirements' => DB::table('intake_requirements')
                ->where('communication_log_id', $duplicate->id)->get()->toArray(),
            'duplicate_income_assessment' => DB::table('intake_income_assessments')
                ->where('communication_log_id', $duplicate->id)->get()->toArray(),
            'duplicate_housing_services' => DB::table('communication_log_housing_services')
                ->where('communication_log_id', $duplicate->id)->get()->toArray(),
            'duplicate_fund_codes' => DB::table('communication_log_anticipated_fund_codes')
                ->where('communication_log_id', $duplicate->id)->get()->toArray(),
            'duplicate_services' => DB::table('communication_log_service')
                ->where('communication_log_id', $duplicate->id)->get()->toArray(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    /**
     * Likely duplicates of this log: other open entries for what looks like the
     * same person. Name is the strong signal, phone and email are exact, and an
     * address match catches the cases where she spelled the name differently.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int,CommunicationLog>
     */
    public function findCandidates(CommunicationLog $log, int $limit = 10)
    {
        $first = trim((string) $log->contact_first_name);
        $last = trim((string) $log->contact_last_name);
        $phone = $this->digits($log->contact_phone);
        $email = strtolower(trim((string) $log->contact_email));
        $address = strtolower(trim((string) $log->contact_address));

        if ($last === '' && $phone === '' && $email === '' && $address === '') {
            return CommunicationLog::whereRaw('1 = 0')->get();
        }

        return CommunicationLog::query()
            ->where('id', '!=', $log->id)
            ->whereNull('merged_into_id')
            ->where(function ($q) use ($first, $last, $phone, $email, $address, $log) {
                if ($last !== '') {
                    $q->orWhere(function ($w) use ($first, $last) {
                        $w->where('contact_last_name', $last);
                        if ($first !== '') {
                            $w->where('contact_first_name', 'like', mb_substr($first, 0, 1).'%');
                        }
                    });
                }

                if ($phone !== '') {
                    // Strip punctuation on both sides so (610) 555-1234 and
                    // 6105551234 are the same number.
                    $q->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(contact_phone,'-',''),' ',''),'(',''),')','') = ?",
                        [$phone]
                    );
                }

                if ($email !== '') {
                    $q->orWhereRaw('LOWER(contact_email) = ?', [$email]);
                }

                if ($address !== '' && $log->contact_zip) {
                    $q->orWhere(function ($w) use ($address, $log) {
                        $w->whereRaw('LOWER(contact_address) = ?', [$address])
                            ->where('contact_zip', $log->contact_zip);
                    });
                }
            })
            ->with('participant')
            ->orderByDesc('communication_date')
            ->limit($limit)
            ->get();
    }

    protected function digits(?string $value): string
    {
        return preg_replace('/[^0-9]/', '', (string) $value) ?? '';
    }
}
