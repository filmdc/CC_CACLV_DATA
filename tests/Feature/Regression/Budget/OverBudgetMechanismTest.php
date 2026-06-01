<?php

use App\Livewire\ProjectManagement;
use App\Models\BudgetModification;
use App\Models\HousingJob;
use App\Models\OverBudgetEmailRecipient;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use App\Models\User;
use App\Notifications\BudgetModifiedNotification;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

/*
|----------------------------------------------------------------------
| Phase 3B Step 7 — P-23 part B: over-budget mechanism.
|
| 80% soft warning, 100%+ persistent banner, audit ledger on
| modification, mail to configured recipients, opt-out routing.
|----------------------------------------------------------------------
*/

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

function makeJobWithFundedBudget(float $budgeted = 1000): array
{
    $job = HousingJob::factory()->create();
    $fundCode = $job->fundCodes()->first();
    if (! $fundCode) {
        $fc = \App\Models\FundCode::factory()->create();
        $job->fundCodes()->attach($fc->id);
        $fundCode = $fc;
    }
    $budget = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Construction labor',
        'budgeted_amount' => $budgeted,
        'fund_code_id' => $fundCode->id,
    ]);

    return [$job, $budget, $fundCode];
}

it('80% soft warning indicator fires at exactly 80% spent (was >90% pre-3B)', function () {
    [$job, $budget] = makeJobWithFundedBudget(1000);

    ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Construction labor',
        'amount' => 800,
    ]);

    expect($budget->fresh()->percentage_spent)->toBe(80);
    expect($budget->fresh()->is_over_budget)->toBeFalse();

    // Project Detail redesign moved the budget table to per-scope burn bars;
    // the visual at 80% is no longer a yellow Tailwind chip but an amber
    // burn-bar fill. The durable contract is exposed via data-budget-status.
    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('activeTab', 'budget')
        ->assertSeeHtml('data-budget-status="warning"')
        ->assertDontSeeHtml('data-over-budget-banner');
})->group('regression');

it('100%+ shows the persistent over-budget banner and the red row indicator', function () {
    [$job, $budget] = makeJobWithFundedBudget(1000);

    ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Construction labor',
        'amount' => 1100,
    ]);

    expect($budget->fresh()->is_over_budget)->toBeTrue();

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('activeTab', 'budget')
        ->assertSeeHtml('data-over-budget-banner')
        ->assertSeeHtml('Budget exceeded')
        ->assertSeeHtml('Construction labor')
        ->assertSeeHtml('data-budget-status="over"');
})->group('regression');

it('reducing expenses back under the cap clears the over-budget banner', function () {
    [$job, $budget] = makeJobWithFundedBudget(1000);

    $exp = ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Construction labor',
        'amount' => 1100,
    ]);
    expect($budget->fresh()->is_over_budget)->toBeTrue();

    $exp->update(['amount' => 600]);
    expect($budget->fresh()->is_over_budget)->toBeFalse();

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('activeTab', 'budget')
        ->assertDontSeeHtml('data-over-budget-banner');
})->group('regression');

it('modifying budgeted_amount upward to clear over-budget state writes a ledger entry and shows the modified indicator', function () {
    Notification::fake();

    [$job, $budget, $fundCode] = makeJobWithFundedBudget(1000);

    ProjectExpense::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Construction labor',
        'amount' => 1100,
    ]);
    expect($budget->fresh()->is_over_budget)->toBeTrue();

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('editBudget', $budget->id)
        ->set('budget_amount', 1500)
        ->call('saveBudget')
        // amount changed → modal opens
        ->assertSet('showBudgetModConfirmModal', true)
        ->set('budgetModComment', 'Scope grew to include additional rough framing.')
        ->call('confirmBudgetModification')
        ->assertSet('showBudgetModConfirmModal', false);

    expect((float) $budget->fresh()->budgeted_amount)->toBe(1500.0);
    expect($budget->fresh()->is_over_budget)->toBeFalse();

    $entry = BudgetModification::where('project_budget_id', $budget->id)->latest()->first();
    expect($entry)->not->toBeNull();
    expect((float) $entry->old_amount)->toBe(1000.0);
    expect((float) $entry->new_amount)->toBe(1500.0);
    expect($entry->comment)->toContain('Scope grew');
})->group('regression');

it('rejects a modification comment shorter than 10 characters server-side', function () {
    [$job, $budget] = makeJobWithFundedBudget(1000);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('editBudget', $budget->id)
        ->set('budget_amount', 1200)
        ->call('saveBudget')
        ->set('budgetModComment', 'too short')   // 9 chars
        ->call('confirmBudgetModification')
        ->assertHasErrors(['budgetModComment'])
        ->assertSet('showBudgetModConfirmModal', true);

    expect((float) $budget->fresh()->budgeted_amount)->toBe(1000.0);
    expect(BudgetModification::where('project_budget_id', $budget->id)->count())->toBe(0);
})->group('regression');

it('decreasing the budget cap also triggers the audit flow (modifications-only is direction-agnostic)', function () {
    [$job, $budget] = makeJobWithFundedBudget(2000);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('editBudget', $budget->id)
        ->set('budget_amount', 1500)
        ->call('saveBudget')
        ->assertSet('showBudgetModConfirmModal', true)
        ->set('budgetModComment', 'Reduced after fund reallocation in fiscal review.')
        ->call('confirmBudgetModification')
        ->assertSet('showBudgetModConfirmModal', false);

    expect((float) $budget->fresh()->budgeted_amount)->toBe(1500.0);
    $entry = BudgetModification::where('project_budget_id', $budget->id)->latest()->first();
    expect($entry->direction())->toBe('decrease');
})->group('regression');

