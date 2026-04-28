<?php

use App\Models\HousingJob;
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

it('creates an ad-hoc task requirement on a project owned by the project manager by default', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);

    $req = app(RequirementInstantiator::class)->adHoc(
        $job,
        Requirement::TYPE_TASK,
        'One-off ad-hoc requirement',
        $manager,
    );

    expect($req->source_type)->toBe('ad_hoc');
    expect($req->source_id)->toBeNull();
    expect($req->source_template_id)->toBeNull();
    expect($req->source_label_snapshot)->toBeNull();
    expect($req->owner_user_id)->toBe($manager->id);
    expect($req->status)->toBe('outstanding');
    expect($req->type)->toBe('task');
});

it('respects an explicit owner override when the project manager wants to assign someone else', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $tony = User::factory()->create(['name' => 'Tony']);
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);

    $req = app(RequirementInstantiator::class)->adHoc(
        $job,
        Requirement::TYPE_DOCUMENT,
        'Tony please send scope of work',
        $manager,
        $tony,
    );

    expect($req->owner_user_id)->toBe($tony->id);
    expect($req->type)->toBe('document');
    Notification::assertSentTo($tony, RequirementAssignedNotification::class);
});

it('does not notify the actor when they assign an ad-hoc requirement to themselves', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);

    app(RequirementInstantiator::class)->adHoc(
        $job,
        Requirement::TYPE_TASK,
        'Self-assigned task',
        $manager,
        $manager,
    );

    Notification::assertNothingSent();
});
