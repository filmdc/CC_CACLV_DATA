<?php

use App\Models\HousingJob;
use App\Models\Requirement;
use App\Models\User;
use App\Notifications\RequirementAssignedNotification;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

it('reassigns the owner and notifies the new owner when the actor is someone else', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $tony = User::factory()->create();
    $peter = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $tony->id,
    ]);

    $req->assign($peter, $manager);

    $req->refresh();
    expect($req->owner_user_id)->toBe($peter->id);
    Notification::assertSentTo($peter, RequirementAssignedNotification::class);
    Notification::assertNotSentTo($tony, RequirementAssignedNotification::class);
    Notification::assertNotSentTo($manager, RequirementAssignedNotification::class);
});

it('does not notify when an actor reassigns a requirement to themselves', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => null,
    ]);

    $req->assign($manager, $manager);

    expect($req->fresh()->owner_user_id)->toBe($manager->id);
    Notification::assertNothingSent();
});

it('is a no-op when the new owner equals the current owner', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $req = Requirement::factory()->create(['owner_user_id' => $owner->id]);

    $req->assign($owner, $actor);

    Notification::assertNothingSent();
});
