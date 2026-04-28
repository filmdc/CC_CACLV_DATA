<?php

use App\Models\FundCode;
use App\Models\FundCodeRequirement;
use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\User;
use App\Services\RequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

it('cascades requirement rows when the housing_jobs row is deleted (FK constraint)', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    // Cover all three sources so we know the FK doesn't discriminate.
    $code = FundCode::factory()->create();
    FundCodeRequirement::factory()->create(['fund_code_id' => $code->id]);
    $job->fundCodes()->attach($code->id);
    app(RequirementInstantiator::class)->forFundCode($job->fresh(), $code, $manager);

    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);
    Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
    ]);

    $jobId = $job->id;
    expect(Requirement::where('housing_job_id', $jobId)->count())->toBeGreaterThanOrEqual(3);

    $job->delete();

    expect(HousingJob::find($jobId))->toBeNull();
    expect(Requirement::where('housing_job_id', $jobId)->count())->toBe(0);
})->group('regression');

it('does not cascade requirements on a different project when one is deleted', function () {
    $manager = User::factory()->create();

    $jobA = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $jobB = HousingJob::factory()->create(['project_manager_id' => $manager->id]);

    Requirement::factory()->task()->count(2)->create(['housing_job_id' => $jobA->id, 'source_type' => 'ad_hoc']);
    $reqsB = Requirement::factory()->task()->count(3)->create(['housing_job_id' => $jobB->id, 'source_type' => 'ad_hoc']);

    $jobA->delete();

    expect(Requirement::where('housing_job_id', $jobA->id)->count())->toBe(0);
    expect(Requirement::where('housing_job_id', $jobB->id)->count())->toBe(3);

    foreach ($reqsB as $r) {
        expect(Requirement::find($r->id))->not->toBeNull();
    }
})->group('regression');
