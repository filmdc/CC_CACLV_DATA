<?php

/**
 * Activity audit log (spatie/laravel-activitylog) — capture + viewer.
 *
 * Pins two things:
 *   1. Model writes on the housing + comm-log surfaces produce activity
 *      rows with the acting user as causer and a before/after diff.
 *   2. The admin viewer renders for an audit.view holder and 403s for a
 *      user without it.
 */

use App\Livewire\ActivityAuditLog;
use App\Models\CommunicationLog;
use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\User;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('logs a milestone update with the acting user as causer and a before/after diff', function () {
    $this->actingAs($this->user);

    $job = HousingJob::factory()->create();
    $milestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'name' => 'Original name',
        'status' => 'not_started',
    ]);

    $milestone->update(['name' => 'Renamed milestone', 'status' => 'in_progress']);

    $activity = Activity::where('subject_type', ProjectMilestone::class)
        ->where('subject_id', $milestone->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($this->user->id);
    expect($activity->log_name)->toBe('housing');
    expect($activity->properties['old']['name'] ?? null)->toBe('Original name');
    expect($activity->properties['attributes']['name'] ?? null)->toBe('Renamed milestone');
    expect($activity->properties['old']['status'] ?? null)->toBe('not_started');
    expect($activity->properties['attributes']['status'] ?? null)->toBe('in_progress');
})->group('regression');

it('logs requirement creation and deletion as distinct events', function () {
    $this->actingAs($this->user);

    $job = HousingJob::factory()->create();
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    $req = Requirement::create([
        'housing_job_id' => $job->id,
        'type' => Requirement::TYPE_TASK,
        'title' => 'Audit-probe task',
        'status' => Requirement::STATUS_OUTSTANDING,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    expect(Activity::where('subject_type', Requirement::class)
        ->where('subject_id', $req->id)
        ->where('event', 'created')
        ->exists())->toBeTrue();

    $reqId = $req->id;
    $req->delete();

    expect(Activity::where('subject_type', Requirement::class)
        ->where('subject_id', $reqId)
        ->where('event', 'deleted')
        ->exists())->toBeTrue();
})->group('regression');

it('logs communication log field edits under the comm_log log name', function () {
    $this->actingAs($this->user);

    $log = CommunicationLog::factory()->create(['subject' => 'Initial subject']);
    $log->update(['subject' => 'Edited subject']);

    $activity = Activity::where('subject_type', CommunicationLog::class)
        ->where('subject_id', $log->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->log_name)->toBe('comm_log');
    expect($activity->properties['old']['subject'] ?? null)->toBe('Initial subject');
    expect($activity->properties['attributes']['subject'] ?? null)->toBe('Edited subject');
})->group('regression');

it('does NOT log the derived spent_amount rollup on budget (avoids expense spam)', function () {
    $this->actingAs($this->user);

    $job = HousingJob::factory()->create();
    $fundCode = \App\Models\FundCode::factory()->create();
    $job->fundCodes()->attach($fundCode->id);

    $budget = \App\Models\ProjectBudget::create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $fundCode->id,
        'category' => 'Materials',
        'budgeted_amount' => 1000,
        'spent_amount' => 0,
    ]);

    $before = Activity::where('subject_type', \App\Models\ProjectBudget::class)
        ->where('subject_id', $budget->id)
        ->where('event', 'updated')
        ->count();

    // A spent-only change mirrors what the ProjectExpense observer does.
    $budget->update(['spent_amount' => 250]);

    $after = Activity::where('subject_type', \App\Models\ProjectBudget::class)
        ->where('subject_id', $budget->id)
        ->where('event', 'updated')
        ->count();

    // spent_amount is excluded from logOnly AND recalculateSpent saves
    // quietly — a spent-only change adds no activity row.
    expect($after - $before)->toBe(0);
})->group('regression');

it('renders the activity audit log for a user with audit.view', function () {
    $this->actingAs($this->user);

    $job = HousingJob::factory()->create();
    ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'name' => 'Visible milestone']);

    Livewire::test(ActivityAuditLog::class)
        ->assertStatus(200)
        ->assertSee('Activity Audit Log');
})->group('regression');

it('forbids the activity audit log for a user without audit.view', function () {
    // A plain user with no roles/permissions.
    $plain = User::factory()->create(['active' => true]);

    $this->actingAs($plain);

    Livewire::test(ActivityAuditLog::class)->assertForbidden();
})->group('regression');

it('filters activity by entity type', function () {
    $this->actingAs($this->user);

    $job = HousingJob::factory()->create(['job_title' => 'Filter probe job']);
    $milestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'name' => 'Filter probe milestone',
    ]);

    // Generate a milestone update so there's a row to filter to.
    $milestone->update(['name' => 'Filter probe milestone v2']);

    Livewire::test(ActivityAuditLog::class)
        ->set('entityFilter', 'Milestone')
        ->assertSee('Milestone')
        ->set('entityFilter', 'Communication log')
        ->assertDontSee('Filter probe milestone v2');
})->group('regression');
