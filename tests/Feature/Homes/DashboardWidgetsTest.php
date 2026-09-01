<?php

use App\Livewire\Homes\Settings\DashboardWidgets as WidgetSettings;
use App\Livewire\Homes\SettingsHub;
use App\Livewire\Homes\Today;
use App\Models\CommunicationLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Homes\DashboardWidgets;
use Livewire\Livewire;

function widgetUser(array $permissionNames = ['communications_log.view'], string $roleName = 'homes_widget_test'): User
{
    $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => 'Homes Widget Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function widgetAdmin(): User
{
    $role = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Administrator']);
    $ids = collect(['communications_log.view', 'projects.view'])
        ->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->syncWithoutDetaching($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

beforeEach(function () {
    SiteSetting::query()->delete();
    SiteSetting::clearCache();
    cache()->forget('site_setting_'.DashboardWidgets::SETTING_KEY);
});

// ── Defaults ────────────────────────────────────────────────────────

it('hides Follow-ups due by default and keeps the other cards on', function () {
    $state = app(DashboardWidgets::class)->state();

    expect($state['follow_ups'])->toBeFalse()
        ->and($state['waiting'])->toBeTrue()
        ->and($state['ready'])->toBeTrue()
        ->and($state['glance'])->toBeTrue()
        ->and($state['pinned'])->toBeTrue();
});

it('leaves the Follow-ups card off the Today dashboard out of the box', function () {
    $this->actingAs(widgetUser());

    CommunicationLog::factory()->create([
        'contact_first_name' => 'Evelyn',
        'contact_last_name' => 'Lapenna',
        'follow_up_date' => now()->subDays(2)->toDateString(),
        'follow_up_action' => 'Call to check in',
        'follow_up_completed' => false,
    ]);

    Livewire::test(Today::class)
        ->assertDontSee('Follow-ups due')
        // The card is not merely hidden — it is not queried.
        ->assertViewHas('followUps', fn ($rows) => $rows->isEmpty())
        ->assertSee('Waiting on documents');
});

it('still tells her how many follow-ups are open, with a link to find them', function () {
    $this->actingAs(widgetUser());

    CommunicationLog::factory()->create([
        'follow_up_date' => now()->subDays(2)->toDateString(),
        'follow_up_completed' => false,
    ]);

    Livewire::test(Today::class)
        ->assertViewHas('openFollowUps', 1)
        ->assertSee('1 follow-up need')
        ->assertSee('See them');
});

// ── Turning it back on ──────────────────────────────────────────────

it('shows the Follow-ups card again once an admin enables it', function () {
    app(DashboardWidgets::class)->save([
        'pinned' => true, 'follow_ups' => true, 'waiting' => true, 'ready' => true, 'glance' => true,
    ]);

    $this->actingAs(widgetUser());
    CommunicationLog::factory()->create([
        'contact_first_name' => 'Evelyn',
        'contact_last_name' => 'Lapenna',
        'follow_up_date' => now()->subDays(2)->toDateString(),
        'follow_up_completed' => false,
    ]);

    Livewire::test(Today::class)
        ->assertSee('Follow-ups due')
        ->assertSee('Evelyn Lapenna')
        ->assertViewHas('followUps', fn ($rows) => $rows->count() === 1);
});

it('lets an admin toggle a card and persists it site-wide', function () {
    $this->actingAs(widgetAdmin());

    Livewire::test(WidgetSettings::class)
        ->assertSet('widgets.follow_ups', false)
        ->set('widgets.follow_ups', true)
        ->call('save')
        ->assertSet('widgets.follow_ups', true);

    // A different user sees the same dashboard.
    $this->actingAs(widgetUser());
    expect(app(DashboardWidgets::class)->isEnabled('follow_ups'))->toBeTrue();
});

it('resets back to the built-in defaults', function () {
    $this->actingAs(widgetAdmin());

    Livewire::test(WidgetSettings::class)
        ->set('widgets.follow_ups', true)
        ->set('widgets.glance', false)
        ->call('save')
        ->call('resetToDefaults')
        ->assertSet('widgets.follow_ups', false)
        ->assertSet('widgets.glance', true);
});

// ── Access ──────────────────────────────────────────────────────────

it('lets an administrator in with no permission seeded', function () {
    $this->actingAs(widgetAdmin());

    $this->get(route('homes.settings.dashboard'))->assertOk();
});

it('lets a non-admin holding homes.dashboard.manage in', function () {
    $this->actingAs(widgetUser(['homes.dashboard.manage'], 'homes_widget_delegate'));

    $this->get(route('homes.settings.dashboard'))->assertOk();
});

it('keeps everyone else out', function () {
    $this->actingAs(widgetUser(['communications_log.view'], 'homes_widget_plain'));

    $this->get(route('homes.settings.dashboard'))->assertForbidden();
});

it('shows the settings hub card to an admin but not to the project manager', function () {
    $this->actingAs(widgetAdmin());
    Livewire::test(SettingsHub::class)->assertSee('Dashboard widgets');

    $this->actingAs(widgetUser(['communications_log.view'], 'homes_widget_plain'));
    Livewire::test(SettingsHub::class)->assertDontSee('Dashboard widgets');
});

// ── Storage robustness ──────────────────────────────────────────────

it('ignores unknown keys in a submitted state', function () {
    app(DashboardWidgets::class)->save(['follow_ups' => true, 'bogus_widget' => true]);

    $stored = json_decode(SiteSetting::get(DashboardWidgets::SETTING_KEY), true);

    expect($stored)->not->toHaveKey('bogus_widget')
        ->and(array_keys($stored))->toBe(array_keys(DashboardWidgets::REGISTRY));
});

it('falls back to the registry default for a widget the stored value predates', function () {
    // An older saved value that only knew about follow_ups.
    SiteSetting::set(DashboardWidgets::SETTING_KEY, json_encode(['follow_ups' => true]), 'json');

    $state = app(DashboardWidgets::class)->state();

    expect($state['follow_ups'])->toBeTrue()
        ->and($state['glance'])->toBeTrue()
        ->and($state)->toHaveKeys(array_keys(DashboardWidgets::REGISTRY));
});

it('survives a corrupt stored value', function () {
    SiteSetting::set(DashboardWidgets::SETTING_KEY, 'not json at all', 'json');

    expect(app(DashboardWidgets::class)->state())->toBe(DashboardWidgets::defaults());
});

it('renders Today with every card switched off', function () {
    app(DashboardWidgets::class)->save([
        'pinned' => false, 'follow_ups' => false, 'waiting' => false, 'ready' => false, 'glance' => false,
    ]);

    $this->actingAs(widgetUser());

    Livewire::test(Today::class)
        ->assertOk()
        ->assertSee('Every dashboard card is switched off');
});
