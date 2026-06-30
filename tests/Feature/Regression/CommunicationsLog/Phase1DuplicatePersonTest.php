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

it('flags a possible duplicate person when the name matches an existing log', function () {
    CommunicationLog::factory()->create([
        'contact_first_name' => 'Reginald',
        'contact_last_name' => 'Worthington',
        'communication_date' => '2026-02-01',
        'subject' => 'Initial intake call',
    ]);

    $matches = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'reginald') // case-insensitive
        ->set('contact_last_name', 'WORTHINGTON')
        ->call('checkPersonDuplicates')
        ->get('personMatches');

    expect($matches)->toHaveCount(1);
    expect($matches[0]['count'])->toBe(1);
    expect($matches[0]['name'])->toBe('Reginald Worthington');
})->group('regression');

it('collapses many prior entries for the same person into one row with a count', function () {
    CommunicationLog::factory()->count(5)->create([
        'contact_first_name' => 'Marcus',
        'contact_last_name' => 'Holloway',
    ]);

    $matches = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'Marcus')
        ->set('contact_last_name', 'Holloway')
        ->call('checkPersonDuplicates')
        ->get('personMatches');

    // One person row, not five.
    expect($matches)->toHaveCount(1);
    expect($matches[0]['count'])->toBe(5);
})->group('regression');

it('matches on exact email even when the name differs', function () {
    CommunicationLog::factory()->create([
        'contact_first_name' => 'Bob',
        'contact_last_name' => 'Smith',
        'contact_email' => 'shared@example.com',
    ]);

    $matches = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'Robert') // different name
        ->set('contact_last_name', 'Smithson')
        ->set('contact_email', 'Shared@Example.com') // case-insensitive match
        ->call('checkPersonDuplicates')
        ->get('personMatches');

    expect($matches)->toHaveCount(1);
})->group('regression');

it('matches on phone regardless of formatting', function () {
    CommunicationLog::factory()->create([
        'contact_first_name' => 'Dana',
        'contact_last_name' => 'Pierce',
        'contact_phone' => '(702) 555-8190',
    ]);

    $matches = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'Someone') // no name match
        ->set('contact_last_name', 'Else')
        ->set('contact_phone', '702.555.8190') // same digits, different format
        ->call('checkPersonDuplicates')
        ->get('personMatches');

    expect($matches)->toHaveCount(1);
})->group('regression');

it('does not flag when there is no matching person', function () {
    CommunicationLog::factory()->create([
        'contact_first_name' => 'Existing',
        'contact_last_name' => 'Person',
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'Totally')
        ->set('contact_last_name', 'Unrelated')
        ->set('contact_phone', '999-999-9999')
        ->call('checkPersonDuplicates')
        ->assertSet('personMatches', []);
})->group('regression');

it('save short-circuits with a non-blocking warning when the person already exists', function () {
    CommunicationLog::factory()->create([
        'contact_first_name' => 'Walter',
        'contact_last_name' => 'Keene',
    ]);

    $countBefore = CommunicationLog::count();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_date', '2026-04-10')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('contact_first_name', 'Walter')
        ->set('contact_last_name', 'Keene')
        ->set('subject', 'Possible duplicate person')
        ->set('category', 'general')
        ->set('priority', 'normal')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', true); // warning shown, form still open

    expect(CommunicationLog::count())->toBe($countBefore);
})->group('regression');

it('acknowledgeDuplicatesAndSave proceeds despite the person warning', function () {
    CommunicationLog::factory()->create([
        'contact_first_name' => 'Walter',
        'contact_last_name' => 'Keene',
    ]);

    $countBefore = CommunicationLog::count();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_date', '2026-04-10')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('contact_first_name', 'Walter')
        ->set('contact_last_name', 'Keene')
        ->set('subject', 'Confirmed duplicate person save')
        ->set('category', 'general')
        ->set('priority', 'normal')
        ->call('acknowledgeDuplicatesAndSave')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    expect(CommunicationLog::count())->toBe($countBefore + 1);
})->group('regression');

it('editing a log excludes itself but still matches a sibling for the same person', function () {
    $editing = CommunicationLog::factory()->create([
        'contact_first_name' => 'Priya',
        'contact_last_name' => 'Nandakumar',
    ]);

    CommunicationLog::factory()->create([
        'contact_first_name' => 'Priya',
        'contact_last_name' => 'Nandakumar',
    ]);

    $matches = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $editing->id)
        ->call('checkPersonDuplicates')
        ->get('personMatches');

    // One person row, counting only the sibling (self excluded).
    expect($matches)->toHaveCount(1);
    expect($matches[0]['count'])->toBe(1);
})->group('regression');
