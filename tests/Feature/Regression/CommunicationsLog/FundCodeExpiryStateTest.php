<?php

/*
| Phase 4: FundCode expiry_state + days_to_expiry accessors, and the
| save-time block against newly checking an expired fund code.
*/

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('expiry_state is "perpetual" when expires_at is null', function () {
    $fund = FundCode::factory()->create(['expires_at' => null]);

    expect($fund->expiry_state)->toBe('perpetual');
    expect($fund->days_to_expiry)->toBeNull();
})->group('regression');

it('expiry_state is "expired" when expires_at is in the past', function () {
    $fund = FundCode::factory()->create(['expires_at' => now()->subDays(7)->toDateString()]);

    expect($fund->expiry_state)->toBe('expired');
    expect($fund->days_to_expiry)->toBe(7);
})->group('regression');

it('expiry_state is "soon" when expires_at is ≤30 days out', function () {
    $fund = FundCode::factory()->create(['expires_at' => now()->addDays(15)->toDateString()]);

    expect($fund->expiry_state)->toBe('soon');
    expect($fund->days_to_expiry)->toBe(15);
})->group('regression');

it('expiry_state is "warning" when expires_at is 31–90 days out', function () {
    $fund = FundCode::factory()->create(['expires_at' => now()->addDays(60)->toDateString()]);

    expect($fund->expiry_state)->toBe('warning');
    expect($fund->days_to_expiry)->toBe(60);
})->group('regression');

it('expiry_state is "ok" when expires_at is >90 days out', function () {
    $fund = FundCode::factory()->create(['expires_at' => now()->addDays(180)->toDateString()]);

    expect($fund->expiry_state)->toBe('ok');
    expect($fund->days_to_expiry)->toBe(180);
})->group('regression');

it('save blocks newly checking an expired fund code with a clear error', function () {
    $expired = FundCode::factory()->create([
        'expires_at' => now()->subDays(10)->toDateString(),
        'is_active' => true,
        'code' => 'EXP-1',
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'outgoing')
        ->set('category', 'general')
        ->set('contact_first_name', 'Jane')
        ->set('selectedFundCodeIds', [$expired->id])
        ->call('save')
        ->assertHasErrors(['selectedFundCodeIds']);
})->group('regression');

it('save allows an already-attached expired fund to remain (no new check)', function () {
    $expired = FundCode::factory()->create([
        'expires_at' => now()->subDays(10)->toDateString(),
        'is_active' => true,
        'code' => 'EXP-2',
    ]);

    $log = CommunicationLog::factory()->create();
    $log->anticipatedFundCodes()->attach($expired->id, [
        'created_by_user_id' => $this->user->id,
        'created_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $log->id)
        // Re-saving with the same fund attached should NOT error.
        ->call('save')
        ->assertHasNoErrors();
})->group('regression');

it('save allows checking a non-expired fund', function () {
    $live = FundCode::factory()->create([
        'expires_at' => now()->addDays(60)->toDateString(),
        'is_active' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'outgoing')
        ->set('category', 'general')
        ->set('contact_first_name', 'Jane')
        ->set('selectedFundCodeIds', [$live->id])
        ->call('save')
        ->assertHasNoErrors();
})->group('regression');
