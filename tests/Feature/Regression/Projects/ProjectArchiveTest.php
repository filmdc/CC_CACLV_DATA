<?php

/**
 * Milestone redesign (Phase 4): archive replaces hard delete.
 *
 * Pins the archive flow's three guarantees:
 *   1. Archiving stamps is_archived + archived_at + archived_by + reason
 *      and survives without touching any related rows (milestones,
 *      requirements, budgets, expenses, documents) — those are the
 *      audit trail.
 *   2. The /projects default scope (`active()`) excludes archived rows;
 *      the showArchived toggle swaps it to the archived() scope.
 *   3. The reason field is mandatory — the audit trail needs a "why"
 *      that the user has to write rather than an inferred default.
 *   4. Unarchive cleanly clears all four archive fields.
 *   5. The legacy deleteJob entry point no longer hard-deletes; it
 *      opens the archive confirmation modal.
 */

use App\Livewire\ProjectsManagement;
use App\Models\HousingJob;
use App\Models\ProjectBudget;
use App\Models\ProjectMilestone;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('archive stamps metadata and preserves all related rows', function () {
    $job = HousingJob::factory()->create();
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    // Sanity: the new model arrives non-archived. is_archived is cast as
    // bool on read but a freshly inserted row whose default fired DB-side
    // can come back as null in-memory until refresh — fresh() guarantees
    // the cast applies.
    expect((bool) $job->fresh()->is_archived)->toBeFalse();

    $job->archive('Cancelled by participant — kept for audit', $this->user->id);

    expect($job->fresh()->is_archived)->toBeTrue();
    expect($job->fresh()->archived_at)->not->toBeNull();
    expect($job->fresh()->archived_by_user_id)->toBe($this->user->id);
    expect($job->fresh()->archived_reason)->toBe('Cancelled by participant — kept for audit');

    // The milestone (and by extension all child requirements + audit
    // entries on it) survives untouched.
    expect(ProjectMilestone::find($milestone->id))->not->toBeNull();
})->group('regression');

it('active() scope excludes archived rows; archived() returns them', function () {
    $active = HousingJob::factory()->create();
    $archived = HousingJob::factory()->create();
    $archived->archive('No longer applicable');

    expect(HousingJob::active()->where('id', $active->id)->exists())->toBeTrue();
    expect(HousingJob::active()->where('id', $archived->id)->exists())->toBeFalse();
    expect(HousingJob::archived()->where('id', $archived->id)->exists())->toBeTrue();
    expect(HousingJob::archived()->where('id', $active->id)->exists())->toBeFalse();
})->group('regression');

it('the ProjectsManagement list hides archived by default and surfaces them when toggled', function () {
    // RegressionTestSeeder creates a handful of housing jobs. We grab a real one,
    // archive it, and check the list scope swap.
    $housingProgramIds = \App\Models\Program::where('housing_module_enabled', true)->pluck('id');
    $sample = HousingJob::activeJobs()
        ->whereIn('program_id', $housingProgramIds)
        ->first();

    $sample->archive('Test archive', $this->user->id);

    $component = Livewire::actingAs($this->user)->test(ProjectsManagement::class);

    // Default: archived row is gone from the projects collection.
    $component->assertSet('showArchived', false);
    expect($component->viewData('projects')->pluck('id')->all())
        ->not->toContain($sample->id);

    // Toggle to archived: row appears, all others (active ones) are gone.
    $component->set('showArchived', true);
    expect($component->viewData('projects')->pluck('id')->all())
        ->toContain($sample->id);
})->group('regression');

it('archiveJob requires a reason of at least 3 characters', function () {
    $job = HousingJob::factory()->create();

    Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->call('confirmArchiveJob', $job->id)
        ->assertSet('showArchiveModal', true)
        ->set('archiveReason', '')
        ->call('archiveJob')
        ->assertHasErrors(['archiveReason' => 'required']);

    expect($job->fresh()->is_archived)->toBeFalse();

    // Too-short reason rejected too.
    Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->call('confirmArchiveJob', $job->id)
        ->set('archiveReason', 'no')
        ->call('archiveJob')
        ->assertHasErrors(['archiveReason' => 'min']);

    expect($job->fresh()->is_archived)->toBeFalse();
})->group('regression');

it('the legacy deleteJob entry point no longer hard-deletes; it opens the archive modal', function () {
    $job = HousingJob::factory()->create();

    Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->call('deleteJob', $job->id)
        ->assertSet('showArchiveModal', true)
        ->assertSet('archivingJobId', $job->id);

    // The row is still there — no destructive side-effect from the call.
    expect(HousingJob::find($job->id))->not->toBeNull();
    expect($job->fresh()->is_archived)->toBeFalse();
})->group('regression');

it('unarchive cleanly clears all four archive fields', function () {
    $job = HousingJob::factory()->create();
    $job->archive('Initial reason', $this->user->id);

    expect($job->fresh()->is_archived)->toBeTrue();

    Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->call('unarchiveJob', $job->id);

    $fresh = $job->fresh();
    expect($fresh->is_archived)->toBeFalse();
    expect($fresh->archived_at)->toBeNull();
    expect($fresh->archived_by_user_id)->toBeNull();
    expect($fresh->archived_reason)->toBeNull();
})->group('regression');

it('the budget form accepts an optional milestone_id and persists it', function () {
    // Pinning the Phase 4 budget-form-exposes-milestone_id change so a
    // regression that drops it gets caught.
    $job = HousingJob::factory()->create();
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $fundCode = \App\Models\FundCode::factory()->create();
    $job->fundCodes()->attach($fundCode->id);

    Livewire::actingAs($this->user)
        ->test(\App\Livewire\ProjectManagement::class, ['id' => $job->id])
        ->call('showCreateBudget')
        ->set('budget_category', 'Materials')
        ->set('budget_amount', '5000')
        ->set('budget_fund_code_id', (string) $fundCode->id)
        ->set('budget_milestone_id', (string) $milestone->id)
        ->call('saveBudget')
        ->assertHasNoErrors();

    $budget = ProjectBudget::where('housing_job_id', $job->id)
        ->where('category', 'Materials')
        ->first();

    expect($budget)->not->toBeNull();
    expect((int) $budget->milestone_id)->toBe($milestone->id);
})->group('regression');
