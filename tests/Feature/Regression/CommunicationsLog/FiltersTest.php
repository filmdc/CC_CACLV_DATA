<?php

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use Carbon\Carbon;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

/*
|----------------------------------------------------------------------
| P-03 (Phase 3B) — Communications Log multi-select filters and full
| URL persistence.
|
| Three filters changed shape:
|   fundCodeFilter   single-select string  → multi-select array
|   followUpFilter   single-select string  → multi-select array, with
|                                             a new "completed" option
|                                             and stricter "pending"
|                                             semantics (future-or-today
|                                             AND not done; the loose
|                                             scopePendingFollowUp
|                                             remains for the dashboard
|                                             count)
|   conversionFilter (new)                 → multi-select array
|
| The full filter set round-trips through queryString so any filter
| state can be shared as a URL.
|----------------------------------------------------------------------
*/

beforeEach(function () {
    // Pin time so "pending vs overdue" buckets are deterministic.
    Carbon::setTestNow('2026-04-01 12:00:00');
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('fund-code multi-select returns logs attached to ANY of the selected codes', function () {
    $fcA = FundCode::where('code', 'FC-FIX-001')->firstOrFail(); // CL-2, CL-3
    $fcB = FundCode::where('code', 'FC-FIX-003')->firstOrFail(); // CL-4

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('fundCodeFilter', [(string) $fcA->id, (string) $fcB->id])
        ->assertSee('CL-2: Single fund code')
        ->assertSee('CL-3: Multiple fund codes')
        ->assertSee('CL-4: Converted to project (HJ-1), single fund code')
        ->assertDontSee('CL-1: General inquiry')
        ->assertDontSee('CL-5: Converted to project (HJ-2), no fund code');
})->group('regression');

it('fund-code filter composes AND with date range', function () {
    $fcA = FundCode::where('code', 'FC-FIX-001')->firstOrFail();

    // FC-FIX-001 is on CL-2 (2026-03-02) and CL-3 (2026-03-03).
    // Restrict the date range to 2026-03-03+ — only CL-3 should remain.
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('fundCodeFilter', [(string) $fcA->id])
        ->set('dateFrom', '2026-03-03')
        ->assertSee('CL-3: Multiple fund codes')
        ->assertDontSee('CL-2: Single fund code');
})->group('regression');

it('conversion filter "converted" returns only logs linked to a housing job', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('conversionFilter', ['converted'])
        ->assertSee('CL-4: Converted to project (HJ-1), single fund code')
        ->assertSee('CL-5: Converted to project (HJ-2), no fund code')
        ->assertDontSee('CL-1: General inquiry')
        ->assertDontSee('CL-2: Single fund code')
        ->assertDontSee('CL-3: Multiple fund codes');
})->group('regression');

it('conversion filter "not_converted" returns only logs not yet linked', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('conversionFilter', ['not_converted'])
        ->assertSee('CL-1: General inquiry')
        ->assertSee('CL-2: Single fund code')
        ->assertSee('CL-3: Multiple fund codes')
        ->assertDontSee('CL-4: Converted to project (HJ-1), single fund code')
        ->assertDontSee('CL-5: Converted to project (HJ-2), no fund code');
})->group('regression');

