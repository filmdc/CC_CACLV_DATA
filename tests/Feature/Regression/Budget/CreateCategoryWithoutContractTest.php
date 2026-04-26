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

// P-21: budget categories must be creatable without a contract reference.
// Validation in ProjectManagement::saveBudget keeps `budget_contract_id`
// nullable. The Phase 1 UI work makes the optional state visually obvious
// (label "— Optional", placeholder "No contract — leave blank or pick one",
// help text, and an explicit "No contract (clear selection)" item in the
// dropdown). This test guards the functional contract.
it('a category with no contract is created successfully (P-21)', function () {
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
