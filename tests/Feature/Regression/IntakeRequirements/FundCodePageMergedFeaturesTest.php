<?php

/*
| Phase 10.4: the Fund Codes management page now hosts both the
| housing-service catalog (moved from /requirements-library) and the
| per-fund-code atom-link modal.
*/

use App\Livewire\FundCodeManagement;
use App\Models\FundCode;
use App\Models\HousingService;
use App\Models\RequirementDefinition;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('default tab is fund_codes; setTab switches to housing_services', function () {
    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->assertSet('activeTab', 'fund_codes')
        ->call('setTab', 'housing_services')
        ->assertSet('activeTab', 'housing_services');
})->group('regression');

it('openAtomLinkModal loads the current linked atoms for the fund code', function () {
    $fc = FundCode::factory()->create();
    $atom1 = RequirementDefinition::factory()->create();
    $atom2 = RequirementDefinition::factory()->create();
    $fc->requirementDefinitions()->attach([$atom1->id, $atom2->id]);

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('openAtomLinkModal', $fc->id)
        ->assertSet('showAtomLinkModal', true)
        ->assertSet('atomLinkFundCodeId', $fc->id)
        ->assertSet('atomLinkSelectedIds', [$atom1->id, $atom2->id]);
})->group('regression');

it('toggleAtomLink + saveAtomLinks sync the pivot', function () {
    $fc = FundCode::factory()->create();
    $atom = RequirementDefinition::factory()->create();

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('openAtomLinkModal', $fc->id)
        ->call('toggleAtomLink', $atom->id)
        ->assertSet('atomLinkSelectedIds', [$atom->id])
        ->call('saveAtomLinks');

    expect($fc->fresh()->requirementDefinitions->pluck('id')->all())->toBe([$atom->id]);
})->group('regression');

it('housing-service CRUD on the fund-codes page creates and edits services', function () {
    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('setTab', 'housing_services')
        ->call('showCreateService')
        ->set('serviceName', 'Foreclosure Prevention')
        ->set('serviceDescription', 'PHFA HEMAP help.')
        ->call('saveService')
        ->assertHasNoErrors();

    $svc = HousingService::where('name', 'Foreclosure Prevention')->firstOrFail();
    expect($svc->created_by_user_id)->toBe($this->user->id);
})->group('regression');

it('saveServiceLinks (on the fund-codes page) syncs fund codes + atoms on a service', function () {
    $svc = HousingService::factory()->create();
    $fc = FundCode::factory()->create();
    $atom = RequirementDefinition::factory()->create();

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('setTab', 'housing_services')
        ->call('openService', $svc->id)
        ->set('serviceLinkedFundCodeIds', [$fc->id])
        ->set('serviceLinkedAtomIds', [$atom->id])
        ->call('saveServiceLinks');

    $fresh = $svc->fresh();
    expect($fresh->fundCodes->pluck('id')->all())->toBe([$fc->id]);
    expect($fresh->requirementDefinitions->pluck('id')->all())->toBe([$atom->id]);
})->group('regression');
