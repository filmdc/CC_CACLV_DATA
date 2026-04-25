<?php

use App\Livewire\ProjectsManagement;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;
use Tests\Support\SnapshotHelper;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

// P-11 pre-image: full HTML snapshot of the projects list so any structural
// Phase 1 change (columns added/removed, row wrappers changed, bulk-action bar
// moved) shows up as a diff against projects_list.html.
//
// To intentionally update the snapshot after an approved UI change, run:
//   REGRESSION_UPDATE_SNAPSHOTS=1 php artisan test --group=regression
// then commit the updated file under tests/Support/__snapshots__/.

it('matches the committed HTML snapshot for the projects list', function () {
    $html = Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->html();

    SnapshotHelper::assertHtmlMatchesSnapshot('projects_list', $html);
})->group('regression');
