<?php

use App\Livewire\Homes\CallDetail;
use App\Models\CommunicationLog;
use App\Models\IntakeRequirement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Portal parity with the main app's intake-requirement controls: waive,
 * doesn't-apply, undo, the standing note, and full attachment handling.
 */
function reqActionsUser(array $permissionNames = ['communications_log.view', 'communications_log.edit']): User
{
    $role = Role::firstOrCreate(['name' => 'req_actions_test'], ['display_name' => 'Requirement Actions Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function reqActionsFixture(array $attrs = []): array
{
    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->create(array_merge([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'status' => IntakeRequirement::STATUS_PENDING,
    ], $attrs));

    return [$log, $req];
}

/** Attach a fake file, keeping the UploadedFile alive so its temp path survives. */
function attachFakeFile(IntakeRequirement $req, string $name)
{
    $file = UploadedFile::fake()->image($name);

    return $req->addMedia($file)->usingName($name)->usingFileName($name)->toMediaCollection('documents');
}

// ── Waiving ─────────────────────────────────────────────────────────

it('waives a requirement with a reason and counts it as met', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->set('reqActionReason', 'Homeowner is exempt — over 65 on fixed income')
        ->call('applyRequirementAction', 'waived')
        ->assertHasNoErrors()
        ->assertSet('actingOnRequirementId', null);

    $fresh = $req->fresh();
    expect($fresh->status)->toBe(IntakeRequirement::STATUS_WAIVED)
        ->and($fresh->isMet())->toBeTrue()
        ->and($fresh->notes)->toContain('[WAIVED] Homeowner is exempt');
});

it('refuses to waive without a reason', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->call('applyRequirementAction', 'waived')
        ->assertHasErrors('reqActionReason')
        // Modal stays open so she can supply one.
        ->assertSet('actingOnRequirementId', $req->id);

    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_PENDING);
});

it('marks a requirement not applicable with a reason', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->set('reqActionReason', 'No mortgage on this property')
        ->call('applyRequirementAction', 'na')
        ->assertHasNoErrors();

    $fresh = $req->fresh();
    expect($fresh->status)->toBe(IntakeRequirement::STATUS_NA)
        ->and($fresh->isMet())->toBeTrue()
        ->and($fresh->notes)->toContain('[N/A] No mortgage');
});

it('refuses to mark not applicable without a reason', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->call('applyRequirementAction', 'na')
        ->assertHasErrors('reqActionReason');

    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_PENDING);
});

// ── Undoing ─────────────────────────────────────────────────────────

it('undoes a requirement checked off by mistake', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture(['status' => IntakeRequirement::STATUS_RECEIVED]);
    $req->update(['received_at' => now(), 'received_by_user_id' => auth()->id()]);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->call('applyRequirementAction', 'reopen')
        ->assertHasNoErrors();

    $fresh = $req->fresh();
    expect($fresh->status)->toBe(IntakeRequirement::STATUS_PENDING)
        ->and($fresh->isMet())->toBeFalse()
        ->and($fresh->received_at)->toBeNull()
        ->and($fresh->notes)->toContain('[REOPENED');
});

it('undoes a waive back to outstanding', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->set('reqActionReason', 'Not needed')
        ->call('applyRequirementAction', 'waived')
        ->call('openRequirementActions', $req->id)
        ->call('applyRequirementAction', 'reopen');

    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_PENDING);
});

it('re-marks a reopened requirement received', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->call('applyRequirementAction', 'received')
        ->call('openRequirementActions', $req->id)
        ->call('applyRequirementAction', 'reopen')
        ->call('openRequirementActions', $req->id)
        ->call('applyRequirementAction', 'received');

    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_RECEIVED);
});

// ── Editing the note ────────────────────────────────────────────────

it('saves and then rewrites the standing note without touching status', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture(['status' => IntakeRequirement::STATUS_RECEIVED]);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->set('reqWorkerNote', 'Scanned copy is blurry, asked for a re-send')
        ->call('saveRequirementNote')
        ->assertHasNoErrors();

    expect($req->fresh()->worker_note)->toBe('Scanned copy is blurry, asked for a re-send')
        ->and($req->fresh()->status)->toBe(IntakeRequirement::STATUS_RECEIVED);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        // The editor opens pre-filled with what is already stored.
        ->assertSet('reqWorkerNote', 'Scanned copy is blurry, asked for a re-send')
        ->set('reqWorkerNote', 'Clean copy received')
        ->call('saveRequirementNote');

    expect($req->fresh()->worker_note)->toBe('Clean copy received');
});

