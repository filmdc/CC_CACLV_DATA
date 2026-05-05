<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use App\Models\ProjectBudget;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;
use Tests\Support\SnapshotHelper;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

it('snapshot pins the budget modification confirmation modal markup', function () {
    // Pin the fund-code naming so the snapshot survives faker drift on
    // other generated FC rows in the test DB. The dropdown in the
    // modal lists this code by name+description.
    $job = HousingJob::factory()->create([
        'job_title' => 'Snapshot Project',
        'job_number' => 'JOB-2026-99999',
    ]);
    $fc = \App\Models\FundCode::factory()->create([
        'code' => 'FC-SNAPSHOT',
        'description' => 'Snapshot fund code for modal pinning',
    ]);
    $job->fundCodes()->attach($fc->id);

    $budget = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Construction labor',
        'budgeted_amount' => 1000,
        'fund_code_id' => $fc->id,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('editBudget', $budget->id)
        ->set('budget_amount', 1500)
        ->call('saveBudget')
        ->assertSet('showBudgetModConfirmModal', true);

    // Extract just the modal block by its data attribute so the
    // snapshot doesn't capture the surrounding page chrome (which
    // contains faker-generated participant/property/job description
    // text that drifts every run). The modal is self-contained;
    // snapshotting it in isolation is the right scope.
    $html = $component->html();
    if (preg_match('#<div [^>]*data-budget-mod-modal[^>]*>.*?</div>\s*</div>\s*</div>#s', $html, $m)) {
        $html = $m[0];
    }

    SnapshotHelper::assertHtmlMatchesSnapshot('budget_modify_modal', $html);
})->group('regression');
