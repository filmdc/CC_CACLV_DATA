<?php

/*
| Phase 9.5: IntakeRequirementInstantiator::forHousingService — auto-
| stamping when a HousingService is picked on a Communication Log.
*/

use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HousingService;
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

it('forHousingService stamps atoms attached directly to the service (Phase 10: bundle path removed)', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    // Phase 10: atoms link directly to the service, no bundle wrapper.
    $svc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    // The standing income-eligibility row rides along on every graduation;
    // it is not service-sourced, so it is excluded from what this pins.
    $result = $this->service->forHousingService($log, $svc, $this->actor)
        ->reject(fn ($r) => $r->key === IntakeRequirement::KEY_INCOME_ELIGIBILITY);

    expect($result)->toHaveCount(1);
    expect(IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', '!=', IntakeRequirement::KEY_INCOME_ELIGIBILITY)->count())->toBe(1);
    $req = IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', '!=', IntakeRequirement::KEY_INCOME_ELIGIBILITY)->first();
    expect($req->via)->toBe(['service:'.$svc->id]);
})->group('regression');

it('forHousingService stamps directly-linked atoms (no bundle)', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'hmis-consent', 'kind' => 'form']);
    $svc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    $result = $this->service->forHousingService($log, $svc, $this->actor)
        ->reject(fn ($r) => $r->key === IntakeRequirement::KEY_INCOME_ELIGIBILITY);

    expect($result)->toHaveCount(1);
    $req = $result->first();
    expect($req->key)->toBe('hmis-consent');
    expect($req->source_type)->toBe(IntakeRequirement::SOURCE_DEFINITION);
    expect($req->via)->toBe(['service:'.$svc->id]);
})->group('regression');

it('forHousingService auto-attaches linked fund codes to anticipatedFundCodes', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $fund = FundCode::factory()->create(['code' => 'HSG-AUTO']);
    $svc->fundCodes()->attach($fund->id, ['created_at' => now()]);

    expect($log->anticipatedFundCodes()->count())->toBe(0);

    $this->service->forHousingService($log, $svc, $this->actor);

    expect($log->fresh()->anticipatedFundCodes->pluck('id')->all())->toContain($fund->id);
})->group('regression');

it('forHousingService is idempotent — re-running merges via tags', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $svc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    $this->service->forHousingService($log, $svc, $this->actor);
    $this->service->forHousingService($log, $svc, $this->actor);

    expect(IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', '!=', IntakeRequirement::KEY_INCOME_ELIGIBILITY)->count())->toBe(1);
    // ...and the standing row is stamped exactly once too.
    expect(IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)->count())->toBe(1);
})->group('regression');

it('forHousingService preserves fund codes already on the log', function () {
    $log = CommunicationLog::factory()->create();
    $existingFund = FundCode::factory()->create(['code' => 'PRE-EXISTING']);
    $log->anticipatedFundCodes()->attach($existingFund->id, [
        'created_by_user_id' => $this->actor->id,
        'created_at' => now(),
    ]);

    $svc = HousingService::factory()->create();
    $newFund = FundCode::factory()->create(['code' => 'NEW-FROM-SVC']);
    $svc->fundCodes()->attach($newFund->id, ['created_at' => now()]);

    $this->service->forHousingService($log, $svc, $this->actor);

    $codes = $log->fresh()->anticipatedFundCodes->pluck('code')->all();
    expect($codes)->toContain('PRE-EXISTING');
    expect($codes)->toContain('NEW-FROM-SVC');
})->group('regression');
