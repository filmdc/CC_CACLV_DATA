<?php

use App\Livewire\Homes\CallDetail;
use App\Livewire\Homes\CallsIntakes;
use App\Livewire\Homes\ProjectDetail;
use App\Livewire\Homes\Projects;
use App\Livewire\Homes\Today;
use App\Models\CommunicationLog;
use App\Models\HomesPin;
use App\Models\HousingJob;
use App\Models\Permission;
use App\Models\ProjectMilestone;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

function pinsUser(array $permissionNames = ['communications_log.view', 'projects.view']): User
{
    $role = Role::firstOrCreate(['name' => 'homes_pins_test'], ['display_name' => 'Homes Pins Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

// ── Toggling ────────────────────────────────────────────────────────

it('pins and unpins a call from the Calls list', function () {
    $this->actingAs($user = pinsUser());
    $log = CommunicationLog::factory()->create();

    $component = Livewire::test(CallsIntakes::class)
        ->call('togglePin', 'call', $log->id);

    expect(HomesPin::where('user_id', $user->id)->where('pin_type', 'call')->where('pinnable_id', $log->id)->exists())->toBeTrue();

    $component->call('togglePin', 'call', $log->id);

    expect(HomesPin::where('user_id', $user->id)->count())->toBe(0);
});

it('pins a project from its detail page and reports it as pinned', function () {
    $this->actingAs($user = pinsUser());
    $job = HousingJob::factory()->create(['status' => 'in_progress']);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('toggleProjectPin')
        ->assertOk();

    expect(HomesPin::idsFor($user->id, HomesPin::TYPE_PROJECT))->toBe([$job->id]);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->assertSee('Pinned');
});

it('pins a call from its detail page', function () {
    $this->actingAs($user = pinsUser());
    $log = CommunicationLog::factory()->create();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('toggleCallPin')
        ->assertOk();

    expect(HomesPin::idsFor($user->id, HomesPin::TYPE_CALL))->toBe([$log->id]);
});

it('keeps pins private to the user who made them', function () {
    $mine = pinsUser();
    $theirs = pinsUser();
    $log = CommunicationLog::factory()->create(['contact_first_name' => 'Shared', 'contact_last_name' => 'Record']);

    HomesPin::toggle($mine->id, HomesPin::TYPE_CALL, $log->id);

    expect(HomesPin::idsFor($mine->id, HomesPin::TYPE_CALL))->toBe([$log->id]);
    expect(HomesPin::idsFor($theirs->id, HomesPin::TYPE_CALL))->toBe([]);

    $this->actingAs($theirs);
    Livewire::test(Today::class)->assertViewHas('pinnedCalls', fn ($rows) => $rows->isEmpty());
});

it('refuses to pin a type the user cannot view', function () {
    $this->actingAs(pinsUser(['projects.view']));
    $log = CommunicationLog::factory()->create();

    Livewire::test(Projects::class)
        ->call('togglePin', 'call', $log->id)
        ->assertForbidden();

    expect(HomesPin::count())->toBe(0);
});

it('ignores an unknown pin type and a record that no longer exists', function () {
    $this->actingAs(pinsUser());

    Livewire::test(Projects::class)
        ->call('togglePin', 'nonsense', 1)
        ->call('togglePin', 'project', 999999)
        ->assertOk();

    expect(HomesPin::count())->toBe(0);
});

// ── Surfacing on Today ──────────────────────────────────────────────

it('shows pinned calls and projects on the Today dashboard', function () {
    $this->actingAs($user = pinsUser());

    $log = CommunicationLog::factory()->create([
        'contact_first_name' => 'Pinned',
        'contact_last_name' => 'Caller',
        'follow_up_date' => null,
    ]);
    $job = HousingJob::factory()->create(['status' => 'in_progress', 'job_title' => 'Pinned roof job']);

    HomesPin::toggle($user->id, HomesPin::TYPE_CALL, $log->id);
    HomesPin::toggle($user->id, HomesPin::TYPE_PROJECT, $job->id);

    Livewire::test(Today::class)
        ->assertViewHas('pinnedCalls', fn ($rows) => $rows->pluck('id')->all() === [$log->id])
        ->assertViewHas('pinnedProjects', fn ($rows) => $rows->pluck('id')->all() === [$job->id])
        ->assertSee('Pinned Caller')
        ->assertSee('Pinned roof job');
});

it('drops a pin whose record has since been deleted', function () {
    $this->actingAs($user = pinsUser());
    $log = CommunicationLog::factory()->create();
    HomesPin::toggle($user->id, HomesPin::TYPE_CALL, $log->id);
    $log->delete();

    Livewire::test(Today::class)
        ->assertOk()
        ->assertViewHas('pinnedCalls', fn ($rows) => $rows->isEmpty());
});

// ── Lifted above the lists ──────────────────────────────────────────

it('lifts a pinned call above the Calls list and out of the list below', function () {
    $this->actingAs($user = pinsUser());

    $pinned = CommunicationLog::factory()->create(['contact_first_name' => 'Pinned', 'contact_last_name' => 'Caller']);
    CommunicationLog::factory()->create(['contact_first_name' => 'Ordinary', 'contact_last_name' => 'Caller']);

    HomesPin::toggle($user->id, HomesPin::TYPE_CALL, $pinned->id);

    Livewire::test(CallsIntakes::class)
        ->assertViewHas('pinnedRows', fn ($rows) => $rows->pluck('id')->all() === [$pinned->id])
        ->assertViewHas('logs', fn ($logs) => ! collect($logs->items())->pluck('id')->contains($pinned->id))
        ->assertSee('Pinned Caller')
        ->assertSee('Ordinary Caller');
});

it('keeps a pinned call visible even when the active pill would exclude it', function () {
    $this->actingAs($user = pinsUser());

    // No follow-up, so the "needs something" pill would normally hide it.
    $quiet = CommunicationLog::factory()->create([
        'contact_first_name' => 'Quiet',
        'contact_last_name' => 'Record',
        'follow_up_date' => null,
    ]);
    HomesPin::toggle($user->id, HomesPin::TYPE_CALL, $quiet->id);

    Livewire::test(CallsIntakes::class)
        ->set('pill', 'needs')
        ->assertViewHas('pinnedRows', fn ($rows) => $rows->pluck('id')->all() === [$quiet->id])
        ->assertSee('Quiet Record');
});

it('lifts a pinned project above the Projects list and out of the list below', function () {
    $this->actingAs($user = pinsUser());

    $pinned = HousingJob::factory()->create(['status' => 'in_progress', 'job_title' => 'Pinned roof job']);
    HousingJob::factory()->create(['status' => 'in_progress', 'job_title' => 'Ordinary siding job']);

    HomesPin::toggle($user->id, HomesPin::TYPE_PROJECT, $pinned->id);

    Livewire::test(Projects::class)
        ->assertViewHas('pinnedRows', fn ($rows) => $rows->pluck('id')->all() === [$pinned->id])
        ->assertViewHas('rows', fn ($rows) => ! $rows->pluck('id')->contains($pinned->id))
        ->assertSee('Pinned roof job')
        ->assertSee('Ordinary siding job');
});

it('keeps a finished pinned project visible under the Open pill', function () {
    $this->actingAs($user = pinsUser());

    $finished = HousingJob::factory()->create(['status' => 'completed', 'job_title' => 'Finished porch job']);
    HomesPin::toggle($user->id, HomesPin::TYPE_PROJECT, $finished->id);

    Livewire::test(Projects::class)
        ->set('pill', 'open')
        ->assertViewHas('pinnedRows', fn ($rows) => $rows->pluck('id')->all() === [$finished->id])
        ->assertSee('Finished porch job');
});

it('still counts a pinned project in the pill badges', function () {
    $this->actingAs($user = pinsUser());

    $hot = HousingJob::factory()->create(['status' => 'in_progress']);
    ProjectMilestone::factory()->create([
        'housing_job_id' => $hot->id,
        'status' => 'in_progress',
        'target_date' => now()->subDays(3)->toDateString(),
    ]);
    HomesPin::toggle($user->id, HomesPin::TYPE_PROJECT, $hot->id);

    Livewire::test(Projects::class)
        ->assertViewHas('counts', fn ($counts) => $counts['open'] === 1 && $counts['attention'] === 1);
});

it('holds the pinned section back while a search is running so results stay complete', function () {
    $this->actingAs($user = pinsUser());

    $pinned = CommunicationLog::factory()->create(['contact_first_name' => 'Pinned', 'contact_last_name' => 'Caller']);
    HomesPin::toggle($user->id, HomesPin::TYPE_CALL, $pinned->id);

    Livewire::test(CallsIntakes::class)
        ->set('search', 'Pinned')
        ->assertViewHas('pinnedRows', fn ($rows) => $rows->isEmpty())
        // …and the record is back in the list itself rather than filtered out.
        ->assertViewHas('logs', fn ($logs) => collect($logs->items())->pluck('id')->contains($pinned->id));
});

it('keeps pins in the order they were made', function () {
    $this->actingAs($user = pinsUser());

    $first = CommunicationLog::factory()->create();
    $second = CommunicationLog::factory()->create();

    HomesPin::toggle($user->id, HomesPin::TYPE_CALL, $second->id);
    HomesPin::toggle($user->id, HomesPin::TYPE_CALL, $first->id);

    expect(HomesPin::idsFor($user->id, HomesPin::TYPE_CALL))->toBe([$second->id, $first->id]);
});
