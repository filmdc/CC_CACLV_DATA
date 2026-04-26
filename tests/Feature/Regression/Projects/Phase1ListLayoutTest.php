<?php

use App\Livewire\ProjectsManagement;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('removes the auto-generated Project # column header from the projects list (P-09)', function () {
    $html = Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->html();

    // The "Project #" column header was the auto-generated job-number column.
    // The job_number is still on the model and reachable via the project
    // detail page; it should just no longer appear as a list column header.
    expect($html)->not->toContain('>Project #<');
})->group('regression');

it('renders Property as the leftmost identifier column, before Participant (P-10)', function () {
    $html = Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->html();

    $propertyHeaderPos = strpos($html, '>Property<');
    $participantHeaderPos = strpos($html, '>Participant<');

    expect($propertyHeaderPos)->not->toBeFalse('Property header missing');
    expect($participantHeaderPos)->not->toBeFalse('Participant header missing');
    expect($propertyHeaderPos)->toBeLessThan($participantHeaderPos);
})->group('regression');

it('shows derived fund codes in the projects list, sourced from associated communication logs (P-11)', function () {
    // RegressionTestSeeder wires CL-4 (with FC-FIX-003) to HJ-1 via both
    // housing_job_id and converted_to_job_id. The list should surface that
    // code on the HJ-1 row without any new project↔fund_code schema.
    $html = Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->assertSee('Fund Codes')
        ->html();

    expect($html)->toContain('FC-FIX-003');
})->group('regression');

it('tightens row density to py-2 on table body cells (P-12)', function () {
    $html = Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->html();

    // Pull just the <tbody> region and check no py-3 cells linger there.
    // py-3 may still appear elsewhere in the page (filter bar, modals).
    preg_match('#<tbody[^>]*>(.*?)</tbody>#s', $html, $matches);
    expect($matches)->not->toBeEmpty('Could not isolate tbody');
    $tbody = $matches[1];

    expect($tbody)->toContain('py-2');
    expect($tbody)->not->toContain('py-3');
})->group('regression');
