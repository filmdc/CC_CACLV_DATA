<?php

/*
| Phase 12.8: per-user default view-mode preference for the
| Communications Log list page.
|
| Precedence in CommunicationsLogManagement::mount():
|   URL ?viewMode=  >  user.comm_log_default_view  >  'table' default.
*/

use App\Livewire\CommunicationsLogManagement;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('defaults viewMode to table when the user has no preference set', function () {
    $this->user->update(['comm_log_default_view' => null]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->assertSet('viewMode', 'table');
})->group('regression');

it("honors the user's 'card' preference on mount", function () {
    $this->user->update(['comm_log_default_view' => 'card']);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->assertSet('viewMode', 'card');
})->group('regression');

it("honors the user's 'table' preference on mount", function () {
    $this->user->update(['comm_log_default_view' => 'table']);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->assertSet('viewMode', 'table');
})->group('regression');

it('URL ?viewMode=table overrides a card preference', function () {
    $this->user->update(['comm_log_default_view' => 'card']);

    Livewire::actingAs($this->user)
        ->withQueryParams(['viewMode' => 'table'])
        ->test(CommunicationsLogManagement::class)
        ->assertSet('viewMode', 'table');
})->group('regression');

it('URL ?viewMode=card overrides a table preference', function () {
    $this->user->update(['comm_log_default_view' => 'table']);

    Livewire::actingAs($this->user)
        ->withQueryParams(['viewMode' => 'card'])
        ->test(CommunicationsLogManagement::class)
        ->assertSet('viewMode', 'card');
})->group('regression');

it('an invalid preference value is ignored (falls back to table)', function () {
    // Directly set a junk value bypassing model-level validation to simulate
    // an out-of-band write (e.g. seed drift, manual SQL).
    \DB::table('users')->where('id', $this->user->id)->update(['comm_log_default_view' => 'rubbish']);

    Livewire::actingAs($this->user->fresh())
        ->test(CommunicationsLogManagement::class)
        ->assertSet('viewMode', 'table');
})->group('regression');

it('profile page saves the comm_log_default_view preference', function () {
    Livewire::actingAs($this->user)
        ->test('settings.profile')
        ->set('comm_log_default_view', 'card')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($this->user->fresh()->comm_log_default_view)->toBe('card');
})->group('regression');

it('profile page clears the preference when the user picks "No preference"', function () {
    $this->user->update(['comm_log_default_view' => 'card']);

    Livewire::actingAs($this->user)
        ->test('settings.profile')
        ->set('comm_log_default_view', '')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($this->user->fresh()->comm_log_default_view)->toBeNull();
})->group('regression');

it('profile page rejects an invalid view-mode value', function () {
    Livewire::actingAs($this->user)
        ->test('settings.profile')
        ->set('comm_log_default_view', 'kanban')
        ->call('updateProfileInformation')
        ->assertHasErrors(['comm_log_default_view']);

    expect($this->user->fresh()->comm_log_default_view)->toBeNull();
})->group('regression');
