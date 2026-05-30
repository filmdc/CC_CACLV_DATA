<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;
use Tests\Support\SnapshotHelper;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
    $this->job = HousingJob::where('job_number', 'JOB-FIX-00002')->firstOrFail();
});

// HTML snapshot of the ProjectManagement component for HJ-2 — a project with
// a mid-milestone timeline (Initial Inspection completed, Estimate Approval
// in_progress, Construction not_started). Milestone redesign (Phase 3): the
// legacy 'milestones' tab is now the unified 'work' tab; the snapshot
// captures that rendering. Regenerate after intentional UI changes with
// REGRESSION_UPDATE_SNAPSHOTS=1.
it('matches the committed HTML snapshot for HJ-2 milestone display', function () {
    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->set('activeTab', 'work')
        ->html();

    SnapshotHelper::assertHtmlMatchesSnapshot('milestone_display', $html);
})->group('regression');
