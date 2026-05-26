<?php

/*
| Phase 12.4: auto-category state machine on CommunicationLog.
|
|   - no service attached       → 'general'
|   - service attached, Phase 1
|     incomplete                → 'intake'
|   - service attached, Phase 1
|     complete                  → 'scheduling'
|   - manual dropdown pick      → locked via category_overridden_at
*/

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\HousingService;
use App\Models\IntakeRequirement;
use App\Models\Service;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('computeAutoCategory returns general when no services are attached', function () {
    $log = CommunicationLog::factory()->create(['category' => 'other']);

    expect($log->computeAutoCategory())->toBe('general');
})->group('regression');

it('computeAutoCategory returns intake when a service is attached and Phase 1 is incomplete', function () {
    $svc = HousingService::factory()->create();
    $log = CommunicationLog::factory()->create();
    $log->housingServices()->attach($svc->id, ['created_at' => now()]);

    IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'ID',
        'key' => 'id-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    expect($log->fresh()->computeAutoCategory())->toBe('intake');
})->group('regression');

it('computeAutoCategory returns scheduling when Phase 1 requirements are all met', function () {
    $svc = HousingService::factory()->create();
    $log = CommunicationLog::factory()->create();
    $log->housingServices()->attach($svc->id, ['created_at' => now()]);

    IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'ID',
        'key' => 'id-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_RECEIVED,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);
    IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Income',
        'key' => 'inc-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_WAIVED,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    expect($log->fresh()->computeAutoCategory())->toBe('scheduling');
})->group('regression');

it('computeAutoCategory returns null for converted logs (project owns category)', function () {
    $job = \App\Models\HousingJob::factory()->create();
    $log = CommunicationLog::factory()->create(['converted_to_job_id' => $job->id]);

    expect($log->computeAutoCategory())->toBeNull();
})->group('regression');

it('Phase 1 met requires non-orphaned rows — orphans do not contribute', function () {
    $svc = HousingService::factory()->create();
    $log = CommunicationLog::factory()->create();
    $log->housingServices()->attach($svc->id, ['created_at' => now()]);

    $orphan = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Stale',
        'key' => 'stale-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);
    $orphan->is_orphaned = true;
    $orphan->save();

    IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Live',
        'key' => 'live-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_RECEIVED,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    // Only the live row counts — and it's met, so we're at scheduling.
    expect($log->fresh()->computeAutoCategory())->toBe('scheduling');
})->group('regression');

it('applyAutoCategory does NOT touch the row when category_overridden_at is set', function () {
    $svc = HousingService::factory()->create();
    $log = CommunicationLog::factory()->create([
        'category' => 'complaint',
        'category_overridden_at' => now(),
    ]);
    $log->housingServices()->attach($svc->id, ['created_at' => now()]);

    $resolved = $log->fresh()->applyAutoCategory();

    expect($resolved)->toBe('complaint')
        ->and($log->fresh()->category)->toBe('complaint');
})->group('regression');

it('Livewire save() with no service attaches sets category to general', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'New')
        ->set('contact_last_name', 'Caller')
        ->set('subject', 'General inquiry')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('selectedServices', [])
        ->set('selectedHousingServiceIds', [])
        ->call('save');

    $log = CommunicationLog::latest('id')->first();
    expect($log->category)->toBe('general')
        ->and($log->category_overridden_at)->toBeNull();
})->group('regression');

it('Livewire save() with a housing-service attached auto-flips to intake when not overridden', function () {
    $svc = HousingService::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'Service')
        ->set('contact_last_name', 'Caller')
        ->set('subject', 'Wants help')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('selectedHousingServiceIds', [$svc->id])
        ->call('save');

    $log = CommunicationLog::latest('id')->first();
    expect($log->category)->toBe('intake');
})->group('regression');

it('Livewire updatedCategory + save persists the override and the auto-bump leaves it alone', function () {
    $svc = HousingService::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('contact_first_name', 'Manual')
        ->set('contact_last_name', 'Pick')
        ->set('subject', 'Got an opinion')
        ->set('communication_date', now()->toDateString())
        ->set('communication_type', 'phone_call')
        ->set('direction', 'incoming')
        ->set('priority', 'normal')
        ->set('selectedHousingServiceIds', [$svc->id])
        ->set('category', 'complaint')
        ->assertSet('categoryTouched', true)
        ->call('save');

    $log = CommunicationLog::latest('id')->first();
    expect($log->category)->toBe('complaint')
        ->and($log->category_overridden_at)->not->toBeNull();
})->group('regression');

it('marking the last Phase 1 requirement received auto-promotes category to scheduling', function () {
    $svc = HousingService::factory()->create();
    $log = CommunicationLog::factory()->create();
    $log->housingServices()->attach($svc->id, ['created_at' => now()]);

    $req = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Photo ID',
        'key' => 'pid-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    // Pre-state: intake (service + pending req).
    $log->fresh()->applyAutoCategory();
    expect($log->fresh()->category)->toBe('intake');

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('openRequirementActionModal', $req->id)
        ->call('applyRequirementAction', 'received');

    expect($log->fresh()->category)->toBe('scheduling');
})->group('regression');

it('marking the last Phase 1 requirement received does NOT bump when user has overridden the category', function () {
    $svc = HousingService::factory()->create();
    $log = CommunicationLog::factory()->create([
        'category' => 'outreach',
        'category_overridden_at' => now(),
    ]);
    $log->housingServices()->attach($svc->id, ['created_at' => now()]);

    $req = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Photo ID',
        'key' => 'pid-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('openRequirementActionModal', $req->id)
        ->call('applyRequirementAction', 'received');

    expect($log->fresh()->category)->toBe('outreach');
})->group('regression');

it('toggleHousingServiceOnLog attaching a service auto-bumps general → intake', function () {
    $log = CommunicationLog::factory()->create(['category' => 'general']);
    $svc = HousingService::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('toggleHousingServiceOnLog', $svc->id);

    // Whether requirements got stamped depends on the service's atom pivot.
    // Either way, with a service attached, the auto-category should leave
    // 'general' behind — it'll land on 'intake' or 'scheduling' depending
    // on whether atoms got stamped.
    expect($log->fresh()->category)->toBeIn(['intake', 'scheduling']);
})->group('regression');
