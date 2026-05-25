<?php

/*
| Phase 11: the detail viewer organizes content into 3 numbered focus
| areas — communication logging, service selection, requirements +
| compliance.
*/

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HousingService;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('viewer renders all three focus-area section headers', function () {
    $log = CommunicationLog::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->assertSee('Communication logging')
        ->assertSee('Service selection')
        ->assertSee('Requirements &amp; compliance', false);
})->group('regression');

it('Service selection section lists attached housing services + anticipated fund codes', function () {
    $svc = HousingService::factory()->create(['name' => 'FTHB Counseling']);
    $fc = FundCode::factory()->create(['code' => 'HSG-X-99', 'description' => 'Test fund']);

    $log = CommunicationLog::factory()->create();
    $log->housingServices()->attach($svc->id, ['created_at' => now()]);
    $log->anticipatedFundCodes()->attach($fc->id, [
        'created_by_user_id' => $this->user->id,
        'created_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->assertSee('FTHB Counseling')
        ->assertSee('HSG-X-99');
})->group('regression');

it('Service selection section shows empty-state hints when nothing is attached', function () {
    $log = CommunicationLog::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->assertSee('No housing service selected.')
        ->assertSee('None yet');
})->group('regression');
