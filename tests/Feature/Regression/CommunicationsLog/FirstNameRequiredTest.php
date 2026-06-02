<?php

/*
| Phase 12.7: contact_first_name is required and must accept a value
| typed after a prior empty-submit validation error.
|
| Kaitlyn's bug report (2026-06-02): she forgot to fill in First Name,
| got the "required" error on Save, then typed a name and pressed Save
| again — and the form kept rejecting it as if the field were still
| blank. Same shape as the Phase 12.6 email bug: bare wire:model defers
| the sync, Save click races the blur, server re-validates the empty
| pre-edit value. Fix: wire:model.blur on the input + updatedContactFirstName
| hook that trims and clears the error bag on edit.
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

it('save() fails with a "required" error when first name is empty', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_last_name', 'Surnameonly')
        ->set('subject', 'Missing first name')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('category', 'general')
        ->call('save')
        ->assertHasErrors(['contact_first_name' => 'required']);

    expect(CommunicationLog::where('subject', 'Missing first name')->exists())->toBeFalse();
})->group('regression');

it("filling in the first name after a failed save lets the user submit — Kaitlyn's bug", function () {
    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_last_name', 'Repro')
        ->set('subject', 'Forgot first name then filled it')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('category', 'general');

    // First save: empty first name → required error.
    $component->call('save')->assertHasErrors(['contact_first_name' => 'required']);

    // User types the name and resubmits. The stale error must NOT block.
    $component->set('contact_first_name', 'Kaitlyn')
        ->call('save')
        ->assertHasNoErrors();

    expect(CommunicationLog::where('subject', 'Forgot first name then filled it')->first()?->contact_first_name)
        ->toBe('Kaitlyn');
})->group('regression');

it('whitespace-only first name is treated as empty by the updated hook and still fails required', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', '   ')
        ->assertSet('contact_first_name', '')   // hook trimmed to empty
        ->set('contact_last_name', 'Whitespace')
        ->set('subject', 'Whitespace first name')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('category', 'general')
        ->call('save')
        ->assertHasErrors(['contact_first_name' => 'required']);

    expect(CommunicationLog::where('subject', 'Whitespace first name')->exists())->toBeFalse();
})->group('regression');

it('first name with surrounding whitespace is trimmed and saved cleanly', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', '  Padded  ')
        ->assertSet('contact_first_name', 'Padded')
        ->set('contact_last_name', 'Name')
        ->set('subject', 'Padded first name')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('category', 'general')
        ->call('save')
        ->assertHasNoErrors();

    expect(CommunicationLog::where('subject', 'Padded first name')->first()?->contact_first_name)
        ->toBe('Padded');
})->group('regression');
