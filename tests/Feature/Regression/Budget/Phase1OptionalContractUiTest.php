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

it('budget form labels the contract selector as Optional (P-21)', function () {
    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->call('showCreateBudget')
        ->html();

    expect($html)->toContain('Funding Source (Contract)');
    expect($html)->toContain('— Optional');
    // Placeholder makes the no-contract option obvious without opening the dropdown.
    expect($html)->toContain('No contract — leave blank or pick one');
    // Help text invites blank submission.
    expect($html)->toContain('Leave blank to create a budget category');
})->group('regression');

it('clearing the contract on save persists contract_id as NULL (P-21)', function () {
    // Attach a contract first, save, then clear and re-save.
    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->call('showCreateBudget')
        ->set('budget_category', 'Permits')
        ->set('budget_amount', 250)
        ->set('budget_contract_id', '') // explicitly empty
        ->call('saveBudget')
        ->assertHasNoErrors()
        ->assertSet('showBudgetForm', false);

    $budget = ProjectBudget::where('housing_job_id', $this->job->id)
        ->where('category', 'Permits')
        ->first();

    expect($budget)->not->toBeNull();
    expect($budget->contract_id)->toBeNull();
})->group('regression');
