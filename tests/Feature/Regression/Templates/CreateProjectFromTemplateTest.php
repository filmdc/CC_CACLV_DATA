<?php

use App\Livewire\ProjectsManagement;
use App\Models\HousingJob;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Property;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('createFromTemplate + saveJobFromTemplate creates a new active job from the template', function () {
    $template = HousingJob::where('is_template', true)->firstOrFail();
    expect($template->template_name)->toBe('Standard Roofing Workflow');

    // Pick a head-of-household participant that has a property linked.
    $participant = Participant::where('is_head_of_household', true)
        ->whereHas('properties')
        ->first();
    expect($participant)->not->toBeNull();
    $property = $participant->properties()->first();
    $program = Program::where('name', 'Fixture Housing Program')->firstOrFail();

    $activeJobsBefore = HousingJob::where('is_template', false)->count();

    Livewire::actingAs($this->user)
        ->test(ProjectsManagement::class)
        ->call('createFromTemplate', $template->id)
        ->assertSet('selectedTemplateId', $template->id)
        ->assertSet('showCreateForm', true)
        ->set('participant_id', $participant->id)
        ->set('property_id', $property->id)
        ->set('program_id', $program->id)
        ->set('job_title', 'Regression: project from template')
        ->set('job_description', 'Created from Standard Roofing Workflow template by regression test.')
        ->set('status', 'pending')
        ->set('request_date', '2026-04-15')
        ->set('priority', 'medium')
        ->call('saveJobFromTemplate')
        ->assertHasNoErrors();

    expect(HousingJob::where('is_template', false)->count())->toBe($activeJobsBefore + 1);

    $job = HousingJob::where('job_title', 'Regression: project from template')->first();
    // `is_template` is stored tinyint(1) and NOT cast on the model today,
    // so raw reads return an int. Use loose-equals / explicit cast.
    expect($job)->not->toBeNull()
        ->and((bool) $job->is_template)->toBeFalse()
        ->and($job->participant_id)->toBe($participant->id)
        ->and($job->property_id)->toBe($property->id)
        ->and($job->program_id)->toBe($program->id)
        ->and($job->household_id)->toBe($participant->household_id);
})->group('regression');
