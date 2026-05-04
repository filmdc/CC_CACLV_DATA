<?php

use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\User;
use App\Notifications\RequirementAssignedNotification;
use App\Services\RequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\Notification;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

it('creates a milestone-bound requirement that defaults to the milestone assignee as owner', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $milestoneAssignee = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $milestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'assigned_to_user_id' => $milestoneAssignee->id,
    ]);

    $req = app(RequirementInstantiator::class)->forMilestone(
        $milestone,
        Requirement::TYPE_TASK,
        'Confirm scope with property owner',
        $manager,
    );

    expect($req->source_type)->toBe('milestone');
    expect($req->source_id)->toBe($milestone->id);
    expect($req->owner_user_id)->toBe($milestoneAssignee->id);
    expect($req->status)->toBe('outstanding');

    Notification::assertSentTo($milestoneAssignee, RequirementAssignedNotification::class);
});

it('falls through to the project manager when the milestone has no assignee', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $milestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'assigned_to_user_id' => null,
    ]);

    $req = app(RequirementInstantiator::class)->forMilestone(
        $milestone,
        Requirement::TYPE_DOCUMENT,
        'Upload signed agreement',
        $manager,
    );

    expect($req->owner_user_id)->toBe($manager->id);
    expect($req->type)->toBe('document');
});

it('respects an explicit owner override on milestone-bound requirements', function () {
    $manager = User::factory()->create();
    $milestoneAssignee = User::factory()->create();
    $explicitOwner = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $milestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'assigned_to_user_id' => $milestoneAssignee->id,
    ]);

    $req = app(RequirementInstantiator::class)->forMilestone(
        $milestone,
        Requirement::TYPE_TASK,
        'Custom-owner item',
        $manager,
        $explicitOwner,
    );

    expect($req->owner_user_id)->toBe($explicitOwner->id);
});
