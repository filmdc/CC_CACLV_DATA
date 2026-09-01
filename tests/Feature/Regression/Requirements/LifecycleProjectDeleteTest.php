<?php

use App\Models\FundCode;
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

/*
 * These pin the DATABASE-level ON DELETE CASCADE on requirements.housing_job_id.
 * HousingJob soft-deletes as of 2026_08_31_110000, so ->delete() no longer
 * issues a SQL DELETE — forceDelete() is what exercises the FK now. The
 * constraint itself is unchanged. The soft-delete path is pinned separately
 * below: it must NOT cascade, or an admin restore would come back gutted.
 */
it('cascades requirement rows when the housing_jobs row is hard-deleted (FK constraint)', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    // Cover all three sources so we know the FK doesn't discriminate.
    $code = FundCode::factory()->create();
    RegressionHelpers::linkProjectRequirements($code, 1);
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

    $job->forceDelete();

    expect(HousingJob::withTrashed()->find($jobId))->toBeNull();
    expect(Requirement::where('housing_job_id', $jobId)->count())->toBe(0);
})->group('regression');

it('does not cascade requirements on a different project when one is hard-deleted', function () {
    $manager = User::factory()->create();

    $jobA = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $jobB = HousingJob::factory()->create(['project_manager_id' => $manager->id]);

    Requirement::factory()->task()->count(2)->create(['housing_job_id' => $jobA->id, 'source_type' => 'ad_hoc']);
    $reqsB = Requirement::factory()->task()->count(3)->create(['housing_job_id' => $jobB->id, 'source_type' => 'ad_hoc']);

    $jobA->forceDelete();

    expect(Requirement::where('housing_job_id', $jobA->id)->count())->toBe(0);
    expect(Requirement::where('housing_job_id', $jobB->id)->count())->toBe(3);

    foreach ($reqsB as $r) {
        expect(Requirement::find($r->id))->not->toBeNull();
    }
})->group('regression');

it('keeps requirement rows when a project is soft-deleted, so a restore is whole', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    Requirement::factory()->task()->count(2)->create(['housing_job_id' => $job->id, 'source_type' => 'ad_hoc']);

    $job->delete();

    expect(HousingJob::find($job->id))->toBeNull();
    expect(HousingJob::withTrashed()->find($job->id)->trashed())->toBeTrue();
    expect(Requirement::where('housing_job_id', $job->id)->count())->toBe(2);

    $job->restore();

    expect(HousingJob::find($job->id))->not->toBeNull();
    expect(Requirement::where('housing_job_id', $job->id)->count())->toBe(2);
})->group('regression');
