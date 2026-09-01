<?php

use App\Livewire\Homes\CallDetail;
use App\Livewire\Homes\CallsIntakes;
use App\Livewire\Homes\ProjectDetail;
use App\Livewire\Homes\Projects;
use App\Livewire\RecycleBin;
use App\Models\CommunicationLog;
use App\Models\HousingJob;
use App\Models\Permission;
use App\Models\ProjectMilestone;
use App\Models\Role;
use App\Models\User;
use App\Services\Homes\HousingRecordDeleter;
use Livewire\Livewire;

function deleteTestUser(array $permissionNames): User
{
    $role = Role::firstOrCreate(['name' => 'homes_delete_test'], ['display_name' => 'Homes Delete Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

/** The PM: can view and delete both kinds, but cannot reach the recycle bin. */
function pmUser(): User
{
    return deleteTestUser([
        'communications_log.view', 'communications_log.delete',
        'projects.view', 'projects.delete',
    ]);
}

function recycleAdmin(): User
{
    $role = Role::firstOrCreate(['name' => 'homes_recycle_admin'], ['display_name' => 'Homes Recycle Admin']);
    $ids = collect(['recycle_bin.manage'])->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

// ── Deleting a call ─────────────────────────────────────────────────

it('soft-deletes a call from the portal and keeps the row restorable', function () {
    $this->actingAs($user = pmUser());
    $log = CommunicationLog::factory()->create(['contact_first_name' => 'Doomed', 'contact_last_name' => 'Caller']);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('confirmDelete')
        ->assertSet('showDelete', true)
        ->call('deleteCall')
        ->assertRedirect(route('homes.calls'));

    expect(CommunicationLog::find($log->id))->toBeNull();

    $trashed = CommunicationLog::withTrashed()->find($log->id);
    expect($trashed)->not->toBeNull()
        ->and($trashed->trashed())->toBeTrue()
        ->and($trashed->deleted_by)->toBe($user->id);
});

it('drops a deleted call out of the Calls list', function () {
    $this->actingAs(pmUser());
    $gone = CommunicationLog::factory()->create(['contact_first_name' => 'Gone', 'contact_last_name' => 'Caller']);
    CommunicationLog::factory()->create(['contact_first_name' => 'Still', 'contact_last_name' => 'Here']);
    $gone->delete();

    Livewire::test(CallsIntakes::class)
        ->assertSee('Still Here')
        ->assertDontSee('Gone Caller');
});

it('lets Cancel back out of the delete confirmation without deleting', function () {
    $this->actingAs(pmUser());
    $log = CommunicationLog::factory()->create();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('confirmDelete')
        ->call('cancelDelete')
        ->assertSet('showDelete', false);

    expect(CommunicationLog::find($log->id))->not->toBeNull();
});

it('requires communications_log.delete to delete a call', function () {
    $this->actingAs(deleteTestUser(['communications_log.view']));
    $log = CommunicationLog::factory()->create();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('confirmDelete')
        ->assertForbidden();

    expect(CommunicationLog::find($log->id))->not->toBeNull();
});

// ── The project-first ordering rule ─────────────────────────────────

it('blocks deleting a call while its project is still live', function () {
    $this->actingAs(pmUser());

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $log = CommunicationLog::factory()->create(['converted_to_job_id' => $job->id]);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('confirmDelete')
        ->assertSet('showDelete', false)
        // The banner tells her what has to happen first, in her words.
        ->assertSee('Delete that project first');

    expect(CommunicationLog::find($log->id))->not->toBeNull();
});

it('allows deleting the call once its project has been deleted', function () {
    $this->actingAs(pmUser());

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $log = CommunicationLog::factory()->create(['converted_to_job_id' => $job->id]);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('confirmDelete')
        ->call('deleteProject')
        ->assertRedirect(route('homes.projects'));

    Livewire::test(CallDetail::class, ['log' => $log->fresh()])
        ->call('confirmDelete')
        ->assertSet('showDelete', true)
        ->call('deleteCall')
        ->assertRedirect(route('homes.calls'));

    expect(CommunicationLog::withTrashed()->find($log->id)->trashed())->toBeTrue();
});

it('refuses the delete in the service layer too, not just the UI', function () {
    $this->actingAs(pmUser());

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $log = CommunicationLog::factory()->create(['housing_job_id' => $job->id]);
    $deleter = app(HousingRecordDeleter::class);

    expect($deleter->callCanBeDeleted($log))->toBeFalse();
    expect(fn () => $deleter->deleteCall($log))->toThrow(RuntimeException::class);
});

// ── Deleting a project ──────────────────────────────────────────────

it('soft-deletes a project and drops it out of the Projects list', function () {
    $this->actingAs($user = pmUser());

    $job = HousingJob::factory()->create(['status' => 'in_progress', 'job_title' => 'Doomed roof job']);
    HousingJob::factory()->create(['status' => 'in_progress', 'job_title' => 'Surviving siding job']);
    ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'status' => 'in_progress']);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('deleteProject')
        ->assertRedirect(route('homes.projects'));

    Livewire::test(Projects::class)
        ->set('pill', 'all')
        ->assertSee('Surviving siding job')
        ->assertDontSee('Doomed roof job');

    $trashed = HousingJob::withTrashed()->find($job->id);
    expect($trashed->trashed())->toBeTrue()
        ->and($trashed->deleted_by)->toBe($user->id);

    // The milestone is untouched, so a restore brings the whole project back.
    expect(ProjectMilestone::where('housing_job_id', $job->id)->count())->toBe(1);
});

it('requires projects.delete to delete a project', function () {
    $this->actingAs(deleteTestUser(['projects.view']));
    $job = HousingJob::factory()->create(['status' => 'in_progress']);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('confirmDelete')
        ->assertForbidden();

    expect(HousingJob::find($job->id))->not->toBeNull();
});

// ── Admin recycle bin ───────────────────────────────────────────────

it('gates the recycle bin on recycle_bin.manage', function () {
    $this->actingAs(pmUser());
    $this->get(route('admin.recycle-bin'))->assertForbidden();

    $this->actingAs(recycleAdmin());
    $this->get(route('admin.recycle-bin'))->assertOk();
});

it('lists deleted calls and projects with who deleted them', function () {
    $pm = pmUser();
    $this->actingAs($pm);

    $log = CommunicationLog::factory()->create(['contact_first_name' => 'Deleted', 'contact_last_name' => 'Caller']);
    $job = HousingJob::factory()->create(['status' => 'in_progress', 'job_title' => 'Deleted roof job']);
    $log->delete();
    $job->delete();

    $this->actingAs(recycleAdmin());

    Livewire::test(RecycleBin::class)
        ->assertViewHas('callCount', 1)
        ->assertViewHas('projectCount', 1)
        ->assertSee('Deleted Caller')
        ->assertSee($pm->name)
        ->call('setTab', 'projects')
        ->assertSee('Deleted roof job');
});

it('restores a deleted call back into the portal', function () {
    $this->actingAs(pmUser());
    $log = CommunicationLog::factory()->create(['contact_first_name' => 'Oops', 'contact_last_name' => 'Caller']);
    $log->delete();

    $this->actingAs(recycleAdmin());
    Livewire::test(RecycleBin::class)->call('restoreCall', $log->id);

    $restored = CommunicationLog::find($log->id);
    expect($restored)->not->toBeNull()
        ->and($restored->deleted_at)->toBeNull()
        ->and($restored->deleted_by)->toBeNull();

    $this->actingAs(pmUser());
    Livewire::test(CallsIntakes::class)->assertSee('Oops Caller');
});

it('restores a deleted project with its milestones intact', function () {
    $this->actingAs(pmUser());
    $job = HousingJob::factory()->create(['status' => 'in_progress', 'job_title' => 'Oops roof job']);
    ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'status' => 'in_progress']);
    $job->delete();

    $this->actingAs(recycleAdmin());
    Livewire::test(RecycleBin::class)->call('setTab', 'projects')->call('restoreProject', $job->id);

    $restored = HousingJob::find($job->id);
    expect($restored)->not->toBeNull()
        ->and($restored->deleted_by)->toBeNull()
        ->and($restored->milestones()->count())->toBe(1);
});

it('warns that restoring a call alone leaves its deleted project behind', function () {
    $this->actingAs(pmUser());

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $log = CommunicationLog::factory()->create(['converted_to_job_id' => $job->id]);
    $job->delete();
    $log->delete();

    $this->actingAs(recycleAdmin());

    Livewire::test(RecycleBin::class)
        ->assertSee($job->job_number.' is also deleted', false);
});

it('does not let a non-admin restore through the component', function () {
    $this->actingAs(pmUser());
    $log = CommunicationLog::factory()->create();
    $log->delete();

    Livewire::test(RecycleBin::class)->assertForbidden();

    expect(CommunicationLog::withTrashed()->find($log->id)->trashed())->toBeTrue();
});
