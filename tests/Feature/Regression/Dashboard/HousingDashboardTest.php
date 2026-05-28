<?php

use App\Livewire\HousingDashboard;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\Program;
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

function dashboardJob(array $overrides = []): HousingJob
{
    $program = Program::first()
        ?? Program::factory()->create(['housing_module_enabled' => true]);
    if (! $program->housing_module_enabled) {
        $program->update(['housing_module_enabled' => true]);
    }

    return HousingJob::factory()->create(array_merge([
        'program_id' => $program->id,
        'is_template' => false,
    ], $overrides));
}

it('mounts the housing dashboard with the intakes tab and overview sub-view as default', function () {
    Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->assertSet('section', 'intakes')
        ->assertSet('view', 'overview')
        ->assertSet('filter_status', 'outstanding')
        ->assertSet('filter_owner_id', null)
        ->assertSet('filter_type', 'all')
        ->assertSet('filter_source', 'all')
        ->assertSet('filter_project_id', null);
})->group('regression');

it('Cut A renders one row per accessible housing project with outstanding count', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);
    Requirement::factory()->task()->complete()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->assertSet('section', 'projects')
        ->html();

    expect($html)->toContain('1 outstanding');
    expect($html)->toContain('/ 2 total');
})->group('regression');

it('Cut A surfaces fund-expiration urgent indicator when any attached fund expires within 30 days', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    $code = FundCode::factory()->expiringIn(15)->create();
    $job->fundCodes()->attach($code->id);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->html();

    expect($html)->toContain('data-test="indicator-fund-urgent"');
    expect($html)->toContain('Fund expires in 15d');
})->group('regression');

it('Cut A surfaces fund-expired indicator on a project whose fund has already expired', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    $code = FundCode::factory()->expiredSince(5)->create();
    $job->fundCodes()->attach($code->id);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->html();

    expect($html)->toContain('data-test="indicator-fund-expired"');
})->group('regression');

it('Cut A surfaces blocked-milestone count when at least one milestone is blocked', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $b = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $a->dependencies()->attach($b->id);

    Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $b->id,
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->html();

    expect($html)->toContain('data-test="indicator-blocked"');
    expect($html)->toContain('1 blocked');
})->group('regression');

it('Cut A combines multiple indicators distinctly on a single project row', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);

    // Expiring fund + overdue milestone
    $code = FundCode::factory()->expiringIn(7)->create();
    $job->fundCodes()->attach($code->id);
    ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'target_date' => now()->subDays(2)->toDateString(),
    ]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->html();

    expect($html)->toContain('data-test="indicator-fund-urgent"');
    expect($html)->toContain('data-test="indicator-milestone-overdue"');
})->group('regression');

it('Cut B switches to per-requirement view when toggled', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $this->admin->id,
        'title' => 'Inspect the foundation',
    ]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->call('setView', 'requirements')
        ->assertSet('view', 'requirements')
        ->html();

    expect($html)->toContain('Inspect the foundation');
    expect($html)->toContain('data-test="dashboard-cut-b"');
})->group('regression');

it('Cut B defaults owner filter to "me" and surfaces only the current user\'s requirements', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    $other = User::factory()->create(['name' => 'Tony']);

    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $this->admin->id,
        'title' => 'Mine to do',
    ]);
    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $other->id,
        'title' => "Tony's job",
    ]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->call('setView', 'requirements')
        ->html();

    expect($html)->toContain('Mine to do');
    expect($html)->not->toContain("Tony's job");
})->group('regression');

it('Cut B "anyone" owner filter shows all owners (chase view)', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    $other = User::factory()->create(['name' => 'Tony']);

    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $this->admin->id,
        'title' => 'CHASE_TEST_admin_req',
    ]);
    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $other->id,
        'title' => 'CHASE_TEST_other_req',
    ]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->call('setView', 'requirements')
        ->set('filter_owner_id', 0) // 'anyone'
        ->html();

    expect($html)->toContain('CHASE_TEST_admin_req');
    expect($html)->toContain('CHASE_TEST_other_req');
})->group('regression');

it('Cut B explicit owner filter surfaces a specific user\'s queue (chase Tony)', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    $tony = User::factory()->create(['name' => 'Tony']);

    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $this->admin->id,
        // Use a unique sentinel that cannot collide with random faker
        // names appearing in the user-options dropdown.
        'title' => 'CHASE_TEST_admin_only_req',
    ]);
    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $tony->id,
        'title' => 'CHASE_TEST_tony_only_req',
    ]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->call('setView', 'requirements')
        ->set('filter_owner_id', $tony->id)
        ->html();

    expect($html)->toContain('CHASE_TEST_tony_only_req');
    expect($html)->not->toContain('CHASE_TEST_admin_only_req');
})->group('regression');

it('Cut B status filter narrows to a single status', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $this->admin->id,
        'title' => 'Outstanding A',
    ]);
    Requirement::factory()->task()->complete()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $this->admin->id,
        'title' => 'Done B',
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->call('setView', 'requirements');

    // Default = outstanding
    $html = $component->html();
    expect($html)->toContain('Outstanding A');
    expect($html)->not->toContain('Done B');

    // Switch to complete
    $component->set('filter_status', 'complete');
    $html = $component->html();
    expect($html)->toContain('Done B');
    expect($html)->not->toContain('Outstanding A');
})->group('regression');

