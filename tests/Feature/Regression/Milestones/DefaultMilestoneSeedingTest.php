<?php

/**
 * Milestone redesign (Phase 1): DefaultMilestoneSeeder behavior.
 *
 * Confirms the new Comm Log → Convert to Project path auto-seeds a
 * milestone (plus its single canonical gating Requirement) for every
 * RequirementDefinition flagged is_default_milestone=true. Also pins
 * the idempotency guard and the negative cases (non-flagged atoms,
 * soft-deleted atoms, repeat invocation).
 */

use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\RequirementDefinition;
use App\Services\DefaultMilestoneSeeder;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('seeds one milestone per is_default_milestone atom and creates the canonical Requirement', function () {
    $envReview = RequirementDefinition::factory()
        ->category(RequirementDefinition::CATEGORY_SERVICE_DELIVERY)
        ->kind(RequirementDefinition::KIND_DOC)
        ->create(['label' => 'Environmental Review', 'is_default_milestone' => true]);

    $leadPaint = RequirementDefinition::factory()
        ->category(RequirementDefinition::CATEGORY_SERVICE_DELIVERY)
        ->kind(RequirementDefinition::KIND_DOC)
        ->create(['label' => 'Lead Paint Clearance', 'is_default_milestone' => true]);

    $job = HousingJob::factory()->create();

    $created = app(DefaultMilestoneSeeder::class)->seed($job, $this->user);

    expect($created)->toHaveCount(2);

    expect($job->milestones()->whereNotNull('requirement_definition_id')->count())->toBe(2);

    $envMilestone = ProjectMilestone::where('housing_job_id', $job->id)
        ->where('requirement_definition_id', $envReview->id)
        ->first();

    expect($envMilestone)->not->toBeNull();
    expect($envMilestone->name)->toBe('Environmental Review');
    expect($envMilestone->isPromoted())->toBeTrue();

    // Each promoted milestone gets one canonical gating Requirement,
    // source_template_id tied back to the library atom.
    $req = Requirement::where('housing_job_id', $job->id)
        ->where('source_type', Requirement::SOURCE_MILESTONE)
        ->where('source_id', $envMilestone->id)
        ->first();

    expect($req)->not->toBeNull();
    expect($req->source_template_id)->toBe($envReview->id);
    expect($req->type)->toBe(Requirement::TYPE_DOCUMENT);
    expect($req->status)->toBe(Requirement::STATUS_OUTSTANDING);
})->group('regression');

it('is idempotent — calling seed twice does not duplicate milestones', function () {
    RequirementDefinition::factory()
        ->category(RequirementDefinition::CATEGORY_SERVICE_DELIVERY)
        ->create(['label' => 'Title Verification', 'is_default_milestone' => true]);

    $job = HousingJob::factory()->create();
    $seeder = app(DefaultMilestoneSeeder::class);

    $first = $seeder->seed($job, $this->user);
    $second = $seeder->seed($job, $this->user);

    expect($first)->toHaveCount(1);
    expect($second)->toHaveCount(0);
    expect($job->milestones()->count())->toBe(1);
})->group('regression');

it('skips atoms not flagged is_default_milestone', function () {
    RequirementDefinition::factory()
        ->category(RequirementDefinition::CATEGORY_SERVICE_DELIVERY)
        ->create(['label' => 'Routine Phase 2 thing', 'is_default_milestone' => false]);

    RequirementDefinition::factory()
        ->category(RequirementDefinition::CATEGORY_ELIGIBILITY)
        ->create(['label' => 'Photo ID', 'is_default_milestone' => true]);  // wrong phase but still flagged

    $job = HousingJob::factory()->create();

    $created = app(DefaultMilestoneSeeder::class)->seed($job, $this->user);

    // is_default_milestone is the sole gate — category is admin's
    // problem, not the seeder's. So the eligibility-but-flagged atom
    // DOES seed; the unflagged Phase 2 atom does NOT.
    expect($created)->toHaveCount(1);
    expect($created->first()->name)->toBe('Photo ID');
})->group('regression');

it('skips soft-deleted library atoms', function () {
    $atom = RequirementDefinition::factory()
        ->category(RequirementDefinition::CATEGORY_SERVICE_DELIVERY)
        ->create(['label' => 'Deprecated Requirement', 'is_default_milestone' => true]);

    $atom->delete();

    $job = HousingJob::factory()->create();
    $created = app(DefaultMilestoneSeeder::class)->seed($job, $this->user);

    expect($created)->toHaveCount(0);
})->group('regression');

it('preserves existing sequence_order when seeding alongside PM-composed milestones', function () {
    RequirementDefinition::factory()
        ->category(RequirementDefinition::CATEGORY_SERVICE_DELIVERY)
        ->create(['label' => 'Seeded', 'is_default_milestone' => true]);

    $job = HousingJob::factory()->create();

    // PM-composed milestone already on the project.
    $existing = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'sequence_order' => 5,
        'requirement_definition_id' => null,
    ]);

    $created = app(DefaultMilestoneSeeder::class)->seed($job, $this->user);

    expect($created)->toHaveCount(1);
    expect($created->first()->sequence_order)->toBe(6);
    expect($existing->fresh()->sequence_order)->toBe(5);
})->group('regression');
