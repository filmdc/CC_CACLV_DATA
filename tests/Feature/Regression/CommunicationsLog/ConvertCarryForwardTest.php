<?php

/*
| Comm Log v2 / Convert-to-Project carry-forward.
|
| The housing module is self-contained: a project is born from the
| counties + fund codes selected during intake, NOT the legacy
| Program/Service tie-in. On promotion, IntakeRequirementInstantiator::
| carryForwardToProject must:
|   - carry the log's counties onto the job (housing_job_housing_service);
|   - carry the log's anticipated fund codes onto the job;
|   - instantiate Phase 2 (service_delivery_compliance) requirements from
|     each carried fund code;
|   - leave Phase 1 intake requirements on the log (not copied to the job);
|   - record the override audit when a Phase 1 gate was overridden;
|   - be idempotent.
*/

use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\HousingService;
use App\Models\IntakeRequirement;
use App\Models\Requirement;
use App\Models\RequirementDefinition;
use App\Services\IntakeRequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->actor = RegressionHelpers::adminUser();
    $this->service = app(IntakeRequirementInstantiator::class);
});

function ccfFreshJob(): HousingJob
{
    return HousingJob::create([
        'job_title' => 'Carry-forward subject',
        'status' => 'pending',
        'priority' => 'medium',
        'request_date' => now(),
        'created_by' => RegressionHelpers::adminUser()->id,
        'project_manager_id' => RegressionHelpers::adminUser()->id,
    ]);
}

it('carries the log counties onto the new job', function () {
    $log = CommunicationLog::factory()->create();
    $county = HousingService::factory()->create(['name' => 'Lehigh County']);
    $log->housingServices()->attach($county->id, [
        'created_by_user_id' => $this->actor->id,
        'created_at' => now(),
    ]);
    $job = ccfFreshJob();

    $this->service->carryForwardToProject($log, $job, $this->actor);

    expect($job->fresh()->housingServices->pluck('name')->all())->toContain('Lehigh County');
})->group('regression');

it('carries the log anticipated fund codes onto the new job', function () {
    $log = CommunicationLog::factory()->create();
    $fund = FundCode::factory()->create(['code' => 'CARRY-FC']);
    $log->anticipatedFundCodes()->attach($fund->id, [
        'created_by_user_id' => $this->actor->id,
        'created_at' => now(),
    ]);
    $job = ccfFreshJob();

    $this->service->carryForwardToProject($log, $job, $this->actor);

    expect($job->fresh()->fundCodes->pluck('code')->all())->toContain('CARRY-FC');
})->group('regression');

it('instantiates Phase 2 requirements from carried fund codes and skips Phase 1 atoms', function () {
    $log = CommunicationLog::factory()->create();
    $fund = FundCode::factory()->create(['code' => 'P2-FC']);

    $phase2 = RequirementDefinition::factory()
        ->category(RequirementDefinition::CATEGORY_SERVICE_DELIVERY)
        ->create(['label' => 'Final inspection sign-off']);
    $phase1 = RequirementDefinition::factory()
        ->category(RequirementDefinition::CATEGORY_ELIGIBILITY)
        ->create(['label' => 'Photo ID']);

    $fund->requirementDefinitions()->attach([$phase2->id, $phase1->id], [
        'created_by_user_id' => $this->actor->id,
        'created_at' => now(),
    ]);

    $log->anticipatedFundCodes()->attach($fund->id, [
        'created_by_user_id' => $this->actor->id,
        'created_at' => now(),
    ]);
    $job = ccfFreshJob();

    $count = $this->service->carryForwardToProject($log, $job, $this->actor);

    expect($count)->toBe(1);
    $titles = Requirement::where('housing_job_id', $job->id)->pluck('title')->all();
    expect($titles)->toContain('Final inspection sign-off');
    expect($titles)->not->toContain('Photo ID');
})->group('regression');

it('leaves Phase 1 intake requirements on the log, not on the job', function () {
    $log = CommunicationLog::factory()->create();
    $this->service->baseline($log, $this->actor); // an intake (Phase 1) row
    $job = ccfFreshJob();

    $this->service->carryForwardToProject($log, $job, $this->actor);

    expect(IntakeRequirement::where('communication_log_id', $log->id)->count())->toBeGreaterThan(0);
    // Baseline intake rows are never copied into project requirements.
    expect(Requirement::where('housing_job_id', $job->id)->count())->toBe(0);
})->group('regression');

it('records the override audit on the job when a reason is given', function () {
    $log = CommunicationLog::factory()->create();
    $job = ccfFreshJob();

    $this->service->carryForwardToProject($log, $job, $this->actor, 'Emergency promotion, docs to follow');

    $job->refresh();
    expect($job->requirement_override_reason)->toBe('Emergency promotion, docs to follow');
    expect($job->requirement_override_by_user_id)->toBe($this->actor->id);
    expect($job->requirement_overridden_at)->not->toBeNull();
})->group('regression');

it('is idempotent — re-running does not duplicate funds, counties, or requirements', function () {
    $log = CommunicationLog::factory()->create();
    $county = HousingService::factory()->create();
    $log->housingServices()->attach($county->id, ['created_at' => now()]);
    $fund = FundCode::factory()->create();
    $phase2 = RequirementDefinition::factory()
        ->category(RequirementDefinition::CATEGORY_SERVICE_DELIVERY)
        ->create();
    $fund->requirementDefinitions()->attach($phase2->id, ['created_at' => now()]);
    $log->anticipatedFundCodes()->attach($fund->id, ['created_at' => now()]);
    $job = ccfFreshJob();

    $this->service->carryForwardToProject($log, $job, $this->actor);
    $this->service->carryForwardToProject($log, $job, $this->actor);

    $job->refresh();
    expect($job->housingServices)->toHaveCount(1);
    expect($job->fundCodes)->toHaveCount(1);
    expect(Requirement::where('housing_job_id', $job->id)->count())->toBe(1);
})->group('regression');
