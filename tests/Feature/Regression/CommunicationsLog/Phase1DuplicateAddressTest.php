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

it('flags a possible duplicate when entering an address that matches an existing property (P-01)', function () {
    // RegressionTestSeeder creates properties with addresses like "1 Test Lane",
    // "2 Test Lane" etc., in Allentown. AddressNormalizer should treat
    // "1 Test Ln" (suffix-abbreviated, lowercased) as a match.
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_address', '1 test ln')
        ->set('contact_city', 'Allentown')
        ->assertCount('duplicateMatches', 1, 'Expected exactly one matching property');
})->group('regression');

it('does not flag duplicates when the address has no match (P-01)', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_address', '999 Nowhere Boulevard')
        ->set('contact_city', 'Atlantis')
        ->assertSet('duplicateMatches', []);
})->group('regression');

it('save short-circuits with a non-blocking warning when address matches existing property (P-01)', function () {
    $countBefore = CommunicationLog::count();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_date', '2026-04-10')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('contact_first_name', 'Test')
        ->set('contact_last_name', 'User')
        ->set('contact_address', '1 Test Lane')
        ->set('contact_city', 'Allentown')
        ->set('subject', 'Possible duplicate')
        ->set('category', 'general')
        ->set('priority', 'normal')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', true); // warning panel shown, form still open

    // No log was created.
    expect(CommunicationLog::count())->toBe($countBefore);
})->group('regression');

it('acknowledgeDuplicatesAndSave proceeds with the save despite the warning (P-01)', function () {
    $countBefore = CommunicationLog::count();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_date', '2026-04-10')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('contact_first_name', 'Test')
        ->set('contact_last_name', 'User')
        ->set('contact_address', '1 Test Lane')
        ->set('contact_city', 'Allentown')
        ->set('subject', 'Confirmed duplicate save')
        ->set('category', 'general')
        ->set('priority', 'normal')
        ->call('acknowledgeDuplicatesAndSave')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    expect(CommunicationLog::count())->toBe($countBefore + 1);
    expect(CommunicationLog::latest('id')->first()->subject)
        ->toBe('Confirmed duplicate save');
})->group('regression');

it('editing an existing log does not match itself but does match its sibling (P-01)', function () {
    // Two logs at the same denormalized address. Editing one should match
    // the other but not itself.
    $editing = CommunicationLog::factory()->create([
        'contact_address' => '777 Selfmatch Street',
        'contact_city' => 'Allentown',
        'contact_state' => 'PA',
        'contact_zip' => '18101',
        'subject' => 'Editing this one',
    ]);

    $sibling = CommunicationLog::factory()->create([
        'contact_address' => '777 selfmatch st',
        'contact_city' => 'allentown',
        'contact_state' => 'PA',
        'contact_zip' => '18101',
        'subject' => 'Sibling at same address',
    ]);

    $matches = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $editing->id)
        ->call('checkAddressDuplicates')
        ->get('duplicateMatches');

    $logMatches = collect($matches)->where('kind', 'log');

    // Editing log is excluded …
    expect($logMatches->pluck('id')->all())->not->toContain($editing->id);
    // … but the sibling is still flagged.
    expect($logMatches->pluck('id')->all())->toContain($sibling->id);
})->group('regression');
