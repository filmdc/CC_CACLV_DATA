<?php

use App\Livewire\HousingDashboard;
use App\Models\FundCode;
use App\Models\FundCodeRequirement;
use App\Models\HousingJob;
use App\Models\Program;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

/**
 * Phase 3A / Step 9 — full-render query bound. The 7.5c
 * IndicatorQueryBoundsTest covers the precompute service in isolation;
 * this test asserts the END-TO-END Livewire render stays bounded as
 * requirement count grows. A naive implementation that walked
 * $req->source() per row would balloon to O(N) extra queries; the
 * precomputed map + eager loads must keep it constant-ish.
 *
 * Bounded assertion: ≤ N for both the small (10 reqs) and large (100
 * reqs) fixtures. Exact pin avoided — innocuous refactors will shift
 * by ±1 query without changing the asymptotics.
 */
function makeDashboardFixture(int $requirementCount): void
{
    $program = Program::first();
    if (! $program?->housing_module_enabled) {
        $program?->update(['housing_module_enabled' => true]);
    }

    $job = HousingJob::factory()->create([
        'program_id' => $program->id,
        'is_template' => false,
    ]);

    // One milestone, one fund code with templates so requirements have
    // mixed sources and the dashboard render exercises every join path.
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $fund = FundCode::factory()->expiringIn(20)->create();
    $job->fundCodes()->attach($fund->id);
    FundCodeRequirement::factory()->count(2)->create(['fund_code_id' => $fund->id]);

    $admin = test()->admin;

    for ($i = 0; $i < $requirementCount; $i++) {
        $sourceType = $i % 3 === 0 ? Requirement::SOURCE_FUND_CODE
                    : ($i % 3 === 1 ? Requirement::SOURCE_MILESTONE
                                    : Requirement::SOURCE_AD_HOC);
        $sourceId = match ($sourceType) {
            Requirement::SOURCE_FUND_CODE => $fund->id,
            Requirement::SOURCE_MILESTONE => $milestone->id,
            default => null,
        };

        Requirement::factory()->task()->create([
            'housing_job_id' => $job->id,
            'owner_user_id' => $admin->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ]);
    }
}

// Constant query budget — the dashboard render pulls Auth checks +
// permission lookups + relevant-user dropdown + 3 precompute queries
// + 1 main query + a handful of eager-loaded relation queries +
// the Phase 12.2/12.3 intake-pipeline, pinned, and oldest-logs
// sidecars (each O(1) constant-time, not row-dependent).
// Empirically lands at ~43 queries; budget = 50 gives comfortable
// headroom for future innocuous additions while still catching N+1
// regressions (which would push the 100-req count well above this).
const DASHBOARD_QUERY_BUDGET = 50;

it('Cut B render runs in a bounded query count with 10 requirements', function () {
    makeDashboardFixture(10);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->call('setView', 'requirements')
        ->html();

    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThanOrEqual(DASHBOARD_QUERY_BUDGET);
})->group('regression');

it('Cut B render runs in the SAME bounded query count with 100 requirements', function () {
    makeDashboardFixture(100);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->call('setView', 'requirements')
        ->html();

    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThanOrEqual(DASHBOARD_QUERY_BUDGET);
})->group('regression');

it('Cut A render also runs bounded with 100 requirements across many projects', function () {
    // 20 projects, 5 requirements each — exercises Cut A's per-project
    // rollup against a non-trivial input.
    for ($i = 0; $i < 20; $i++) {
        makeDashboardFixture(5);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->html();

    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queries)->toBeLessThanOrEqual(DASHBOARD_QUERY_BUDGET);
})->group('regression');
