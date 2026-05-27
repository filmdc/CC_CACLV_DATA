<?php

use App\Livewire\FundCodeManagement;
use App\Models\Contract;
use App\Models\FundCode;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('renders the fund codes index without 500ing on contract-linked codes (P-05)', function () {
    // The seeder creates FC-FIX-001 / FC-FIX-002 with contract_id set; this
    // would 500 before commit 7's typo fix. Smoke check from the component
    // angle as well as the route.
    $html = Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->html();

    expect($html)->toContain('FC-FIX-001');
    expect($html)->toContain('FC-FIX-002');
})->group('regression');

it('creates a fund code with contract_id deliberately blank (P-05)', function () {
    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('showCreateForm')
        ->set('code', 'BARE-001')
        ->set('description', 'Bare fund code without contract')
        ->set('contract_id', '')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $created = FundCode::where('code', 'BARE-001')->firstOrFail();
    expect($created->contract_id)->toBeNull();
    expect($created->is_active)->toBeTrue();
})->group('regression');

it('creates a fund code linked to a contract (P-05)', function () {
    $contract = Contract::factory()->create([
        'title' => 'Linked Contract Probe',
    ]);

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('showCreateForm')
        ->set('code', 'LINKED-001')
        ->set('description', 'Linked fund code')
        ->set('contract_id', $contract->id)
        ->call('save')
        ->assertHasNoErrors();

    $created = FundCode::where('code', 'LINKED-001')->firstOrFail();
    expect($created->contract_id)->toBe($contract->id);
})->group('regression');

it('uppercases the code on save (existing behavior, regression-locked)', function () {
    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('showCreateForm')
        ->set('code', 'lower-case')
        ->set('description', 'should be uppercased')
        ->call('save')
        ->assertHasNoErrors();

    // utf8mb4_unicode_ci collation matches case-insensitively, so we
    // compare the stored bytes via the model attribute rather than via a
    // WHERE that the DB would treat as equivalent.
    $stored = FundCode::where('description', 'should be uppercased')->firstOrFail();
    expect($stored->code)->toBe('LOWER-CASE');
})->group('regression');

it('exposes the Fund Requirements management button on each fund-code row (P-06; relabeled in comm-log-v2)', function () {
    // comm-log-v2 redesign: the old inline "Requirements (N)" count badge
    // was replaced by a "Fund Requirements" button that opens the library
    // atom-link modal (openAtomLinkModal). The count is no longer surfaced
    // on the row by design; assert the management affordance is present.
    $code = FundCode::factory()->create(['code' => 'REQ-COUNT-001']);

    $html = Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->set('search', 'REQ-COUNT-001')
        ->html();

    expect($html)->toContain('REQ-COUNT-001');
    expect($html)->toContain('Fund Requirements');
    expect($html)->toContain('openAtomLinkModal('.$code->id.')');
})->group('regression');
