<?php

use App\Livewire\ProjectsManagement;
use App\Models\HousingJob;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Property;
use App\Models\Service;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

// Pins the housing-only scope on the projects management page. The fixture
// already provides one housing-enabled program (Fixture Housing Program) +
// 4 jobs (JOB-FIX-00001..00004). This test adds a second program with
// housing_module_enabled = false and a job hung off it — the deliberate
// "drift" case the new scope is supposed to hide.

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();

    $this->housingProgram = Program::where('name', 'Fixture Housing Program')->firstOrFail();

    $this->nonHousingProgram = Program::factory()->create([
        'name' => 'Fixture Non-Housing Program',
        'description' => 'Deliberate non-housing program for housing-scope regression test.',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'active' => true,
        'housing_module_enabled' => false,
    ]);

    // A drift-case housing job hanging off the non-housing program.
    $participant = Participant::query()->where('is_head_of_household', true)->firstOrFail();
    $property = Property::query()->where('participant_id', $participant->id)->firstOrFail();

    $this->driftJob = HousingJob::factory()->create([
        'participant_id' => $participant->id,
        'household_id' => $participant->household_id,
        'property_id' => $property->id,
        'program_id' => $this->nonHousingProgram->id,
        'job_number' => 'JOB-FIX-DRIFT-00001',
        'job_title' => 'HJ-DRIFT: Job hung off a non-housing program',
        'is_template' => false,
    ]);

    // One service on each program so the service-filter assertion can compare.
    $this->housingService = Service::factory()->create([
        'program_id' => $this->housingProgram->id,
        'name' => 'Fixture Housing Service',
        'active' => true,
    ]);

    $this->nonHousingService = Service::factory()->create([
        'program_id' => $this->nonHousingProgram->id,
        'name' => 'Fixture Non-Housing Service',
        'active' => true,
    ]);
});

it('excludes jobs whose program is not housing_module_enabled', function () {
    $projects = Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->viewData('projects');

    $jobNumbers = $projects->pluck('job_number')->all();

    expect($jobNumbers)
        ->toContain('JOB-FIX-00001')
        ->toContain('JOB-FIX-00002')
        ->toContain('JOB-FIX-00003')
        ->toContain('JOB-FIX-00004')
        ->not->toContain('JOB-FIX-DRIFT-00001');
})->group('regression');

it('only lists housing-enabled programs in the program filter dropdown', function () {
    $programs = Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->viewData('programs');

    $names = $programs->pluck('name')->all();

    expect($names)
        ->toContain('Fixture Housing Program')
        ->not->toContain('Fixture Non-Housing Program');
})->group('regression');

it('restricts the default service filter to services on housing-enabled programs', function () {
    $services = Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->set('filterProgram', '') // no program selected — fall back to housing-only services
        ->viewData('servicesForFilter');

    $names = $services->pluck('name')->all();

    expect($names)
        ->toContain('Fixture Housing Service')
        ->not->toContain('Fixture Non-Housing Service');
})->group('regression');

it('auto-selects a housing-enabled program when showCreateJob is invoked', function () {
    $component = Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->call('showCreateJob');

    $housingProgramIds = Program::where('housing_module_enabled', true)->pluck('id')->all();

    expect($component->get('program_id'))
        ->not->toBeEmpty()
        ->and((int) $component->get('program_id'))->toBeIn($housingProgramIds);
})->group('regression');

it('renders the /projects route with HTTP 200 after the housing-scope changes', function () {
    $response = $this->actingAs($this->user)->get('/projects');

    expect($response->status())->toBe(
        200,
        "GET /projects returned {$response->status()}, expected 200. ".
        'Body excerpt: '.substr(strip_tags((string) $response->getContent()), 0, 200)
    );
})->group('regression');
