<?php

use App\Livewire\Homes\CallsIntakes;
use App\Livewire\Homes\Projects;
use App\Models\HomesSavedView;
use App\Models\HousingJob;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

function savedViewsUser(array $permissionNames = ['communications_log.view', 'projects.view']): User
{
    $role = Role::firstOrCreate(['name' => 'saved_views_test'], ['display_name' => 'Saved Views Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

// ── New built-in defaults ───────────────────────────────────────────

it('defaults Calls & Intakes to Everything with the expanded view on', function () {
    $this->actingAs(savedViewsUser());

    Livewire::test(CallsIntakes::class)
        ->assertSet('pill', 'all')
        ->assertSet('expanded', true)
        ->assertSet('activeViewId', null);
});

it('keeps explicit URL filter state over the built-in defaults', function () {
    $this->actingAs(savedViewsUser());

    Livewire::withQueryParams(['pill' => 'needs', 'expanded' => '0'])
        ->test(CallsIntakes::class)
        ->assertSet('pill', 'needs')
        ->assertSet('expanded', false);
});

// ── Creating and applying custom views ──────────────────────────────

it('creates a custom view and applies it immediately', function () {
    $this->actingAs($user = savedViewsUser());

    Livewire::test(CallsIntakes::class)
        ->call('openCreateView')
        ->assertSet('showViewModal', true)
        ->set('viewName', 'My intake queue')
        ->call('toggleViewColumn', 'type')
        ->call('toggleViewColumn', 'direction')
        ->set('viewFilters.pill', 'collecting')
        ->set('viewFilters.expanded', true)
        ->call('saveView')
        ->assertSet('showViewModal', false)
        ->assertSet('pill', 'collecting')
        ->assertSet('expanded', true);

    $view = HomesSavedView::where('user_id', $user->id)->where('screen', 'calls')->first();
    expect($view)->not->toBeNull()
        ->and($view->name)->toBe('My intake queue')
        ->and($view->columns)->not->toContain('type')
        ->and($view->columns)->not->toContain('direction')
        ->and($view->columns)->toContain('who')
        ->and($view->filters['pill'])->toBe('collecting')
        ->and($view->is_default)->toBeFalse();
});

it('always keeps locked columns visible', function () {
    $this->actingAs(savedViewsUser());

    Livewire::test(CallsIntakes::class)
        ->call('openCreateView')
        ->call('toggleViewColumn', 'who')
        ->set('viewName', 'No contact?')
        ->call('saveView');

    expect(HomesSavedView::first()->columns)->toContain('who');
});

it('rejects a duplicate view name for the same user and screen', function () {
    $this->actingAs($user = savedViewsUser());

    HomesSavedView::create([
        'user_id' => $user->id, 'screen' => 'calls', 'name' => 'Mine',
        'columns' => ['who'], 'filters' => ['pill' => 'all', 'expanded' => true],
    ]);

    Livewire::test(CallsIntakes::class)
        ->call('openCreateView')
        ->set('viewName', 'Mine')
        ->call('saveView')
        ->assertHasErrors('viewName')
        ->assertSet('showViewModal', true);

    expect(HomesSavedView::count())->toBe(1);
});

// ── Default view behavior ────────────────────────────────────────────

it('applies the user default view on mount', function () {
    $this->actingAs($user = savedViewsUser());

    $view = HomesSavedView::create([
        'user_id' => $user->id, 'screen' => 'calls', 'name' => 'Needs list',
        'columns' => ['who', 'where', 'next'],
        'filters' => ['pill' => 'needs', 'expanded' => false],
        'is_default' => true,
    ]);

    Livewire::test(CallsIntakes::class)
        ->assertSet('activeViewId', $view->id)
        ->assertSet('pill', 'needs')
        ->assertSet('expanded', false)
        ->assertSet('columns', ['who', 'where', 'next']);
});

it('lets a URL filter override the default view filters but keeps its columns', function () {
    $this->actingAs($user = savedViewsUser());

    $view = HomesSavedView::create([
        'user_id' => $user->id, 'screen' => 'calls', 'name' => 'Needs list',
        'columns' => ['who', 'stage'],
        'filters' => ['pill' => 'needs', 'expanded' => false],
        'is_default' => true,
    ]);

    Livewire::withQueryParams(['pill' => 'all'])
        ->test(CallsIntakes::class)
        ->assertSet('pill', 'all')
        ->assertSet('columns', ['who', 'stage']);
});

it('marks a view default and clears the previous default', function () {
    $this->actingAs($user = savedViewsUser());

    $a = HomesSavedView::create([
        'user_id' => $user->id, 'screen' => 'calls', 'name' => 'A',
        'columns' => ['who'], 'filters' => ['pill' => 'all', 'expanded' => true], 'is_default' => true,
    ]);
    $b = HomesSavedView::create([
        'user_id' => $user->id, 'screen' => 'calls', 'name' => 'B',
        'columns' => ['who'], 'filters' => ['pill' => 'needs', 'expanded' => true],
    ]);

    Livewire::test(CallsIntakes::class)
        ->call('makeDefaultView', $b->id);

    expect($a->fresh()->is_default)->toBeFalse()
        ->and($b->fresh()->is_default)->toBeTrue();
});

it('deletes a view and falls back to the standard view when it was active', function () {
    $this->actingAs($user = savedViewsUser());

    $view = HomesSavedView::create([
        'user_id' => $user->id, 'screen' => 'calls', 'name' => 'Doomed',
        'columns' => ['who', 'stage'], 'filters' => ['pill' => 'needs', 'expanded' => false], 'is_default' => true,
    ]);

    Livewire::test(CallsIntakes::class)
        ->assertSet('activeViewId', $view->id)
        ->call('deleteView', $view->id)
        ->assertSet('activeViewId', null)
        ->assertSet('pill', 'all')
        ->assertSet('expanded', true);

    expect(HomesSavedView::count())->toBe(0);
});

// ── Isolation ────────────────────────────────────────────────────────

it('never applies or exposes another user\'s views', function () {
    $other = User::factory()->create();
    $foreign = HomesSavedView::create([
        'user_id' => $other->id, 'screen' => 'calls', 'name' => 'Not yours',
        'columns' => ['who'], 'filters' => ['pill' => 'needs', 'expanded' => false], 'is_default' => true,
    ]);

    $this->actingAs(savedViewsUser());

    Livewire::test(CallsIntakes::class)
        ->assertSet('activeViewId', null)
        ->assertSet('pill', 'all')
        ->call('applyView', $foreign->id)
        ->assertSet('activeViewId', null)
        ->call('deleteView', $foreign->id);

    expect($foreign->fresh())->not->toBeNull();
});

// ── Projects screen ──────────────────────────────────────────────────

it('supports saved views on the Projects list', function () {
    $this->actingAs($user = savedViewsUser());

    HousingJob::factory()->create(['status' => 'completed', 'job_title' => 'Finished porch job']);

    $view = HomesSavedView::create([
        'user_id' => $user->id, 'screen' => 'projects', 'name' => 'Everything, lean rows',
        'columns' => ['who', 'status'],
        'filters' => ['pill' => 'all'],
        'is_default' => true,
    ]);

    Livewire::test(Projects::class)
        ->assertSet('activeViewId', $view->id)
        ->assertSet('pill', 'all')
        ->assertSet('columns', ['who', 'status'])
        ->assertSee('Finished porch job');
});

it('sanitizes unknown columns and filters from a stored view', function () {
    $this->actingAs($user = savedViewsUser());

    $view = HomesSavedView::create([
        'user_id' => $user->id, 'screen' => 'calls', 'name' => 'Stale',
        'columns' => ['who', 'bogus_column', 'stage'],
        'filters' => ['pill' => 'bogus_pill', 'expanded' => 1],
        'is_default' => true,
    ]);

    Livewire::test(CallsIntakes::class)
        ->assertSet('columns', ['who', 'stage'])
        ->assertSet('pill', 'all')
        ->assertSet('expanded', true);
});

// ── Optional record-ID columns ───────────────────────────────────────

it('offers the comm log ID as an optional Calls & Intakes column', function () {
    $this->actingAs(savedViewsUser());

    $log = \App\Models\CommunicationLog::factory()->create();

    Livewire::test(CallsIntakes::class)
        ->assertSet('columns', ['who', 'where', 'stage', 'docs', 'last', 'next', 'type', 'direction'])
        ->call('openCreateView')
        ->call('toggleViewColumn', 'log_id')
        ->set('viewName', 'With log IDs')
        ->call('saveView')
        ->assertHasNoErrors()
        ->assertSet('columns', ['log_id', 'who', 'where', 'stage', 'docs', 'last', 'next', 'type', 'direction'])
        ->assertSee('Log ID')
        ->assertSee('COMM-'.$log->id);
});

it('offers the project number as an optional Projects column', function () {
    $this->actingAs($user = savedViewsUser());

    $job = HousingJob::factory()->create(['status' => 'completed', 'job_title' => 'Numbered roof job']);

    // Not part of the built-in standard view.
    Livewire::test(Projects::class)
        ->assertSet('columns', ['who', 'status', 'progress', 'next']);

    HomesSavedView::create([
        'user_id' => $user->id, 'screen' => 'projects', 'name' => 'With project numbers',
        'columns' => ['number', 'who'],
        'filters' => ['pill' => 'all'],
        'is_default' => true,
    ]);

    Livewire::test(Projects::class)
        ->assertSet('columns', ['number', 'who'])
        ->assertSee($job->job_number);
});
