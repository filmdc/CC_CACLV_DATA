<?php

use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Services\DashboardIndicatorSupport;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->support = new DashboardIndicatorSupport;
});

/**
 * Phase 3A / 7.5c — query-bound test.
 *
 * The risk is that a naive dashboard implementation iterates each
 * requirement → loads its milestone → loads dependencies → loads each
 * dependency's requirements. The precompute strategy must keep the
 * query count CONSTANT regardless of input size. Bounded assertion
 * (≤ N for both 10 and 100 milestones), not exact pin — exact-pin is
 * brittle to innocuous refactors; bounded catches the real concern.
 */

const QUERY_BUDGET = 5;

function buildBlockedFixture(int $milestoneCount): array
{
    $jobIds = [];

    for ($i = 0; $i < $milestoneCount; $i++) {
        $job = HousingJob::factory()->create();
        $jobIds[] = $job->id;
        $depender = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
        $dep = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
        $depender->dependencies()->attach($dep->id);
        Requirement::factory()->create([
            'housing_job_id' => $job->id,
            'source_type' => Requirement::SOURCE_MILESTONE,
            'source_id' => $dep->id,
            'status' => Requirement::STATUS_OUTSTANDING,
        ]);
    }

    return $jobIds;
}

it('blockedMilestoneIds runs in a constant number of queries for 10 milestones', function () {
    $jobIds = buildBlockedFixture(10);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $blocked = $this->support->blockedMilestoneIds($jobIds);
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect(count($blocked))->toBe(10);
    expect($queries)->toBeLessThanOrEqual(QUERY_BUDGET);
})->group('regression');

it('blockedMilestoneIds runs in the SAME constant number of queries for 100 milestones', function () {
    $jobIds = buildBlockedFixture(100);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $blocked = $this->support->blockedMilestoneIds($jobIds);
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect(count($blocked))->toBe(100);
    expect($queries)->toBeLessThanOrEqual(QUERY_BUDGET);
})->group('regression');

it('fundCodesAttachedToProjects also runs bounded regardless of project count', function () {
    $jobs = HousingJob::factory()->count(50)->create();
    $codes = FundCode::factory()->count(20)->create();
    foreach ($jobs as $job) {
        $job->fundCodes()->attach($codes->random(rand(1, 5))->pluck('id')->all());
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    $result = $this->support->fundCodesAttachedToProjects($jobs->pluck('id')->all());
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($result->count())->toBeGreaterThan(0);
    expect($queries)->toBeLessThanOrEqual(QUERY_BUDGET);
})->group('regression');
