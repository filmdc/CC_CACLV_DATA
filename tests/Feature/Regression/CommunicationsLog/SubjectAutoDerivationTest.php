<?php

/*
| Phase 5: Subject auto-derivation with the subject_overridden_at flag.
|
| The form rebuilds Subject from address fields by default, but once
| the user types in the Subject input the override sticks: future
| address changes must NOT clobber the rename.
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

it('updatedContactAddress auto-fills Subject when no override is set', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->assertSet('subject', '')
        ->set('contact_address', '123 Main St')
        ->set('contact_city', 'Allentown')
        ->assertSet('subject', '123 Main St, Allentown, PA');
})->group('regression');

it('user-typed Subject sets subjectTouched and blocks auto-rebuild', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('subject', 'Outreach to Maria — week 3')
        ->assertSet('subjectTouched', true)
        // Now setting an address should NOT clobber the user's typed Subject.
        ->set('contact_address', '123 Main St')
        ->set('contact_city', 'Allentown')
        ->assertSet('subject', 'Outreach to Maria — week 3');
})->group('regression');

it('save persists subject_overridden_at when subject was touched', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'outgoing')
        ->set('category', 'general')
        ->set('contact_first_name', 'Jane')
        ->set('subject', 'Custom rename')
        ->call('save')
        ->assertHasNoErrors();

    $log = CommunicationLog::latest('id')->first();
    expect($log->subject)->toBe('Custom rename');
    expect($log->subject_overridden_at)->not->toBeNull();
})->group('regression');

it('reopening a log with subject_overridden_at preserves the override on subsequent edits', function () {
    // Create a log with an explicit override flag.
    $log = CommunicationLog::factory()->create([
        'subject' => 'Custom name',
        'subject_overridden_at' => now()->subDays(2),
        'contact_address' => '999 Old Ave',
        'contact_city' => 'Bethlehem',
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $log->id)
        ->assertSet('subject', 'Custom name')
        // Changing the address must NOT rewrite the subject.
        ->set('contact_address', '456 New St')
        ->assertSet('subject', 'Custom name');
})->group('regression');

it('save preserves an existing subject_overridden_at when user did not touch Subject this session', function () {
    $log = CommunicationLog::factory()->create([
        'subject' => 'Locked-in name',
        'subject_overridden_at' => now()->subDays(5),
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $log->id)
        ->set('notes', 'Updated some notes only.')
        ->call('save');

    expect($log->fresh()->subject_overridden_at)->not->toBeNull();
    expect($log->fresh()->subject)->toBe('Locked-in name');
})->group('regression');
