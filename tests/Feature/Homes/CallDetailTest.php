<?php

use App\Livewire\Homes\CallDetail;
use App\Models\CommunicationLog;
use App\Models\IntakeRequirement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

function callDetailUser(array $permissionNames = []): User
{
    $role = Role::firstOrCreate(['name' => 'call_detail_test'], ['display_name' => 'Call Detail Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

it('gates the page on communications_log.view', function () {
    $log = CommunicationLog::factory()->create();

    $this->actingAs(callDetailUser([]));
    $this->get(route('homes.call', $log))->assertForbidden();

    $this->actingAs(callDetailUser(['communications_log.view']));
    $this->get(route('homes.call', $log))->assertOk();
});

it('renders the timeline with the root call and its updates', function () {
    $this->actingAs(callDetailUser(['communications_log.view']));

    $log = CommunicationLog::factory()->create(['summary' => 'Roof leaking over the kitchen.']);
    $log->addUpdate('Sent the application packet.', 'note');

    Livewire::test(CallDetail::class, ['log' => $log])
        ->assertSee('Roof leaking over the kitchen.')
        ->assertSee('Sent the application packet.')
        ->assertSee('Every touch');
});

it('marks a requirement received status-only (no file needed)', function () {
    $user = callDetailUser(['communications_log.view', 'communications_log.edit']);
    $this->actingAs($user);

    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'label' => 'Photo ID',
        'status' => IntakeRequirement::STATUS_PENDING,
    ]);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('markReceived', $req->id)
        ->assertHasNoErrors();

    $fresh = $req->fresh();
    expect($fresh->status)->toBe(IntakeRequirement::STATUS_RECEIVED);
    expect($fresh->received_by_user_id)->toBe($user->id);
    // The transition itself writes a status_change timeline entry.
    expect($log->updates()->where('update_type', 'status_change')->exists())->toBeTrue();
});

it('first nudge marks the requirement requested; a repeat nudge writes a follow_up update', function () {
    $user = callDetailUser(['communications_log.view', 'communications_log.edit']);
    $this->actingAs($user);

    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'label' => 'Pay stubs',
        'status' => IntakeRequirement::STATUS_PENDING,
    ]);

    $component = Livewire::test(CallDetail::class, ['log' => $log]);

    $component->call('nudge', $req->id);
    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_REQUESTED);

    $component->call('nudge', $req->id);
    $followUp = $log->updates()->where('update_type', 'follow_up')->first();
    expect($followUp)->not->toBeNull();
    expect($followUp->update_content)->toContain('Pay stubs');
    // Status unchanged by the repeat nudge.
    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_REQUESTED);
});

it('blocks requirement actions on a requirement belonging to another log', function () {
    $this->actingAs(callDetailUser(['communications_log.view', 'communications_log.edit']));

    $log = CommunicationLog::factory()->create();
    $otherReq = IntakeRequirement::factory()->create(); // different log

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('markReceived', $otherReq->id)
        ->assertForbidden();
});

it('blocks requirement actions without communications_log.edit', function () {
    $this->actingAs(callDetailUser(['communications_log.view']));

    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->create(['communication_log_id' => $log->id]);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('markReceived', $req->id)
        ->assertForbidden();
});

it('edits and completes the follow-up', function () {
    $user = callDetailUser(['communications_log.view', 'communications_log.edit']);
    $this->actingAs($user);

    $log = CommunicationLog::factory()->create(['follow_up_date' => null]);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('startEditFollowUp')
        ->set('followUpAction', 'Call back about the roof')
        ->set('followUpDate', now()->addDays(3)->toDateString())
        ->call('saveFollowUp')
        ->assertHasNoErrors()
        ->call('completeFollowUp');

    $fresh = $log->fresh();
    expect($fresh->follow_up_action)->toBe('Call back about the roof');
    expect($fresh->follow_up_completed)->toBeTrue();
    expect($fresh->follow_up_completed_by)->toBe($user->id);
});
