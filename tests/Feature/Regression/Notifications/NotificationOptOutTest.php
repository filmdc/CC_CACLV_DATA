<?php

use App\Models\HousingJob;
use App\Models\Requirement;
use App\Models\User;
use App\Notifications\RequirementAssignedNotification;
use App\Notifications\RequirementCompletedNotification;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

/*
|----------------------------------------------------------------------
| Phase 3B per-user email opt-outs. The database channel always fires;
| the mail channel is gated on User::shouldEmailFor(). Null preferences
| (the default) mean every category is on. Tests below pin both
| directions of the predicate.
|----------------------------------------------------------------------
*/

it('shouldEmailFor returns true on a fresh user (null preferences default to opt-in)', function () {
    $user = User::factory()->create();

    expect($user->shouldEmailFor('assignment_email'))->toBeTrue();
    expect($user->shouldEmailFor('completion_email'))->toBeTrue();
    expect($user->shouldEmailFor('over_budget_email'))->toBeTrue();
})->group('regression');

it('shouldEmailFor returns false for an explicit opt-out', function () {
    $user = User::factory()->create([
        'notification_preferences' => [
            'assignment_email' => false,
            'completion_email' => true,
            'over_budget_email' => true,
        ],
    ]);

    expect($user->shouldEmailFor('assignment_email'))->toBeFalse();
    expect($user->shouldEmailFor('completion_email'))->toBeTrue();
    expect($user->shouldEmailFor('over_budget_email'))->toBeTrue();
})->group('regression');

it('falls back to opt-in when a category is missing from the stored preferences', function () {
    $user = User::factory()->create([
        'notification_preferences' => [
            'assignment_email' => false,
            // completion_email + over_budget_email omitted entirely
        ],
    ]);

    expect($user->shouldEmailFor('assignment_email'))->toBeFalse();
    expect($user->shouldEmailFor('completion_email'))->toBeTrue();
    expect($user->shouldEmailFor('over_budget_email'))->toBeTrue();
})->group('regression');

it('assignment notification routes both channels when the recipient is opted in', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $tony = User::factory()->create(); // null preferences = opt-in
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => null,
    ]);

    $req->assign($tony, $manager);

    Notification::assertSentTo($tony, RequirementAssignedNotification::class, function ($notification, array $channels) {
        return in_array('database', $channels) && in_array('mail', $channels);
    });
})->group('regression');

it('assignment notification suppresses the mail channel when the recipient has opted out', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $tony = User::factory()->create([
        'notification_preferences' => ['assignment_email' => false],
    ]);
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => null,
    ]);

    $req->assign($tony, $manager);

    Notification::assertSentTo($tony, RequirementAssignedNotification::class, function ($notification, array $channels) {
        return in_array('database', $channels) && ! in_array('mail', $channels);
    });
})->group('regression');

it('completion notification routes both channels when the owner is opted in', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $tony = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $tony->id,
    ]);

    $req->markComplete($manager, 'Closed.');

    Notification::assertSentTo($tony, RequirementCompletedNotification::class, function ($notification, array $channels) {
        return in_array('database', $channels) && in_array('mail', $channels);
    });
})->group('regression');

it('completion notification suppresses the mail channel when the owner has opted out', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $tony = User::factory()->create([
        'notification_preferences' => ['completion_email' => false],
    ]);
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $tony->id,
    ]);

    $req->markComplete($manager, 'Closed.');

    Notification::assertSentTo($tony, RequirementCompletedNotification::class, function ($notification, array $channels) {
        return in_array('database', $channels) && ! in_array('mail', $channels);
    });
})->group('regression');

it('opting out of one category does not suppress notifications in other categories', function () {
    Notification::fake();

    $manager = User::factory()->create();
    // Tony opts out of completion emails only — assignment emails still on.
    $tony = User::factory()->create([
        'notification_preferences' => [
            'assignment_email' => true,
            'completion_email' => false,
        ],
    ]);
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => null,
    ]);

    // Assignment fires both channels for Tony (assignment_email=true).
    $req->assign($tony, $manager);

    Notification::assertSentTo($tony, RequirementAssignedNotification::class, function ($notification, array $channels) {
        return in_array('mail', $channels);
    });

    // Manager closes the requirement; completion drops mail for Tony
    // (completion_email=false) but database channel still fires.
    $req->markComplete($manager, 'Closed.');

    Notification::assertSentTo($tony, RequirementCompletedNotification::class, function ($notification, array $channels) {
        return in_array('database', $channels) && ! in_array('mail', $channels);
    });
})->group('regression');
