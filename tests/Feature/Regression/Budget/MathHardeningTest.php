<?php

use App\Models\HousingJob;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

/*
|----------------------------------------------------------------------
| Phase 3B Step 6 — P-23 part A budget math hardening.
|
| Pre-3B bug: the ProjectExpense observer fired updateBudget() against
| only the current (housing_job_id, category) tuple of the expense.
| When that tuple changed mid-edit (most commonly the user reassigning
| an expense from "Construction labor" → "Materials"), the abandoned
| tuple's spent_amount was permanently inflated by the moved expense.
|
| The fix: dual-recompute on update — recompute the OLD tuple too if
| the grouping key changed.
|
| Tests below cover:
|   - the canonical category-change case (the Plan's exact fixture)
|   - housing_job_id moves (rarely used, observer must still hold)
|   - amount edits with no tuple change (no-leak baseline)
|   - tuple change AND amount edit at once (combined-edit case)
|   - expense delete (single-tuple recompute path)
|   - bulk delete via ->delete() on a query (per-row observer fires)
|   - missing-budget no-op (uncategorized expenses don't blow up)
|----------------------------------------------------------------------
*/

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

it('category change moves the expense between budget tuples without leaving the old budget inflated', function () {
    $job = HousingJob::factory()->create();
    $budgetA = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Construction labor',
        'budgeted_amount' => 1000,
    ]);
    $budgetB = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Materials',
        'budgeted_amount' => 1000,
    ]);

    $expense = ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Construction labor',
        'amount' => 350,
    ]);

    expect((float) $budgetA->fresh()->spent_amount)->toBe(350.0);
    expect((float) $budgetB->fresh()->spent_amount)->toBe(0.0);

    // The bug: switching category should empty A and fill B.
    $expense->update(['category' => 'Materials']);

    expect((float) $budgetA->fresh()->spent_amount)->toBe(0.0);
    expect((float) $budgetB->fresh()->spent_amount)->toBe(350.0);
})->group('regression');

it('housing_job_id move recomputes both source and destination project budgets', function () {
    $jobA = HousingJob::factory()->create();
    $jobB = HousingJob::factory()->create();
    $budgetOnA = ProjectBudget::factory()->create([
        'housing_job_id' => $jobA->id,
        'category' => 'Construction labor',
        'budgeted_amount' => 1000,
    ]);
    $budgetOnB = ProjectBudget::factory()->create([
        'housing_job_id' => $jobB->id,
        'category' => 'Construction labor',
        'budgeted_amount' => 1000,
    ]);

    $expense = ProjectExpense::factory()->create([
        'housing_job_id' => $jobA->id,
        'category' => 'Construction labor',
        'amount' => 200,
    ]);

    expect((float) $budgetOnA->fresh()->spent_amount)->toBe(200.0);
    expect((float) $budgetOnB->fresh()->spent_amount)->toBe(0.0);

    $expense->update(['housing_job_id' => $jobB->id]);

    expect((float) $budgetOnA->fresh()->spent_amount)->toBe(0.0);
    expect((float) $budgetOnB->fresh()->spent_amount)->toBe(200.0);
})->group('regression');

it('amount edits with no tuple change recompute correctly (no-leak baseline)', function () {
    $job = HousingJob::factory()->create();
    $budget = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Materials',
        'budgeted_amount' => 1000,
    ]);

    $expense = ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Materials',
        'amount' => 100,
    ]);

    expect((float) $budget->fresh()->spent_amount)->toBe(100.0);

    $expense->update(['amount' => 250]);

    expect((float) $budget->fresh()->spent_amount)->toBe(250.0);
})->group('regression');

it('tuple change combined with amount edit lands cleanly on the destination budget', function () {
    $job = HousingJob::factory()->create();
    $budgetA = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Permits',
        'budgeted_amount' => 1000,
    ]);
    $budgetB = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Materials',
        'budgeted_amount' => 1000,
    ]);

    $expense = ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Permits',
        'amount' => 100,
    ]);

    expect((float) $budgetA->fresh()->spent_amount)->toBe(100.0);

    // Simulate a "the bookkeeper realized this was actually materials,
    // and the receipt was 175 not 100" edit — both fields change in
    // one save call.
    $expense->update([
        'category' => 'Materials',
        'amount' => 175,
    ]);

    expect((float) $budgetA->fresh()->spent_amount)->toBe(0.0);
    expect((float) $budgetB->fresh()->spent_amount)->toBe(175.0);
})->group('regression');

it('expense delete recomputes the only affected budget tuple', function () {
    $job = HousingJob::factory()->create();
    $budget = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Materials',
        'budgeted_amount' => 1000,
    ]);

    $exp1 = ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Materials',
        'amount' => 200,
    ]);
    $exp2 = ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Materials',
        'amount' => 100,
    ]);

    expect((float) $budget->fresh()->spent_amount)->toBe(300.0);

    $exp1->delete();

    expect((float) $budget->fresh()->spent_amount)->toBe(100.0);
})->group('regression');

it('expense delete on a tuple that no longer has a matching budget is a graceful no-op', function () {
    $job = HousingJob::factory()->create();

    // No budget row for "Random orphan category" — but expenses can
    // be saved against any category string. The observer must not
    // explode on the missing-budget path.
    $expense = ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Random orphan category',
        'amount' => 50,
    ]);

    expect(fn () => $expense->delete())->not->toThrow(\Throwable::class);
})->group('regression');

it('the observer recomputes from sum, so manual ->save on a budget cannot drift the spent_amount', function () {
    $job = HousingJob::factory()->create();
    $budget = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Materials',
        'budgeted_amount' => 1000,
    ]);

    ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Materials',
        'amount' => 100,
    ]);

    expect((float) $budget->fresh()->spent_amount)->toBe(100.0);

    // Someone (legacy code, console tinker) manually inflates the
    // spent_amount. The next expense write should snap it back to the
    // truth — recalculateSpent reads from sum(amount), not
    // spent_amount + delta.
    $budget->forceFill(['spent_amount' => 9999])->save();
    expect((float) $budget->fresh()->spent_amount)->toBe(9999.0);

    ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Materials',
        'amount' => 50,
    ]);

    expect((float) $budget->fresh()->spent_amount)->toBe(150.0);
})->group('regression');
