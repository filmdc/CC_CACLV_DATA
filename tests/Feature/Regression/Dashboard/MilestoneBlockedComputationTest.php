<?php

use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Services\DashboardIndicatorSupport;
use Database\Seeders\RegressionTestSeeder;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->support = new DashboardIndicatorSupport;
});

it('a milestone with no dependencies is not blocked', function () {
    $job = HousingJob::factory()->create();
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    expect($this->support->blockedMilestoneIds([$job->id]))->not->toContain($a->id);
})->group('regression');

it('a milestone with one dependency whose requirements are all complete is not blocked', function () {
    $job = HousingJob::factory()->create();
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $b = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    $a->dependencies()->attach($b->id);

    Requirement::factory()->complete()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $b->id,
    ]);

    expect($this->support->blockedMilestoneIds([$job->id]))->not->toContain($a->id);
})->group('regression');

it('a milestone with one dependency that has at least one outstanding requirement is blocked', function () {
    $job = HousingJob::factory()->create();
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $b = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    $a->dependencies()->attach($b->id);

    Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $b->id,
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);

    expect($this->support->blockedMilestoneIds([$job->id]))->toContain($a->id);
})->group('regression');

it('a milestone is blocked if ANY of its multiple dependencies has outstanding work', function () {
    $job = HousingJob::factory()->create();
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $b = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $c = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    $a->dependencies()->attach([$b->id, $c->id]);

    // B is fully complete; C has outstanding work.
    Requirement::factory()->complete()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $b->id,
    ]);
    Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $c->id,
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);

    expect($this->support->blockedMilestoneIds([$job->id]))->toContain($a->id);
})->group('regression');

it('not_applicable counts as "done" for blocking purposes', function () {
    $job = HousingJob::factory()->create();
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $b = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    $a->dependencies()->attach($b->id);

    Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $b->id,
        'status' => Requirement::STATUS_NOT_APPLICABLE,
    ]);

    // B's only requirement is not_applicable; A should NOT be blocked.
    expect($this->support->blockedMilestoneIds([$job->id]))->not->toContain($a->id);
})->group('regression');

it('scopes blocked-set computation to the requested housing_jobs', function () {
    $jobA = HousingJob::factory()->create();
    $jobB = HousingJob::factory()->create();

    $milestoneOnA = ProjectMilestone::factory()->create(['housing_job_id' => $jobA->id]);
    $milestoneOnA_dep = ProjectMilestone::factory()->create(['housing_job_id' => $jobA->id]);
    $milestoneOnA->dependencies()->attach($milestoneOnA_dep->id);
    Requirement::factory()->create([
        'housing_job_id' => $jobA->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestoneOnA_dep->id,
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);

    // Scope to jobB only — milestoneOnA shouldn't appear.
    expect($this->support->blockedMilestoneIds([$jobB->id]))->not->toContain($milestoneOnA->id);

    // Scope to jobA — it should.
    expect($this->support->blockedMilestoneIds([$jobA->id]))->toContain($milestoneOnA->id);
})->group('regression');
