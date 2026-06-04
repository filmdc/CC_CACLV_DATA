<?php

use App\Livewire\ParticipantManagement;
use App\Models\Participant;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Creates a user holding exactly the given participant permissions, so we can verify
 * both the happy path and the permission gate on the merge/delete UI. The 'admin'
 * role bypasses the per-record program-access checks in ParticipantPolicy.
 */
function participantUser(array $permissionNames, string $roleName = 'admin'): User
{
    $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

    $ids = collect($permissionNames)->map(function ($name) {
        return Permission::firstOrCreate(['name' => $name], ['display_name' => $name])->id;
    });
    $role->permissions()->syncWithoutDetaching($ids->all());

    $user = User::factory()->create();
    $user->roles()->attach($role->id);

    return $user;
}

it('merges two selected participants through the component', function () {
    $user = participantUser(['participants.view', 'participants.merge']);

    $survivor = Participant::factory()->create();
    $loser = Participant::factory()->create();

    $this->actingAs($user);

    Livewire::test(ParticipantManagement::class)
        ->set('selectedIds', [(string) $survivor->id, (string) $loser->id])
        ->call('openMergeModal')
        ->assertSet('showMergeModal', true)
        ->call('confirmMerge')
        ->assertHasNoErrors();

    expect(Participant::find($loser->id))->toBeNull();
    expect(Participant::find($survivor->id))->not->toBeNull();
});

it('blocks merge without the participants.merge permission', function () {
    $user = participantUser(['participants.view'], 'plain_user_test');
    $this->actingAs($user);

    Livewire::test(ParticipantManagement::class)
        ->set('selectedIds', [1, 2])
        ->call('openMergeModal')
        ->assertForbidden();
});

it('deletes a participant through the component with typed confirmation', function () {
    Storage::fake('local');
    $user = participantUser(['participants.view', 'participants.delete']);

    $participant = Participant::factory()->create();
    $this->actingAs($user);

    Livewire::test(ParticipantManagement::class)
        ->call('startDelete', $participant->id)
        ->assertSet('showDeleteModal', true)
        ->set('deleteConfirmText', 'DELETE')
        ->call('confirmDelete')
        ->assertHasNoErrors();

    expect(Participant::find($participant->id))->toBeNull();
});

it('does not delete without the typed confirmation', function () {
    $user = participantUser(['participants.view', 'participants.delete']);

    $participant = Participant::factory()->create();
    $this->actingAs($user);

    Livewire::test(ParticipantManagement::class)
        ->call('startDelete', $participant->id)
        ->set('deleteConfirmText', 'nope')
        ->call('confirmDelete');

    expect(Participant::find($participant->id))->not->toBeNull();
});
