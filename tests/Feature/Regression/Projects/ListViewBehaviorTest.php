<?php

use App\Livewire\ProjectsManagement;
use App\Models\HousingJob;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('renders all 4 non-template housing jobs in the projects list', function () {
    expect(HousingJob::where('is_template', false)->count())->toBe(4)
        ->and(HousingJob::where('is_template', true)->count())->toBe(1);

    $component = Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->assertSee('JOB-FIX-00001')
        ->assertSee('JOB-FIX-00002')
        ->assertSee('JOB-FIX-00003')
        ->assertSee('JOB-FIX-00004');

    // Template job should NOT be visible by default (showTemplates is false).
    $component->assertDontSee('JOB-FIX-TPL-00001');
})->group('regression');

it('template library modal surfaces the seeded template job', function () {
    // Template job is rendered inside the Template Library modal, toggled by
    // the `showTemplateModal` public property. The view uses the
    // `template_name` field (not `job_number`) for display.
    Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->set('showTemplateModal', true)
        ->assertSee('Standard Roofing Workflow');
})->group('regression');

it('status filter narrows list to matching jobs only', function () {
    Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->set('filterStatus', 'completed')
        ->assertSee('JOB-FIX-00003')
        ->assertDontSee('JOB-FIX-00001')
        ->assertDontSee('JOB-FIX-00002')
        ->assertDontSee('JOB-FIX-00004');
})->group('regression');

it('orders non-template jobs newest-first by created_at in the paginated result', function () {
    // Assert on the viewData (the paginated $projects collection) rather than
    // searching the raw HTML for job numbers — the page renders filter
    // dropdowns etc. that mention jobs in their own order, which would make a
    // text-position assertion brittle.
    $component = Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class);

    $projects = $component->viewData('projects');
    $ordered = $projects->pluck('job_number')->all();

    // Seeder created_at values:
    //   HJ-4 = 2026-04-01 (newest)
    //   HJ-1 = 2026-03-04
    //   HJ-2 = 2026-01-15
    //   HJ-3 = 2025-10-01 (oldest)
    // Template is excluded by activeJobs() scope.
    expect($ordered)->toBe([
        'JOB-FIX-00004',
        'JOB-FIX-00001',
        'JOB-FIX-00002',
        'JOB-FIX-00003',
    ]);
})->group('regression');
