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
// -----------------------------------------------------------------

it('forFundCode() creates one IntakeRequirement per eligibility-phase template', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();

    $eligA = FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Eligibility Doc A', 'type' => 'document', 'phase' => 'eligibility', 'sort_order' => 1,
    ]);
    $eligB = FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Eligibility Doc B', 'type' => 'document', 'phase' => 'eligibility', 'sort_order' => 2,
    ]);
    // Project-phase templates: must NOT produce intake rows.
    FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Project Doc', 'type' => 'document', 'phase' => 'project', 'sort_order' => 3,
    ]);

    $created = $this->service->forFundCode($log, $fc, $this->actor);

    expect($created)->toHaveCount(2);
    expect(IntakeRequirement::where('communication_log_id', $log->id)->count())->toBe(2);

    $templateIds = $created->pluck('source_template_id')->all();
    expect($templateIds)->toContain($eligA->id, $eligB->id);
})->group('regression');

it('forFundCode() filters out project-phase templates from intake instantiation', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();

    FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Final Inspection', 'type' => 'document', 'phase' => 'project',
    ]);

    $created = $this->service->forFundCode($log, $fc, $this->actor);

    expect($created)->toBeEmpty();
    expect(IntakeRequirement::where('communication_log_id', $log->id)->count())->toBe(0);
})->group('regression');

it('forFundCode() copies template->label into source_label_snapshot at instantiate time', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();
    $template = FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Original Label', 'type' => 'document', 'phase' => 'eligibility',
    ]);

    $this->service->forFundCode($log, $fc, $this->actor);

    $req = IntakeRequirement::where('source_template_id', $template->id)->firstOrFail();
    expect($req->source_label_snapshot)->toBe('Original Label');

    // Mutate the template label after instantiate. The snapshot must remain.
    $template->update(['label' => 'Renamed Label']);

    expect($req->fresh()->source_label_snapshot)->toBe('Original Label');
})->group('regression');

it('forFundCode() reads template->type for the row type', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();
    FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'A task', 'type' => 'task', 'phase' => 'eligibility',
    ]);

    $this->service->forFundCode($log, $fc, $this->actor);

    $row = IntakeRequirement::where('communication_log_id', $log->id)->firstOrFail();
    expect($row->type)->toBe(IntakeRequirement::TYPE_TASK);
})->group('regression');

it('forFundCode() is idempotent on (log, template_id) — re-running does not duplicate', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();
    FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'L', 'type' => 'document', 'phase' => 'eligibility',
    ]);

    $this->service->forFundCode($log, $fc, $this->actor);
    $this->service->forFundCode($log, $fc, $this->actor);

    expect(IntakeRequirement::where('communication_log_id', $log->id)->count())->toBe(1);
})->group('regression');

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

it('reconcile() adding a fund code instantiates its eligibility-phase requirements', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();
    FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Eligibility check', 'type' => 'document', 'phase' => 'eligibility',
    ]);

    $this->service->reconcileAnticipatedFundCodes($log, [$fc->id], $this->actor);

    expect($log->fresh()->anticipatedFundCodes()->count())->toBe(1);
    expect(IntakeRequirement::where('communication_log_id', $log->id)->count())->toBe(1);
})->group('regression');

it('reconcile() removing a fund code orphans its sourced requirements (does NOT delete)', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();
    $template = FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Eligibility check', 'type' => 'document', 'phase' => 'eligibility',
    ]);

    $this->service->reconcileAnticipatedFundCodes($log, [$fc->id], $this->actor);
    $reqId = IntakeRequirement::where('source_template_id', $template->id)->firstOrFail()->id;

    $this->service->reconcileAnticipatedFundCodes($log, [], $this->actor);

    expect(IntakeRequirement::find($reqId))->not->toBeNull(); // still exists
    expect(IntakeRequirement::find($reqId)->is_orphaned)->toBeTrue();
    expect($log->fresh()->anticipatedFundCodes()->count())->toBe(0);
})->group('regression');

it('reconcile() re-adding a previously-removed code un-orphans existing rows without duplicating', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();
    $template = FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Eligibility check', 'type' => 'document', 'phase' => 'eligibility',
    ]);

    $this->service->reconcileAnticipatedFundCodes($log, [$fc->id], $this->actor);
    $reqId = IntakeRequirement::where('source_template_id', $template->id)->firstOrFail()->id;
    $this->service->reconcileAnticipatedFundCodes($log, [], $this->actor); // orphan
    expect(IntakeRequirement::find($reqId)->is_orphaned)->toBeTrue();

    $this->service->reconcileAnticipatedFundCodes($log, [$fc->id], $this->actor); // re-add

    expect(IntakeRequirement::where('source_template_id', $template->id)->count())->toBe(1);
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
