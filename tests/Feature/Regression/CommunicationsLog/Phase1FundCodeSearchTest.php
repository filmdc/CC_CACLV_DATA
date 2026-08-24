<?php

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

// RegressionTestSeeder wires fund codes to comm logs as follows:
//   FC-FIX-001 (Roofing, Contract A) → CL-2, CL-3
//   FC-FIX-002 (Plumbing, Contract A) → CL-3
//   FC-FIX-003 (Standalone)           → CL-4

it('search by fund code surfaces logs that have that code attached (P-02)', function () {
    $matches = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('search', 'FC-FIX-003')
        ->viewData('logs');

    $subjects = $matches->pluck('subject')->all();
    expect($subjects)->toContain('CL-4: Converted to project (HJ-1), single fund code');
    // Logs without FC-FIX-003 should not match.
    expect($subjects)->not->toContain('CL-1: Initial intake call');
})->group('regression');

it('search does NOT match fund code descriptions (2026-08 city-search fix)', function () {
    // Deliberate reversal of the original P-02 description matching:
    // fund codes named after places ("Allentown Better Homes") made every
    // log tagged with that fund hit a search for the city, regardless of
    // where the caller actually lives. Fund codes now match on the short
    // code only; the description is ignored.
    $matches = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('search', 'Roofing')
        ->viewData('logs');

    // CL-2 and CL-3 carry FC-FIX-001 (description "Roofing") but nothing
    // else about them mentions roofing — they must no longer match.
    $subjects = $matches->pluck('subject')->all();
    $hasFc001Log = collect($subjects)->contains(
        fn ($s) => str_contains($s, 'CL-2') || str_contains($s, 'CL-3')
    );
    expect($hasFc001Log)->toBeFalse('Fund code descriptions must not leak into free-text search');
})->group('regression');

it('a place-named fund code does not pollute a city search (2026-08 city-search fix)', function () {
    $fund = \App\Models\FundCode::factory()->create([
        'code' => 'F-PLACE',
        'description' => 'Allentown Better Homes',
    ]);

    $inAllentown = CommunicationLog::factory()->create([
        'subject' => 'Caller in Allentown',
        'contact_city' => 'Allentown',
    ]);
    $inWhitehall = CommunicationLog::factory()->create([
        'subject' => 'Caller in Whitehall',
        'contact_city' => 'Whitehall',
    ]);
    $inWhitehall->anticipatedFundCodes()->attach($fund->id);

    $subjects = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('search', 'Allentown')
        ->viewData('logs')
        ->pluck('subject')->all();

    expect($subjects)->toContain('Caller in Allentown');
    expect($subjects)->not->toContain('Caller in Whitehall');
})->group('regression');

it('search by name still works (P-02 is additive, does not regress existing search)', function () {
    // Pre-create a log we can search by name.
    CommunicationLog::factory()->create([
        'subject' => 'Search probe',
        'contact_first_name' => 'Zelda',
        'contact_last_name' => 'Smithington',
    ]);

    $matches = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('search', 'Smithington')
        ->viewData('logs');

    expect($matches->pluck('subject')->all())->toContain('Search probe');
})->group('regression');

it('search returns empty when nothing matches (P-02)', function () {
    $matches = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('search', 'NoSuchFundCodeOrAddressOrName-XYZ123')
        ->viewData('logs');

    expect($matches->total())->toBe(0);
})->group('regression');
