<?php

/*
| Phase 9.4: housing-service picker on the Comm Log edit form.
|
| Covers toggleHousingService (auto-merge of fund codes), persistence
| via syncHousingServices() on save, idempotent re-save, and
| end-to-end requirements stamping.
*/

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HousingService;
use App\Models\IntakeRequirement;
use App\Models\RequirementDefinition;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('toggleHousingService auto-merges the service fund codes into selectedFundCodeIds', function () {
    $svc = HousingService::factory()->create();
    $fund = FundCode::factory()->create(['code' => 'HSG-AUTO']);
    $svc->fundCodes()->attach($fund->id, ['created_at' => now()]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->assertSet('selectedFundCodeIds', [])
        ->assertSet('selectedHousingServiceIds', [])
        ->call('toggleHousingService', $svc->id)
        ->assertSet('selectedHousingServiceIds', [$svc->id])
        ->assertSet('selectedFundCodeIds', [$fund->id]);
})->group('regression');

it('toggleHousingService a second time deselects the service (but leaves fund codes alone)', function () {
    $svc = HousingService::factory()->create();
    $fund = FundCode::factory()->create();
    $svc->fundCodes()->attach($fund->id, ['created_at' => now()]);

    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->call('toggleHousingService', $svc->id)
        ->assertSet('selectedHousingServiceIds', [$svc->id])
        ->call('toggleHousingService', $svc->id)
        ->assertSet('selectedHousingServiceIds', []);

    // Fund codes are left alone on detach (user can explicitly remove them).
    expect($component->get('selectedFundCodeIds'))->toBe([$fund->id]);
})->group('regression');

it('save persists the housing-service pivot AND stamps requirements via the instantiator', function () {
    $svc = HousingService::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $svc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);
    $fund = FundCode::factory()->create(['code' => 'HSG-FUND']);
    $svc->fundCodes()->attach($fund->id, ['created_at' => now()]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'outgoing')
        ->set('category', 'general')
        ->set('contact_first_name', 'Maria')
        ->call('toggleHousingService', $svc->id)
        ->call('save')
        ->assertHasNoErrors();

    $log = CommunicationLog::latest('id')->first();

    // Pivot persisted.
    expect($log->housingServices->pluck('id')->all())->toContain($svc->id);

    // Fund codes persisted (toggle pre-merged them into selection).
    expect($log->anticipatedFundCodes->pluck('id')->all())->toContain($fund->id);

    // Requirement instantiated from the service's atom.
    $req = $log->intakeRequirements()->first();
    expect($req)->not->toBeNull();
    expect($req->key)->toBe('photo-id');
    expect($req->via)->toContain('service:'.$svc->id);
})->group('regression');

it('re-saving the same log with the same housing service does not duplicate intake requirements', function () {
    $svc = HousingService::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $svc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'outgoing')
        ->set('category', 'general')
        ->set('contact_first_name', 'Maria')
        ->call('toggleHousingService', $svc->id)
        ->call('save');

    $log = CommunicationLog::latest('id')->first();
    $countAfterFirst = $log->intakeRequirements()->count();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $log->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($log->fresh()->intakeRequirements()->count())->toBe($countAfterFirst);
})->group('regression');

it('detaching a housing service via save removes the pivot row', function () {
    $svc = HousingService::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('communication_type', 'phone_call')
        ->set('direction', 'outgoing')
        ->set('category', 'general')
        ->set('contact_first_name', 'Maria')
        ->call('toggleHousingService', $svc->id)
        ->call('save');

    $log = CommunicationLog::latest('id')->first();
    expect($log->housingServices->pluck('id')->all())->toContain($svc->id);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $log->id)
        ->call('toggleHousingService', $svc->id)  // detach
        ->call('save');

    expect($log->fresh()->housingServices()->count())->toBe(0);
})->group('regression');
