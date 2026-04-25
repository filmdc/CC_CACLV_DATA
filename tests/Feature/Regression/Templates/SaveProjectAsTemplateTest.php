<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('saveAsTemplate creates a new is_template=true job from an existing project', function () {
    // HJ-3 (completed) has 4 milestones. Save it as a template.
    $source = HousingJob::where('job_number', 'JOB-FIX-00003')->firstOrFail();
    expect($source->milestones()->count())->toBe(4);

    $templatesBefore = HousingJob::where('is_template', true)->count();

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $source->id])
        ->set('template_name', 'Regression: saved-from-HJ-3 template')
        ->set('template_description', 'Derived from the completed HJ-3 for regression.')
        ->call('saveAsTemplate')
        ->assertHasNoErrors();

    expect(HousingJob::where('is_template', true)->count())->toBe($templatesBefore + 1);

    $template = HousingJob::where('is_template', true)
        ->where('template_name', 'Regression: saved-from-HJ-3 template')
        ->first();
    // `is_template` is tinyint(1) and NOT cast on the model today.
    expect($template)->not->toBeNull()
        ->and((bool) $template->is_template)->toBeTrue()
        ->and($template->template_description)->toBe('Derived from the completed HJ-3 for regression.')
        ->and($template->milestones()->count())->toBe(4); // copied from source
})->group('regression');

it('HousingJob::templates scope returns only is_template=true rows', function () {
    $templateCount = HousingJob::templates()->count();
    $totalTemplates = HousingJob::where('is_template', true)->count();

    expect($templateCount)->toBe($totalTemplates)
        ->and($templateCount)->toBe(1); // seeder creates exactly one
})->group('regression');
