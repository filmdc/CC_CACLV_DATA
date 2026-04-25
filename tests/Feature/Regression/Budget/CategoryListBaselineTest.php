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
    $this->job = HousingJob::where('job_number', 'JOB-FIX-00004')->firstOrFail();
});

// P-22 pre-image: HTML snapshot of HJ-4's ProjectManagement page — a project
// with two contract-attached budget categories (Roofing, Plumbing) and a
// stack of 3 Roofing expenses summing to 7000. Captures current budget UI
// layout / column order / money formatting so a Phase 1 P-22 restructure
// shows as a diff in budget_categories_with_contract.html.
it('matches the committed snapshot for HJ-4 budget categories (with contract)', function () {
    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->html();

    SnapshotHelper::assertHtmlMatchesSnapshot('budget_categories_with_contract', $html);
})->group('regression');