it('Cut B type and source filters compose correctly', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    $code = FundCode::factory()->create();
    $job->fundCodes()->attach($code->id);

    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $this->admin->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'title' => 'Adhoc task',
    ]);
    Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $this->admin->id,
        'source_type' => Requirement::SOURCE_FUND_CODE,
        'source_id' => $code->id,
        'title' => 'Fund-code doc',
    ]);

    // type=document AND source=fund_code → only the fund-code doc.
    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->call('setView', 'requirements')
        ->set('filter_type', 'document')
        ->set('filter_source', 'fund_code')
        ->html();

    expect($html)->toContain('Fund-code doc');
    expect($html)->not->toContain('Adhoc task');
})->group('regression');

it('Cut B fund-source rows show urgent / warning / expired indicators reflecting the source fund', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    $urgent = FundCode::factory()->expiringIn(10)->create();
    $expired = FundCode::factory()->expiredSince(5)->create();
    $job->fundCodes()->attach([$urgent->id, $expired->id]);

    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $this->admin->id,
        'source_type' => Requirement::SOURCE_FUND_CODE,
        'source_id' => $urgent->id,
        'title' => 'Urgent fund req',
    ]);
    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $this->admin->id,
        'source_type' => Requirement::SOURCE_FUND_CODE,
        'source_id' => $expired->id,
        'title' => 'Expired fund req',
    ]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->call('setView', 'requirements')
        ->html();

    expect($html)->toContain('data-test="row-indicator-fund-urgent"');
    expect($html)->toContain('data-test="row-indicator-fund-expired"');
})->group('regression');

it('Cut B blocked indicator fires on requirement rows whose milestone is blocked', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    $blockedMilestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $depMilestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $blockedMilestone->dependencies()->attach($depMilestone->id);

    Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $depMilestone->id,
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);
    Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $this->admin->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $blockedMilestone->id,
        'title' => 'Work on blocked milestone',
    ]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->call('setView', 'requirements')
        ->html();

    expect($html)->toContain('Work on blocked milestone');
    expect($html)->toContain('data-test="row-indicator-blocked"');
})->group('regression');

it('Cut B sorts fund-source requirements by fund code alphabetically, then by title', function () {
    $job = dashboardJob(['project_manager_id' => $this->admin->id]);
    $codeM = FundCode::factory()->create(['code' => 'MA1000']);
    $codeA = FundCode::factory()->create(['code' => 'AA0500']);
    $job->fundCodes()->attach([$codeM->id, $codeA->id]);

    Requirement::factory()->task()->create(['housing_job_id' => $job->id, 'owner_user_id' => $this->admin->id, 'source_type' => 'fund_code', 'source_id' => $codeM->id, 'title' => 'M-task']);
    Requirement::factory()->task()->create(['housing_job_id' => $job->id, 'owner_user_id' => $this->admin->id, 'source_type' => 'fund_code', 'source_id' => $codeA->id, 'title' => 'A-task']);
    Requirement::factory()->task()->create(['housing_job_id' => $job->id, 'owner_user_id' => $this->admin->id, 'source_type' => 'ad_hoc', 'title' => 'ad-hoc-z']);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->call('setView', 'requirements')
        ->html();

    // The A-task (fund=AA0500) should appear BEFORE the M-task (fund=MA1000),
    // and both fund-source rows should appear before the ad-hoc row.
    $posA = strpos($html, 'A-task');
    $posM = strpos($html, 'M-task');
    $posAdHoc = strpos($html, 'ad-hoc-z');

    expect($posA)->toBeLessThan($posM);
    expect($posM)->toBeLessThan($posAdHoc);
})->group('regression');

it('a user without the housing_dashboard.view permission is forbidden from mounting the housing dashboard', function () {
    $user = User::factory()->create();
    expect($user->hasPermission('housing_dashboard.view'))->toBeFalse();

    Livewire::actingAs($user)
        ->test(HousingDashboard::class)
        ->assertStatus(403);
})->group('regression');

it('a non-admin housing user sees only projects they manage / created / own a requirement on', function () {
    // Build a "housing-coordinator" role with just housing_dashboard.view,
    // attach it to a non-admin user. Two projects: one managed by them,
    // one managed by a different user. Predicate should isolate the first.
    $housingRole = \App\Models\Role::firstOrCreate(
        ['name' => 'test-housing-coordinator'],
        ['display_name' => 'Test Housing Coordinator']
    );
    $housingRole->permissions()->syncWithoutDetaching(
        \App\Models\Permission::where('name', 'housing_dashboard.view')->pluck('id')->all()
    );

    $userA = User::factory()->create();
    $userA->roles()->attach($housingRole->id);
    expect($userA->isAdmin())->toBeFalse();
    expect($userA->hasPermission('housing_dashboard.view'))->toBeTrue();

    $userB = User::factory()->create();

    $jobMine = dashboardJob(['project_manager_id' => $userA->id]);
    $jobNotMine = dashboardJob(['project_manager_id' => $userB->id, 'created_by' => $userB->id]);

    $html = Livewire::actingAs($userA)
        ->test(HousingDashboard::class)
        ->call('setSection', 'projects')
        ->html();

    expect($html)->toContain($jobMine->job_number);
    expect($html)->not->toContain($jobNotMine->job_number);
})->group('regression');
