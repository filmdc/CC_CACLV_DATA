<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\HousingService;
use App\Models\IntakeRequirement;
use App\Models\RequirementDefinition;
use App\Models\RequirementTemplateSet;
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
     * The standing income-eligibility requirement.
     *
     * Every log that graduates to an intake gets one: household income
     * has to be captured and placed against the HHS poverty guidelines
     * before any fund can be committed, regardless of which county or
     * fund codes are in play. Called from forHousingService() and
     * reconcileTaggedServices() — attaching a service is exactly what
     * CommunicationLog::computeAutoCategory() treats as graduation.
     *
     * Idempotent on (communication_log_id, key). The atom is
     * self-healing via firstOrCreate so environments that predate this
     * feature pick it up without a data migration; withTrashed() keeps
     * a deliberately-retired library entry from being duplicated.
     *
     * via[] is tagged 'standard' rather than 'service:N' on purpose:
     * income eligibility is not owned by any one service, so detaching
     * the service that first triggered it must not orphan it.
     */
    public function incomeEligibility(CommunicationLog $log, User $actor): IntakeRequirement
    {
        $atom = RequirementDefinition::withTrashed()->firstOrCreate(
            ['key' => IntakeRequirement::KEY_INCOME_ELIGIBILITY],
            [
                'label' => 'Income Eligibility',
                'kind' => RequirementDefinition::KIND_DOC,
                'category' => RequirementDefinition::CATEGORY_ELIGIBILITY,
                'default_required' => true,
                'hint' => 'Annual household income, household size, and proof: pay stubs, tax return, benefit letters.',
                'created_by_user_id' => $actor->id,
            ]
        );

        $existing = IntakeRequirement::query()
            ->where('communication_log_id', $log->id)
            ->where('key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)
            ->first();

        if ($existing !== null) {
            $this->mergeViaTags($existing, ['standard'], $actor);
            if ($existing->is_orphaned) {
                $existing->unmarkOrphaned($actor);
            }

            return $existing;
        }

        return $this->insert($log, [
            // Document type so the existing upload plumbing carries the
            // pay stubs / tax return / benefit letters this collects.
            'type' => IntakeRequirement::TYPE_DOCUMENT,
            'label' => $atom->label,
            'key' => $atom->key,
            'kind' => $atom->kind,
            'via' => ['standard'],
            'source_type' => IntakeRequirement::SOURCE_DEFINITION,
            'source_template_id' => null,
            'source_definition_id' => $atom->id,
            'source_label_snapshot' => $atom->label,
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
        $result = collect();
        $fundTag = 'fund:'.$fundCode->id;

        // Phase 10+: the legacy fund_code_requirements stamping path has
        // been removed. Legacy rows were migrated forward into
        // requirement_definitions + fund_code_requirement_definitions
        // by migration 2026_05_25_140001 — they're now picked up by
        // the loop below. Stamping both paths would double-create
        // IntakeRequirement rows.
        //
        // Phase separation: intake stamps only PHASE_1 atoms
        // (eligibility + compliance). PHASE_2 atoms
        // (service_delivery_compliance) linked to the same fund code are
        // project-execution-only and flow through RequirementInstantiator.
        $atoms = $fundCode->requirementDefinitions()
            ->whereIn('category', RequirementDefinition::PHASE_1_CATEGORIES)
            ->get();

        foreach ($atoms as $atom) {
            $existing = IntakeRequirement::query()
                ->where('communication_log_id', $log->id)
                ->where('key', $atom->key)
                ->first();

            if ($existing !== null) {
                $this->mergeViaTags($existing, [$fundTag], $actor);
                if ($existing->is_orphaned) {
                    $existing->unmarkOrphaned($actor);
                }
                $result->push($existing);

                continue;
            }

            $result->push($this->insert($log, [
                'type' => $atom->kind === RequirementDefinition::KIND_DOC
                    ? IntakeRequirement::TYPE_DOCUMENT
                    : IntakeRequirement::TYPE_TASK,
                'label' => $atom->label,
                'key' => $atom->key,
                'kind' => $atom->kind,
                'via' => [$fundTag],
                'source_type' => IntakeRequirement::SOURCE_DEFINITION,
                'source_template_id' => null,
                'source_definition_id' => $atom->id,
                'source_label_snapshot' => $atom->label,
            ], $actor));
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

    // ─────────────────────────────────────────────────────────────
    // Comm Log v2 (Option A): unified-model entry points.
    // ─────────────────────────────────────────────────────────────

    /**
     * Stamp every atom in a bundle onto the log, deduping by atom key.
     *
     * If an instance already exists for an atom's key, the bundle's
     * source tag is appended to `via[]` (so attribution survives even
     * when the same atom is pulled in twice) and the existing instance
     * is un-orphaned if it was previously orphaned. Otherwise a new
     * instance is created with source_type=template_set.
     *
     * @return Collection<int, IntakeRequirement>
     */
    public function forTemplateSet(
        CommunicationLog $log,
        RequirementTemplateSet $set,
        User $actor,
        ?string $additionalViaTag = null
    ): Collection {
        // When invoked via reconcileTaggedServices (additionalViaTag is
        // a `service:N` tag), the bundle is only present because of that
        // service — so we tag with the service alone, not `bundle:N`.
        // That way, stripping the service tag on detach removes the
        // bundle's footprint and the instance can orphan correctly.
        //
        // When invoked manually (additionalViaTag is null), we tag with
        // `bundle:N` to record the manual apply, and that tag survives
        // any service-pivot reconciliation.
        $tags = $additionalViaTag !== null
            ? [$additionalViaTag]
            : ['bundle:'.$set->id];

        $atoms = $set->items()->get();
        if ($atoms->isEmpty()) {
            return collect();
        }

        return DB::transaction(function () use ($log, $atoms, $actor, $tags) {
            $existingByKey = IntakeRequirement::query()
                ->where('communication_log_id', $log->id)
                ->whereIn('key', $atoms->pluck('key')->all())
                ->get()
                ->keyBy('key');

            $result = collect();

            foreach ($atoms as $atom) {
                $existing = $existingByKey->get($atom->key);
                if ($existing !== null) {
                    $this->mergeViaTags($existing, $tags, $actor);
                    if ($existing->is_orphaned) {
                        $existing->unmarkOrphaned($actor);
                    }
                    $result->push($existing);

                    continue;
                }

                $row = $this->insert($log, [
                    'type' => $atom->kind === RequirementDefinition::KIND_DOC
                        ? IntakeRequirement::TYPE_DOCUMENT
                        : IntakeRequirement::TYPE_TASK,
                    'label' => $atom->label,
                    'key' => $atom->key,
                    'kind' => $atom->kind,
                    'via' => $tags,
                    'source_type' => IntakeRequirement::SOURCE_TEMPLATE_SET,
                    'source_template_id' => null,
                    'source_definition_id' => $atom->id,
                    'source_label_snapshot' => $atom->label,
                ], $actor);

                $result->push($row);
            }

            return $result;
        });
    }

    /**
     * Stamp a single atom onto the log (manual pick from the library —
     * not via a bundle). Deduped by key; on dupe, appends `definition:N`
     * to via[].
     */
    public function forDefinition(
        CommunicationLog $log,
        RequirementDefinition $atom,
        User $actor
    ): IntakeRequirement {
        $tag = 'definition:'.$atom->id;

        $existing = IntakeRequirement::query()
            ->where('communication_log_id', $log->id)
            ->where('key', $atom->key)
            ->first();

        if ($existing !== null) {
            $this->mergeViaTags($existing, [$tag], $actor);
            if ($existing->is_orphaned) {
                $existing->unmarkOrphaned($actor);
            }

            return $existing;
        }

        return $this->insert($log, [
            'type' => $atom->kind === RequirementDefinition::KIND_DOC
                ? IntakeRequirement::TYPE_DOCUMENT
                : IntakeRequirement::TYPE_TASK,
            'label' => $atom->label,
            'key' => $atom->key,
            'kind' => $atom->kind,
            'via' => [$tag],
            'source_type' => IntakeRequirement::SOURCE_DEFINITION,
            'source_template_id' => null,
            'source_definition_id' => $atom->id,
            'source_label_snapshot' => $atom->label,
        ], $actor);
    }

    /**
     * Comm Log v2 / Phase 9: stamp every requirement implied by a
     * HousingService (the Kaitlyn-curated catalog item) onto the log.
     *
     * Three sources contribute:
     *   1. atoms in every linked bundle (forTemplateSet, tagged via:'service:N')
     *   2. atoms linked directly to the service (no bundle wrapper)
     *   3. fund codes linked to the service (reconciled into the log's
     *      anticipated_fund_codes pivot — that in turn instantiates the
     *      legacy fund-code-driven eligibility templates via forFundCode).
     *
     * Idempotent on all three axes — re-running with the same service
     * merges via tags rather than creating dupes. Runs in a transaction.
     *
     * @return Collection<int, IntakeRequirement> all instances created or merged
     */
    public function forHousingService(
        CommunicationLog $log,
        HousingService $service,
        User $actor
    ): Collection {
        $serviceTag = 'service:'.$service->id;

        return DB::transaction(function () use ($log, $service, $actor, $serviceTag) {
            $result = collect();

            // Graduation to an intake: income eligibility is standing,
            // not service-specific, so it is stamped before anything the
            // service itself contributes.
            $result->push($this->incomeEligibility($log, $actor));

            // Phase 10 — bundle path removed. Atoms link directly to
            // services now via housing_service_definitions (and to fund
            // codes via fund_code_requirement_definitions, picked up
            // below as a side-effect of the fund-code reconcile).
            foreach ($service->requirementDefinitions()->get() as $atom) {
                $existing = IntakeRequirement::query()
                    ->where('communication_log_id', $log->id)
                    ->where('key', $atom->key)
                    ->first();

                if ($existing !== null) {
                    $this->mergeViaTags($existing, [$serviceTag], $actor);
                    if ($existing->is_orphaned) {
                        $existing->unmarkOrphaned($actor);
                    }
                    $result->push($existing);

                    continue;
                }

                $result->push($this->insert($log, [
                    'type' => $atom->kind === RequirementDefinition::KIND_DOC
                        ? IntakeRequirement::TYPE_DOCUMENT
                        : IntakeRequirement::TYPE_TASK,
                    'label' => $atom->label,
                    'key' => $atom->key,
                    'kind' => $atom->kind,
                    'via' => [$serviceTag],
                    'source_type' => IntakeRequirement::SOURCE_DEFINITION,
                    'source_template_id' => null,
                    'source_definition_id' => $atom->id,
                    'source_label_snapshot' => $atom->label,
                ], $actor));
            }

            // Fund codes — merge into the log's anticipatedFundCodes
            //    via the existing reconciler. This is additive: any
            //    funds already anticipated stay, and the service's funds
            //    are unioned in. This also picks up legacy fund-code-
            //    eligibility templates via forFundCode under the hood.
            $serviceFundIds = $service->fundCodes()->pluck('fund_codes.id')->all();
            if (! empty($serviceFundIds)) {
                $currentFundIds = $log->anticipatedFundCodes()->pluck('fund_codes.id')->all();
                $merged = array_values(array_unique(array_merge(
                    array_map('intval', $currentFundIds),
                    array_map('intval', $serviceFundIds)
                )));
                $this->reconcileAnticipatedFundCodes($log, $merged, $actor);
            }

            return $result;
        });
    }

    /**
     * Reconcile the log's tagged-services pivot against a target set.
     *
     * For each newly-added service: pull every bundle linked to that
     * service and instantiate via forTemplateSet, appending `service:N`
     * to each resulting instance's via[].
     *
     * For each newly-removed service: strip the `service:N` tags from
     * affected instances. If an instance's via[] becomes empty AND its
     * source_type=template_set (i.e. it was purely auto-instantiated
     * from a now-untagged service, never manually picked), mark it
     * orphaned. Manually-picked atoms (`source_type=definition`) survive
     * regardless.
     *
     * Runs inside a transaction so partial reconciliation cannot leave
     * the pivot and requirements out of sync.
     *
     * @param  array<int, int>  $newServiceIds
     */
    public function reconcileTaggedServices(
        CommunicationLog $log,
        array $newServiceIds,
        User $actor
    ): void {
        $newServiceIds = array_values(array_unique(array_map('intval', $newServiceIds)));

        DB::transaction(function () use ($log, $newServiceIds, $actor) {
            $currentIds = $log->services()->pluck('services.id')->all();

            $toAdd = array_diff($newServiceIds, $currentIds);
            $toRemove = array_diff($currentIds, $newServiceIds);

            if (! empty($toRemove)) {
                $log->services()->detach($toRemove);

                $removedTags = array_map(fn ($id) => 'service:'.$id, $toRemove);

                $affected = IntakeRequirement::query()
                    ->where('communication_log_id', $log->id)
                    ->get();

                foreach ($affected as $req) {
                    $via = is_array($req->via) ? $req->via : [];
                    if (empty(array_intersect($removedTags, $via))) {
                        continue;
                    }

                    $remaining = array_values(array_diff($via, $removedTags));
                    $req->via = $remaining;
                    $req->updated_by_user_id = $actor->id;
                    $req->save();

                    if (empty($remaining)
                        && $req->source_type === IntakeRequirement::SOURCE_TEMPLATE_SET
                        && ! $req->is_orphaned) {
                        $req->markOrphaned($actor);
                    }
                }
            }

            if (! empty($toAdd)) {
                // Attaching a service is what makes this an intake; the
                // standing income-eligibility requirement comes with it.
                $this->incomeEligibility($log, $actor);
            }

            foreach ($toAdd as $serviceId) {
                $log->services()->attach($serviceId);

                $serviceTag = 'service:'.$serviceId;
                $bundleIds = DB::table('program_service_requirement_template_sets')
                    ->where('service_id', $serviceId)
                    ->pluck('template_set_id')
                    ->all();

                foreach ($bundleIds as $bundleId) {
                    $bundle = RequirementTemplateSet::find($bundleId);
                    if ($bundle !== null) {
                        $this->forTemplateSet($log, $bundle, $actor, $serviceTag);
                    }
                }
            }
        });
    }

    /**
     * Convert-to-Project carry-forward.
     *
     * The housing module is now self-contained: a project is born from
     * the county/counties and fund codes selected during intake, NOT from
     * the legacy Program/Service tie-in. On promotion we:
     *
     *   1. Record the requirement-override audit on the new job (if the
     *      promoter overrode an unmet Phase 1 gate).
     *   2. Carry the log's selected counties (HousingService rows) onto
     *      the job's housing_job_housing_service pivot — historical record
     *      of which county the project serves.
     *   3. Carry the log's anticipated fund codes onto the job's
     *      fund_code_housing_job pivot — these become the project's
     *      funding sources for the budget tab.
     *   4. Instantiate the Phase 2 (project-phase / service-delivery-
     *      compliance) requirements from each carried fund code, via
     *      RequirementInstantiator::forFundCode. These are the
     *      requirements the project manager works through on the project
     *      page; the milestones tab tracks how the fund codes are used to
     *      deliver services.
     *
     * Phase 1 (eligibility/compliance) intake requirements deliberately
     * stay on the Communication Log as the historical intake record — they
     * are NOT copied onto the project (see CLAUDE.md "Intake vs. Project
     * requirements"). All three carry steps are idempotent: fund codes and
     * counties sync-without-detach, and forFundCode skips definitions that
     * already have a row on this project.
     *
     * @return int the number of Phase 2 project requirements created
     */
    public function carryForwardToProject(
        CommunicationLog $log,
        HousingJob $job,
        User $actor,
        ?string $overrideReason = null
    ): int {
        return DB::transaction(function () use ($log, $job, $actor, $overrideReason) {
            if ($overrideReason !== null && trim($overrideReason) !== '') {
                $job->requirement_override_reason = $overrideReason;
                $job->requirement_override_by_user_id = $actor->id;
                $job->requirement_overridden_at = now();
                $job->save();
            }

            // (2) Counties → job. Stamp created_by/created_at on the pivot
            // (the table carries no updated_at), matching the comm-log side.
            $countyIds = $log->housingServices()->pluck('housing_services.id')->all();
            if (! empty($countyIds)) {
                $pivot = [];
                foreach ($countyIds as $id) {
                    $pivot[$id] = [
                        'created_by_user_id' => $actor->id,
                        'created_at' => now(),
                    ];
                }
                $job->housingServices()->syncWithoutDetaching($pivot);
            }

            // (3) Fund codes → job. The fund_code_housing_job pivot uses
            // standard timestamps, so a plain id sync suffices.
            $fundCodeIds = $log->anticipatedFundCodes()->pluck('fund_codes.id')->all();
            if (! empty($fundCodeIds)) {
                $job->fundCodes()->syncWithoutDetaching($fundCodeIds);
            }

            // (4) Phase 2 requirements from each carried fund code.
            $instantiator = app(RequirementInstantiator::class);
            $created = 0;
            foreach ($job->fundCodes()->get() as $fundCode) {
                $created += $instantiator->forFundCode($job, $fundCode, $actor)->count();
            }

            return $created;
        });
    }

    /**
     * Append-and-dedupe tags into an instance's via[] array. Saves only
     * if anything actually changed.
     */
    protected function mergeViaTags(IntakeRequirement $req, array $newTags, User $actor): void
    {
        $current = is_array($req->via) ? $req->via : [];
        $merged = array_values(array_unique(array_merge($current, $newTags)));
        if ($merged === $current) {
            return;
        }
        $req->via = $merged;
        $req->updated_by_user_id = $actor->id;
        $req->save();
    }

    /**
     * Shared insert path. Sets default status, stamps created_by_user_id
     * and updated_by_user_id from the actor.
     */
    protected function insert(CommunicationLog $log, array $attributes, User $actor): IntakeRequirement
    {
        $req = new IntakeRequirement(array_merge([
            'status' => IntakeRequirement::STATUS_PENDING,
            'is_orphaned' => false,
        ], $attributes));

        $req->communication_log_id = $log->id;
        $req->created_by_user_id = $actor->id;
        $req->updated_by_user_id = $actor->id;
        $req->save();

        return $req;
    }
}
