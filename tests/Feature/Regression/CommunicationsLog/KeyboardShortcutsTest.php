<?php

/*
| Phase 8: keyboard shortcuts.
|
| The Alpine keymap is exercised at the browser level; here we cover
| the Livewire methods it invokes so the wiring is regression-safe:
|   - saveAndNew()  (⌘⇧⏎)
|   - editFromViewer() (E in viewer)
*/

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('saveAndNew saves and immediately opens a blank Create form', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'outgoing')
        ->set('category', 'general')
        ->set('contact_first_name', 'Save-and-new')
        ->call('saveAndNew')
        ->assertHasNoErrors()
        ->assertSet('showForm', true)
        ->assertSet('editingId', null)
        ->assertSet('contact_first_name', '');

    // The first save persisted.
    expect(CommunicationLog::where('contact_first_name', 'Save-and-new')->count())->toBe(1);
})->group('regression');

it('saveAndNew with validation errors leaves the form open and does NOT re-open create', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        // Missing required first name — save should error.
        ->set('communication_type', 'phone_call')
        ->set('contact_first_name', '')
        ->call('saveAndNew')
        ->assertHasErrors(['contact_first_name'])
        ->assertSet('showForm', true);
})->group('regression');

it('editFromViewer closes the viewer and opens the edit form on the same id', function () {
    $log = CommunicationLog::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->assertSet('showDetailModal', true)
        ->call('editFromViewer')
        ->assertSet('showDetailModal', false)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $log->id);
})->group('regression');

it('editFromViewer is a no-op when no viewer is open', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('editFromViewer')
        ->assertSet('showForm', false)
        ->assertSet('showDetailModal', false);
})->group('regression');
