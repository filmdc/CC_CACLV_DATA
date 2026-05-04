<?php

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;
use Tests\Support\SnapshotHelper;

/*
|----------------------------------------------------------------------
| P-04 (Phase 3B) — view-mode toggle on the Communications Log.
|
| Two modes: 'table' (compact, dense rows — existing UX) and 'card'
| (more per row, fewer rows on screen — full notes body, fund-code
| badges, contact metadata block). URL-only persistence; no per-user
| default. Toggle lives next to "Clear all filters" on row 3.
|----------------------------------------------------------------------
*/

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('defaults viewMode to table on first load', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->assertSet('viewMode', 'table');
})->group('regression');

it('exposes viewMode in queryString with table as the except-value (URL-only persistence)', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class);

    $qs = (function () {
        return $this->queryString;
    })->call($component->instance());

    expect($qs)->toHaveKey('viewMode')
        ->and($qs['viewMode'])->toBe(['except' => 'table']);
})->group('regression');

it('round-trips between table and card via the toggle', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->assertSet('viewMode', 'table')
        ->call('$set', 'viewMode', 'card')
        ->assertSet('viewMode', 'card')
        ->call('$set', 'viewMode', 'table')
        ->assertSet('viewMode', 'table');
})->group('regression');

it('table view clips long summaries; card view shows the full body', function () {
    // CL-3 has fund codes attached (from RegressionTestSeeder); make
    // its summary deliberately longer than 200 chars so the table-vs-
    // card divergence is visible in render.
    $cl3 = CommunicationLog::where('subject', 'CL-3: Multiple fund codes, not converted')->firstOrFail();
    $longSummary = 'OPENING-MARKER ' . str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 8) . 'CLOSING-MARKER';
    $cl3->update(['summary' => $longSummary]);

    // Table view: closing marker should be clipped off (Str::limit at 200).
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->assertSet('viewMode', 'table')
        ->assertSee('OPENING-MARKER')
        ->assertDontSee('CLOSING-MARKER');

    // Card view: full body, both markers visible.
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('viewMode', 'card')
        ->assertSee('OPENING-MARKER')
        ->assertSee('CLOSING-MARKER');
})->group('regression');

it('card view surfaces fund-code badges that the table view omits', function () {
    // CL-3 in the fixture has FC-FIX-001 + FC-FIX-002 attached.
    $cl3 = CommunicationLog::where('subject', 'CL-3: Multiple fund codes, not converted')->firstOrFail();
    expect($cl3->fundCodes()->count())->toBe(2);

    // Table view does NOT render the per-row fund-code badge block —
    // fund codes are only surfaced in the filter dropdown.
    $tableHtml = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->html();

    expect($tableHtml)->not->toContain('data-card-fund-codes');

    // Card view renders the badge block per-row.
    $cardHtml = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('viewMode', 'card')
        ->html();

    expect($cardHtml)->toContain('data-card-fund-codes');
    expect($cardHtml)->toContain('FC-FIX-001');
    expect($cardHtml)->toContain('FC-FIX-002');
})->group('regression');

it('card view surfaces contact metadata that the table view omits', function () {
    $cl3 = CommunicationLog::where('subject', 'CL-3: Multiple fund codes, not converted')->firstOrFail();
    $cl3->update([
        'contact_organization' => 'ACME Roofing Co.',
        'contact_email' => 'tony@acme.example',
        'contact_phone' => '555-0123',
    ]);

    $tableHtml = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->html();
    expect($tableHtml)->not->toContain('data-card-contact-metadata');
    expect($tableHtml)->not->toContain('ACME Roofing Co.');

    $cardHtml = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('viewMode', 'card')
        ->html();
    expect($cardHtml)->toContain('data-card-contact-metadata');
    expect($cardHtml)->toContain('ACME Roofing Co.');
    expect($cardHtml)->toContain('tony@acme.example');
    expect($cardHtml)->toContain('555-0123');
})->group('regression');

it('card view renders the full notes body (when set) in its own block', function () {
    $cl3 = CommunicationLog::where('subject', 'CL-3: Multiple fund codes, not converted')->firstOrFail();
    $cl3->update(['notes' => "First line of detailed notes.\nSecond line with extra context for the case worker.\nThird line."]);

    // Notes block is card-only.
    $tableHtml = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->html();
    expect($tableHtml)->not->toContain('data-card-notes');
    expect($tableHtml)->not->toContain('First line of detailed notes.');

    $cardHtml = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('viewMode', 'card')
        ->html();
    expect($cardHtml)->toContain('data-card-notes');
    expect($cardHtml)->toContain('First line of detailed notes.');
    expect($cardHtml)->toContain('Third line.');
})->group('regression');

it('toggle interacts cleanly with active filters (filter state survives view switch)', function () {
    $fcA = FundCode::where('code', 'FC-FIX-001')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('fundCodeFilter', [(string) $fcA->id])
        ->set('conversionFilter', ['not_converted'])
        ->set('viewMode', 'card')
        ->assertSet('fundCodeFilter', [(string) $fcA->id])
        ->assertSet('conversionFilter', ['not_converted'])
        ->set('viewMode', 'table')
        ->assertSet('fundCodeFilter', [(string) $fcA->id])
        ->assertSet('conversionFilter', ['not_converted']);
})->group('regression');

it('snapshot pins the card-mode markup', function () {
    $cl3 = CommunicationLog::where('subject', 'CL-3: Multiple fund codes, not converted')->firstOrFail();
    $cl3->update([
        'contact_organization' => 'Snapshot Org',
        'contact_email' => 'snapshot@example.com',
        'contact_phone' => '555-0000',
        'notes' => "Snapshot notes — full body.\nSecond line.",
    ]);

    $html = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('viewMode', 'card')
        ->html();

    SnapshotHelper::assertHtmlMatchesSnapshot('communications_log_card_view', $html);
})->group('regression');
