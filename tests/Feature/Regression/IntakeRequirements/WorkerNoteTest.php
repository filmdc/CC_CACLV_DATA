<?php

/*
|----------------------------------------------------------------------
| Worker notes — free-form, editable per-requirement annotations.
|
| Covers both requirement systems:
|   - IntakeRequirement (Phase 1, Comm Log) via CommunicationsLogManagement
|   - Requirement (Phase 2/3, Housing Job) via ProjectManagement
|
| worker_note is distinct from the audit-trail `notes` / `completion_note`
| columns: editable in any status, replaced (not appended) on save, and
| surfaced under each item in the requirements panel.
|----------------------------------------------------------------------
*/

use App\Livewire\CommunicationsLogManagement;
use App\Livewire\ProjectManagement;
use App\Models\CommunicationLog;
use App\Models\IntakeRequirement;
use App\Models\Requirement;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->actor = RegressionHelpers::adminUser();
    $this->actingAs($this->actor);
});

// ---- Model-level guards (fillable + migration) ----------------------

it('persists worker_note on an intake requirement independently of notes and status', function () {
    $req = IntakeRequirement::factory()->create([
        'status' => IntakeRequirement::STATUS_PENDING,
        'notes' => null,
    ]);

    $req->worker_note = 'only need paystub covering 4/1-4/13, all others in file';
    $req->save();

    $fresh = $req->fresh();
    expect($fresh->worker_note)->toBe('only need paystub covering 4/1-4/13, all others in file')
        ->and($fresh->status)->toBe(IntakeRequirement::STATUS_PENDING)
        ->and($fresh->notes)->toBeNull();
})->group('regression');

it('persists worker_note on a project requirement independently of completion_note and status', function () {
    $req = Requirement::factory()->create([
        'status' => Requirement::STATUS_OUTSTANDING,
        'completion_note' => null,
    ]);

    $req->worker_note = 'partial scope received; awaiting final invoice';
    $req->save();

    $fresh = $req->fresh();
    expect($fresh->worker_note)->toBe('partial scope received; awaiting final invoice')
        ->and($fresh->status)->toBe(Requirement::STATUS_OUTSTANDING)
        ->and($fresh->completion_note)->toBeNull();
})->group('regression');

// ---- Intake: CommunicationsLogManagement::saveRequirementNote -------

it('saveRequirementNote persists a trimmed note without changing status', function () {
    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'status' => IntakeRequirement::STATUS_PENDING,
    ]);

    Livewire::test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('openRequirementActionModal', $req->id)
        ->set('reqWorkerNote', '  needs 2024 W-2 only  ')
        ->call('saveRequirementNote')
        ->assertHasNoErrors();

    $fresh = $req->fresh();
    expect($fresh->worker_note)->toBe('needs 2024 W-2 only')
        ->and($fresh->status)->toBe(IntakeRequirement::STATUS_PENDING);
})->group('regression');

it('saveRequirementNote stores null for a blank note', function () {
    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'worker_note' => 'prior note',
    ]);

    Livewire::test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('openRequirementActionModal', $req->id)
        ->set('reqWorkerNote', '   ')
        ->call('saveRequirementNote');

    expect($req->fresh()->worker_note)->toBeNull();
})->group('regression');

it('saveRequirementNote refuses a requirement from a different log', function () {
    $log = CommunicationLog::factory()->create();
    $otherLog = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->create([
        'communication_log_id' => $otherLog->id,
    ]);

    Livewire::test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->set('actingOnRequirementId', $req->id)
        ->set('reqWorkerNote', 'should not save')
        ->call('saveRequirementNote')
        ->assertForbidden();

    expect($req->fresh()->worker_note)->toBeNull();
})->group('regression');

// ---- Project: ProjectManagement::saveRequirement threads worker_note ---

it('saveRequirement persists worker_note on edit', function () {
    $req = Requirement::factory()->create([
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);

    Livewire::test(ProjectManagement::class, ['id' => $req->housing_job_id])
        ->call('showEditRequirement', $req->id)
        ->set('requirement_worker_note', 'partial docs in; need lien waiver')
        ->call('saveRequirement')
        ->assertHasNoErrors();

    expect($req->fresh()->worker_note)->toBe('partial docs in; need lien waiver');
})->group('regression');

it('openRequirementNote + saveRequirementNote annotates a fund-code-sourced requirement', function () {
    // Fund-code requirements hide the full Edit form in the row UI; the
    // note-only editor must still let them be annotated.
    $req = Requirement::factory()->create([
        'source_type' => 'fund_code',
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);

    Livewire::test(ProjectManagement::class, ['id' => $req->housing_job_id])
        ->call('openRequirementNote', $req->id)
        ->set('requirement_note_text', '  fund-code req: partial docs in  ')
        ->call('saveRequirementNote')
        ->assertHasNoErrors();

    $fresh = $req->fresh();
    expect($fresh->worker_note)->toBe('fund-code req: partial docs in')
        ->and($fresh->status)->toBe(Requirement::STATUS_OUTSTANDING);
})->group('regression');

it('saveRequirementNote refuses a requirement from a different project', function () {
    $req = Requirement::factory()->create();
    $otherReq = Requirement::factory()->create();

    Livewire::test(ProjectManagement::class, ['id' => $req->housing_job_id])
        ->set('notingRequirementId', $otherReq->id)
        ->set('requirement_note_text', 'should not save')
        ->call('saveRequirementNote')
        ->assertForbidden();

    expect($otherReq->fresh()->worker_note)->toBeNull();
})->group('regression');
