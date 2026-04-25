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

it('viewDetail opens the modal with the targeted log id', function () {
    $log = CommunicationLog::where('subject', 'like', 'CL-3:%')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->assertSet('showDetailModal', true)
        ->assertSet('viewingId', $log->id)
        ->assertSet('newUpdateContent', '')
        ->assertSet('newUpdateType', 'note');
})->group('regression');

it('closeDetailModal clears modal state', function () {
    $log = CommunicationLog::where('subject', 'like', 'CL-1:%')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('closeDetailModal')
        ->assertSet('showDetailModal', false)
        ->assertSet('viewingId', null);
})->group('regression');
