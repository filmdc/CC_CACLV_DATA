<?php

/*
| Phase 10.2: forFundCode also stamps atoms attached via the new
| fund_code_requirement_definitions pivot. The legacy
| fund_code_requirements (P-39b) path still fires.
*/

use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\IntakeRequirement;
use App\Models\RequirementDefinition;
use App\Services\IntakeRequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->actor = RegressionHelpers::adminUser();
    $this->service = app(IntakeRequirementInstantiator::class);
});

it('forFundCode stamps atoms linked via fund_code_requirement_definitions with via:fund:N', function () {
    $log = CommunicationLog::factory()->create();
    $fund = FundCode::factory()->create(['code' => 'FC-HSG-1']);
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $fund->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    $this->service->forFundCode($log, $fund, $this->actor);

    $req = IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', 'photo-id')->first();
    expect($req)->not->toBeNull();
    expect($req->via)->toContain('fund:'.$fund->id);
    expect($req->source_type)->toBe(IntakeRequirement::SOURCE_DEFINITION);
})->group('regression');

it('forFundCode is idempotent across re-runs (atom dedupe by key)', function () {
    $log = CommunicationLog::factory()->create();
    $fund = FundCode::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $fund->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    $this->service->forFundCode($log, $fund, $this->actor);
    $this->service->forFundCode($log, $fund, $this->actor);

    expect(IntakeRequirement::where('communication_log_id', $log->id)->where('key', 'photo-id')->count())->toBe(1);
})->group('regression');

it('reconcileAnticipatedFundCodes triggers the new atom-stamp path transparently', function () {
    $log = CommunicationLog::factory()->create();
    $fund = FundCode::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'income-verif']);
    $fund->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    $this->service->reconcileAnticipatedFundCodes($log, [$fund->id], $this->actor);

    expect(IntakeRequirement::where('communication_log_id', $log->id)->where('key', 'income-verif')->count())->toBe(1);
})->group('regression');

it('forHousingService no longer stamps via bundles (only direct atoms + fund codes)', function () {
    // Old path used templateSets; Phase 10 only relies on requirementDefinitions().
    // Bundle relation is irrelevant to the stamping path now.
    $log = CommunicationLog::factory()->create();
    $svc = \App\Models\HousingService::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $svc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    $result = $this->service->forHousingService($log, $svc, $this->actor)
        ->reject(fn ($r) => $r->key === IntakeRequirement::KEY_INCOME_ELIGIBILITY);

    expect($result)->toHaveCount(1);
    expect($result->first()->key)->toBe('photo-id');
})->group('regression');
