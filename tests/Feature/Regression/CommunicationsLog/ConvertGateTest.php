<?php

/*
| Phase 7: Convert-to-Project requirements gate.
|
| Asserts the gate blocks conversion when required intake requirements
| are unmet, lets the user override with an audit-logged reason, and
| records that reason on the housing_jobs row via the instantiator's
| carryForwardToProject helper.
*/

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\HousingJob;
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

it('blocks Convert-to-Project when required requirements are unmet and override is not ticked', function () {
    $log = CommunicationLog::where('subject', 'like', 'CL-1:%')->firstOrFail();
    $program = Program::where('name', 'Fixture Housing Program')->firstOrFail();

    // Stamp an unmet required requirement onto the log.
    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id', 'default_required' => true]);
    $this->service->forDefinition($log, $atom, $this->user);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('openConvertModal', $log->id)
        ->set('convertJobTitle', 'Should be blocked')
        ->set('convertProgramId', $program->id)
        ->call('convertToProject')
        ->assertHasErrors(['requirementOverride']);

    expect($log->fresh()->converted_to_job_id)->toBeNull();
})->group('regression');

it('blocks override without a reason', function () {
    $log = CommunicationLog::where('subject', 'like', 'CL-1:%')->firstOrFail();
    $program = Program::where('name', 'Fixture Housing Program')->firstOrFail();

    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id', 'default_required' => true]);
    $this->service->forDefinition($log, $atom, $this->user);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('openConvertModal', $log->id)
        ->set('convertJobTitle', 'Override no reason')
        ->set('convertProgramId', $program->id)
        ->set('requirementOverride', true)
        ->set('requirementOverrideReason', '   ')
        ->call('convertToProject')
        ->assertHasErrors(['requirementOverrideReason']);
})->group('regression');

it('allows conversion with override + reason and records the audit on housing_jobs', function () {
    $log = CommunicationLog::where('subject', 'like', 'CL-1:%')->firstOrFail();
    $program = Program::where('name', 'Fixture Housing Program')->firstOrFail();

    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id', 'default_required' => true]);
    $this->service->forDefinition($log, $atom, $this->user);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('openConvertModal', $log->id)
        ->set('convertJobTitle', 'Promoted with override')
        ->set('convertProgramId', $program->id)
        ->set('requirementOverride', true)
        ->set('requirementOverrideReason', 'Client returning next week with paperwork; promoting now so the case stays in the queue.')
        ->call('convertToProject')
        ->assertHasNoErrors();

    $job = HousingJob::find($log->fresh()->converted_to_job_id);
    expect($job)->not->toBeNull();
    expect($job->requirement_override_reason)->toContain('Client returning');
    expect($job->requirement_override_by_user_id)->toBe($this->user->id);
    expect($job->requirement_overridden_at)->not->toBeNull();
})->group('regression');

it('allows conversion with no requirements (clean intake) and no override audit', function () {
    $log = CommunicationLog::where('subject', 'like', 'CL-1:%')->firstOrFail();
    $program = Program::where('name', 'Fixture Housing Program')->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('openConvertModal', $log->id)
        ->set('convertJobTitle', 'Clean convert')
        ->set('convertProgramId', $program->id)
        ->call('convertToProject')
        ->assertHasNoErrors();

    $job = HousingJob::find($log->fresh()->converted_to_job_id);
    expect($job)->not->toBeNull();
    expect($job->requirement_override_reason)->toBeNull();
})->group('regression');

it('allows conversion when all required requirements are met (no override needed)', function () {
    $log = CommunicationLog::where('subject', 'like', 'CL-1:%')->firstOrFail();
    $program = Program::where('name', 'Fixture Housing Program')->firstOrFail();

    $atom = RequirementDefinition::factory()->create(['key' => 'photo-id', 'default_required' => true]);
    $req = $this->service->forDefinition($log, $atom, $this->user);
    $req->markReceived($this->user);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('openConvertModal', $log->id)
        ->set('convertJobTitle', 'Clean met')
        ->set('convertProgramId', $program->id)
        ->call('convertToProject')
        ->assertHasNoErrors();

    expect($log->fresh()->converted_to_job_id)->not->toBeNull();
})->group('regression');
