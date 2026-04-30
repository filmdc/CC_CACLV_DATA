<?php

use App\Livewire\NotificationBell;
use App\Models\HousingJob;
use App\Models\Requirement;
use App\Models\User;
use App\Notifications\RequirementAssignedNotification;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

it('renders an empty state with no badge when the user has no notifications', function () {
    $user = User::factory()->create();

    // The bell renders with no badge; the empty-state text only appears
    // once the user toggles the dropdown open.
    Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->assertDontSee('notification-bell-count')
        ->call('toggle')
        ->assertSee('No notifications yet');
})->group('regression');

it('shows an unread-count badge equal to the user\'s unread notification count', function () {
    $user = User::factory()->create();
    $job = HousingJob::factory()->create();

    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $user->id,
    ]);

    // Send 3 notifications.
    $user->notify(new RequirementAssignedNotification($req));
    $user->notify(new RequirementAssignedNotification($req));
    $user->notify(new RequirementAssignedNotification($req));

    Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->assertSet('open', false)
        ->assertSee('3'); // badge count
})->group('regression');

it('toggle opens and closes the dropdown panel', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->assertSet('open', false)
        ->call('toggle')
        ->assertSet('open', true)
        ->call('toggle')
        ->assertSet('open', false);
})->group('regression');

it('lists the user\'s notifications inside the dropdown', function () {
    $user = User::factory()->create();
    $job = HousingJob::factory()->create([
        'job_title' => 'Roof Replacement',
        'job_number' => 'JOB-FIX-99999',
    ]);
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $user->id,
        'title' => 'Inspect the roof condition',
    ]);

    $user->notify(new RequirementAssignedNotification($req));

    Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->call('toggle')
        ->assertSee('Inspect the roof condition')
        ->assertSee('Roof Replacement')
        ->assertSee('JOB-FIX-99999');
})->group('regression');

it('marks a single notification as read and decrements the badge', function () {
    $user = User::factory()->create();
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $user->id,
    ]);

    $user->notify(new RequirementAssignedNotification($req));
    $user->notify(new RequirementAssignedNotification($req));

    expect($user->fresh()->unreadNotifications->count())->toBe(2);

    $firstId = $user->fresh()->notifications()->latest()->first()->id;

    Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->call('markAsRead', $firstId);

    expect($user->fresh()->unreadNotifications->count())->toBe(1);
})->group('regression');

it('marks all notifications as read in one call', function () {
    $user = User::factory()->create();
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $user->id,
    ]);

    for ($i = 0; $i < 5; $i++) {
        $user->notify(new RequirementAssignedNotification($req));
    }

    expect($user->fresh()->unreadNotifications->count())->toBe(5);

    Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->call('markAllAsRead');

    expect($user->fresh()->unreadNotifications->count())->toBe(0);
})->group('regression');

it('does not surface another user\'s notifications', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $bob->id,
        'title' => "Bob's private requirement",
    ]);

    $bob->notify(new RequirementAssignedNotification($req));

    Livewire::actingAs($alice)
        ->test(NotificationBell::class)
        ->call('toggle')
        ->assertSee('No notifications yet')
        ->assertDontSee("Bob's private requirement");
})->group('regression');

it('caps the dropdown at the 10 most recent notifications', function () {
    $user = User::factory()->create();
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $user->id,
    ]);

    for ($i = 0; $i < 15; $i++) {
        $user->notify(new RequirementAssignedNotification($req));
    }

    $rendered = Livewire::actingAs($user)
        ->test(NotificationBell::class)
        ->call('toggle')
        ->html();

    // 1 wire:key for the bell wrapper + at most 10 for list items.
    $matches = substr_count((string) $rendered, 'wire:key="notification-');
    expect($matches)->toBeLessThanOrEqual(11);
})->group('regression');
