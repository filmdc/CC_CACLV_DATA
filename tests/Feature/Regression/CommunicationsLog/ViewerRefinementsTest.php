<?php

/*
| Phase 12 viewer refinements:
|   - Linked Records card is omitted when nothing is linked.
|   - The bottom-bar "Convert to Project" button is gated by the Phase 1
|     intake-requirements check (visually disabled until ready).
|   - The Focus 3 panel's "+ ad-hoc requirement" + per-row action buttons
|     are wired to real component methods (not placeholders).
*/

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\IntakeRequirement;
use App\Models\Participant;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('viewer hides the Linked Records card when nothing is linked', function () {
    $log = CommunicationLog::factory()->create([
        'participant_id' => null,
        'property_id' => null,
        'housing_job_id' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        // The empty-state copy and the heading itself should both be absent
        // from the Focus 1 panel when no records are attached.
        ->assertDontSee('No linked records')
        ->assertDontSee('Click to unlink a record');
})->group('regression');

it('viewer shows the Linked Records card when at least one record is linked', function () {
    $participant = Participant::factory()->create();
    $log = CommunicationLog::factory()->create([
        'participant_id' => $participant->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->assertSee($participant->full_name);
})->group('regression');

it('Convert-to-Project button is enabled when no Phase 1 requirements are outstanding', function () {
    $log = CommunicationLog::factory()->create(['housing_job_id' => null]);

    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id);

    expect($component->instance()->intakeRequirementsGate($log->fresh())['ready'])->toBeTrue();
    $component->assertSee('Convert to Project');
})->group('regression');

it('Convert-to-Project button is disabled visually when a Phase 1 requirement is unmet', function () {
    $log = CommunicationLog::factory()->create(['housing_job_id' => null]);

    // Create an ad-hoc requirement (defaults to eligibility category via the
    // null-definition fallback) that is still pending.
    IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'ID copy',
        'key' => 'id-copy-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id);

    $gate = $component->instance()->intakeRequirementsGate($log->fresh());
    expect($gate['ready'])->toBeFalse()
        ->and($gate['unmet'])->toBe(1);

    $component->assertSee('cursor-not-allowed')
        ->assertSee('0/1 requirements met');
})->group('regression');

it('openAdHocRequirementForm via add-ad-hoc-requirement event opens the inline form', function () {
    $log = CommunicationLog::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->assertSet('showAdHocReqForm', false)
        ->dispatch('add-ad-hoc-requirement')
        ->assertSet('showAdHocReqForm', true);
})->group('regression');

it('saveAdHocRequirement creates an ad-hoc IntakeRequirement on the viewing log', function () {
    $log = CommunicationLog::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('openAdHocRequirementForm')
        ->set('adHocReqLabel', 'Hardship affidavit')
        ->set('adHocReqType', IntakeRequirement::TYPE_DOCUMENT)
        ->call('saveAdHocRequirement')
        ->assertSet('showAdHocReqForm', false);

    $req = IntakeRequirement::where('communication_log_id', $log->id)->firstOrFail();
    expect($req->label)->toBe('Hardship affidavit')
        ->and($req->source_type)->toBe(IntakeRequirement::SOURCE_AD_HOC)
        ->and($req->status)->toBe(IntakeRequirement::STATUS_PENDING);
})->group('regression');

it('applyRequirementAction("received") flips status to received and closes the modal', function () {
    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Pay stubs',
        'key' => 'pay-stubs-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->dispatch('act-on-requirement', id: $req->id)
        ->assertSet('actingOnRequirementId', $req->id)
        ->call('applyRequirementAction', 'received')
        ->assertSet('actingOnRequirementId', null);

    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_RECEIVED)
        ->and($req->fresh()->received_at)->not->toBeNull();
})->group('regression');

it('applyRequirementAction("waived") requires a reason and appends [WAIVED] note', function () {
    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Misc form',
        'key' => 'misc-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('openRequirementActionModal', $req->id);

    // Without reason → error, modal stays open.
    $component->set('reqActionReason', '')
        ->call('applyRequirementAction', 'waived')
        ->assertHasErrors('reqActionReason')
        ->assertSet('actingOnRequirementId', $req->id);

    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_PENDING);

    // With reason → waived.
    $component->set('reqActionReason', 'Already on file from 2024 case')
        ->call('applyRequirementAction', 'waived')
        ->assertSet('actingOnRequirementId', null);

    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_WAIVED)
        ->and($req->fresh()->notes)->toContain('[WAIVED] Already on file from 2024 case');
})->group('regression');

it('uploadRequirementFiles attaches the file to the requirement media collection and marks received', function () {
    \Illuminate\Support\Facades\Storage::fake('private_media');

    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Photo ID',
        'key' => 'photo-id-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    // Use a .txt file: text/plain is in DOCUMENT_MIME_TYPES, and fake()->create()
    // produces empty content which finfo detects reliably as text/plain.
    $file = \Illuminate\Http\Testing\File::createWithContent('proof-of-id.txt', "scanned id contents\n");

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('openRequirementActionModal', $req->id)
        ->set('requirementFiles', [$file])
        ->call('uploadRequirementFiles')
        ->assertSet('actingOnRequirementId', null);

    $fresh = $req->fresh();
    expect($fresh->status)->toBe(IntakeRequirement::STATUS_RECEIVED)
        ->and($fresh->getMedia('documents')->count())->toBe(1)
        ->and($fresh->getMedia('documents')->first()->name)->toBe('proof-of-id.txt');
})->group('regression');

it('uploadRequirementFiles refuses task-type requirements (422)', function () {
    \Illuminate\Support\Facades\Storage::fake('private_media');

    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_TASK,
        'label' => 'Phone screen',
        'key' => 'phone-'.uniqid(),
        'kind' => 'verify',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    $file = \Illuminate\Http\Testing\File::createWithContent('whatever.txt', "content\n");

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('openRequirementActionModal', $req->id)
        ->set('requirementFiles', [$file])
        ->call('uploadRequirementFiles')
        ->assertStatus(422);

    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_PENDING);
})->group('regression');

it('applyRequirementAction rejects cross-log requirement IDs', function () {
    $logA = CommunicationLog::factory()->create();
    $logB = CommunicationLog::factory()->create();
    $reqB = IntakeRequirement::create([
        'communication_log_id' => $logB->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Other-log req',
        'key' => 'other-'.uniqid(),
        'kind' => 'doc',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $logA->id)
        ->set('actingOnRequirementId', $reqB->id)
        ->call('applyRequirementAction', 'received')
        ->assertForbidden();

    expect($reqB->fresh()->status)->toBe(IntakeRequirement::STATUS_PENDING);
})->group('regression');
