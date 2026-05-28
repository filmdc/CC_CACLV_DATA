<?php

use App\Livewire\HousingDashboard;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\Program;
use App\Models\ProjectMilestone;
use App\Models\Property;
use App\Models\Requirement;
use Carbon\Carbon;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;
use Tests\Support\SnapshotHelper;

beforeEach(function () {
    // Pin time so seeded milestone target_dates (2026-02-05, 2026-03-15,
    // 2026-05-01, etc.) and our relative fixture dates always render
    // into the same indicator buckets regardless of when the test
    // runs. Without this, the day-7 "approaching" boundary on seeded
    // milestone target_date='2026-05-01' silently shifts to "overdue"
    // a few days later and the snapshot diffs.
    Carbon::setTestNow('2026-05-01 12:00:00');

    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * Phase 3A — Step 9 baselines.
 *
 * The fixture is deliberately heterogeneous: one project with a
 * fund-code attached (urgent expiration), an overdue milestone, and a
 * blocked milestone, plus a second project with no indicators. This
 * captures multi-indicator combinations on Cut A and source-mixed
 * rows on Cut B in a single render. SnapshotHelper normalizes IDs,
 * timestamps, and Livewire identifiers, so re-running the test on a
 * different day produces the same snapshot (the indicator math reads
 * "now" as the relative reference for the fixed +X-day fixture
 * dates).
 */
function dashboardSnapshotFixture(): array
{
    // Pin the admin's name so the user-options dropdown rendered into
    // the snapshot is deterministic. The default RegressionHelpers
    // admin gets a faker-random name which would diff every run.
    test()->admin->update(['name' => 'Snapshot Admin']);

    $program = Program::first();
    if (! $program?->housing_module_enabled) {
        $program?->update(['housing_module_enabled' => true]);
    }

    // Project A: rich indicators — urgent fund, overdue milestone,
    // blocked milestone. Pin participant first/last name so the
    // rendered "— First Last" snippet stays deterministic across runs.
    $participantA = \App\Models\Participant::factory()->create([
        'first_name' => 'Snapshot',
        'last_name' => 'Alpha',
    ]);
    $propA = Property::factory()->create([
        'address_line_1' => '5 Test Lane',
        'participant_id' => $participantA->id,
    ]);
    $jobA = HousingJob::factory()->create([
        'program_id' => $program->id,
        'is_template' => false,
        'project_manager_id' => test()->admin->id,
        'job_title' => 'Project A snapshot',
        'job_number' => 'JOB-SNAP-0001',
        'property_id' => $propA->id,
        'participant_id' => $participantA->id,
    ]);

    $fund = FundCode::factory()->expiringIn(15)->create(['code' => 'SNAP-FC-A']);
    $jobA->fundCodes()->attach($fund->id);

    // Overdue milestone (target_date in the past).
    $overdueMs = ProjectMilestone::factory()->create([
        'housing_job_id' => $jobA->id,
        'name' => 'Overdue milestone',
        'sequence_order' => 1,
        'target_date' => now()->subDays(3)->toDateString(),
    ]);

    // Blocked milestone: depends on a milestone that has an outstanding requirement.
    $blockedMs = ProjectMilestone::factory()->create([
        'housing_job_id' => $jobA->id,
        'name' => 'Blocked milestone',
        'sequence_order' => 2,
    ]);
    $depMs = ProjectMilestone::factory()->create([
        'housing_job_id' => $jobA->id,
        'name' => 'Dependency milestone',
        'sequence_order' => 3,
    ]);
    $blockedMs->dependencies()->attach($depMs->id);

    Requirement::factory()->task()->create([
        'housing_job_id' => $jobA->id,
        'owner_user_id' => test()->admin->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $depMs->id,
        'status' => Requirement::STATUS_OUTSTANDING,
        'title' => 'SNAP_blocking_dep_req',
    ]);
    Requirement::factory()->task()->create([
        'housing_job_id' => $jobA->id,
        'owner_user_id' => test()->admin->id,
        'source_type' => Requirement::SOURCE_FUND_CODE,
        'source_id' => $fund->id,
        'status' => Requirement::STATUS_OUTSTANDING,
        'title' => 'SNAP_fund_req',
    ]);
    Requirement::factory()->task()->create([
        'housing_job_id' => $jobA->id,
        'owner_user_id' => test()->admin->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'status' => Requirement::STATUS_OUTSTANDING,
        'title' => 'SNAP_adhoc_req',
    ]);

    // Project B: no indicators, single ad-hoc requirement.
    $participantB = \App\Models\Participant::factory()->create([
        'first_name' => 'Snapshot',
        'last_name' => 'Bravo',
    ]);
    $propB = Property::factory()->create([
        'address_line_1' => '99 Quiet Way',
        'participant_id' => $participantB->id,
    ]);
    $jobB = HousingJob::factory()->create([
        'program_id' => $program->id,
        'is_template' => false,
        'project_manager_id' => test()->admin->id,
        'job_title' => 'Project B snapshot',
        'job_number' => 'JOB-SNAP-0002',
        'property_id' => $propB->id,
        'participant_id' => $participantB->id,
    ]);
    Requirement::factory()->task()->create([
        'housing_job_id' => $jobB->id,
        'owner_user_id' => test()->admin->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'status' => Requirement::STATUS_OUTSTANDING,
        'title' => 'SNAP_quiet_req',
    ]);

    return ['jobA' => $jobA, 'jobB' => $jobB];
}

it('matches the committed snapshot for Cut A — per-project view with combined indicators', function () {
    dashboardSnapshotFixture();

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->html();

    SnapshotHelper::assertHtmlMatchesSnapshot('housing_dashboard_cut_a', $html);
})->group('regression');

it('matches the committed snapshot for Cut B — per-requirement view with sort-by-fund-code', function () {
    dashboardSnapshotFixture();

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setView', 'requirements')
        ->html();

    SnapshotHelper::assertHtmlMatchesSnapshot('housing_dashboard_cut_b', $html);
})->group('regression');
