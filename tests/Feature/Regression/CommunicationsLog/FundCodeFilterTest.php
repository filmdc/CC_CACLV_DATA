<?php

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

// Pins the page-level $fundCodeFilter dropdown on the Communications Log
// management view. P-03 (Phase 3B) converted this filter from single-
// select string to multi-select array. Tests assert array-shape state
// throughout. Fixture coverage:
//   FC-FIX-001 → CL-2, CL-3 (two attached logs)
//   FC-FIX-002 → CL-3 (one attached log)
//   FC-FIX-003 → CL-4 (one attached log; the "FC-B" partner here)
//   CL-1, CL-5 → no fund codes attached.

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('narrows the rendered list to logs attached to the chosen fund code', function () {
    $fcA = FundCode::where('code', 'FC-FIX-001')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('fundCodeFilter', [(string) $fcA->id])
        ->assertSee('CL-2: Single fund code')
        ->assertSee('CL-3: Multiple fund codes')
        ->assertDontSee('CL-1: General inquiry')
        ->assertDontSee('CL-4: Converted to project (HJ-1), single fund code')
        ->assertDontSee('CL-5: Converted to project (HJ-2), no fund code');
})->group('regression');

it('clears the filter when fundCodeFilter is set back to an empty array', function () {
    $fcA = FundCode::where('code', 'FC-FIX-001')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('fundCodeFilter', [(string) $fcA->id])
        ->assertDontSee('CL-1: General inquiry')
        ->set('fundCodeFilter', [])
        ->assertSee('CL-1: General inquiry')
        ->assertSee('CL-2: Single fund code')
        ->assertSee('CL-3: Multiple fund codes')
        ->assertSee('CL-4: Converted to project (HJ-1), single fund code')
        ->assertSee('CL-5: Converted to project (HJ-2), no fund code');
})->group('regression');

it('exposes fundCodeFilter via the component query-string config', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class);

    // Read the protected $queryString config off the component instance.
    $qs = (function () {
        return $this->queryString;
    })->call($component->instance());

    expect($qs)->toHaveKey('fundCodeFilter')
        ->and($qs['fundCodeFilter'])->toBe(['except' => []]);
})->group('regression');

it('resets pagination to page 1 when fundCodeFilter changes', function () {
    $fcA = FundCode::where('code', 'FC-FIX-001')->firstOrFail();

    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('paginators.page', 2);

    expect($component->instance()->getPage())->toBe(2);

    $component->set('fundCodeFilter', [(string) $fcA->id]);

    expect($component->instance()->getPage())->toBe(1);
})->group('regression');

it('renders every active fund code as an option in the filter dropdown', function () {
    // The fixture creates FC-FIX-001, FC-FIX-002, FC-FIX-003 — all is_active=true.
    // The dropdown labels each option with the model's full_display accessor:
    //   "{code} – {description}".
    $html = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->html();

    expect($html)
        ->toContain('FC-FIX-001 – Fixture Fund — Roofing (Contract A)')
        ->toContain('FC-FIX-002 – Fixture Fund — Plumbing (Contract A)')
        ->toContain('FC-FIX-003 – Fixture Fund — Standalone (no contract)');
})->group('regression');
