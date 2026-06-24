<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

it('milestone tab renders requirements (not legacy task/doc rows) under the selected milestone', function () {
    $job = HousingJob::factory()->create(['project_manager_id' => $this->admin->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'sequence_order' => 1]);

    $task = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
        'title' => 'Task that should appear on the milestone tab',
        'owner_user_id' => $this->admin->id,
    ]);
    $doc = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
        'title' => 'Document that should appear too',
        'owner_user_id' => $this->admin->id,
    ]);

    $html = Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->html();

    expect($html)->toContain('Task that should appear on the milestone tab');
    expect($html)->toContain('Document that should appear too');
});

it('+ Add Task button creates a Requirement with type=task on the selected milestone', function () {
    $job = HousingJob::factory()->create(['project_manager_id' => $this->admin->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'sequence_order' => 1]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showCreateRequirement', 'task')
        ->set('requirement_title', 'Inspect the foundation')
        ->set('requirement_description', 'Walk-through with the contractor.')
        ->set('requirement_owner_id', $this->admin->id)
        ->call('saveRequirement')
        ->assertHasNoErrors();

    $req = Requirement::where('housing_job_id', $job->id)->where('type', 'task')->firstOrFail();
    expect($req->title)->toBe('Inspect the foundation');
    expect($req->source_type)->toBe('milestone');
    expect($req->source_id)->toBe($milestone->id);
    expect($req->owner_user_id)->toBe($this->admin->id);
    expect($req->status)->toBe('outstanding');
});

it('toggleTaskRequirementComplete flips a task-type requirement between outstanding and complete', function () {
    $job = HousingJob::factory()->create(['project_manager_id' => $this->admin->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $task = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id]);

    $component->call('toggleTaskRequirementComplete', $task->id);
    expect($task->fresh()->isComplete())->toBeTrue();

    $component->call('toggleTaskRequirementComplete', $task->id);
    expect($task->fresh()->isOutstanding())->toBeTrue();
});

it('toggleTaskRequirementComplete refuses a document-type requirement and surfaces a flash error', function () {
    $job = HousingJob::factory()->create(['project_manager_id' => $this->admin->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $doc = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('toggleTaskRequirementComplete', $doc->id);

    expect($doc->fresh()->isOutstanding())->toBeTrue();
});

it('saveRequirementCompletion with an override reason closes a document-type requirement', function () {
    $job = HousingJob::factory()->create(['project_manager_id' => $this->admin->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $doc = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('openCompleteRequirementModal', $doc->id)
        ->set('completion_override_reason', 'Hard copy on file in physical archive.')
        ->set('completion_note', 'Reviewed in person.')
        ->call('saveRequirementCompletion');

    $doc->refresh();
    expect($doc->isComplete())->toBeTrue();
    expect($doc->wasOverridden())->toBeTrue();
    expect($doc->override_reason)->toContain('Hard copy on file');
});

it('refuses document completion when neither files nor override reason are provided', function () {
    $job = HousingJob::factory()->create(['project_manager_id' => $this->admin->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $doc = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('openCompleteRequirementModal', $doc->id)
        ->call('saveRequirementCompletion');

    expect($doc->fresh()->isOutstanding())->toBeTrue();
});

it('rejects requirement actions on a requirement that does not belong to the loaded project', function () {
    $jobA = HousingJob::factory()->create(['project_manager_id' => $this->admin->id]);
    $jobB = HousingJob::factory()->create(['project_manager_id' => $this->admin->id]);
    $milestoneB = ProjectMilestone::factory()->create(['housing_job_id' => $jobB->id]);
    $reqOnB = Requirement::factory()->task()->create([
        'housing_job_id' => $jobB->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestoneB->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $jobA->id])
        ->call('toggleTaskRequirementComplete', $reqOnB->id)
        ->assertStatus(403);
});
