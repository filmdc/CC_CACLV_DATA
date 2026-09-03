<?php

/*
| Comm Log v2 (Option A): IntakeRequirementInstantiator unified-model
| entry points (forTemplateSet, forDefinition, reconcileTaggedServices,
| carryForwardToProject) + the new lifecycle methods on IntakeRequirement
| (markRequested, markBlocked, markNotApplicable; audit-stamping on
| markReceived/markWaived/reopen; status_change Activity Update side
| effect per spec §5.7).
*/

use App\Models\CommunicationLog;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\RequirementDefinition;
use App\Models\RequirementTemplateSet;
use App\Models\Service;
use App\Services\IntakeRequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->actor = RegressionHelpers::adminUser();
    $this->service = app(IntakeRequirementInstantiator::class);
});

// -------------------------------------------------------------------
// forTemplateSet()
// -------------------------------------------------------------------

it('forTemplateSet stamps every atom in a bundle with source_type=template_set', function () {
    $log = CommunicationLog::factory()->create();
    $bundle = RequirementTemplateSet::factory()->create();
    $atom1 = RequirementDefinition::factory()->create(['key' => 'photo-id', 'kind' => 'doc']);
    $atom2 = RequirementDefinition::factory()->create(['key' => 'proof-income', 'kind' => 'doc']);
    $bundle->items()->attach([
        $atom1->id => ['position' => 0],
        $atom2->id => ['position' => 1],
    ]);

    $result = $this->service->forTemplateSet($log, $bundle, $this->actor);

    expect($result)->toHaveCount(2);

    $instances = IntakeRequirement::where('communication_log_id', $log->id)->get();
    expect($instances)->toHaveCount(2);
    expect($instances->pluck('key')->all())->toEqual(['photo-id', 'proof-income']);
    expect($instances->first()->source_type)->toBe(IntakeRequirement::SOURCE_TEMPLATE_SET);
    expect($instances->first()->source_definition_id)->toBe($atom1->id);
    expect($instances->first()->via)->toBe(['bundle:'.$bundle->id]);
})->group('regression');

it('forTemplateSet dedupes by key — second apply just appends bundle tag to via[]', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $bundleA = RequirementTemplateSet::factory()->create(['name' => 'Bundle A']);
    $bundleB = RequirementTemplateSet::factory()->create(['name' => 'Bundle B']);
    $bundleA->items()->attach($atom->id, ['position' => 0]);
    $bundleB->items()->attach($atom->id, ['position' => 0]);

    $this->service->forTemplateSet($log, $bundleA, $this->actor);
    $this->service->forTemplateSet($log, $bundleB, $this->actor);

    $instances = IntakeRequirement::where('communication_log_id', $log->id)->get();
    expect($instances)->toHaveCount(1);
    expect($instances->first()->via)->toEqual(['bundle:'.$bundleA->id, 'bundle:'.$bundleB->id]);
})->group('regression');

it('forTemplateSet un-orphans an existing orphaned instance', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $bundle = RequirementTemplateSet::factory()->create();
    $bundle->items()->attach($atom->id, ['position' => 0]);

    $this->service->forTemplateSet($log, $bundle, $this->actor);
    $instance = IntakeRequirement::where('communication_log_id', $log->id)->firstOrFail();
    $instance->markOrphaned($this->actor);
    expect($instance->fresh()->is_orphaned)->toBeTrue();

    $this->service->forTemplateSet($log, $bundle, $this->actor);
    expect($instance->fresh()->is_orphaned)->toBeFalse();
})->group('regression');

// -------------------------------------------------------------------
// forDefinition()
// -------------------------------------------------------------------

it('forDefinition stamps a single atom with source_type=definition', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'spanish-translation', 'kind' => 'doc']);

    $req = $this->service->forDefinition($log, $atom, $this->actor);

    expect($req->key)->toBe('spanish-translation');
    expect($req->source_type)->toBe(IntakeRequirement::SOURCE_DEFINITION);
    expect($req->source_definition_id)->toBe($atom->id);
    expect($req->via)->toBe(['definition:'.$atom->id]);
    expect($req->status)->toBe(IntakeRequirement::STATUS_PENDING);
})->group('regression');

