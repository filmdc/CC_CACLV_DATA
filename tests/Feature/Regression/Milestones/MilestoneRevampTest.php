<?php

/*
| Milestone revamp:
|   - ProjectManagement lands on the "requirements" tab by default.
|   - Milestone create/edit form exposes three new multi-selects:
|       * fund codes served by the milestone (pins
|         fund_code_housing_job.milestone_id),
|       * budget items the milestone owns
|         (project_budgets.milestone_id),
|       * Phase 2 requirement definitions (atoms) — each selection
|         materialises a milestone-source Requirement with
|         source_template_id pointing at the definition.
|   - HousingJob::createFromTemplate remaps the milestone_id on
|     cloned budgets to the freshly-cloned milestone.
*/

use App\Livewire\ProjectManagement;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\Program;
use App\Models\ProjectBudget;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\RequirementDefinition;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

function revampJob(array $overrides = []): HousingJob
{
    $program = Program::first()
        ?? Program::factory()->create(['housing_module_enabled' => true]);
    if (! $program->housing_module_enabled) {
        $program->update(['housing_module_enabled' => true]);
    }

    return HousingJob::factory()->create(array_merge([
        'program_id' => $program->id,
        'is_template' => false,
        'project_manager_id' => test()->user->id,
    ], $overrides));
}

it('ProjectManagement lands on the unified Work tab by default (formerly Requirements)', function () {
    // Milestone redesign (Phase 3): the legacy Requirements + Milestones&Tasks
    // tabs collapsed into a single 'work' tab. mount() normalizes any old
    // saved activeTab='requirements' to 'work' so deep links keep landing.
    $job = revampJob();

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->assertSet('activeTab', 'work');
})->group('regression');

it('saving a milestone with selected fund codes pins milestone_id on the project pivot', function () {
    $job = revampJob();
    $code = FundCode::factory()->create();
    $job->fundCodes()->attach($code->id);

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showCreateMilestone')
        ->set('milestone_name', 'Build phase')
        ->set('milestone_icon', 'Circle')
        ->set('milestone_color', '#6366f1')
        ->set('milestone_fund_code_ids', [$code->id])
        ->call('saveMilestone');

    $milestone = ProjectMilestone::where('housing_job_id', $job->id)
        ->where('name', 'Build phase')->firstOrFail();

    $pivot = DB::table('fund_code_housing_job')
        ->where('housing_job_id', $job->id)
        ->where('fund_code_id', $code->id)
        ->first();

    expect((int) $pivot->milestone_id)->toBe($milestone->id);
})->group('regression');

it('saving a milestone with selected budget items sets project_budgets.milestone_id', function () {
    $job = revampJob();
    $budget = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'milestone_id' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showCreateMilestone')
        ->set('milestone_name', 'Closeout phase')
        ->set('milestone_icon', 'Circle')
        ->set('milestone_color', '#6366f1')
        ->set('milestone_budget_ids', [$budget->id])
        ->call('saveMilestone');

    $milestone = ProjectMilestone::where('housing_job_id', $job->id)
        ->where('name', 'Closeout phase')->firstOrFail();

    expect((int) $budget->fresh()->milestone_id)->toBe($milestone->id);
})->group('regression');

it('saving a milestone with selected Phase 2 atoms materialises milestone-source requirements', function () {
    $job = revampJob();
    $atom = RequirementDefinition::factory()->create([
        'key' => 'roof-inspection',
        'label' => 'Roof inspection',
        'kind' => RequirementDefinition::KIND_DOC,
        'category' => RequirementDefinition::CATEGORY_SERVICE_DELIVERY,
    ]);

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showCreateMilestone')
        ->set('milestone_name', 'Inspection phase')
        ->set('milestone_icon', 'Circle')
        ->set('milestone_color', '#6366f1')
        ->set('milestone_requirement_definition_ids', [$atom->id])
        ->call('saveMilestone');

    $milestone = ProjectMilestone::where('housing_job_id', $job->id)
        ->where('name', 'Inspection phase')->firstOrFail();

    $req = Requirement::where('housing_job_id', $job->id)
        ->where('source_type', Requirement::SOURCE_MILESTONE)
        ->where('source_id', $milestone->id)
        ->where('source_template_id', $atom->id)
        ->first();

    expect($req)->not->toBeNull();
    expect($req->title)->toBe('Roof inspection');
    expect($req->type)->toBe(Requirement::TYPE_DOCUMENT);
})->group('regression');

it('deselecting a Phase 2 atom on a milestone unlinks the atom but preserves the Requirement row', function () {
    $job = revampJob();
    $atom = RequirementDefinition::factory()->create([
        'category' => RequirementDefinition::CATEGORY_SERVICE_DELIVERY,
        'kind' => RequirementDefinition::KIND_DOC,
        'label' => 'Permit copy',
    ]);

    // Create the milestone with the atom.
    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showCreateMilestone')
        ->set('milestone_name', 'Permits')
        ->set('milestone_icon', 'Circle')
        ->set('milestone_color', '#6366f1')
        ->set('milestone_requirement_definition_ids', [$atom->id])
        ->call('saveMilestone');

    $milestone = ProjectMilestone::where('housing_job_id', $job->id)
        ->where('name', 'Permits')->firstOrFail();
    $req = Requirement::where('source_type', Requirement::SOURCE_MILESTONE)
        ->where('source_id', $milestone->id)
        ->where('source_template_id', $atom->id)
        ->firstOrFail();

    // Edit the same milestone and clear the atom selection.
    $component
        ->call('showEditMilestone', $milestone->id)
        ->set('milestone_requirement_definition_ids', [])
        ->call('saveMilestone');

    // Row persists; the atom link is gone.
    $req->refresh();
    expect($req->exists)->toBeTrue();
    expect($req->source_template_id)->toBeNull();
})->group('regression');

it('createFromTemplate remaps milestone_id on cloned budgets to the freshly-cloned milestone', function () {
    $program = Program::first();
    if (! $program?->housing_module_enabled) {
        $program?->update(['housing_module_enabled' => true]);
    }

    // Build a template with one milestone + one budget linked to it.
    $template = HousingJob::factory()->create([
        'program_id' => $program->id,
        'is_template' => true,
        'template_name' => 'T1',
    ]);
    $tplMilestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $template->id,
        'name' => 'Build',
        'sequence_order' => 1,
    ]);
    ProjectBudget::factory()->create([
        'housing_job_id' => $template->id,
        'category' => 'Roofing',
        'budgeted_amount' => 1000,
        'milestone_id' => $tplMilestone->id,
    ]);

    // Instantiate from template.
    $newJob = HousingJob::createFromTemplate($template, [
        'job_title' => 'Cloned job',
        'request_date' => now()->toDateString(),
        'priority' => 'medium',
        'status' => 'pending',
        'program_id' => $program->id,
    ]);

    $newMilestone = ProjectMilestone::where('housing_job_id', $newJob->id)
        ->where('name', 'Build')->firstOrFail();
    $newBudget = ProjectBudget::where('housing_job_id', $newJob->id)
        ->where('category', 'Roofing')->firstOrFail();

    expect((int) $newBudget->milestone_id)->toBe($newMilestone->id);
    // And the template's milestone_id was not carried verbatim.
    expect($newBudget->milestone_id)->not->toBe($tplMilestone->id);
})->group('regression');
