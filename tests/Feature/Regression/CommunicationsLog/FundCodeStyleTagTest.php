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

// P-27 pre-image: how attached fund codes currently surface on a communication
// log's detail view. Current behavior (verified 2026-04-24): fund codes are
// ONLY rendered inside the detail modal, not on the list view, via
// `@foreach($viewingLog->fundCodes as $fundCode)` in
// resources/views/livewire/communications-log-management.blade.php:507-510.
// This test captures that so any Phase 1 styling / placement change (e.g.
// moving fund-code tags to the list view, or restyling as chips) shows as a
// diff.

it('renders attached fund-code codes when the log is opened in detail view', function () {
    $cl3 = CommunicationLog::where('subject', 'like', 'CL-3:%')->firstOrFail();
    expect($cl3->fundCodes)->toHaveCount(2);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $cl3->id)
        ->assertSet('showDetailModal', true)
        ->assertSee('FC-FIX-001')
        ->assertSee('FC-FIX-002');
})->group('regression');

it('shows only attached fund codes on detail view — unattached ones are absent', function () {
    // CL-2 has ONLY FC-FIX-001 attached; FC-FIX-003 is the standalone fund
    // code (not attached to any log).
    $cl2 = CommunicationLog::where('subject', 'like', 'CL-2:%')->firstOrFail();
    expect($cl2->fundCodes)->toHaveCount(1);

    $html = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $cl2->id)
        ->assertSee('FC-FIX-001')
        ->html();

    // The detail-modal chip wraps codes as <span class="font-mono font-semibold">{code}</span>.
    // FC-FIX-003 may legitimately appear elsewhere on the page (e.g., the
    // page-level fund-code filter dropdown), so scope this assertion to the
    // chip markup rather than the whole document.
    expect($html)->not->toContain('font-mono font-semibold">FC-FIX-003<');
})->group('regression');
