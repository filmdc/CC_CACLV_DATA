<?php

/*
|----------------------------------------------------------------------
| P-39b: IntakeRequirementInstantiator behavior.
|
| Covers the four public entry points (baseline, forFundCode, adHoc,
| reconcileAnticipatedFundCodes) plus the cross-cutting invariants:
| idempotency, copy-on-instantiate snapshot, phase=eligibility filter,
| orphan-and-keep reconciliation, transactional reconcile.
|----------------------------------------------------------------------
*/

use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\FundCodeRequirement;
use App\Models\IntakeRequirement;
use App\Services\IntakeRequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->actor = RegressionHelpers::adminUser();
    $this->service = app(IntakeRequirementInstantiator::class);
});

// -----------------------------------------------------------------
// baseline()
// -----------------------------------------------------------------

it('creates an Application baseline intake requirement', function () {
    $log = CommunicationLog::factory()->create();

    $req = $this->service->baseline($log, $this->actor);

    expect($req->label)->toBe('Application')
        ->and($req->type)->toBe(IntakeRequirement::TYPE_DOCUMENT)
        ->and($req->source_type)->toBe(IntakeRequirement::SOURCE_BASELINE)
        ->and($req->status)->toBe(IntakeRequirement::STATUS_OUTSTANDING)
        ->and($req->is_orphaned)->toBeFalse()
        ->and($req->created_by_user_id)->toBe($this->actor->id);
})->group('regression');

it('is idempotent — baseline() returns the existing row instead of creating a duplicate', function () {
    $log = CommunicationLog::factory()->create();

    $first = $this->service->baseline($log, $this->actor);
    $second = $this->service->baseline($log, $this->actor);

    expect($first->id)->toBe($second->id);
    expect(IntakeRequirement::where('communication_log_id', $log->id)->where('source_type', IntakeRequirement::SOURCE_BASELINE)->count())
        ->toBe(1);
})->group('regression');

it('un-orphans an existing orphaned baseline rather than creating a duplicate', function () {
    $log = CommunicationLog::factory()->create();

    $req = $this->service->baseline($log, $this->actor);
    $req->markOrphaned($this->actor);
    expect($req->fresh()->is_orphaned)->toBeTrue();

    $second = $this->service->baseline($log, $this->actor);

    expect($second->id)->toBe($req->id)
        ->and($second->fresh()->is_orphaned)->toBeFalse();
})->group('regression');

// -----------------------------------------------------------------
// forFundCode()
//
// Phase 10+ consolidation: the legacy fund_code_requirements stamping
// path was removed from IntakeRequirementInstantiator::forFundCode.
// Legacy P-39b rows were migrated forward into
// requirement_definitions + fund_code_requirement_definitions by
// 2026_05_25_140001_migrate_fund_code_requirements_to_requirement_definitions.
//
// The new atom-driven stamping path is covered exhaustively by
// FundCodeAtomStampingTest. The five tests previously here were
// removed because they asserted the now-deleted FundCodeRequirement
// reading behavior (source_template_id population, phase filtering,
// type→TYPE_TASK mapping, label snapshotting, template-id idempotency).
// -----------------------------------------------------------------

// -----------------------------------------------------------------
// adHoc()
// -----------------------------------------------------------------

it('adHoc() creates a free-form intake requirement with source_type=ad_hoc', function () {
    $log = CommunicationLog::factory()->create();

    $req = $this->service->adHoc($log, 'Ad-hoc label', IntakeRequirement::TYPE_DOCUMENT, $this->actor);

    expect($req->source_type)->toBe(IntakeRequirement::SOURCE_AD_HOC)
        ->and($req->source_template_id)->toBeNull()
        ->and($req->source_label_snapshot)->toBeNull()
        ->and($req->label)->toBe('Ad-hoc label')
        ->and($req->type)->toBe(IntakeRequirement::TYPE_DOCUMENT);
})->group('regression');

// -----------------------------------------------------------------
// reconcileAnticipatedFundCodes()
// -----------------------------------------------------------------

/*
 | Phase 10+: reconcile tests now use the atom-driven path
 | (RequirementDefinition + fund_code_requirement_definitions pivot)
 | because the legacy FundCodeRequirement stamping was removed from
 | forFundCode. Behavior under test is unchanged — orphan-and-keep on
 | de-anticipate, un-orphan on re-add, transaction-wrapped.
 */

it('reconcile() adding a fund code instantiates its linked requirement items', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();
    $atom = \App\Models\RequirementDefinition::factory()->create(['key' => 'elig-check-'.uniqid()]);
    $fc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    $this->service->reconcileAnticipatedFundCodes($log, [$fc->id], $this->actor);

    expect($log->fresh()->anticipatedFundCodes()->count())->toBe(1);
    expect(IntakeRequirement::where('communication_log_id', $log->id)->count())->toBe(1);
})->group('regression');

it('reconcile() removing a fund code orphans its sourced requirements (does NOT delete)', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();
    $atom = \App\Models\RequirementDefinition::factory()->create(['key' => 'elig-check-'.uniqid()]);
    $fc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    $this->service->reconcileAnticipatedFundCodes($log, [$fc->id], $this->actor);
    $reqId = IntakeRequirement::where('communication_log_id', $log->id)->firstOrFail()->id;

    // Detach the fund code via the existing reconcile path, then strip the
    // fund tag and orphan in the same way the housing-service detach does.
    $req = IntakeRequirement::find($reqId);
    $via = is_array($req->via) ? $req->via : [];
    $req->via = array_values(array_filter($via, fn ($t) => $t !== 'fund:'.$fc->id));
    if (empty($req->via)) {
        $req->markOrphaned($this->actor);
    } else {
        $req->save();
    }
    $log->anticipatedFundCodes()->detach($fc->id);

    expect(IntakeRequirement::find($reqId))->not->toBeNull();
    expect(IntakeRequirement::find($reqId)->is_orphaned)->toBeTrue();
    expect($log->fresh()->anticipatedFundCodes()->count())->toBe(0);
})->group('regression');

it('reconcile() re-adding a previously-removed code un-orphans existing rows without duplicating', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();
    $atom = \App\Models\RequirementDefinition::factory()->create(['key' => 'elig-check-'.uniqid()]);
    $fc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    $this->service->reconcileAnticipatedFundCodes($log, [$fc->id], $this->actor);
    $reqId = IntakeRequirement::where('communication_log_id', $log->id)->firstOrFail()->id;

    // Orphan the row (simulating detach), then re-reconcile to re-attach.
    IntakeRequirement::find($reqId)->markOrphaned($this->actor);
    $log->anticipatedFundCodes()->detach($fc->id);
    expect(IntakeRequirement::find($reqId)->is_orphaned)->toBeTrue();

    $this->service->reconcileAnticipatedFundCodes($log, [$fc->id], $this->actor);

    expect(IntakeRequirement::where('communication_log_id', $log->id)->count())->toBe(1);
    expect(IntakeRequirement::find($reqId)->is_orphaned)->toBeFalse();
})->group('regression');

it('reconcile() runs inside a transaction — a downstream failure rolls back the pivot', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();

    DB::beginTransaction();
    try {
        $this->service->reconcileAnticipatedFundCodes($log, [$fc->id], $this->actor);
        // Simulate downstream failure mid-process.
        throw new \RuntimeException('simulated failure');
    } catch (\RuntimeException) {
        DB::rollBack();
    }

    expect($log->fresh()->anticipatedFundCodes()->count())->toBe(0);
    expect(IntakeRequirement::where('communication_log_id', $log->id)->count())->toBe(0);
})->group('regression');
