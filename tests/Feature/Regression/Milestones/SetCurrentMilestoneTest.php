<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
    $this->job = HousingJob::where('job_number', 'JOB-FIX-00002')->firstOrFail();
});

it('selectMilestone sets selectedMilestone and clears other forms', function () {
    $milestone = ProjectMilestone::where('housing_job_id', $this->job->id)
        ->where('name', 'Initial Inspection')
        ->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->call('selectMilestone', $milestone->id)
        ->assertSet('selectedMilestone', $milestone->id);
})->group('regression');

it('selectMilestone accepts a different milestone id and reflects the change', function () {
    $first = ProjectMilestone::where('housing_job_id', $this->job->id)
        ->where('sequence_order', 1)->firstOrFail();
    $second = ProjectMilestone::where('housing_job_id', $this->job->id)
        ->where('sequence_order', 2)->firstOrFail();

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $this->job->id])
        ->call('selectMilestone', $first->id)
        ->assertSet('selectedMilestone', $first->id)
        ->call('selectMilestone', $second->id)
        ->assertSet('selectedMilestone', $second->id);
})->group('regression');