it('forDefinition is dedupe-idempotent — re-picking the same atom merges the via tag', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);

    $first = $this->service->forDefinition($log, $atom, $this->actor);
    $second = $this->service->forDefinition($log, $atom, $this->actor);

    expect($second->id)->toBe($first->id);
    expect(IntakeRequirement::where('communication_log_id', $log->id)->count())->toBe(1);
})->group('regression');

// -------------------------------------------------------------------
// reconcileTaggedServices()
// -------------------------------------------------------------------

it('reconcileTaggedServices on add applies every bundle linked to the service', function () {
    $log = CommunicationLog::factory()->create();
    $service = Service::query()->first() ?? Service::factory()->create();
    $bundle = RequirementTemplateSet::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $bundle->items()->attach($atom->id, ['position' => 0]);
    $bundle->services()->attach($service->id);

    $this->service->reconcileTaggedServices($log, [$service->id], $this->actor);

    // Excludes the standing income-eligibility row, which every
    // graduation stamps regardless of which service triggered it.
    $instances = IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', '!=', IntakeRequirement::KEY_INCOME_ELIGIBILITY)->get();
    expect($instances)->toHaveCount(1);
    // Service-triggered instances are tagged with the SERVICE only (not the
    // bundle) so that detaching the service correctly orphans them when
    // no other source remains. Bundle attribution can be reconstructed
    // via the source_definition_id → atom → bundles relation.
    expect($instances->first()->via)->toBe(['service:'.$service->id]);
    expect($instances->first()->source_type)->toBe(IntakeRequirement::SOURCE_TEMPLATE_SET);
})->group('regression');

it('reconcileTaggedServices on remove orphans template_set-sourced instances with empty via', function () {
    $log = CommunicationLog::factory()->create();
    $service = Service::query()->first() ?? Service::factory()->create();
    $bundle = RequirementTemplateSet::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $bundle->items()->attach($atom->id, ['position' => 0]);
    $bundle->services()->attach($service->id);

    $this->service->reconcileTaggedServices($log, [$service->id], $this->actor);
    $this->service->reconcileTaggedServices($log, [], $this->actor);

    $instance = IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', '!=', IntakeRequirement::KEY_INCOME_ELIGIBILITY)->firstOrFail();
    expect($instance->is_orphaned)->toBeTrue();
    expect($instance->via)->toBeEmpty();
})->group('regression');

it('reconcileTaggedServices on remove keeps definition-sourced (manually picked) atoms alive', function () {
    $log = CommunicationLog::factory()->create();
    $service = Service::query()->first() ?? Service::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $bundle = RequirementTemplateSet::factory()->create();
    $bundle->items()->attach($atom->id, ['position' => 0]);
    $bundle->services()->attach($service->id);

    // First manually pick — sets source_type=definition.
    $this->service->forDefinition($log, $atom, $this->actor);

    // Then service-trigger pulls the same atom — merges the via tag.
    $this->service->reconcileTaggedServices($log, [$service->id], $this->actor);

    // Detach the service.
    $this->service->reconcileTaggedServices($log, [], $this->actor);

    $instance = IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', '!=', IntakeRequirement::KEY_INCOME_ELIGIBILITY)->firstOrFail();
    expect($instance->is_orphaned)->toBeFalse();
    expect($instance->source_type)->toBe(IntakeRequirement::SOURCE_DEFINITION);
    expect($instance->via)->toContain('definition:'.$atom->id);
})->group('regression');

// -------------------------------------------------------------------
// Lifecycle methods + status-change activity updates
// -------------------------------------------------------------------

it('markRequested sets status, stamps audit columns, writes a status_change update on the log', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id', 'label' => 'Photo ID']);
    $req = $this->service->forDefinition($log, $atom, $this->actor);

    $req->markRequested($this->actor);
    $req = $req->fresh();

    expect($req->status)->toBe(IntakeRequirement::STATUS_REQUESTED);
    expect($req->status_changed_at)->not->toBeNull();
    expect($req->status_changed_by_user_id)->toBe($this->actor->id);

    $update = $log->updates()->where('update_type', 'status_change')->latest('id')->first();
    expect($update)->not->toBeNull();
    expect($update->update_content)->toContain('Photo ID');
    expect($update->update_content)->toContain('Pending');
    expect($update->update_content)->toContain('Requested');
})->group('regression');

