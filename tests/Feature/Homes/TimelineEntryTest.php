<?php

use App\Livewire\Homes\CallDetail;
use App\Models\CommunicationLog;
use App\Models\CommunicationLogUpdate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

/**
 * Correcting and removing entries on the "Every touch" timeline.
 *
 * Two rules carry the whole feature: what a person typed is hers to fix or
 * remove, and what the SYSTEM wrote about the record is never reworded —
 * only an admin can take one off the timeline. Nothing is ever destroyed.
 */
function timelineUser(array $permissionNames = [], bool $admin = false): User
{
    $roleName = $admin ? 'admin' : 'timeline_test';
    $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function timelinePm(): User
{
    return timelineUser(['communications_log.view', 'communications_log.edit']);
}

it('corrects an entry without changing who wrote it or when', function () {
    $pm = timelinePm();
    $this->actingAs($pm);

    $author = User::factory()->create();
    $log = CommunicationLog::factory()->create();
    $entry = $log->addUpdate('Left a voicmail for the roofer.', 'note', $author->id);
    $writtenOn = $entry->created_at;

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openEditEntry', $entry->id)
        ->assertSet('entryContent', 'Left a voicmail for the roofer.')
        ->set('entryContent', 'Left a voicemail for the roofer.')
        ->set('entryType', 'follow_up')
        ->set('entryDate', '2026-10-01')
        ->call('saveEntry')
        ->assertHasNoErrors()
        ->assertSet('editingEntryId', null);

    $fresh = $entry->fresh();
    expect($fresh->update_content)->toBe('Left a voicemail for the roofer.');
    expect($fresh->update_type)->toBe('follow_up');
    expect($fresh->update_date->format('Y-m-d'))->toBe('2026-10-01');
    // Authorship and position in the chronology are untouched.
    expect($fresh->created_by)->toBe($author->id);
    expect($fresh->created_at->eq($writtenOn))->toBeTrue();
    // ...and the correction is on the record.
    expect($fresh->edited_by)->toBe($pm->id);
    expect($fresh->edited_at)->not->toBeNull();
});

it('requires content and a real kind on a correction', function () {
    $this->actingAs(timelinePm());

    $log = CommunicationLog::factory()->create();
    $entry = $log->addUpdate('Something', 'note');

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openEditEntry', $entry->id)
        ->set('entryContent', '   ')
        ->call('saveEntry')
        ->assertHasErrors('entryContent');

    // 'status_change' is system-only and is not offered as a kind.
    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openEditEntry', $entry->id)
        ->set('entryType', 'status_change')
        ->call('saveEntry')
        ->assertHasErrors('entryType');

    expect($entry->fresh()->update_content)->toBe('Something');
});

it('never lets a system entry be reworded, by the PM or an admin', function () {
    $log = CommunicationLog::factory()->create();
    $entry = $log->addUpdate('Converted to Project: JOB-2026-00001', 'status_change');

    foreach ([timelinePm(), timelineUser(['communications_log.view', 'communications_log.edit'], admin: true)] as $user) {
        $this->actingAs($user);

        Livewire::test(CallDetail::class, ['log' => $log])
            ->call('openEditEntry', $entry->id)
            ->assertSet('editingEntryId', null);
    }

    expect($entry->fresh()->update_content)->toBe('Converted to Project: JOB-2026-00001');
});

it('removes an entry recoverably and puts it back', function () {
    $pm = timelinePm();
    $this->actingAs($pm);

    $log = CommunicationLog::factory()->create();
    $entry = $log->addUpdate('Note filed on the wrong household.', 'note');

    $component = Livewire::test(CallDetail::class, ['log' => $log])
        ->assertSee('Note filed on the wrong household.')
        ->call('confirmRemoveEntry', $entry->id)
        ->assertSet('removingEntryId', $entry->id)
        ->call('removeEntry')
        ->assertSet('removingEntryId', null);

    // Gone from the timeline and from every count taken off the relation...
    expect($log->updates()->count())->toBe(0);
    // ...but not destroyed, and stamped with who removed it.
    $trashed = CommunicationLogUpdate::withTrashed()->find($entry->id);
    expect($trashed)->not->toBeNull();
    expect($trashed->trashed())->toBeTrue();
    expect($trashed->deleted_by)->toBe($pm->id);

    $component->call('toggleRemovedEntries')
        ->assertSee('Note filed on the wrong household.')
        ->call('restoreEntry', $entry->id);

    expect($log->updates()->count())->toBe(1);
    expect(CommunicationLogUpdate::find($entry->id)->deleted_by)->toBeNull();
});

it('lets only an admin remove an entry the system wrote', function () {
    $log = CommunicationLog::factory()->create();
    $entry = $log->addUpdate('Photo ID marked received', 'status_change');

    $this->actingAs(timelinePm());
    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('confirmRemoveEntry', $entry->id)
        ->assertSet('removingEntryId', null);
    expect($log->updates()->count())->toBe(1);

    $this->actingAs(timelineUser(['communications_log.view', 'communications_log.edit'], admin: true));
    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('confirmRemoveEntry', $entry->id)
        ->assertSet('removingEntryId', $entry->id)
        ->call('removeEntry');

    expect($log->updates()->count())->toBe(0);
});

it('refuses an entry that belongs to another call', function () {
    $this->actingAs(timelinePm());

    $log = CommunicationLog::factory()->create();
    $otherEntry = CommunicationLog::factory()->create()->addUpdate('Not this record.', 'note');

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openEditEntry', $otherEntry->id)
        ->assertForbidden();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('confirmRemoveEntry', $otherEntry->id)
        ->assertForbidden();
});

it('refuses editing and removing without communications_log.edit', function () {
    $this->actingAs(timelineUser(['communications_log.view']));

    $log = CommunicationLog::factory()->create();
    $entry = $log->addUpdate('Read-only user.', 'note');

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openEditEntry', $entry->id)
        ->assertForbidden();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('confirmRemoveEntry', $entry->id)
        ->assertForbidden();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('restoreEntry', $entry->id)
        ->assertForbidden();
});

it('keeps a removed entry out of the merge preview count', function () {
    $this->actingAs(timelinePm());

    $log = CommunicationLog::factory()->create();
    $log->addUpdate('Kept.', 'note');
    $removed = $log->addUpdate('Removed.', 'note');
    $removed->delete();

    expect($log->updates()->count())->toBe(1);
});
