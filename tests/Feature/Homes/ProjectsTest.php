<?php

use App\Livewire\Homes\ProjectDetail;
use App\Livewire\Homes\Projects;
use App\Models\HousingJob;
use App\Models\Permission;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

function projectsPortalUser(array $permissionNames = []): User
{
    $role = Role::firstOrCreate(['name' => 'projects_portal_test'], ['display_name' => 'Projects Portal Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

it('gates the projects list on projects.view', function () {
    $this->actingAs(projectsPortalUser([]));
    $this->get(route('homes.projects'))->assertForbidden();

    $this->actingAs(projectsPortalUser(['projects.view']));
    $this->get(route('homes.projects'))->assertOk();
});

it('maps plain-language status: finished / attention / active', function () {
    $finished = HousingJob::factory()->create(['status' => 'completed']);
    expect(Projects::plainStatus($finished->load('milestones', 'budgets')))->toBe('finished');

    $overdue = HousingJob::factory()->create(['status' => 'in_progress']);
    ProjectMilestone::factory()->create([
        'housing_job_id' => $overdue->id,
        'status' => 'in_progress',
        'target_date' => now()->subWeek()->toDateString(),
    ]);
    expect(Projects::plainStatus($overdue->load('milestones', 'budgets')))->toBe('attention');

    $active = HousingJob::factory()->create(['status' => 'in_progress']);
    ProjectMilestone::factory()->create([
        'housing_job_id' => $active->id,
        'status' => 'in_progress',
        'target_date' => now()->addMonth()->toDateString(),
    ]);
    expect(Projects::plainStatus($active->load('milestones', 'budgets')))->toBe('active');
});

it('lists projects and filters the attention pill', function () {
    $this->actingAs(projectsPortalUser(['projects.view']));

    $calm = HousingJob::factory()->create(['status' => 'in_progress', 'job_title' => 'Calm siding job']);
    $hot = HousingJob::factory()->create(['status' => 'in_progress', 'job_title' => 'Hot roof job']);
    ProjectMilestone::factory()->create([
        'housing_job_id' => $hot->id,
        'status' => 'in_progress',
        'target_date' => now()->subDays(3)->toDateString(),
    ]);

    Livewire::test(Projects::class)
        ->set('pill', 'all')
        ->assertSee('Calm siding job')
        ->assertSee('Hot roof job')
        ->call('setPill', 'attention')
        ->assertSee('Hot roof job')
        ->assertDontSee('Calm siding job');
});

it('shows milestone requirements via the union (milestone-source rows)', function () {
    $this->actingAs(projectsPortalUser(['projects.view']));

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $milestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'name' => 'Environmental review',
        'status' => 'in_progress',
        'sequence_order' => 1,
    ]);
    Requirement::create([
        'housing_job_id' => $job->id,
        'type' => Requirement::TYPE_TASK,
        'title' => 'Attachment A (county)',
        'status' => Requirement::STATUS_OUTSTANDING,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    // mount() auto-expands the first in-progress milestone, so the
    // requirement rows are visible immediately.
    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->assertSet('openMilestone', $milestone->id)
        ->assertSee('Environmental review')
        ->assertSee('Attachment A (county)')
        ->assertSee('0/1 reqs');
});

it('completes a task requirement one-click', function () {
    $user = projectsPortalUser(['projects.view']);
    $this->actingAs($user);

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'sequence_order' => 1]);
    $req = Requirement::create([
        'housing_job_id' => $job->id,
        'type' => Requirement::TYPE_TASK,
        'title' => 'Schedule inspector',
        'status' => Requirement::STATUS_OUTSTANDING,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('completeTaskRequirement', $req->id)
        ->assertHasNoErrors();

    $fresh = $req->fresh();
    expect($fresh->status)->toBe(Requirement::STATUS_COMPLETE);
    expect($fresh->completed_by_user_id)->toBe($user->id);
});

it('refuses document-requirement completion without an upload', function () {
    $this->actingAs(projectsPortalUser(['projects.view']));

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'sequence_order' => 1]);
    $req = Requirement::create([
        'housing_job_id' => $job->id,
        'type' => Requirement::TYPE_DOCUMENT,
        'title' => 'Roofing permit',
        'status' => Requirement::STATUS_OUTSTANDING,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('openCompletionModal', $req->id)
        ->call('saveRequirementCompletion')
        ->assertHasErrors(['completionFiles']);

    expect($req->fresh()->status)->toBe(Requirement::STATUS_OUTSTANDING);
});

it('adds an expense and the observer recomputes the budget spent amount', function () {
    $this->actingAs(projectsPortalUser(['projects.view']));

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $budget = ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'category' => 'Roofing',
        'budgeted_amount' => 1000,
    ]);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('openExpense')
        ->set('expenseCategory', 'Roofing')
        ->set('expenseAmount', '250.00')
        ->set('expenseDate', now()->toDateString())
        ->set('expensePaidTo', 'McAllister HVAC')
        ->call('saveExpense')
        ->assertHasNoErrors();

    $expense = ProjectExpense::where('housing_job_id', $job->id)->first();
    expect($expense)->not->toBeNull();
    expect($expense->description)->toContain('McAllister HVAC');
    expect((float) $budget->fresh()->spent_amount)->toBe(250.0);
});

it('blocks expense actions on requirements of another job', function () {
    $this->actingAs(projectsPortalUser(['projects.view']));

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $otherJob = HousingJob::factory()->create(['status' => 'in_progress']);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $otherJob->id, 'sequence_order' => 1]);
    $foreignReq = Requirement::create([
        'housing_job_id' => $otherJob->id,
        'type' => Requirement::TYPE_TASK,
        'title' => 'Foreign requirement',
        'status' => Requirement::STATUS_OUTSTANDING,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('completeTaskRequirement', $foreignReq->id)
        ->assertForbidden();
});

it('edits the project fields from the portal', function () {
    $user = projectsPortalUser(['projects.view']);
    $this->actingAs($user);

    $job = HousingJob::factory()->create(['status' => 'pending']);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('openEditJob')
        ->set('editJob.job_title', 'Roof + kitchen ceiling')
        ->set('editJob.status', 'in_progress')
        ->set('editJob.priority', 'high')
        ->set('editJob.target_completion_date', now()->addMonths(2)->toDateString())
        ->call('saveEditJob')
        ->assertHasNoErrors();

    $fresh = $job->fresh();
    expect($fresh->job_title)->toBe('Roof + kitchen ceiling');
    expect($fresh->status)->toBe('in_progress');
    expect($fresh->priority)->toBe('high');
    expect($fresh->updated_by)->toBe($user->id);
});

it('adds a milestone with appended sequence order', function () {
    $this->actingAs(projectsPortalUser(['projects.view']));

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'sequence_order' => 1, 'status' => 'in_progress']);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('openMilestoneForm')
        ->set('milestoneName', 'Final inspection')
        ->set('milestoneTargetDate', now()->addMonth()->toDateString())
        ->call('saveMilestone')
        ->assertHasNoErrors();

    $milestone = $job->milestones()->where('name', 'Final inspection')->first();
    expect($milestone)->not->toBeNull();
    expect($milestone->sequence_order)->toBe(2);
    expect($milestone->status)->toBe('not_started');
});

