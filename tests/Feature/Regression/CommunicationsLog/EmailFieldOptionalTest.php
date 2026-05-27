<?php

/*
| Phase 12.6: email field is optional and must remain submittable
| after a failed attempt with invalid content has been cleared.
|
| Kaitlyn's bug report (2026-05-26): the type="email" browser native
| validation held a stale :invalid state after she cleared the field,
| blocking submission of an entry that did not require an email.
| Fix: switch to type="text" + inputmode="email" and let server-side
| Laravel validation own format checking. updatedContactEmail trims
| so whitespace-only doesn't slip past `nullable|email` as junk.
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

it('save() passes with no email value at all', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'No')
        ->set('contact_last_name', 'Email')
        ->set('subject', 'No email needed')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('category', 'general')
        ->call('save')
        ->assertHasNoErrors();

    expect(CommunicationLog::where('subject', 'No email needed')->exists())->toBeTrue();
})->group('regression');

it('save() fails with a clear validation error when email is invalid junk', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'Bad')
        ->set('contact_last_name', 'Email')
        ->set('subject', 'With junk email')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('category', 'general')
        ->set('contact_email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['contact_email' => 'email']);

    expect(CommunicationLog::where('subject', 'With junk email')->exists())->toBeFalse();
})->group('regression');

it("clearing the email field after a failed save lets the user submit again — Kaitlyn's bug", function () {
    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'Kaitlyn')
        ->set('contact_last_name', 'Repro')
        ->set('subject', 'Email cleared after junk')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('category', 'general')
        ->set('contact_email', 'asdf');

    // First save: invalid → error.
    $component->call('save')->assertHasErrors(['contact_email' => 'email']);

    // Clear the field and resubmit. The error must NOT come back.
    $component->set('contact_email', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(CommunicationLog::where('subject', 'Email cleared after junk')->exists())->toBeTrue();
})->group('regression');

it('whitespace-only email is treated as empty (trimmed by updatedContactEmail)', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'Whitespace')
        ->set('contact_last_name', 'Email')
        ->set('subject', 'Whitespace email')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('category', 'general')
        ->set('contact_email', '   ')
        ->assertSet('contact_email', '')   // hook trimmed to empty
        ->call('save')
        ->assertHasNoErrors();

    expect(CommunicationLog::where('subject', 'Whitespace email')->first()?->contact_email)->toBeNull();
})->group('regression');

it('valid emails still save correctly with leading/trailing whitespace trimmed', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'Padded')
        ->set('contact_last_name', 'Email')
        ->set('subject', 'Padded email')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('category', 'general')
        ->set('contact_email', '  good@example.com  ')
        ->assertSet('contact_email', 'good@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(CommunicationLog::where('subject', 'Padded email')->first()?->contact_email)
        ->toBe('good@example.com');
})->group('regression');
