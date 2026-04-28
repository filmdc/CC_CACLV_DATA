<?php

use App\Models\HousingJob;
use App\Models\Requirement;
use App\Models\User;
use App\Notifications\RequirementCompletedNotification;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

it('marks a task-type requirement complete with just an actor and an optional note', function () {
    $req = Requirement::factory()->task()->create();
    $actor = User::factory()->create();

    $req->markComplete($actor, 'Done, scheduled with vendor');

    $req->refresh();
    expect($req->isComplete())->toBeTrue();
    expect($req->completed_by_user_id)->toBe($actor->id);
    expect($req->completion_note)->toBe('Done, scheduled with vendor');
    expect($req->wasOverridden())->toBeFalse();
});

it('refuses to mark a document-type requirement complete without media', function () {
    $req = Requirement::factory()->document()->create();
    $actor = User::factory()->create();

    expect(fn () => $req->markComplete($actor))
        ->toThrow(\DomainException::class);

    $req->refresh();
    expect($req->isOutstanding())->toBeTrue();
});

it('allows a document-type requirement to be closed via override with a reason', function () {
    $req = Requirement::factory()->document()->create();
    $actor = User::factory()->create();

    $req->markCompleteWithOverride($actor, 'Hard copy on file in Kaitlyn drawer; no scan available.');

    $req->refresh();
    expect($req->isComplete())->toBeTrue();
    expect($req->wasOverridden())->toBeTrue();
    expect($req->override_reason)->toContain('Hard copy on file');
    expect($req->overridden_by_user_id)->toBe($actor->id);
});

it('rejects an empty override reason as invalid', function () {
    $req = Requirement::factory()->document()->create();
    $actor = User::factory()->create();

    expect(fn () => $req->markCompleteWithOverride($actor, '   '))
        ->toThrow(\InvalidArgumentException::class);
});

it('notifies the project manager when someone else completes a requirement on her project', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $tony = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $tony->id,
    ]);

    $req->markComplete($tony, 'Sent the email at 9am.');

    Notification::assertSentTo($manager, RequirementCompletedNotification::class, function ($n) use ($tony, $req) {
        return $n->completer->id === $tony->id && $n->requirement->id === $req->id;
    });
});

it('suppresses the completion notification when the completer IS the project manager', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $manager->id,
    ]);

    $req->markComplete($manager, 'Done.');

    Notification::assertNotSentTo($manager, RequirementCompletedNotification::class);
});

it('suppresses the completion notification even when the original owner was someone else, as long as the completer is the project manager', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $tony = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $tony->id,
    ]);

    // Kaitlyn closes Tony's requirement on his behalf — she shouldn't get pinged.
    $req->markComplete($manager, "Closed on Tony's behalf after his email.");

    Notification::assertNotSentTo($manager, RequirementCompletedNotification::class);
});