it('starts and completes a milestone', function () {
    $this->actingAs(projectsPortalUser(['projects.view']));

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $milestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'sequence_order' => 1,
        'status' => 'not_started',
    ]);

    $component = Livewire::test(ProjectDetail::class, ['job' => $job]);

    $component->call('updateMilestoneStatus', $milestone->id, 'in_progress');
    expect($milestone->fresh()->status)->toBe('in_progress');

    $component->call('updateMilestoneStatus', $milestone->id, 'completed');
    expect($milestone->fresh()->status)->toBe('completed');
});

it('adds a requirement to a milestone via the instantiator', function () {
    $user = projectsPortalUser(['projects.view']);
    $this->actingAs($user);

    $job = HousingJob::factory()->create(['status' => 'in_progress', 'project_manager_id' => $user->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id, 'sequence_order' => 1]);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('openAddRequirement', $milestone->id)
        ->set('newReqTitle', 'Roofing permit')
        ->set('newReqType', 'document')
        ->call('saveNewRequirement')
        ->assertHasNoErrors();

    $req = Requirement::where('housing_job_id', $job->id)->where('title', 'Roofing permit')->first();
    expect($req)->not->toBeNull();
    expect($req->source_type)->toBe(Requirement::SOURCE_MILESTONE);
    expect($req->source_id)->toBe($milestone->id);
});

it('adds a budget line tied to an attached fund code', function () {
    $this->actingAs(projectsPortalUser(['projects.view']));

    $fund = \App\Models\FundCode::factory()->create();
    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $job->fundCodes()->attach($fund->id);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('openBudgetForm')
        ->set('budgetCategory', 'Roofing')
        ->set('budgetAmount', '5000')
        ->set('budgetFundCodeId', $fund->id)
        ->call('saveBudgetLine')
        ->assertHasNoErrors();

    $budget = ProjectBudget::where('housing_job_id', $job->id)->where('category', 'Roofing')->first();
    expect($budget)->not->toBeNull();
    expect((float) $budget->budgeted_amount)->toBe(5000.0);
    expect($budget->fund_code_id)->toBe($fund->id);
});

it('rejects a budget line whose fund is not attached to the project', function () {
    $this->actingAs(projectsPortalUser(['projects.view']));

    $unattached = \App\Models\FundCode::factory()->create();
    $attached = \App\Models\FundCode::factory()->create();
    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $job->fundCodes()->attach($attached->id);

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('openBudgetForm')
        ->set('budgetCategory', 'Plumbing')
        ->set('budgetAmount', '2000')
        ->set('budgetFundCodeId', $unattached->id)
        ->call('saveBudgetLine')
        ->assertHasErrors(['budgetFundCodeId']);

    expect(ProjectBudget::where('housing_job_id', $job->id)->count())->toBe(0);
});
