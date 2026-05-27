<?php

/*
| Phase 9.3: HousingService CRUD inside the Requirements Library UI.
*/

use App\Livewire\RequirementsLibraryManagement;
use App\Models\FundCode;
use App\Models\HousingService;
use App\Models\RequirementDefinition;
use App\Models\RequirementTemplateSet;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('Services tab is reachable via setTab', function () {
    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('setTab', 'services')
        ->assertSet('activeTab', 'services');
})->group('regression');

it('saveService creates a new HousingService and opens its detail', function () {
    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('setTab', 'services')
        ->call('showCreateService')
        ->set('serviceName', 'PHFA HEMAP Application Assistance')
        ->set('serviceDescription', 'Help applicants prepare HEMAP packets.')
        ->call('saveService')
        ->assertHasNoErrors()
        ->assertSet('showServiceForm', false)
        ->assertNotSet('editingServiceId', null);

    $svc = HousingService::where('name', 'PHFA HEMAP Application Assistance')->firstOrFail();
    expect($svc->created_by_user_id)->toBe($this->user->id);
})->group('regression');

it('toggleServiceFundCode / Bundle / Atom flip the in-memory selection arrays', function () {
    $svc = HousingService::factory()->create();
    $fund = FundCode::factory()->create();
    $bundle = RequirementTemplateSet::factory()->create();
    $atom = RequirementDefinition::factory()->create();

    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('openService', $svc->id)
        ->call('toggleServiceFundCode', $fund->id)
        ->call('toggleServiceBundle', $bundle->id)
        ->call('toggleServiceAtom', $atom->id)
        ->assertSet('serviceLinkedFundCodeIds', [$fund->id])
        ->assertSet('serviceLinkedBundleIds', [$bundle->id])
        ->assertSet('serviceLinkedAtomIds', [$atom->id])
        // Toggle off
        ->call('toggleServiceFundCode', $fund->id)
        ->assertSet('serviceLinkedFundCodeIds', []);
})->group('regression');

it('saveServiceLinks2 syncs all three pivots on the housing service', function () {
    $svc = HousingService::factory()->create();
    $fund = FundCode::factory()->create();
    $bundle = RequirementTemplateSet::factory()->create();
    $atom = RequirementDefinition::factory()->create();

    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('openService', $svc->id)
        ->set('serviceLinkedFundCodeIds', [$fund->id])
        ->set('serviceLinkedBundleIds', [$bundle->id])
        ->set('serviceLinkedAtomIds', [$atom->id])
        ->call('saveServiceLinks2');

    $fresh = $svc->fresh();
    expect($fresh->fundCodes->pluck('id')->all())->toBe([$fund->id]);
    expect($fresh->templateSets->pluck('id')->all())->toBe([$bundle->id]);
    expect($fresh->requirementDefinitions->pluck('id')->all())->toBe([$atom->id]);
})->group('regression');

it('deleteService soft-deletes; restoreService brings it back', function () {
    $svc = HousingService::factory()->create();

    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('setTab', 'services')
        ->call('deleteService', $svc->id);

    expect($svc->fresh()->trashed())->toBeTrue();

    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('setTab', 'services')
        ->call('restoreService', $svc->id);

    expect($svc->fresh()->trashed())->toBeFalse();
})->group('regression');
