<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use App\Models\ProjectBudget;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
    $this->job = HousingJob::where('job_number', 'JOB-FIX-00001')->firstOrFail();
});

// P-21 pre-image: today, ProjectBudget categories CAN be created without a
// contract reference. Validation in ProjectManagement::saveBudget allows
// `budget_contract_id` as nullable (app/Livewire/ProjectManagement.php:873),
// and the form default is ''. Phase 1 P-21 is expected to make contract
// attachment mandatory at the UI and/or validation layer. When P-21 lands
// and this test fails, the assertion should flip from "creation succeeds" to
// "creation is blocked with a specific error".
it('today: a category with no contract is created successfully (P-21 pre-image)', function () {
    $countBefore = ProjectBudget::where('housing_job_id', $this->job->id)->count();

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->call('showCreateBudget')
        ->set('budget_category', 'Miscellaneous Supplies')
        ->set('budget_amount', 500)
        ->set('budget_contract_id', '') // intentionally empty
        ->call('saveBudget')
        ->assertHasNoErrors()
        ->assertSet('showBudgetForm', false);

    expect(ProjectBudget::where('housing_job_id', $this->job->id)->count())->toBe($countBefore + 1);

    $budget = ProjectBudget::where('housing_job_id', $this->job->id)
        ->where('category', 'Miscellaneous Supplies')
        ->first();
    expect($budget)->not->toBeNull()
        ->and($budget->contract_id)->toBeNull()
        ->and((float) $budget->budgeted_amount)->toBe(500.0);
})->group('regression');
