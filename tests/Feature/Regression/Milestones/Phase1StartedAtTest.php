<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('newly-created milestones have started_at = NULL (P-19)', function () {
    $milestone = ProjectMilestone::factory()->create([
        'status' => 'not_started',
    ]);

    expect($milestone->started_at)->toBeNull();
})->group('regression');

it('transitioning to in_progress stamps started_at when previously NULL (P-19)', function () {
    $job = HousingJob::factory()->create();
    $milestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'status' => 'not_started',
        'started_at' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('updateMilestoneStatus', $milestone->id, 'in_progress');

    $milestone->refresh();
    expect($milestone->status)->toBe('in_progress');
    expect($milestone->started_at)->not->toBeNull();
    expect($milestone->started_at->isToday())->toBeTrue();
})->group('regression');

it('re-transitioning to in_progress preserves the original started_at (P-19)', function () {
    $job = HousingJob::factory()->create();
    $original = now()->subDays(7)->startOfDay();
    $milestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'status' => 'in_progress',
        'started_at' => $original,
    ]);

    // in_progress → not_started → in_progress
    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('updateMilestoneStatus', $milestone->id, 'not_started')
        ->call('updateMilestoneStatus', $milestone->id, 'in_progress');

    $milestone->refresh();
    expect($milestone->started_at->equalTo($original))->toBeTrue(
        'started_at must be preserved across re-transitions, got '.$milestone->started_at
    );
})->group('regression');

it('completing a milestone auto-advances the next one and stamps its started_at (P-19)', function () {
    $job = HousingJob::factory()->create();
    $first = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'sequence_order' => 1,
        'status' => 'in_progress',
        'started_at' => now()->subDays(3),
    ]);
    $second = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'sequence_order' => 2,
        'status' => 'not_started',
        'started_at' => null,
    ]);

    expect($first->markAsCompleted())->toBeTrue();

    $second->refresh();
    expect($second->status)->toBe('in_progress');
    expect($second->started_at)->not->toBeNull();
    expect($second->started_at->isToday())->toBeTrue();
})->group('regression');

it('existing in_progress milestones with NULL started_at stay NULL until next transition (P-19)', function () {
    // Models the post-deploy reality: pre-existing in_progress rows have
    // no started_at because the migration intentionally does not backfill.
    $job = HousingJob::factory()->create();
    $milestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'status' => 'in_progress',
        'started_at' => null,
    ]);

    // Loading the page should not stamp anything.
    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->assertSet('jobId', $job->id);

    $milestone->refresh();
    expect($milestone->started_at)->toBeNull();
})->group('regression');
