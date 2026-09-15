<?php

use App\Livewire\Homes\CallsIntakes;
use App\Livewire\Homes\Projects;
use App\Models\CommunicationLog;
use App\Models\HomesSavedView;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\Participant;
use App\Models\Permission;
use App\Models\ProjectMilestone;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

/**
 * Column sorting on the Housing-portal list screens.
 *
 * The two screens sort in different places on purpose — Calls & Intakes is
 * paginated so it orders in SQL, Projects holds its whole active set so it
 * orders those rows in memory — and these tests pin both, plus the shared
 * click/toggle behaviour in HasSortableColumns.
 *
 * Every fixture carries the surname (calls) or title prefix (projects)
 * "Sortmarker", and the ordering assertions search for it: an assertion
 * about the order of a paginated list means nothing if rows the test did
 * not create can fill the page.
 */
function sortingUser(array $permissionNames = ['communications_log.view', 'projects.view']): User
{
    $role = Role::firstOrCreate(['name' => 'homes_sorting_test'], ['display_name' => 'Homes Sorting Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

/** A call whose displayed contact name is exactly "$first Sortmarker". */
function sortingCall(string $first, array $attributes = []): CommunicationLog
{
    return CommunicationLog::factory()->create(array_merge([
        'contact_first_name' => $first,
        'contact_last_name' => 'Sortmarker',
    ], $attributes));
}

/** An in-progress project titled "Sortmarker $title". */
function sortingJob(string $title, array $attributes = []): HousingJob
{
    return HousingJob::factory()->create(array_merge([
        'status' => 'in_progress',
        'job_title' => 'Sortmarker '.$title,
    ], $attributes));
}

/**
 * A homeowner whose displayed name is exactly "$first Q. $last" —
 * Participant::full_name joins first/middle/last, so giving every fixture a
 * middle initial keeps the expected string obvious.
 */
function sortingOwner(string $first, string $last): Participant
{
    return Participant::factory()->create([
        'first_name' => $first,
        'middle_name' => 'Q.',
        'last_name' => $last,
    ]);
}

/** The Calls list, narrowed to this test's fixtures. */
function sortingCallsList()
{
    return Livewire::test(CallsIntakes::class)->set('search', 'Sortmarker');
}

/** The Projects list, narrowed to this test's fixtures. */
function sortingProjectsList(string $pill = 'open')
{
    return Livewire::test(Projects::class)->set('pill', $pill)->set('search', 'Sortmarker');
}

// ── Shared behaviour (HasSortableColumns) ───────────────────────────

it('starts every list on its own default ordering', function () {
    $this->actingAs(sortingUser());

    Livewire::test(CallsIntakes::class)
        ->assertSet('sort', '')
        ->assertSet('sortDir', 'asc');

    Livewire::test(Projects::class)
        ->assertSet('sort', '')
        ->assertSet('sortDir', 'asc');
});

it('cycles a column header: natural direction, reversed, then back to the default', function () {
    $this->actingAs(sortingUser());

    Livewire::test(CallsIntakes::class)
        ->call('sortBy', 'who')
        ->assertSet('sort', 'who')
        ->assertSet('sortDir', 'asc')
        ->call('sortBy', 'who')
        ->assertSet('sortDir', 'desc')
        ->call('sortBy', 'who')
        ->assertSet('sort', '')
        ->assertSet('sortDir', 'asc');
});

it('opens dates and progress in the direction that reads best', function () {
    $this->actingAs(sortingUser());

    // Most recent call first.
    Livewire::test(CallsIntakes::class)
        ->call('sortBy', 'last')
        ->assertSet('sortDir', 'desc');

    // Most complete first, but names still read A→Z.
    Livewire::test(Projects::class)
        ->call('sortBy', 'progress')
        ->assertSet('sortDir', 'desc')
        ->call('sortBy', 'who')
        ->assertSet('sortDir', 'asc');
});

it('ignores a sort column the screen does not offer', function () {
    $this->actingAs(sortingUser());

    Livewire::test(CallsIntakes::class)
        ->call('sortBy', 'bogus_column')
        ->assertSet('sort', '')
        // A hand-edited URL falls back to the default ordering too.
        ->set('sort', 'bogus_column')
        ->assertSet('sort', '');
});

it('honours sort state from the URL', function () {
    $this->actingAs(sortingUser());

    Livewire::withQueryParams(['sort' => 'who', 'sortDir' => 'desc'])
        ->test(CallsIntakes::class)
        ->assertSet('sort', 'who')
        ->assertSet('sortDir', 'desc');
});

it('flips the direction without changing the column, and only once one is picked', function () {
    $this->actingAs(sortingUser());

    Livewire::test(Projects::class)
        ->call('toggleSortDir')
        ->assertSet('sortDir', 'asc')   // nothing explicit to flip yet
        ->call('sortBy', 'who')
        ->call('toggleSortDir')
        ->assertSet('sort', 'who')
        ->assertSet('sortDir', 'desc');
});

// ── Calls & Intakes: ordering happens in SQL ────────────────────────

it('sorts the Calls list by contact name, both ways', function () {
    $this->actingAs(sortingUser());

    sortingCall('Mabel');
    sortingCall('Zoe');
    sortingCall('Aaron');

    sortingCallsList()
        ->call('sortBy', 'who')
        ->assertSeeInOrder(['Aaron Sortmarker', 'Mabel Sortmarker', 'Zoe Sortmarker'])
        ->call('sortBy', 'who')
        ->assertSeeInOrder(['Zoe Sortmarker', 'Mabel Sortmarker', 'Aaron Sortmarker']);
});

it('prefers the linked participant name over the contact fields when sorting', function () {
    $this->actingAs(sortingUser());

    // Shown (and sorted) as "Abigail Adams" even though the contact fields
    // on the log say Zoe.
    sortingCall('Zoe', ['participant_id' => sortingOwner('Abigail', 'Adams')->id]);
    sortingCall('Mabel');

    sortingCallsList()
        ->call('sortBy', 'who')
        ->assertSeeInOrder(['Abigail Q. Adams', 'Mabel Sortmarker']);
});

it('sorts the Calls list by pipeline stage rather than by the stage wording', function () {
    $this->actingAs(sortingUser());

    // Names run backwards through the alphabet, so a name sort would produce
    // the opposite order to the one asserted below.
    sortingCall('Zelda');
    $collecting = sortingCall('Yara');
    $ready = sortingCall('Xena');
    sortingCall('Wanda', ['converted_to_job_id' => HousingJob::factory()->create()->id]);

    IntakeRequirement::factory()->create(['communication_log_id' => $collecting->id]);
    IntakeRequirement::factory()->received()->create(['communication_log_id' => $ready->id]);

    sortingCallsList()
        ->call('sortBy', 'stage')
        ->assertSet('sortDir', 'asc')
        ->assertSeeInOrder([
            'Zelda Sortmarker',    // just a call
            'Yara Sortmarker',     // collecting docs
            'Xena Sortmarker',     // ready for project
            'Wanda Sortmarker',    // converted
        ]);
});

it('sorts the Calls list by the share of docs collected, doc-less cases last', function () {
    $this->actingAs(sortingUser());

    $half = sortingCall('Half');
    IntakeRequirement::factory()->create(['communication_log_id' => $half->id]);
    IntakeRequirement::factory()->received()->create(['communication_log_id' => $half->id]);

    sortingCall('Nodocs');

    $allIn = sortingCall('Complete');
    IntakeRequirement::factory()->received()->create(['communication_log_id' => $allIn->id]);

    sortingCallsList()
        ->call('sortBy', 'docs')
        ->assertSeeInOrder(['Half Sortmarker', 'Complete Sortmarker', 'Nodocs Sortmarker']);
});

it('sorts the Calls list by how soon the next action is due, open ones first', function () {
    $this->actingAs(sortingUser());

    sortingCall('Later', ['follow_up_date' => now()->addWeek()->toDateString(), 'follow_up_completed' => false]);
    sortingCall('Settled', ['follow_up_date' => now()->addDay()->toDateString(), 'follow_up_completed' => true]);
    sortingCall('Soonest', ['follow_up_date' => now()->subDay()->toDateString(), 'follow_up_completed' => false]);

    sortingCallsList()
        ->call('sortBy', 'next')
        ->assertSeeInOrder(['Soonest Sortmarker', 'Later Sortmarker', 'Settled Sortmarker']);
});

it('sorts the whole Calls list, not just the page in view', function () {
    $this->actingAs(sortingUser());

    // 20 rows fill page one. The alphabetically first name is also the
    // oldest call, so the default most-recent-first ordering puts it on
    // page two — it can only come back to page one if the database, not
    // the page, did the sorting.
    foreach (range(1, 24) as $n) {
        sortingCall('Zz'.str_pad((string) $n, 2, '0', STR_PAD_LEFT), [
            'communication_date' => now()->subDays($n)->toDateTimeString(),
        ]);
    }
    sortingCall('Aardvark', ['communication_date' => now()->subYear()->toDateTimeString()]);

    sortingCallsList()
        ->assertDontSee('Aardvark Sortmarker')
        ->call('sortBy', 'who')
        ->assertSee('Aardvark Sortmarker');
});

it('leaves the Calls list on overdue-first when no column is picked', function () {
    $this->actingAs(sortingUser());

    sortingCall('Recent', ['communication_date' => now()->toDateTimeString()]);
    sortingCall('Overdue', [
        'communication_date' => now()->subMonth()->toDateTimeString(),
        'follow_up_date' => now()->subWeek()->toDateString(),
        'follow_up_completed' => false,
    ]);

    sortingCallsList()
        ->assertSet('sort', '')
        ->assertSeeInOrder(['Overdue Sortmarker', 'Recent Sortmarker']);
});

// ── Projects: ordering happens in memory ────────────────────────────

it('sorts the Projects list by homeowner, both ways', function () {
    $this->actingAs(sortingUser());

    sortingJob('one', ['participant_id' => sortingOwner('Mabel', 'Meyers')->id]);
    sortingJob('two', ['participant_id' => sortingOwner('Zoe', 'Zajac')->id]);
    sortingJob('three', ['participant_id' => sortingOwner('Aaron', 'Abbott')->id]);

    sortingProjectsList()
        ->call('sortBy', 'who')
        ->assertSeeInOrder(['Aaron Q. Abbott', 'Mabel Q. Meyers', 'Zoe Q. Zajac'])
        ->call('sortBy', 'who')
        ->assertSeeInOrder(['Zoe Q. Zajac', 'Mabel Q. Meyers', 'Aaron Q. Abbott']);
});

it('sorts the Projects list by status, the ones needing a hand first', function () {
    $this->actingAs(sortingUser());

    sortingJob('calm siding');
    sortingJob('wrapped up', ['status' => 'completed']);
    $hot = sortingJob('hot roof');
    ProjectMilestone::factory()->create([
        'housing_job_id' => $hot->id,
        'status' => 'in_progress',
        'target_date' => now()->subDays(3)->toDateString(),
    ]);

    sortingProjectsList('all')
        ->call('sortBy', 'status')
        ->assertSeeInOrder(['Sortmarker hot roof', 'Sortmarker calm siding', 'Sortmarker wrapped up']);
});

it('sorts the Projects list by the share of milestones done', function () {
    $this->actingAs(sortingUser());

    $ahead = sortingJob('ahead');
    ProjectMilestone::factory()->create(['housing_job_id' => $ahead->id, 'status' => 'completed']);
    ProjectMilestone::factory()->create(['housing_job_id' => $ahead->id, 'status' => 'completed']);
    ProjectMilestone::factory()->create([
        'housing_job_id' => $ahead->id, 'status' => 'not_started',
        'target_date' => now()->addMonth()->toDateString(),
    ]);

    $behind = sortingJob('behind');
    ProjectMilestone::factory()->create([
        'housing_job_id' => $behind->id, 'status' => 'not_started',
        'target_date' => now()->addMonth()->toDateString(),
    ]);

    // Nothing to measure yet — sits at the bottom of the natural order.
    sortingJob('unplanned');

    sortingProjectsList()
        ->call('sortBy', 'progress')
        ->assertSet('sortDir', 'desc')
        ->assertSeeInOrder(['Sortmarker ahead', 'Sortmarker behind', 'Sortmarker unplanned']);
});

it('sorts the Projects list by the next milestone target date', function () {
    $this->actingAs(sortingUser());

    $soon = sortingJob('soon');
    ProjectMilestone::factory()->create([
        'housing_job_id' => $soon->id, 'status' => 'in_progress',
        'target_date' => now()->addDays(3)->toDateString(),
    ]);

    $later = sortingJob('later');
    ProjectMilestone::factory()->create([
        'housing_job_id' => $later->id, 'status' => 'in_progress',
        'target_date' => now()->addMonths(2)->toDateString(),
    ]);

    sortingProjectsList()
        ->call('sortBy', 'next')
        ->assertSet('sortDir', 'asc')
        ->assertSeeInOrder(['Sortmarker soon', 'Sortmarker later']);
});

it('leaves the Projects list newest-first when no column is picked', function () {
    $this->actingAs(sortingUser());

    sortingJob('older', ['created_at' => now()->subMonth()]);
    sortingJob('newer', ['created_at' => now()]);

    sortingProjectsList()
        ->assertSet('sort', '')
        ->assertSeeInOrder(['Sortmarker newer', 'Sortmarker older']);
});

// ── Saved views carry the sort ──────────────────────────────────────

it('captures the active sort in a new saved view', function () {
    $this->actingAs($user = sortingUser());

    Livewire::test(CallsIntakes::class)
        ->call('sortBy', 'last')
        ->call('openCreateView')
        ->set('viewName', 'Newest calls')
        ->call('saveView')
        ->assertHasNoErrors();

    $view = HomesSavedView::where('user_id', $user->id)->where('screen', 'calls')->sole();
    expect($view->filters['sort'])->toBe('last')
        ->and($view->filters['sortDir'])->toBe('desc');
});

it('applies a saved view sort on mount', function () {
    $this->actingAs($user = sortingUser());

    HomesSavedView::create([
        'user_id' => $user->id,
        'screen' => 'projects',
        'name' => 'By homeowner',
        'columns' => ['who'],
        'filters' => ['pill' => 'all', 'sort' => 'who', 'sortDir' => 'asc'],
        'is_default' => true,
    ]);

    Livewire::test(Projects::class)
        ->assertSet('sort', 'who')
        ->assertSet('sortDir', 'asc');
});

it('falls back to the default ordering for a view stored before sorting existed', function () {
    $this->actingAs($user = sortingUser());

    HomesSavedView::create([
        'user_id' => $user->id,
        'screen' => 'calls',
        'name' => 'Legacy view',
        'columns' => ['who', 'stage'],
        'filters' => ['pill' => 'needs', 'expanded' => false],
        'is_default' => true,
    ]);

    Livewire::test(CallsIntakes::class)
        ->assertSet('pill', 'needs')
        ->assertSet('sort', '')
        ->assertSet('sortDir', 'asc');
});

it('drops an illegal sort out of a stored view', function () {
    $this->actingAs($user = sortingUser());

    HomesSavedView::create([
        'user_id' => $user->id,
        'screen' => 'calls',
        'name' => 'Bad sort',
        'columns' => ['who'],
        'filters' => ['pill' => 'all', 'expanded' => true, 'sort' => 'nonsense', 'sortDir' => 'sideways'],
        'is_default' => true,
    ]);

    Livewire::test(CallsIntakes::class)
        ->assertSet('sort', '')
        ->assertSet('sortDir', 'asc');
});
