<?php

use App\Models\User;
use Livewire\Volt\Volt;
use Tests\Support\SnapshotHelper;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the notification settings page for an authed user', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/settings/notifications')->assertOk();
});

it('mounts with all-on defaults when notification_preferences is null', function () {
    $user = User::factory()->create(['notification_preferences' => null]);
    $this->actingAs($user);

    Volt::test('settings.notifications')
        ->assertSet('assignment_email', true)
        ->assertSet('completion_email', true)
        ->assertSet('over_budget_email', true);
});

it('mounts with the stored preferences when present', function () {
    $user = User::factory()->create([
        'notification_preferences' => [
            'assignment_email' => false,
            'completion_email' => true,
            'over_budget_email' => false,
        ],
    ]);
    $this->actingAs($user);

    Volt::test('settings.notifications')
        ->assertSet('assignment_email', false)
        ->assertSet('completion_email', true)
        ->assertSet('over_budget_email', false);
});

it('persists the toggle state on save', function () {
    $user = User::factory()->create(['notification_preferences' => null]);
    $this->actingAs($user);

    Volt::test('settings.notifications')
        ->set('assignment_email', false)
        ->set('completion_email', true)
        ->set('over_budget_email', false)
        ->call('save')
        ->assertHasNoErrors();

    $prefs = $user->fresh()->notification_preferences;
    expect($prefs['assignment_email'])->toBeFalse();
    expect($prefs['completion_email'])->toBeTrue();
    expect($prefs['over_budget_email'])->toBeFalse();
});

it('matches the snapshot of the rendered notification settings page', function () {
    $user = User::factory()->create([
        'name' => 'Snapshot User',
        'email' => 'snapshot@example.com',
        'notification_preferences' => null,
    ]);
    $this->actingAs($user);

    $html = (string) $this->get('/settings/notifications')->getContent();

    SnapshotHelper::assertHtmlMatchesSnapshot('settings_notifications', $html);
})->group('regression');