it('follow-up multi-select pending returns future-or-today not-done items only', function () {
    // Build three logs with discriminating follow-up shapes.
    CommunicationLog::factory()->create([
        'subject' => 'CL-FOLLOW-PENDING',
        'communication_date' => '2026-04-01',
        'follow_up_date' => '2026-04-10',  // future
        'follow_up_completed' => false,
    ]);
    CommunicationLog::factory()->create([
        'subject' => 'CL-FOLLOW-OVERDUE',
        'communication_date' => '2026-03-15',
        'follow_up_date' => '2026-03-20',  // past
        'follow_up_completed' => false,
    ]);
    CommunicationLog::factory()->create([
        'subject' => 'CL-FOLLOW-DONE',
        'communication_date' => '2026-03-15',
        'follow_up_date' => '2026-03-20',
        'follow_up_completed' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('followUpFilter', ['pending'])
        ->assertSee('CL-FOLLOW-PENDING')
        ->assertDontSee('CL-FOLLOW-OVERDUE')
        ->assertDontSee('CL-FOLLOW-DONE');
})->group('regression');

it('follow-up multi-select with pending+overdue selected returns the union', function () {
    CommunicationLog::factory()->create([
        'subject' => 'CL-FOLLOW-PENDING',
        'communication_date' => '2026-04-01',
        'follow_up_date' => '2026-04-10',
        'follow_up_completed' => false,
    ]);
    CommunicationLog::factory()->create([
        'subject' => 'CL-FOLLOW-OVERDUE',
        'communication_date' => '2026-03-15',
        'follow_up_date' => '2026-03-20',
        'follow_up_completed' => false,
    ]);
    CommunicationLog::factory()->create([
        'subject' => 'CL-FOLLOW-DONE',
        'communication_date' => '2026-03-15',
        'follow_up_date' => '2026-03-20',
        'follow_up_completed' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('followUpFilter', ['pending', 'overdue'])
        ->assertSee('CL-FOLLOW-PENDING')
        ->assertSee('CL-FOLLOW-OVERDUE')
        ->assertDontSee('CL-FOLLOW-DONE');
})->group('regression');

it('follow-up multi-select "completed" returns only done items', function () {
    CommunicationLog::factory()->create([
        'subject' => 'CL-FOLLOW-PENDING',
        'communication_date' => '2026-04-01',
        'follow_up_date' => '2026-04-10',
        'follow_up_completed' => false,
    ]);
    CommunicationLog::factory()->create([
        'subject' => 'CL-FOLLOW-DONE',
        'communication_date' => '2026-03-15',
        'follow_up_date' => '2026-03-20',
        'follow_up_completed' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('followUpFilter', ['completed'])
        ->assertSee('CL-FOLLOW-DONE')
        ->assertDontSee('CL-FOLLOW-PENDING');
})->group('regression');

it('queryString covers every visible filter (full URL persistence)', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class);

    $qs = (function () {
        return $this->queryString;
    })->call($component->instance());

    // P-03 requirement: bookmarkable URL state for every visible filter.
    expect($qs)->toHaveKeys([
        'search',
        'typeFilter',
        'categoryFilter',
        'priorityFilter',
        'directionFilter',
        'serviceFilter',
        'tagFilter',
        'fundCodeFilter',
        'followUpFilter',
        'conversionFilter',
        'dateFrom',
        'dateTo',
    ]);

    // Array-shape filters declare their except as an empty array, not
    // an empty string — the Livewire serializer needs the type hint.
    expect($qs['fundCodeFilter'])->toBe(['except' => []]);
    expect($qs['followUpFilter'])->toBe(['except' => []]);
    expect($qs['conversionFilter'])->toBe(['except' => []]);
})->group('regression');

it('clearFilters resets every filter and pagination', function () {
    $fcA = FundCode::where('code', 'FC-FIX-001')->firstOrFail();

    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->set('search', 'something')
        ->set('typeFilter', 'phone_call')
        ->set('fundCodeFilter', [(string) $fcA->id])
        ->set('followUpFilter', ['pending'])
        ->set('conversionFilter', ['converted'])
        ->set('dateFrom', '2026-01-01')
        ->set('dateTo', '2026-12-31')
        ->set('paginators.page', 2);

    $component->call('clearFilters');

    expect($component->instance()->search)->toBe('');
    expect($component->instance()->typeFilter)->toBe('');
    expect($component->instance()->fundCodeFilter)->toBe([]);
    expect($component->instance()->followUpFilter)->toBe([]);
    expect($component->instance()->conversionFilter)->toBe([]);
    expect($component->instance()->dateFrom)->toBe('');
    expect($component->instance()->dateTo)->toBe('');
    expect($component->instance()->getPage())->toBe(1);
})->group('regression');

it('snapshot pins the multi-select filter bar markup', function () {
    $html = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->html();

    \Tests\Support\SnapshotHelper::assertHtmlMatchesSnapshot('communications_log_filter_bar', $html);
})->group('regression');