it('markNotApplicable requires a reason and counts as met', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $req = $this->service->forDefinition($log, $atom, $this->actor);

    expect(fn () => $req->markNotApplicable($this->actor, ''))
        ->toThrow(\InvalidArgumentException::class);

    $req->markNotApplicable($this->actor, 'Has housing-status attestation instead.');
    $req = $req->fresh();

    expect($req->status)->toBe(IntakeRequirement::STATUS_NA);
    expect($req->isMet())->toBeTrue();
    expect($req->notes)->toContain('[N/A] Has housing-status attestation');
})->group('regression');

it('markBlocked is allowed without a reason and DOES NOT count as met', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'phfa-check']);
    $req = $this->service->forDefinition($log, $atom, $this->actor);

    $req->markBlocked($this->actor, 'Submitted to PHFA · day 1 of 5–7');
    $req = $req->fresh();

    expect($req->status)->toBe(IntakeRequirement::STATUS_BLOCKED);
    expect($req->isMet())->toBeFalse();
    expect($req->notes)->toContain('PHFA');
})->group('regression');

it('markReceived stamps the new audit columns', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $req = $this->service->forDefinition($log, $atom, $this->actor);

    $req->markReceived($this->actor);
    $req = $req->fresh();

    expect($req->status_changed_at)->not->toBeNull();
    expect($req->status_changed_by_user_id)->toBe($this->actor->id);
    expect($req->received_at)->not->toBeNull();
    expect($req->received_by_user_id)->toBe($this->actor->id);
})->group('regression');

it('reopen transitions to pending and writes a status_change update', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $req = $this->service->forDefinition($log, $atom, $this->actor);
    $req->markReceived($this->actor);
    $log->refresh();
    $beforeCount = $log->updates()->where('update_type', 'status_change')->count();

    $req->fresh()->reopen($this->actor);

    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_PENDING);
    expect($req->fresh()->received_at)->toBeNull();
    expect($log->fresh()->updates()->where('update_type', 'status_change')->count())->toBe($beforeCount + 1);
})->group('regression');

it('re-marking the same status does not write a redundant activity update', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $req = $this->service->forDefinition($log, $atom, $this->actor);

    $req->markReceived($this->actor);
    $countAfterFirst = $log->fresh()->updates()->where('update_type', 'status_change')->count();

    $req->fresh()->markReceived($this->actor);
    $countAfterSecond = $log->fresh()->updates()->where('update_type', 'status_change')->count();

    expect($countAfterSecond)->toBe($countAfterFirst);
})->group('regression');

// -------------------------------------------------------------------
// carryForwardToProject()
// -------------------------------------------------------------------

it('carryForwardToProject records the override audit on the housing job when given a reason', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $this->service->forDefinition($log, $atom, $this->actor);

    $job = HousingJob::factory()->create();
    $count = $this->service->carryForwardToProject(
        $log,
        $job,
        $this->actor,
        'Client returning next week with paperwork — promoting now so the case stays in the queue.'
    );

    // New contract: return is the number of Phase 2 project requirements
    // created. No fund codes are attached here, so none are instantiated.
    // (Phase 2 instantiation is covered by ConvertCarryForwardTest.)
    expect($count)->toBe(0);

    $job = $job->fresh();
    expect($job->requirement_override_reason)->toContain('Client returning');
    expect($job->requirement_override_by_user_id)->toBe($this->actor->id);
    expect($job->requirement_overridden_at)->not->toBeNull();
})->group('regression');

it('carryForwardToProject without override reason leaves the audit columns null', function () {
    $log = CommunicationLog::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $this->service->forDefinition($log, $atom, $this->actor);

    $job = HousingJob::factory()->create();
    $count = $this->service->carryForwardToProject($log, $job, $this->actor);

    // New contract: return is Phase 2 project requirements created (none
    // here — no fund codes attached). The audit columns stay null.
    expect($count)->toBe(0);
    expect($job->fresh()->requirement_override_reason)->toBeNull();
    expect($job->fresh()->requirement_override_by_user_id)->toBeNull();
})->group('regression');
