<?php

use App\Livewire\DuplicateResolutionManagement;
use App\Models\Household;
use App\Models\Participant;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Creates a user holding exactly the given permissions, under a non-admin role, so we
 * can verify both the happy path and the per-action permission gates on the unified
 * merge/duplicate/delete admin screen.
 */
function mergeToolUser(array $permissionNames): User
{
    $role = Role::firstOrCreate(['name' => 'merge_tool_test'], ['display_name' => 'Merge Tool Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user;
}

it('loads the page for a participant viewer (duplicates tab only)', function () {
    $this->actingAs(mergeToolUser(['participants.view']));

    Livewire::test(DuplicateResolutionManagement::class)
        ->assertOk()
        ->assertSet('activeTab', 'duplicates');
});

it('merges two records selected via the pickers', function () {
    Storage::fake('local');
    $this->actingAs(mergeToolUser(['participants.view', 'participants.merge']));

    $survivor = Participant::factory()->create();
    $loser = Participant::factory()->create();

    Livewire::test(DuplicateResolutionManagement::class)
        ->call('setTab', 'merge')
        ->call('selectMergeSurvivor', $survivor->id)
        ->call('selectMergeLoser', $loser->id)
        ->call('confirmMerge')
        ->assertHasNoErrors();

    expect(Participant::find($loser->id))->toBeNull();
    expect(Participant::find($survivor->id))->not->toBeNull();
});

it('blocks merge without participants.merge', function () {
    $this->actingAs(mergeToolUser(['participants.view']));

    Livewire::test(DuplicateResolutionManagement::class)
        ->call('confirmMerge')
        ->assertForbidden();
});

it('deletes a participant selected via the picker', function () {
    Storage::fake('local');
    $this->actingAs(mergeToolUser(['participants.view', 'participants.delete']));

    $participant = Participant::factory()->create();

    Livewire::test(DuplicateResolutionManagement::class)
        ->call('setTab', 'delete')
        ->call('selectDeleteParticipant', $participant->id)
        ->assertSet('deleteParticipantId', $participant->id)
        ->set('deleteConfirmText', 'DELETE')
        ->call('confirmDelete')
        ->assertHasNoErrors();

    expect(Participant::find($participant->id))->toBeNull();
});

it('blocks delete without participants.delete', function () {
    $this->actingAs(mergeToolUser(['participants.view']));

    Livewire::test(DuplicateResolutionManagement::class)
        ->set('deleteConfirmText', 'DELETE')
        ->call('confirmDelete')
        ->assertForbidden();
});

it('does not delete without the typed confirmation', function () {
    Storage::fake('local');
    $this->actingAs(mergeToolUser(['participants.view', 'participants.delete']));

    $participant = Participant::factory()->create();

    Livewire::test(DuplicateResolutionManagement::class)
        ->call('selectDeleteParticipant', $participant->id)
        ->set('deleteConfirmText', 'nope')
        ->call('confirmDelete');

    expect(Participant::find($participant->id))->not->toBeNull();
});

it('requires a household decision when deleting a head with co-members, then promotes', function () {
    Storage::fake('local');
    $this->actingAs(mergeToolUser(['participants.view', 'participants.delete']));

    $household = Household::factory()->create();
    $head = Participant::factory()->headOfHousehold()->create(['household_id' => $household->id]);
    $member = Participant::factory()->create(['household_id' => $household->id]);

    // Without a decision, the typed-confirm delete is refused (needs decision).
    Livewire::test(DuplicateResolutionManagement::class)
        ->call('selectDeleteParticipant', $head->id)
        ->assertSet('deleteNeedsDecision', true)
        ->set('deleteConfirmText', 'DELETE')
        ->call('confirmDelete'); // no decision chosen → flashes error, no delete

    expect(Participant::find($head->id))->not->toBeNull();

    // Choosing to promote a member completes the delete.
    Livewire::test(DuplicateResolutionManagement::class)
        ->call('selectDeleteParticipant', $head->id)
        ->set('deleteDecision', 'promote')
        ->set('promoteToHeadId', $member->id)
        ->set('deleteConfirmText', 'DELETE')
        ->call('confirmDelete')
        ->assertHasNoErrors();

    expect(Participant::find($head->id))->toBeNull();
    expect($member->fresh()->is_head_of_household)->toBeTrue();
});
