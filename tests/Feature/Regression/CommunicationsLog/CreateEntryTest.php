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

it('creates a communication log entry via showCreateForm + save', function () {
    $countBefore = CommunicationLog::count();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->assertSet('showForm', true)
        ->set('communication_date', '2026-04-10')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('contact_first_name', 'Mona')
        ->set('contact_last_name', 'Fixture')
        ->set('contact_phone', '555-555-0900')
        ->set('subject', 'Regression: brand new entry')
        ->set('category', 'general')
        ->set('priority', 'normal')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    expect(CommunicationLog::count())->toBe($countBefore + 1);

    $created = CommunicationLog::latest('id')->first();
    expect($created->subject)->toBe('Regression: brand new entry')
        ->and($created->contact_first_name)->toBe('Mona')
        ->and($created->contact_last_name)->toBe('Fixture')
        ->and($created->category)->toBe('general')
        ->and($created->priority)->toBe('normal')
        ->and($created->direction)->toBe('incoming')
        ->and($created->created_by)->toBe($this->user->id);
})->group('regression');

it('blocks save when required contact_first_name is missing', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_date', '2026-04-10')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('contact_first_name', '') // required
        ->set('subject', 'Regression: should fail')
        ->set('category', 'general')
        ->set('priority', 'normal')
        ->call('save')
        ->assertHasErrors(['contact_first_name']);
})->group('regression');
