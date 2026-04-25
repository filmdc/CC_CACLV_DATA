<?php

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\HousingJob;
use App\Models\Program;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('happy path: converts a participant-linked comm log into a housing job', function () {
    // CL-1 is participant-linked, property-linked, and not yet converted.
    $log = CommunicationLog::where('subject', 'like', 'CL-1:%')->firstOrFail();
    expect($log->converted_to_job_id)->toBeNull();

    $program = Program::where('name', 'Fixture Housing Program')->firstOrFail();
    $jobsBefore = HousingJob::count();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('openConvertModal', $log->id)
        ->assertSet('showConvertModal', true)
        ->assertSet('convertingLogId', $log->id)
        ->set('convertJobTitle', 'Regression: convert CL-1 to project')
        ->set('convertJobDescription', 'Created by regression test.')
        ->set('convertProgramId', $program->id)
        ->call('convertToProject')
        ->assertHasNoErrors();

    expect(HousingJob::count())->toBe($jobsBefore + 1);

    $log->refresh();
    expect($log->converted_to_job_id)->not->toBeNull();

    $job = HousingJob::find($log->converted_to_job_id);
    expect($job)->not->toBeNull()
        ->and($job->job_title)->toBe('Regression: convert CL-1 to project')
        ->and($job->program_id)->toBe($program->id)
        ->and($job->participant_id)->toBe($log->participant_id)
        ->and($job->property_id)->toBe($log->property_id)
        ->and($job->status)->toBe('pending');
})->group('regression');
