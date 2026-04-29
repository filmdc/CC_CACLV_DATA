<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

it('saves milestone dependencies via the milestone-edit form', function () {
    $job = HousingJob::factory()->create();
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'name' => 'Site Visit']);
    $b = ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'name' => 'Application Receipt']);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showEditMilestone', $a->id)
        ->assertSet('milestone_dependency_ids', [])
        ->set('milestone_dependency_ids', [$b->id])
        ->call('saveMilestone')
        ->assertHasNoErrors();

    expect($a->fresh()->dependencies->pluck('id')->all())->toBe([$b->id]);
});

it('rejects a cyclic dependency through the form and surfaces a flash error', function () {
    $job = HousingJob::factory()->create();
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $b = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    \DB::table('milestone_dependencies')->insert([
        'milestone_id' => $a->id,
        'depends_on_milestone_id' => $b->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showEditMilestone', $b->id)
        // Propose B→A, which combined with A→B closes a cycle.
        ->set('milestone_dependency_ids', [$a->id])
        ->call('saveMilestone');

    // Modal stays open (we returned without closing the form), and B's
    // dependency edges remain unchanged.
    expect($b->fresh()->dependencies->count())->toBe(0);
});

it('replaces (not unions) dependency edges when editing', function () {
    $job = HousingJob::factory()->create();
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $b = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $c = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    \DB::table('milestone_dependencies')->insert([
        'milestone_id' => $a->id,
        'depends_on_milestone_id' => $b->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showEditMilestone', $a->id)
        ->set('milestone_dependency_ids', [$c->id]) // replace B with C
        ->call('saveMilestone')
        ->assertHasNoErrors();

    $deps = $a->fresh()->dependencies->pluck('id')->all();
    expect($deps)->toBe([$c->id]);
    expect($deps)->not->toContain($b->id);
});

it('allows setting an empty dependency list to clear all edges', function () {
    $job = HousingJob::factory()->create();
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $b = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    \DB::table('milestone_dependencies')->insert([
        'milestone_id' => $a->id,
        'depends_on_milestone_id' => $b->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showEditMilestone', $a->id)
        ->set('milestone_dependency_ids', [])
        ->call('saveMilestone')
        ->assertHasNoErrors();

    expect($a->fresh()->dependencies->count())->toBe(0);
});
