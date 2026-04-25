<?php

use App\Livewire\FundCodeManagement;
use App\Models\FundCode;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();

    // The list view breaks when any fund code has contract_id set, because of
    // the pre-existing `route('contracts.show', …)` bug on the view (see the
    // skipped smoke test and PHASE_0_NOTES.md). For current-behavior coverage
    // of FundCodeManagement methods, detach contracts first so the component
    // render path is exercisable.
    FundCode::query()->update(['contract_id' => null]);
});

// Phase 2 pre-image: pins today's observable FundCodeManagement behaviors so
// Phase 2 reworks show up as diffs. Scope of current behavior captured:
//   - save() uppercases + trims `code`.
//   - unique-code validation blocks a second row with the same code.
//   - toggleActive() flips is_active.
//   - delete() is blocked when fund code is attached to any communication log
//     (via FundCode::communicationLogs()).
//   - FundCode::active() scope returns only is_active=true rows.

it('save() uppercases + trims code and persists the fund code', function () {
    $before = FundCode::count();

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('showCreateForm')
        ->assertSet('showForm', true)
        ->set('code', '  fc-new-001  ') // leading/trailing space + lowercase
        ->set('description', 'Newly-added fund code')
        ->set('contract_id', '')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    expect(FundCode::count())->toBe($before + 1);

    $fc = FundCode::where('description', 'Newly-added fund code')->firstOrFail();
    expect($fc->code)->toBe('FC-NEW-001')
        ->and($fc->is_active)->toBeTrue()
        ->and($fc->contract_id)->toBeNull()
        ->and($fc->created_by)->toBe($this->user->id);
})->group('regression');

it('save() rejects a duplicate code via the unique validator', function () {
    // FC-FIX-001 is seeded.
    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('showCreateForm')
        ->set('code', 'FC-FIX-001')
        ->set('description', 'Duplicate attempt')
        ->call('save')
        ->assertHasErrors(['code']);
})->group('regression');

it('toggleActive() flips is_active', function () {
    $fc = FundCode::where('code', 'FC-FIX-003')->firstOrFail();
    expect($fc->is_active)->toBeTrue();

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('toggleActive', $fc->id);

    expect($fc->fresh()->is_active)->toBeFalse();
})->group('regression');

it('delete() is blocked when fund code is attached to a communication log', function () {
    // Re-attach FC-FIX-001 to CL-2 (the beforeEach wiped contract_id but
    // left the pivot intact); verify the in-use rule still fires.
    $fc = FundCode::where('code', 'FC-FIX-001')->firstOrFail();
    expect($fc->communicationLogs()->count())->toBeGreaterThan(0);

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('delete', $fc->id);

    expect(FundCode::find($fc->id))->not->toBeNull(); // still exists
})->group('regression');

it('delete() removes a fund code that has no attachments', function () {
    $fc = FundCode::create([
        'code' => 'FC-ORPHAN-001',
        'description' => 'Unused fund code',
        'is_active' => true,
    ]);

    expect($fc->communicationLogs()->count())->toBe(0);

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('delete', $fc->id);

    expect(FundCode::find($fc->id))->toBeNull();
})->group('regression');

it('FundCode::active() scope excludes inactive rows', function () {
    $activeCount = FundCode::count();
    FundCode::where('code', 'FC-FIX-002')->update(['is_active' => false]);

    expect(FundCode::active()->count())->toBe($activeCount - 1);
})->group('regression');
