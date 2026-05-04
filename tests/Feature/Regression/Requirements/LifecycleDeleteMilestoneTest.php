<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\User;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

it('deletes the milestone immediately when no requirements are attached', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'sequence_order' => 1]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('deleteMilestone', $milestone->id)
        ->assertSet('showDeleteMilestoneModal', false);

    expect(ProjectMilestone::find($milestone->id))->toBeNull();
})->group('regression');

it('opens the keep/delete modal when the milestone has requirements', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    Requirement::factory()->task()->count(3)->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('deleteMilestone', $milestone->id)
        ->assertSet('showDeleteMilestoneModal', true)
        ->assertSet('deleteMilestoneRequirementCount', 3);

    // Modal opened — milestone not yet deleted, requirements intact.
    expect(ProjectMilestone::find($milestone->id))->not->toBeNull();
    expect(Requirement::where('source_id', $milestone->id)->count())->toBe(3);
})->group('regression');

it('keep branch: deletes the milestone but keeps the requirement rows as orphans (source_id null)', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    Requirement::factory()->task()->count(2)->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
        'title' => 'Outlived the milestone',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('deleteMilestone', $milestone->id)
        ->call('confirmDeleteMilestone', false)
        ->assertSet('showDeleteMilestoneModal', false);

    expect(ProjectMilestone::find($milestone->id))->toBeNull();

    $kept = Requirement::where('housing_job_id', $job->id)
        ->where('source_type', Requirement::SOURCE_MILESTONE)
        ->get();
    expect($kept)->toHaveCount(2);
    foreach ($kept as $req) {
        expect($req->source_id)->toBeNull();
        expect($req->title)->toBe('Outlived the milestone');
    }
})->group('regression');

it('delete branch: removes the milestone AND its requirement rows', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    Requirement::factory()->task()->count(2)->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);
    Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('deleteMilestone', $milestone->id)
        ->call('confirmDeleteMilestone', true);

    expect(ProjectMilestone::find($milestone->id))->toBeNull();
    expect(Requirement::where('source_id', $milestone->id)->count())->toBe(0);
    expect(Requirement::where('housing_job_id', $job->id)->count())->toBe(0);
})->group('regression');

it('does not affect requirements attached to OTHER milestones on the same project', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $m1 = ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'sequence_order' => 1]);
    $m2 = ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'sequence_order' => 2]);

    Requirement::factory()->task()->create(['housing_job_id' => $job->id, 'source_type' => Requirement::SOURCE_MILESTONE, 'source_id' => $m1->id]);
    Requirement::factory()->task()->create(['housing_job_id' => $job->id, 'source_type' => Requirement::SOURCE_MILESTONE, 'source_id' => $m2->id]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('deleteMilestone', $m1->id)
        ->call('confirmDeleteMilestone', true);

    expect(ProjectMilestone::find($m1->id))->toBeNull();
    expect(ProjectMilestone::find($m2->id))->not->toBeNull();
    expect(Requirement::where('source_id', $m2->id)->count())->toBe(1);
    expect(Requirement::where('source_id', $m1->id)->count())->toBe(0);
})->group('regression');
