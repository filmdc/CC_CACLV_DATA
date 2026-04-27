<?php

use App\Livewire\ProjectManagement;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\ProjectBudget;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('saveBudget rejects an empty fund_code_id on creation (P-2.5)', function () {
    $job = HousingJob::factory()->create();

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showCreateBudget')
        ->set('budget_category', 'No Code Provided')
        ->set('budget_amount', 100)
        ->set('budget_fund_code_id', '')
        ->call('saveBudget')
        ->assertHasErrors(['budget_fund_code_id']);
})->group('regression');

it('saveBudget creates a budget row with the assigned fund code (P-2.5)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create(['code' => 'FC-CREATE-001']);
    $job->fundCodes()->attach($code->id);

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showCreateBudget')
        ->set('budget_category', 'Roofing')
        ->set('budget_amount', 5000)
        ->set('budget_fund_code_id', $code->id)
        ->call('saveBudget')
        ->assertHasNoErrors()
        ->assertSet('showBudgetForm', false);

    $budget = ProjectBudget::where('housing_job_id', $job->id)
        ->where('category', 'Roofing')
        ->firstOrFail();
    expect($budget->fund_code_id)->toBe($code->id);
    expect($budget->contract_id)->toBeNull();
})->group('regression');

it('the saving-event guard rejects a fund code that is not in the project pivot (P-2.5)', function () {
    $job = HousingJob::factory()->create();
    $orphanCode = FundCode::factory()->create();

    expect(fn () => ProjectBudget::create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $orphanCode->id,
        'category' => 'Trying to bypass',
        'budgeted_amount' => 100,
    ]))->toThrow(\InvalidArgumentException::class, 'not assigned to the project');
})->group('regression');

it('the saving-event guard accepts NULL fund_code_id (legacy rows are still valid) (P-2.5)', function () {
    $job = HousingJob::factory()->create();

    $budget = ProjectBudget::create([
        'housing_job_id' => $job->id,
        'fund_code_id' => null,
        'category' => 'Legacy NULL',
        'budgeted_amount' => 100,
    ]);

    expect($budget->fresh()->fund_code_id)->toBeNull();
})->group('regression');

it('editBudget loads existing values into the form (P-2.5)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create(['code' => 'FC-EDIT-LOAD']);
    $job->fundCodes()->attach($code->id);

    $budget = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $code->id,
        'category' => 'Original Category',
        'budgeted_amount' => 2500,
    ]);

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('editBudget', $budget->id)
        ->assertSet('showBudgetForm', true)
        ->assertSet('editingBudget', $budget->id)
        ->assertSet('budget_category', 'Original Category')
        ->assertSet('budget_fund_code_id', (string) $code->id);
})->group('regression');

it('saveBudget updates an existing budget row when in edit mode (P-2.5)', function () {
    $job = HousingJob::factory()->create();
    $codeA = FundCode::factory()->create(['code' => 'FC-EDIT-A']);
    $codeB = FundCode::factory()->create(['code' => 'FC-EDIT-B']);
    $job->fundCodes()->attach([$codeA->id, $codeB->id]);

    $budget = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $codeA->id,
        'category' => 'Switchable',
        'budgeted_amount' => 1000,
    ]);

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('editBudget', $budget->id)
        ->set('budget_amount', 2000)
        ->set('budget_fund_code_id', $codeB->id)
        ->call('saveBudget')
        ->assertHasNoErrors()
        ->assertSet('showBudgetForm', false);

    $budget->refresh();
    expect($budget->fund_code_id)->toBe($codeB->id);
    expect((float) $budget->budgeted_amount)->toBe(2000.0);
})->group('regression');

it('saveBudget on edit refuses a fund code not in the project pivot (P-2.5)', function () {
    $job = HousingJob::factory()->create();
    $attached = FundCode::factory()->create();
    $orphan = FundCode::factory()->create();
    $job->fundCodes()->attach($attached->id);

    $budget = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $attached->id,
        'category' => 'Edit-Reject',
        'budgeted_amount' => 100,
    ]);

    // Edit + try to switch to the orphan code. The model's saving-event
    // guard catches it as InvalidArgumentException; Livewire's wrapper
    // doesn't catch InvalidArgumentException so the exception propagates.
    expect(function () use ($budget, $orphan) {
        Livewire::actingAs($this->user)
            ->test(ProjectManagement::class, ['id' => $budget->housing_job_id])
            ->call('editBudget', $budget->id)
            ->set('budget_fund_code_id', $orphan->id)
            ->call('saveBudget');
    })->toThrow(\InvalidArgumentException::class, 'not assigned to the project');
})->group('regression');

it('detachFundCode blocks when a budget row references the code, listing affected categories (P-2.5)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create(['code' => 'FC-LOCKED']);
    $job->fundCodes()->attach($code->id);

    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $code->id,
        'category' => 'Locked Roofing',
        'budgeted_amount' => 1000,
    ]);
    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $code->id,
        'category' => 'Locked Plumbing',
        'budgeted_amount' => 2000,
    ]);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('detachFundCode', $code->id)
        ->html();

    // Code still attached after the blocked detach.
    expect($job->fresh()->fundCodes()->where('fund_codes.id', $code->id)->exists())->toBeTrue();

    // Both category names appear in the rendered error block so the
    // manager knows exactly which rows to fix before retrying.
    expect($html)->toContain('Locked Roofing');
    expect($html)->toContain('Locked Plumbing');
    expect($html)->toContain('Re-assign or delete');
})->group('regression');

it('detachFundCode succeeds when no budget row references the code (P-2.5)', function () {
    $job = HousingJob::factory()->create();
    $codeA = FundCode::factory()->create();
    $codeB = FundCode::factory()->create();
    $job->fundCodes()->attach([$codeA->id, $codeB->id]);

    // Only A is referenced by a budget row; B should still be detachable.
    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $codeA->id,
        'category' => 'Some Category',
        'budgeted_amount' => 100,
    ]);

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('detachFundCode', $codeB->id);

    expect($job->fresh()->fundCodes()->where('fund_codes.id', $codeB->id)->exists())->toBeFalse();
    // codeA still attached, untouched.
    expect($job->fresh()->fundCodes()->where('fund_codes.id', $codeA->id)->exists())->toBeTrue();
})->group('regression');

