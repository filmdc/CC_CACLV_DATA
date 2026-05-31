<?php

/**
 * Project Detail redesign rev 1 (May 30 2026) — guardrails for the new
 * consolidated header + two-pane Work + CALV-styled Budget surfaces.
 *
 * Pins five contracts so a future regression that silently reverts to
 * the legacy indigo header / old budget table is caught early:
 *
 *   1. The old indigo header band is gone.
 *   2. The new header carries the breadcrumb + "Projects" link.
 *   3. The unified-progress strip exposes both an Operational and a
 *      Financial meter.
 *   4. The Work tab renders the two-pane grid with a milestone-row
 *      hook on the left and a milestone-detail hook on the right.
 *   5. The Budget tab renders the three summary cards (Total /
 *      Spent to date / Remaining) + at least one budget-scope-bar
 *      when budgets exist.
 */

use App\Livewire\ProjectManagement;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\ProjectBudget;
use App\Models\ProjectMilestone;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('no longer renders the legacy indigo header band', function () {
    $job = HousingJob::factory()->create();

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->html();

    // The pre-redesign header was `<div class="bg-indigo-600 text-white p-6">`.
    // Asserting that exact opening string is absent is the most direct way
    // to catch a silent revert.
    expect($html)->not->toContain('bg-indigo-600 text-white p-6');
})->group('regression');

it('the consolidated header carries the Housing > Projects > {jobNumber} breadcrumb', function () {
    $job = HousingJob::factory()->create(['job_number' => 'JOB-BREADCRUMB-001']);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->html();

    expect($html)->toContain('Housing');
    expect($html)->toContain('Projects');
    expect($html)->toContain('JOB-BREADCRUMB-001');
})->group('regression');

it('the unified progress strip renders both Operational and Financial meters', function () {
    $job = HousingJob::factory()->create();
    ProjectMilestone::factory()->count(2)->create(['housing_job_id' => $job->id]);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->html();

    expect($html)->toContain('Operational');
    expect($html)->toContain('Financial');
    expect($html)->toMatch('/of \d+ milestone/');
})->group('regression');

it('the Work tab renders the two-pane layout with milestone-row and milestone-detail markers', function () {
    $job = HousingJob::factory()->create();
    ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'name' => 'Two-pane probe milestone',
    ]);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->html();

    expect($html)->toContain('data-test="milestone-row"');
    expect($html)->toContain('data-test="milestone-detail"');
    expect($html)->toContain('Two-pane probe milestone');
})->group('regression');

it('the Budget tab shows the three summary cards plus a budget-scope-bar', function () {
    $job = HousingJob::factory()->create();
    $fundCode = FundCode::factory()->create();
    $job->fundCodes()->attach($fundCode->id);
    ProjectBudget::create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $fundCode->id,
        'category' => 'Summary card probe',
        'budgeted_amount' => 1000,
        'spent_amount' => 250,
    ]);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('activeTab', 'budget')
        ->html();

    // Summary cards.
    expect($html)->toContain('Total budget');
    expect($html)->toContain('Spent to date');
    expect($html)->toContain('Remaining');
    // At least one burn-bar card.
    expect($html)->toContain('data-test="budget-scope-bar"');
    // Status attribute exposes the contract for future tests.
    expect($html)->toContain('data-budget-status=');
})->group('regression');