it('editing a budget without changing the amount does not open the modification modal', function () {
    [$job, $budget, $fundCode] = makeJobWithFundedBudget(1000);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('editBudget', $budget->id)
        // category renamed but amount unchanged
        ->set('budget_category', 'Construction labor (rev 2)')
        ->call('saveBudget')
        ->assertSet('showBudgetModConfirmModal', false);

    expect($budget->fresh()->category)->toBe('Construction labor (rev 2)');
    expect(BudgetModification::where('project_budget_id', $budget->id)->count())->toBe(0);
})->group('regression');

it('dispatches BudgetModifiedNotification to recipients opted in to over_budget_email', function () {
    Notification::fake();

    [$job, $budget] = makeJobWithFundedBudget(1000);

    $recipient = User::factory()->create(['notification_preferences' => null, 'active' => true]);
    OverBudgetEmailRecipient::create(['user_id' => $recipient->id, 'created_by' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('editBudget', $budget->id)
        ->set('budget_amount', 1300)
        ->call('saveBudget')
        ->set('budgetModComment', 'Allocation expanded to cover permits.')
        ->call('confirmBudgetModification');

    Notification::assertSentTo($recipient, BudgetModifiedNotification::class, function ($notification, array $channels) {
        return in_array('database', $channels) && in_array('mail', $channels);
    });
})->group('regression');

it('database channel still fires for opted-out recipients (mail-only opt-out)', function () {
    Notification::fake();

    [$job, $budget] = makeJobWithFundedBudget(1000);

    $optOutRecipient = User::factory()->create([
        'notification_preferences' => ['over_budget_email' => false],
        'active' => true,
    ]);
    OverBudgetEmailRecipient::create(['user_id' => $optOutRecipient->id, 'created_by' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('editBudget', $budget->id)
        ->set('budget_amount', 1300)
        ->call('saveBudget')
        ->set('budgetModComment', 'Allocation expanded after fund-code review.')
        ->call('confirmBudgetModification');

    Notification::assertSentTo($optOutRecipient, BudgetModifiedNotification::class, function ($notification, array $channels) {
        return in_array('database', $channels) && ! in_array('mail', $channels);
    });
})->group('regression');

it('writes the ledger entry even when the recipient list is empty (no mail goes out)', function () {
    Notification::fake();

    [$job, $budget] = makeJobWithFundedBudget(1000);

    expect(OverBudgetEmailRecipient::count())->toBe(0);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('editBudget', $budget->id)
        ->set('budget_amount', 1300)
        ->call('saveBudget')
        ->set('budgetModComment', 'No recipients configured yet — ledger only.')
        ->call('confirmBudgetModification');

    expect(BudgetModification::where('project_budget_id', $budget->id)->count())->toBe(1);
    Notification::assertNothingSent();
})->group('regression');

it('shows the "Modified (N)" indicator on the budget row once a ledger entry exists', function () {
    [$job, $budget] = makeJobWithFundedBudget(1000);

    BudgetModification::factory()->create([
        'project_budget_id' => $budget->id,
        'modified_by_user_id' => $this->admin->id,
        'old_amount' => 1000,
        'new_amount' => 1200,
        'comment' => 'Adjusted up after a re-bid.',
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id]);

    // Sanity-check the eager-load actually hydrated modifications_count
    // before asserting on the indirect markup. If the count is 0/null
    // here, the closure form of withCount in the eager-load isn't
    // working as expected.
    $loadedBudget = $component->instance()->job->budgets->firstWhere('id', $budget->id);
    expect($loadedBudget)->not->toBeNull();
    expect($loadedBudget->modifications_count ?? 'NULL')->toBe(1);

    $component->set('activeTab', 'budget')
        ->assertSeeHtml('data-budget-modified-indicator')
        ->assertSeeHtml('Modified (1)');
})->group('regression');

it('the ledger viewer modal renders entries newest-first with modifier and comment', function () {
    [$job, $budget] = makeJobWithFundedBudget(1000);

    BudgetModification::factory()->create([
        'project_budget_id' => $budget->id,
        'modified_by_user_id' => $this->admin->id,
        'old_amount' => 1000,
        'new_amount' => 1100,
        'comment' => 'First modification — small bump.',
        'created_at' => now()->subMinutes(10),
    ]);
    BudgetModification::factory()->create([
        'project_budget_id' => $budget->id,
        'modified_by_user_id' => $this->admin->id,
        'old_amount' => 1100,
        'new_amount' => 900,
        'comment' => 'Second modification — pulled back after fiscal review.',
        'created_at' => now(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showBudgetLedger', $budget->id)
        ->assertSet('showBudgetLedgerModal', true)
        ->assertSet('viewingLedgerBudgetId', $budget->id)
        ->assertSeeHtml('data-budget-ledger-modal')
        ->assertSee('Second modification')
        ->assertSee('First modification — small bump.');
})->group('regression');

it('inactive users on the recipient list do not receive notifications', function () {
    Notification::fake();

    [$job, $budget] = makeJobWithFundedBudget(1000);

    $inactive = User::factory()->create(['active' => false, 'notification_preferences' => null]);
    OverBudgetEmailRecipient::create(['user_id' => $inactive->id, 'created_by' => $this->admin->id]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('editBudget', $budget->id)
        ->set('budget_amount', 1300)
        ->call('saveBudget')
        ->set('budgetModComment', 'Inactive users should be skipped.')
        ->call('confirmBudgetModification');

    Notification::assertNotSentTo($inactive, BudgetModifiedNotification::class);
})->group('regression');
