<?php

use App\Livewire\Homes\ProjectDetail;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\HousingService;
use App\Models\Permission;
use App\Models\ProjectBudget;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\User;
use App\Services\ProjectFundingService;
use Livewire\Livewire;

/**
 * Narrowing a project to the fund code it is actually paid from.
 *
 * A project inherits every code its intake anticipated; once the award
 * lands it is usually restricted to ONE code of its county's set. The pool
 * it may draw on stays the county's, so narrowing is always reversible and
 * never widens a project past what its county covers.
 */
function fundingUser(array $permissionNames = []): User
{
    $role = Role::firstOrCreate(['name' => 'project_funding_test'], ['display_name' => 'Project Funding Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

/** A county covering two funds, and a project carrying both. */
function fundingFixture(): array
{
    $county = HousingService::factory()->create(['name' => 'Lehigh']);
    $home = FundCode::factory()->create(['code' => 'HOME-2026']);
    $cdbg = FundCode::factory()->create(['code' => 'CDBG-2026']);
    $county->fundCodes()->attach([$home->id, $cdbg->id]);

    $job = HousingJob::factory()->create(['status' => 'in_progress']);
    $job->housingServices()->attach($county->id);
    $job->fundCodes()->attach([$home->id, $cdbg->id]);

    return [$job, $county, $home, $cdbg];
}

it('narrows a project to a single fund code', function () {
    [$job, , $home, $cdbg] = fundingFixture();
    $actor = fundingUser(['projects.view', 'projects.manage']);

    $result = app(ProjectFundingService::class)->restrictTo($job, [$home->id], $actor);

    expect($job->fundCodes()->pluck('fund_codes.id')->all())->toBe([$home->id]);
    expect($result['kept'])->toBe(['HOME-2026']);
    expect($result['dropped'])->toBe(['CDBG-2026']);
    expect($cdbg->fresh())->not->toBeNull(); // the code itself is untouched
});

it('refuses to narrow away a fund a budget line still spends from', function () {
    [$job, , $home, $cdbg] = fundingFixture();

    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $cdbg->id,
        'category' => 'Roofing',
    ]);

    $funding = app(ProjectFundingService::class);
    $reason = $funding->blockedReason($job, [$home->id]);

    expect($reason)->toContain('CDBG-2026');
    expect($reason)->toContain('Roofing');

    expect(fn () => $funding->restrictTo($job, [$home->id]))
        ->toThrow(RuntimeException::class);

    // Nothing moved.
    expect($job->fundCodes()->count())->toBe(2);
});

it('refuses to leave a project with no fund code at all', function () {
    [$job] = fundingFixture();

    expect(app(ProjectFundingService::class)->blockedReason($job, []))
        ->toContain('at least one fund code');
});

it('takes off the requirements nobody started, and keeps the ones with progress', function () {
    [$job, , $home, $cdbg] = fundingFixture();
    $actor = fundingUser(['projects.view', 'projects.manage']);

    $untouched = Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_FUND_CODE,
        'source_id' => $cdbg->id,
        'title' => 'CDBG environmental review',
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);

    $done = Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_FUND_CODE,
        'source_id' => $cdbg->id,
        'title' => 'CDBG income cert',
        'status' => Requirement::STATUS_COMPLETE,
    ]);

    $noted = Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_FUND_CODE,
        'source_id' => $cdbg->id,
        'title' => 'CDBG site visit',
        'status' => Requirement::STATUS_OUTSTANDING,
        'worker_note' => 'Scheduled for the 12th.',
    ]);

    // A requirement from the fund being KEPT is never in scope.
    $keeper = Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_FUND_CODE,
        'source_id' => $home->id,
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);

    $result = app(ProjectFundingService::class)->restrictTo($job, [$home->id], $actor, removeUntouched: true);

    expect($result['requirements_removed'])->toBe(1);
    expect(Requirement::find($untouched->id))->toBeNull();
    // Work already done is never destroyed by a funding change.
    expect(Requirement::find($done->id))->not->toBeNull();
    expect(Requirement::find($noted->id))->not->toBeNull();
    expect(Requirement::find($keeper->id))->not->toBeNull();
});

it('leaves every requirement alone when she says not to remove them', function () {
    [$job, , $home, $cdbg] = fundingFixture();

    $req = Requirement::factory()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_FUND_CODE,
        'source_id' => $cdbg->id,
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);

    app(ProjectFundingService::class)->restrictTo($job, [$home->id], null, removeUntouched: false);

    expect(Requirement::find($req->id))->not->toBeNull();
    expect($job->fundCodes()->count())->toBe(1);
});

it('offers back only what the county covers, and refuses anything else', function () {
    [$job, , $home, $cdbg] = fundingFixture();
    $stranger = FundCode::factory()->create(['code' => 'NOT-OURS']);

    $funding = app(ProjectFundingService::class);
    $funding->restrictTo($job, [$home->id]);

    expect($funding->availableFor($job)->pluck('code')->all())
        ->toEqualCanonicalizing(['HOME-2026', 'CDBG-2026']);

    expect(fn () => $funding->add($job, $stranger))
        ->toThrow(RuntimeException::class);

    $funding->add($job, $cdbg);
    expect($job->fundCodes()->count())->toBe(2);
});

it('can still add back a fund the intake anticipated on a county-less project', function () {
    $fund = FundCode::factory()->create(['code' => 'LEGACY-2024']);
    $job = HousingJob::factory()->create();
    $other = FundCode::factory()->create(['code' => 'OTHER-2024']);
    $job->fundCodes()->attach([$fund->id, $other->id]);

    $log = CommunicationLog::factory()->create(['converted_to_job_id' => $job->id]);
    $log->anticipatedFundCodes()->attach([$fund->id, $other->id]);

    $funding = app(ProjectFundingService::class);
    $funding->restrictTo($job, [$fund->id]);

    expect($funding->availableFor($job)->pluck('code')->all())
        ->toEqualCanonicalizing(['LEGACY-2024', 'OTHER-2024']);
});

it('drives the whole thing from the project screen', function () {
    [$job, , $home, $cdbg] = fundingFixture();
    $this->actingAs(fundingUser(['projects.view', 'projects.manage']));

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('openFunding')
        ->assertSee('HOME-2026')
        ->assertSee('CDBG-2026')
        ->call('stageOnlyFundCode', $home->id)
        ->assertSet('fundingKeepIds', [$home->id])
        ->assertSee('Coming off')
        ->call('confirmFunding')
        ->assertSet('fundingKeepIds', []);

    expect($job->fresh()->fundCodes()->pluck('fund_codes.code')->all())->toBe(['HOME-2026']);
});

it('shows the budget block on screen instead of failing on confirm', function () {
    [$job, , $home, $cdbg] = fundingFixture();
    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'fund_code_id' => $cdbg->id,
        'category' => 'Roofing',
    ]);

    $this->actingAs(fundingUser(['projects.view', 'projects.manage']));

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('openFunding')
        ->call('stageOnlyFundCode', $home->id)
        ->assertSee('Roofing')
        ->assertDontSee('Coming off');

    expect($job->fresh()->fundCodes()->count())->toBe(2);
});

it('gates changing funding on projects.manage, not the portal default', function () {
    [$job, , $home] = fundingFixture();

    // projects.view alone opens the project but not its funding.
    $this->actingAs(fundingUser(['projects.view']));

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('openFunding')
        ->assertForbidden();

    Livewire::test(ProjectDetail::class, ['job' => $job])
        ->call('stageOnlyFundCode', $home->id)
        ->assertForbidden();

    expect($job->fresh()->fundCodes()->count())->toBe(2);
});
