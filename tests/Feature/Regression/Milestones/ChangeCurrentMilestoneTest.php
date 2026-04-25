<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
    $this->job = HousingJob::where('job_number', 'JOB-FIX-00002')->firstOrFail();
});

it('updateMilestoneStatus mutates the persisted status', function () {
    // HJ-2 has a third milestone ("Construction") in not_started state.
    $milestone = ProjectMilestone::where('housing_job_id', $this->job->id)
        ->where('name', 'Construction')
        ->firstOrFail();
    expect($milestone->status)->toBe('not_started');

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->call('updateMilestoneStatus', $milestone->id, 'in_progress');

    $milestone->refresh();
    expect($milestone->status)->toBe('in_progress');
})->group('regression');

// P-19 pre-image: today, non-completion status changes (e.g. not_started →
// in_progress) record no dedicated "status changed at" timestamp. The
// ProjectManagement::updateMilestoneStatus path only calls ->save() after
// flipping status (see app/Livewire/ProjectManagement.php:332-333), so the
// only time-sensitive column touched is `updated_at`. There is no
// status_changed_at column on project_milestones today.
//
// This test pins that to the schema and the observed behavior. When P-19
// adds a dedicated status-change timestamp column, this test will fail and
// force the author to update the assertion / remove the skip.
it('non-completion status change leaves no status_changed_at column today', function () {
    expect(Schema::hasColumn('project_milestones', 'status_changed_at'))->toBeFalse();
})->group('regression');

it('status change updates only updated_at (not completed_date) for non-completion transitions', function () {
    $milestone = ProjectMilestone::where('housing_job_id', $this->job->id)
        ->where('name', 'Construction')
        ->firstOrFail();

    $completedDateBefore = $milestone->completed_date;

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->call('updateMilestoneStatus', $milestone->id, 'in_progress');

    $milestone->refresh();
    expect($milestone->completed_date)->toEqual($completedDateBefore)
        ->and($milestone->status)->toBe('in_progress');
})->group('regression');
