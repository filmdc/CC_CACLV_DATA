<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
    $this->job = HousingJob::where('job_number', 'JOB-FIX-00004')->firstOrFail();
});

it('HJ-4 seeder expenses sum to 7000 on the Roofing category', function () {
    $sum = (float) ProjectExpense::where('housing_job_id', $this->job->id)
        ->where('category', 'Roofing')
        ->sum('amount');
    expect($sum)->toBe(7000.0);
})->group('regression');

it('recording a new expense updates the Roofing budget spent_amount via observer', function () {
    $roofingBudget = ProjectBudget::where('housing_job_id', $this->job->id)
        ->where('category', 'Roofing')
        ->firstOrFail();

    $spentBefore = (float) $roofingBudget->spent_amount;

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->call('showCreateExpense')
        ->set('expense_category', 'Roofing')
        ->set('expense_amount', 1200)
        ->set('expense_date', '2026-03-20')
        ->set('expense_description', 'Additional materials.')
        ->call('saveExpense')
        ->assertHasNoErrors()
        ->assertSet('showExpenseForm', false);

    $roofingBudget->refresh();
    expect((float) $roofingBudget->spent_amount - $spentBefore)->toBe(1200.0);
})->group('regression');

it('running total on Roofing after two additional expenses matches the arithmetic', function () {
    // Start: seeder has 7000 spent. Add 500 and 250 expenses → 7750 total.
    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->call('showCreateExpense')
        ->set('expense_category', 'Roofing')
        ->set('expense_amount', 500)
        ->set('expense_date', '2026-03-21')
        ->call('saveExpense')
        ->call('showCreateExpense')
        ->set('expense_category', 'Roofing')
        ->set('expense_amount', 250)
        ->set('expense_date', '2026-03-22')
        ->call('saveExpense');

    $sum = (float) ProjectExpense::where('housing_job_id', $this->job->id)
        ->where('category', 'Roofing')
        ->sum('amount');
    expect($sum)->toBe(7750.0);
})->group('regression');
