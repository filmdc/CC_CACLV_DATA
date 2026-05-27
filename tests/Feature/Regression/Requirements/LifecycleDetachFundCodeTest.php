<?php

use App\Livewire\ProjectManagement;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\ProjectBudget;
use App\Models\Requirement;
use App\Models\User;
use App\Services\RequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

it('detaches without prompting when the fund code has no requirements on the project', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $code = FundCode::factory()->create();
    $job->fundCodes()->attach($code->id);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('detachFundCode', $code->id)
        ->assertSet('showDetachFundCodeModal', false);

    expect($job->fundCodes()->count())->toBe(0);
})->group('regression');

it('opens the keep/delete modal when there are fund-code-source requirements', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $code = FundCode::factory()->create();
    RegressionHelpers::linkProjectRequirements($code, 2);
    $job->fundCodes()->attach($code->id);
    app(RequirementInstantiator::class)->forFundCode($job->fresh(), $code, $manager);

    expect(Requirement::where('housing_job_id', $job->id)->count())->toBe(2);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('detachFundCode', $code->id)
        ->assertSet('showDetachFundCodeModal', true)
        ->assertSet('detachConfirmRequirementCount', 2);

    // Modal opened — pivot row not yet detached.
    expect($job->fresh()->fundCodes()->count())->toBe(1);
    expect(Requirement::where('housing_job_id', $job->id)->count())->toBe(2);
})->group('regression');

it('keep branch: detaches the pivot but preserves the requirement rows for audit', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $code = FundCode::factory()->create();
    RegressionHelpers::linkProjectRequirements($code, 2);
    $job->fundCodes()->attach($code->id);
    app(RequirementInstantiator::class)->forFundCode($job->fresh(), $code, $manager);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('detachFundCode', $code->id)
        ->call('confirmDetachFundCode', false)
        ->assertSet('showDetachFundCodeModal', false);

    expect($job->fresh()->fundCodes()->count())->toBe(0);
    expect(Requirement::where('housing_job_id', $job->id)->count())->toBe(2);
    expect(Requirement::where('source_type', Requirement::SOURCE_FUND_CODE)->where('source_id', $code->id)->count())
        ->toBe(2);
})->group('regression');

it('delete branch: detaches the pivot and cascades the requirement rows', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $code = FundCode::factory()->create();
    RegressionHelpers::linkProjectRequirements($code, 3);
    $job->fundCodes()->attach($code->id);
    app(RequirementInstantiator::class)->forFundCode($job->fresh(), $code, $manager);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('detachFundCode', $code->id)
        ->call('confirmDetachFundCode', true)
        ->assertSet('showDetachFundCodeModal', false);

    expect($job->fresh()->fundCodes()->count())->toBe(0);
    expect(Requirement::where('housing_job_id', $job->id)->count())->toBe(0);
})->group('regression');

it('Phase 2.5 budget guard still blocks detach before reaching the requirements modal', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $code = FundCode::factory()->create();
    RegressionHelpers::linkProjectRequirements($code, 1);
    $job->fundCodes()->attach($code->id);
    app(RequirementInstantiator::class)->forFundCode($job->fresh(), $code, $manager);

    // Pin a budget row to the fund code — Phase 2.5 invariant says detach
    // is blocked while this exists. The new lifecycle prompt must not
    // sneak past the guard.
    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $code->id,
        'category' => 'Construction labor',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('detachFundCode', $code->id)
        // Modal did not open — the pre-check stopped the flow.
        ->assertSet('showDetachFundCodeModal', false)
        // Pre-check did not stash a fund code id either, so the user
        // can't accidentally confirm a no-op detach later.
        ->assertSet('detachConfirmFundCodeId', null);

    // Pivot intact, requirements intact — the budget guard fired before
    // the requirements modal could even be reached.
    expect($job->fresh()->fundCodes()->count())->toBe(1);
    expect(Requirement::where('housing_job_id', $job->id)->count())->toBe(1);
})->group('regression');

it('does not surface other projects\' fund-code requirements when counting for the prompt', function () {
    $manager = User::factory()->create();
    $jobA = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $jobB = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $code = FundCode::factory()->create();
    RegressionHelpers::linkProjectRequirements($code, 2);

    $jobA->fundCodes()->attach($code->id);
    $jobB->fundCodes()->attach($code->id);
    app(RequirementInstantiator::class)->forFundCode($jobA->fresh(), $code, $manager);
    app(RequirementInstantiator::class)->forFundCode($jobB->fresh(), $code, $manager);

    expect(Requirement::count())->toBe(4);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $jobA->id])
        ->call('detachFundCode', $code->id)
        ->assertSet('detachConfirmRequirementCount', 2);
})->group('regression');
