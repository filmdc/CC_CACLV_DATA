<?php

use App\Livewire\Homes\Reports;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\Permission;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use App\Models\Role;
use App\Models\User;
use App\Services\Homes\HousingPortalReports;
use Livewire\Livewire;

function reportsPortalUser(array $permissionNames = []): User
{
    $role = Role::firstOrCreate(['name' => 'reports_portal_test'], ['display_name' => 'Reports Portal Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

it('gates reports on housing.reports.view', function () {
    $this->actingAs(reportsPortalUser([]));
    $this->get(route('homes.reports'))->assertForbidden();

    $this->actingAs(reportsPortalUser(['housing.reports.view']));
    $this->get(route('homes.reports'))->assertOk();
});

it('computes fund spending from project budgets grouped by fund code (standalone-safe)', function () {
    $fund = FundCode::factory()->create(['code' => 'F-TEST']);
    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $job->fundCodes()->attach($fund->id);

    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $fund->id,
        'category' => 'Roofing',
        'budgeted_amount' => 1000,
    ]);
    // spent_amount is derived — create a real expense so the observer rolls it up.
    ProjectExpense::create([
        'housing_job_id' => $job->id,
        'category' => 'Roofing',
        'amount' => 400,
        'expense_date' => now()->toDateString(),
    ]);

    $data = app(HousingPortalReports::class)->fundSpending();

    $row = $data['rows']->firstWhere('code', 'F-TEST');
    expect($row)->not->toBeNull();
    expect($row['allocated'])->toBe(1000.0);
    expect($row['spent'])->toBe(400.0);
    expect($row['balance'])->toBe(600.0);
});

it('computes the intake funnel stages', function () {
    // Just a call — no requirements.
    CommunicationLog::factory()->create();

    // Collecting — one open Phase-1 requirement.
    $collecting = CommunicationLog::factory()->create();
    IntakeRequirement::factory()->create([
        'communication_log_id' => $collecting->id,
        'status' => IntakeRequirement::STATUS_PENDING,
    ]);

    // All-in — one met requirement.
    $allIn = CommunicationLog::factory()->create();
    IntakeRequirement::factory()->create([
        'communication_log_id' => $allIn->id,
        'status' => IntakeRequirement::STATUS_RECEIVED,
    ]);

    // Converted.
    $job = HousingJob::factory()->create();
    CommunicationLog::factory()->create([
        'converted_to_job_id' => $job->id,
        'converted_at' => now(),
    ]);

    $rows = app(HousingPortalReports::class)->intakeFunnel()['rows']->keyBy('stage');

    expect($rows['Calls logged']['n'])->toBe(4);
    expect($rows['Became intakes']['n'])->toBe(3);
    expect($rows['Collecting documents']['n'])->toBe(1);
    expect($rows['All documents in']['n'])->toBe(1);
    expect($rows['Converted to projects']['n'])->toBe(1);
});

it('counts monthly activity in the current month', function () {
    CommunicationLog::factory()->count(3)->create(['communication_date' => now()]);
    CommunicationLog::factory()->create(['communication_date' => now(), 'category' => 'intake']);

    $data = app(HousingPortalReports::class)->monthlyActivity();
    $thisMonth = $data['rows']->last();

    expect($thisMonth['calls'])->toBeGreaterThanOrEqual(4);
    expect($thisMonth['intakes'])->toBeGreaterThanOrEqual(1);
});

it('lists open projects in the status report', function () {
    HousingJob::factory()->create(['status' => 'in_progress', 'job_title' => 'Open fixture job']);
    HousingJob::factory()->create(['status' => 'completed', 'job_title' => 'Done fixture job']);

    $rows = app(HousingPortalReports::class)->projectStatus();

    expect($rows->pluck('program')->implode(' '))->toContain('Open fixture job');
    expect($rows->pluck('program')->implode(' '))->not->toContain('Done fixture job');
});

it('exports the active report as a CSV download', function () {
    $this->actingAs(reportsPortalUser(['housing.reports.view']));

    CommunicationLog::factory()->create(['communication_date' => now()]);

    Livewire::test(Reports::class)
        ->set('report', 'activity')
        ->call('exportCsv')
        ->assertFileDownloaded('housing-activity-'.now()->format('Y-m-d').'.csv');
});

it('switches between the four reports without errors', function () {
    $this->actingAs(reportsPortalUser(['housing.reports.view']));

    Livewire::test(Reports::class)
        ->assertSee('Monthly activity')
        ->call('setReport', 'funds')->assertHasNoErrors()->assertSee('Allocated')
        ->call('setReport', 'funnel')->assertHasNoErrors()->assertSee('Calls logged')
        ->call('setReport', 'status')->assertHasNoErrors()->assertSee('Homeowner');
});
