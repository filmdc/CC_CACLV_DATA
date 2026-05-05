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

/*
|----------------------------------------------------------------------
| Phase 3B subscriber-semantics: completion notification recipient is
| the requirement's owner-of-record, not the project manager. Three
| cases below — rewritten from the 3A "project-manager-only" mechanic
| that was a placeholder.
|----------------------------------------------------------------------
*/

it('notifies the owner when someone else completes a requirement assigned to them', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $tony = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $tony->id,
    ]);

    $req->markComplete($manager, "Closed on Tony's behalf after his email.");

    Notification::assertSentTo($tony, RequirementCompletedNotification::class, function ($n) use ($manager, $req) {
        return $n->completer->id === $manager->id && $n->requirement->id === $req->id;
    });
    Notification::assertNotSentTo($manager, RequirementCompletedNotification::class);
});

it('suppresses the completion notification when the completer IS the owner (self-completion)', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $owner->id,
    ]);

    $req->markComplete($owner, 'Done.');

    Notification::assertNotSentTo($owner, RequirementCompletedNotification::class);
});

it('does not notify on completion of an unassigned requirement (owner_user_id null)', function () {
    Notification::fake();

    $actor = User::factory()->create();
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => null,
    ]);

    $req->markComplete($actor, 'Closed.');

    Notification::assertNothingSent();
});
