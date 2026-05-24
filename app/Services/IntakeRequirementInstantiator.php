<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\IntakeRequirement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * P-39b: single entry point for creating IntakeRequirement rows.
 *
 * Mirrors RequirementInstantiator (Phase 3A) for the intake phase. Key
 * differences:
 *   - rooted on CommunicationLog instead of HousingJob;
 *   - reads templates filtered to phase='eligibility' (the project-phase
 *     analogue reads phase='project'); see addendum D-1;
 *   - reconciliation implements orphan-and-keep (OQ-1) via the
 *     is_orphaned boolean rather than deleting rows;
 *   - no notification dispatch (OQ-6 defers per-row notifications).
 *
 * Copy-on-instantiate is enforced via source_label_snapshot: the
 * template's label is captured at creation time, so future edits to
 * the upstream template do not propagate. Matches the Phase 3A
 * convention.
 */
class IntakeRequirementInstantiator
{
    /**
     * Idempotent baseline ("Application") instantiation. If a baseline
     * row already exists for this log, return it; if it's currently
     * orphaned (rare — baseline does not get orphaned by fund-code
     * reconciliation, but future logic may flip it), un-orphan first.
     * Otherwise create one as a document-type baseline.
     */
    public function baseline(CommunicationLog $log, User $actor): IntakeRequirement
    {
        $existing = IntakeRequirement::query()
            ->where('communication_log_id', $log->id)
            ->where('source_type', IntakeRequirement::SOURCE_BASELINE)
            ->first();

        if ($existing !== null) {
            if ($existing->is_orphaned) {
                $existing->unmarkOrphaned($actor);
            }

            return $existing;
        }

        return $this->insert($log, [
            'type' => IntakeRequirement::TYPE_DOCUMENT,
            'label' => 'Application',
            'source_type' => IntakeRequirement::SOURCE_BASELINE,
            'source_template_id' => null,
            'source_label_snapshot' => null,
        ], $actor);
    }

    /**
     * For every FundCodeRequirement template on the fund code WHERE
     * phase='eligibility', create one IntakeRequirement on the log.
     *
     * Idempotent on (communication_log_id, source_template_id):
     *   - missing rows are inserted;
     *   - existing rows that were previously orphaned (the user
     *     de-anticipated this code earlier and is now re-anticipating
     *     it) are un-orphaned in place;
     *   - existing rows that are not orphaned are left untouched.
     *
     * The phase filter is the single most important behavior of this
     * method. Without it, intake would pull project-execution templates
     * (final inspection sign-off, lien waivers, etc.) into the intake
     * set — exactly the wrong model for Kaitlyn's workflow.
     *
     * The baseline is NOT auto-created here. Callers (39d's eventual
     * dashboard wiring) decide when to instantiate the baseline.
     *
     * @return Collection<int, IntakeRequirement>
     */
    public function forFundCode(CommunicationLog $log, FundCode $fundCode, User $actor): Collection
    {
        $templates = $fundCode->requirements()
            ->where('phase', 'eligibility')
            ->ordered()
            ->get();

        if ($templates->isEmpty()) {
            return collect();
        }

        $existingByTemplateId = IntakeRequirement::query()
            ->where('communication_log_id', $log->id)
            ->where('source_type', IntakeRequirement::SOURCE_FUND_CODE)
            ->whereIn('source_template_id', $templates->pluck('id')->all())
            ->get()
            ->keyBy('source_template_id');

        $result = collect();

        foreach ($templates as $template) {
            $existing = $existingByTemplateId->get($template->id);

            if ($existing !== null) {
                if ($existing->is_orphaned) {
                    $existing->unmarkOrphaned($actor);
                }
                $result->push($existing);

                continue;
            }

            $row = $this->insert($log, [
                'type' => $template->type ?? IntakeRequirement::TYPE_DOCUMENT,
                'label' => $template->label,
                'source_type' => IntakeRequirement::SOURCE_FUND_CODE,
                'source_template_id' => $template->id,
                'source_label_snapshot' => $template->label,
            ], $actor);

            $result->push($row);
        }

        return $result;
    }

    /**
     * Free-form intake requirement. source_type=ad_hoc; no template
     * pointer. Useful for one-off documents Kaitlyn needs from this
     * specific applicant that aren't templated on any fund code.
     */
    public function adHoc(
        CommunicationLog $log,
        string $label,
        string $type,
        User $actor,
        ?string $notes = null
    ): IntakeRequirement {
        return $this->insert($log, [
            'type' => $type,
            'label' => $label,
            'source_type' => IntakeRequirement::SOURCE_AD_HOC,
            'source_template_id' => null,
            'source_label_snapshot' => null,
            'notes' => $notes,
        ], $actor);
    }

    /**
     * Reconcile the log's anticipated_fund_codes pivot against a target
     * set. For each newly-added code: instantiate its eligibility
     * templates via forFundCode(). For each newly-removed code: mark
     * every sourced requirement orphaned (do NOT delete — orphan-and-
     * keep, OQ-1).
     *
     * Runs inside a transaction so a partial reconciliation never
     * leaves the pivot and the requirements out of sync.
     *
     * @param  array<int, int>  $newFundCodeIds
     */
    public function reconcileAnticipatedFundCodes(
        CommunicationLog $log,
        array $newFundCodeIds,
        User $actor
    ): void {
        $newFundCodeIds = array_values(array_unique(array_map('intval', $newFundCodeIds)));

        DB::transaction(function () use ($log, $newFundCodeIds, $actor) {
            $currentIds = $log->anticipatedFundCodes()->pluck('fund_codes.id')->all();

            $toAdd = array_diff($newFundCodeIds, $currentIds);
            $toRemove = array_diff($currentIds, $newFundCodeIds);

            if (! empty($toRemove)) {
                $log->anticipatedFundCodes()->detach($toRemove);

                $orphanCandidates = IntakeRequirement::query()
                    ->where('communication_log_id', $log->id)
                    ->where('source_type', IntakeRequirement::SOURCE_FUND_CODE)
                    ->whereNotNull('source_template_id')
                    ->whereIn(
                        'source_template_id',
                        DB::table('fund_code_requirements')
                            ->whereIn('fund_code_id', $toRemove)
                            ->pluck('id')
                    )
                    ->get();

                foreach ($orphanCandidates as $req) {
                    if (! $req->is_orphaned) {
                        $req->markOrphaned($actor);
                    }
                }
            }

            foreach ($toAdd as $fundCodeId) {
                $log->anticipatedFundCodes()->attach($fundCodeId, [
                    'created_by_user_id' => $actor->id,
                    'created_at' => now(),
                ]);

                $fundCode = FundCode::find($fundCodeId);
                if ($fundCode !== null) {
                    $this->forFundCode($log, $fundCode, $actor);
                }
            }
        });
    }

    /**
     * Shared insert path. Sets default status, stamps created_by_user_id
     * and updated_by_user_id from the actor.
     */
    protected function insert(CommunicationLog $log, array $attributes, User $actor): IntakeRequirement
    {
        $req = new IntakeRequirement(array_merge([
            'status' => IntakeRequirement::STATUS_OUTSTANDING,
            'is_orphaned' => false,
        ], $attributes));

        $req->communication_log_id = $log->id;
        $req->created_by_user_id = $actor->id;
        $req->updated_by_user_id = $actor->id;
        $req->save();

        return $req;
    }
}
