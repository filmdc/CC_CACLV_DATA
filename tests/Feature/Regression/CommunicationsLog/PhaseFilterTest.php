<?php

/*
| Phase 10.5: the Comm Log surfaces only Phase 1 (eligibility +
| compliance) requirements; service_delivery_compliance items are
| stamped on the log but hidden in intake views, and don't block
| Convert-to-Project promotion.
*/

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\HousingService;
use App\Models\IntakeRequirement;
use App\Models\Program;
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

it('currentRequirements on the form filters to Phase 1 categories', function () {
    $log = CommunicationLog::factory()->create();

    $atomP1 = RequirementDefinition::factory()->create(['key' => 'photo-id', 'category' => 'eligibility']);
    $atomP1Compliance = RequirementDefinition::factory()->create(['key' => 'hmis-consent', 'category' => 'compliance']);
    $atomP2 = RequirementDefinition::factory()->create(['key' => 'final-inspection', 'category' => 'service_delivery_compliance']);

    foreach ([$atomP1, $atomP1Compliance, $atomP2] as $a) {
        $this->service->forDefinition($log, $a, $this->user);
    }

    expect(IntakeRequirement::where('communication_log_id', $log->id)->count())->toBe(3);

    // The form's `currentRequirements` view binding filters to Phase 1.
    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $log->id);

    $rendered = $component->instance()->render()->getData();
    $keys = $rendered['currentRequirements']->pluck('key')->all();

    expect($keys)->toContain('photo-id');
    expect($keys)->toContain('hmis-consent');
    expect($keys)->not->toContain('final-inspection');
})->group('regression');

it('Convert-to-Project gate does NOT block on Phase 2 unmet requirements', function () {
    $log = CommunicationLog::where('subject', 'like', 'CL-1:%')->firstOrFail();
    $program = Program::where('name', 'Fixture Housing Program')->firstOrFail();

    // Unmet service-delivery-compliance atom on the log. Should NOT block.
    $atomP2 = RequirementDefinition::factory()->create(['category' => 'service_delivery_compliance', 'default_required' => true]);
    $this->service->forDefinition($log, $atomP2, $this->user);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('openConvertModal', $log->id)
        ->set('convertJobTitle', 'Phase 2 should not block')
        ->set('convertProgramId', $program->id)
        ->call('convertToProject')
        ->assertHasNoErrors();

    expect($log->fresh()->converted_to_job_id)->not->toBeNull();
})->group('regression');

it('Convert-to-Project gate DOES block on Phase 1 unmet requirements', function () {
    $log = CommunicationLog::where('subject', 'like', 'CL-2:%')->firstOrFail();
    $program = Program::where('name', 'Fixture Housing Program')->firstOrFail();

    $atomP1 = RequirementDefinition::factory()->create(['category' => 'eligibility', 'default_required' => true]);
    $this->service->forDefinition($log, $atomP1, $this->user);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('openConvertModal', $log->id)
        ->set('convertJobTitle', 'Phase 1 must block')
        ->set('convertProgramId', $program->id)
        ->call('convertToProject')
        ->assertHasErrors(['requirementOverride']);
})->group('regression');
