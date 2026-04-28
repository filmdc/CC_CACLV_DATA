<?php

use App\Livewire\FundCodeManagement;
use App\Models\FundCode;
use App\Models\FundCodeRequirement;
use App\Models\HousingJob;
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

it('deletes the fund code immediately when no requirements exist anywhere', function () {
    $code = FundCode::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(FundCodeManagement::class)
        ->call('delete', $code->id)
        ->assertSet('showDeleteFundCodeModal', false);

    expect(FundCode::find($code->id))->toBeNull();
})->group('regression');

it('opens the cascade-or-keep modal when at least one project has fund-code-source requirements', function () {
    $manager = User::factory()->create();
    $code = FundCode::factory()->create();
    FundCodeRequirement::factory()->create(['fund_code_id' => $code->id]);

    $jobA = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $jobB = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $jobA->fundCodes()->attach($code->id);
    $jobB->fundCodes()->attach($code->id);
    app(RequirementInstantiator::class)->forFundCode($jobA->fresh(), $code, $manager);
    app(RequirementInstantiator::class)->forFundCode($jobB->fresh(), $code, $manager);

    expect(Requirement::count())->toBe(2);

    Livewire::actingAs($this->admin)
        ->test(FundCodeManagement::class)
        ->call('delete', $code->id)
        ->assertSet('showDeleteFundCodeModal', true)
        ->assertSet('deleteFundCodeRequirementCount', 2);

    // Fund code still exists; requirements still in place.
    expect(FundCode::find($code->id))->not->toBeNull();
    expect(Requirement::count())->toBe(2);
})->group('regression');

it('keep branch: deletes the fund code but preserves audit-quality requirement rows', function () {
    $manager = User::factory()->create();
    $code = FundCode::factory()->create();
    FundCodeRequirement::factory()->create(['fund_code_id' => $code->id, 'label' => 'Snapshot text survives delete']);
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $job->fundCodes()->attach($code->id);
    app(RequirementInstantiator::class)->forFundCode($job->fresh(), $code, $manager);

    Livewire::actingAs($this->admin)
        ->test(FundCodeManagement::class)
        ->call('delete', $code->id)
        ->call('confirmDeleteFundCode', false)
        ->assertSet('showDeleteFundCodeModal', false);

    expect(FundCode::find($code->id))->toBeNull();
    $surviving = Requirement::where('source_type', Requirement::SOURCE_FUND_CODE)
        ->where('source_id', $code->id)
        ->get();
    expect($surviving)->toHaveCount(1);
    expect($surviving->first()->source_label_snapshot)->toBe('Snapshot text survives delete');
})->group('regression');

it('cascade branch: removes the fund code AND every fund-code-source requirement on every project', function () {
    $manager = User::factory()->create();
    $code = FundCode::factory()->create();
    FundCodeRequirement::factory()->count(2)->create(['fund_code_id' => $code->id]);
    $jobA = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $jobB = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $jobA->fundCodes()->attach($code->id);
    $jobB->fundCodes()->attach($code->id);
    app(RequirementInstantiator::class)->forFundCode($jobA->fresh(), $code, $manager);
    app(RequirementInstantiator::class)->forFundCode($jobB->fresh(), $code, $manager);

    expect(Requirement::count())->toBe(4);

    Livewire::actingAs($this->admin)
        ->test(FundCodeManagement::class)
        ->call('delete', $code->id)
        ->call('confirmDeleteFundCode', true);

    expect(FundCode::find($code->id))->toBeNull();
    expect(Requirement::count())->toBe(0);
})->group('regression');

it('does not affect requirements bound to a different fund code', function () {
    $manager = User::factory()->create();
    $codeA = FundCode::factory()->create();
    $codeB = FundCode::factory()->create();
    FundCodeRequirement::factory()->create(['fund_code_id' => $codeA->id]);
    FundCodeRequirement::factory()->create(['fund_code_id' => $codeB->id]);

    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $job->fundCodes()->attach([$codeA->id, $codeB->id]);
    app(RequirementInstantiator::class)->forFundCode($job->fresh(), $codeA, $manager);
    app(RequirementInstantiator::class)->forFundCode($job->fresh(), $codeB, $manager);

    expect(Requirement::count())->toBe(2);

    Livewire::actingAs($this->admin)
        ->test(FundCodeManagement::class)
        ->call('delete', $codeA->id)
        ->call('confirmDeleteFundCode', true);

    expect(FundCode::find($codeA->id))->toBeNull();
    expect(FundCode::find($codeB->id))->not->toBeNull();
    expect(Requirement::where('source_id', $codeB->id)->count())->toBe(1);
    expect(Requirement::where('source_id', $codeA->id)->count())->toBe(0);
})->group('regression');
