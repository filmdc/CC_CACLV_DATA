<?php

/*
| UX shift: housing-service add/remove moves from the edit form to the
| detail viewer's Focus 2 section. Tests cover the toggle method's
| attach + detach paths and the orphan-on-detach behavior.
*/

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HousingService;
use App\Models\IntakeRequirement;
use App\Models\RequirementDefinition;
use App\Services\IntakeRequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
    $this->service = app(IntakeRequirementInstantiator::class);
});

it('toggleHousingServiceOnLog attaches a service to the viewed log + auto-attaches funds + stamps requirements', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $fund = FundCode::factory()->create(['code' => 'HSG-AUTO']);
    $svc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);
    $svc->fundCodes()->attach($fund->id, ['created_at' => now()]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('toggleHousingServiceOnLog', $svc->id);

    $fresh = $log->fresh();
    expect($fresh->housingServices->pluck('id')->all())->toContain($svc->id);
    expect($fresh->anticipatedFundCodes->pluck('id')->all())->toContain($fund->id);
    expect($fresh->intakeRequirements()->where('key', 'photo-id')->exists())->toBeTrue();
})->group('regression');

it('toggleHousingServiceOnLog a second time detaches the service and orphans auto-applied requirements', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id']);
    $svc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('toggleHousingServiceOnLog', $svc->id);

    // Re-toggle = detach
    $component->call('toggleHousingServiceOnLog', $svc->id);

    $fresh = $log->fresh();
    expect($fresh->housingServices()->count())->toBe(0);

    $req = $fresh->intakeRequirements()->where('key', 'photo-id')->first();
    expect($req)->not->toBeNull();
    expect($req->is_orphaned)->toBeTrue();
    expect($req->via)->toBeEmpty();
})->group('regression');

it('detaching a service leaves fund codes attached (user removes funds manually)', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $fund = FundCode::factory()->create(['code' => 'KEEP-ME']);
    $svc->fundCodes()->attach($fund->id, ['created_at' => now()]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('toggleHousingServiceOnLog', $svc->id)
        ->call('toggleHousingServiceOnLog', $svc->id);

    expect($log->fresh()->anticipatedFundCodes->pluck('id')->all())->toContain($fund->id);
})->group('regression');
