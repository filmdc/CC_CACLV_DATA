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

it('showEditForm hydrates form fields from the existing log', function () {
    $log = CommunicationLog::where('subject', 'like', 'CL-1:%')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $log->id)
        ->assertSet('editingId', $log->id)
        ->assertSet('showForm', true)
        ->assertSet('subject', $log->subject)
        ->assertSet('communication_type', $log->communication_type)
        ->assertSet('direction', $log->direction)
        ->assertSet('category', $log->category)
        ->assertSet('priority', $log->priority);
})->group('regression');

it('save updates the persisted log and updated_by', function () {
    $log = CommunicationLog::where('subject', 'like', 'CL-2:%')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $log->id)
        ->set('subject', 'CL-2: Single fund code, edited by regression')
        ->set('summary', 'Updated summary text.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $log->refresh();
    expect($log->subject)->toBe('CL-2: Single fund code, edited by regression')
        ->and($log->summary)->toBe('Updated summary text.')
        ->and($log->updated_by)->toBe($this->user->id);
})->group('regression');