it('clears the note when emptied', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture(['worker_note' => 'Old note']);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->set('reqWorkerNote', '   ')
        ->call('saveRequirementNote');

    expect($req->fresh()->worker_note)->toBeNull();
});

// ── Attachments ─────────────────────────────────────────────────────

it('lists an uploaded file with view and download links', function () {
    Storage::fake('private_media');
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture();

    attachFakeFile($req, 'photo-id.jpg');

    $media = $req->fresh()->getMedia('documents')->first();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->assertViewHas('documentRows', fn ($rows) => $rows->count() === 1)
        ->assertSee('Documents on file')
        ->assertSee('photo-id.jpg')
        ->assertSee(route('media.preview', $media->id))
        ->assertSee(route('media.download', $media->id));
});

it('lets a user with delete rights remove an attachment', function () {
    Storage::fake('private_media');
    $this->actingAs(reqActionsUser([
        'communications_log.view', 'communications_log.edit', 'communications_log.delete',
    ]));
    [$log, $req] = reqActionsFixture();

    attachFakeFile($req, 'wrong.jpg');
    $media = $req->fresh()->getMedia('documents')->first();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->assertViewHas('canRemoveFiles', true)
        ->call('deleteRequirementMedia', $req->id, $media->id);

    expect($req->fresh()->getMedia('documents'))->toHaveCount(0);
});

it('refuses to remove an attachment without delete rights', function () {
    Storage::fake('private_media');
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture();

    attachFakeFile($req, 'keep.jpg');
    $media = $req->fresh()->getMedia('documents')->first();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->assertViewHas('canRemoveFiles', false)
        ->call('deleteRequirementMedia', $req->id, $media->id)
        ->assertForbidden();

    expect($req->fresh()->getMedia('documents'))->toHaveCount(1);
});

it('refuses to remove an attachment belonging to another log', function () {
    Storage::fake('private_media');
    $this->actingAs(reqActionsUser([
        'communications_log.view', 'communications_log.edit', 'communications_log.delete',
    ]));
    [$log] = reqActionsFixture();
    [$otherLog, $otherReq] = reqActionsFixture();

    attachFakeFile($otherReq, 'theirs.jpg');
    $media = $otherReq->fresh()->getMedia('documents')->first();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('deleteRequirementMedia', $otherReq->id, $media->id)
        ->assertForbidden();

    expect($otherReq->fresh()->getMedia('documents'))->toHaveCount(1);
});

it('uploading from the actions modal closes it so modals do not stack', function () {
    Storage::fake('private_media');
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->call('openUpload', $req->id)
        ->assertSet('actingOnRequirementId', null)
        ->assertSet('uploadingRequirementId', $req->id);
});

it('still refuses uploads on a task-type requirement', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture(['type' => IntakeRequirement::TYPE_TASK]);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openUpload', $req->id)
        ->assertStatus(422);
});

// ── Gating ──────────────────────────────────────────────────────────

it('blocks the action modal without communications_log.edit', function () {
    $this->actingAs(reqActionsUser(['communications_log.view']));
    [$log, $req] = reqActionsFixture();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->assertForbidden();
});

it('blocks acting on a requirement belonging to another log', function () {
    $this->actingAs(reqActionsUser());
    [$log] = reqActionsFixture();
    [$otherLog, $otherReq] = reqActionsFixture();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $otherReq->id)
        ->assertForbidden();
});

it('rejects an unknown action', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openRequirementActions', $req->id)
        ->call('applyRequirementAction', 'explode')
        ->assertStatus(422);
});

// ── Nudge is hidden ─────────────────────────────────────────────────

it('does not offer a Nudge button on the checklist', function () {
    $this->actingAs(reqActionsUser());
    [$log, $req] = reqActionsFixture();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->assertSee('Received')
        ->assertDontSee('>Nudge<', false)
        ->assertDontSee('wire:click="nudge(', false);
});
