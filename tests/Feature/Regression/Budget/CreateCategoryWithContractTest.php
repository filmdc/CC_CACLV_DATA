<?php

use App\Livewire\ProjectManagement;
use App\Models\Contract;
use App\Models\HousingJob;
use App\Models\ProjectBudget;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
    $this->job = HousingJob::where('job_number', 'JOB-FIX-00001')->firstOrFail();
    $this->contract = Contract::where('contract_number', 'FIX-A-0001')->firstOrFail();
});

it('creates a budget category with a contract attached', function () {
    $countBefore = ProjectBudget::where('housing_job_id', $this->job->id)->count();

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->call('showCreateBudget')
        ->assertSet('showBudgetForm', true)
        ->set('budget_category', 'Windows')
        ->set('budget_amount', 4500)
        ->set('budget_contract_id', $this->contract->id)
        ->call('saveBudget')
        ->assertHasNoErrors()
        ->assertSet('showBudgetForm', false);

    expect(ProjectBudget::where('housing_job_id', $this->job->id)->count())->toBe($countBefore + 1);

    $budget = ProjectBudget::where('housing_job_id', $this->job->id)
        ->where('category', 'Windows')
        ->first();
    expect($budget)->not->toBeNull()
        ->and($budget->contract_id)->toBe($this->contract->id)
        ->and((float) $budget->budgeted_amount)->toBe(4500.0)
        ->and($budget->created_by)->toBe($this->user->id);
})->group('regression');
