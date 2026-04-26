<?php

use App\Models\FundCode;
use App\Models\HousingJob;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('throws when primary_fund_code_id is set to a code not in the pivot (P-08 invariant)', function () {
    $job = HousingJob::factory()->create();
    $orphan = FundCode::factory()->create();

    expect(fn () => tap($job, function ($j) use ($orphan) {
        $j->primary_fund_code_id = $orphan->id;
        $j->save();
    }))->toThrow(\InvalidArgumentException::class, 'not attached to the project');
})->group('regression');

it('accepts primary_fund_code_id when the code IS in the pivot (P-08 invariant)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create();

    $job->fundCodes()->attach($code->id);
    $job->primary_fund_code_id = $code->id;
    $job->save();

    expect($job->refresh()->primary_fund_code_id)->toBe($code->id);
})->group('regression');

it('accepts null primary_fund_code_id unconditionally (P-08 invariant)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create();

    $job->fundCodes()->attach($code->id);
    $job->primary_fund_code_id = $code->id;
    $job->save();

    // Now clear it — should succeed without the guard tripping.
    $job->primary_fund_code_id = null;
    $job->save();

    expect($job->refresh()->primary_fund_code_id)->toBeNull();
})->group('regression');

it('HousingJob::setPrimaryFundCode helper attaches before setting, even for codes not in pivot (P-08)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create();

    // Sanity: not in pivot before we call.
    expect($job->fundCodes()->where('fund_codes.id', $code->id)->exists())->toBeFalse();

    $job->setPrimaryFundCode($code);

    $job->refresh();
    expect($job->primary_fund_code_id)->toBe($code->id);
    expect($job->fundCodes()->where('fund_codes.id', $code->id)->exists())->toBeTrue();
})->group('regression');

it('throws when attempting to set primary on a brand-new (unsaved) job (P-08 — guards against the no-id corner)', function () {
    // Setting primary_fund_code_id on a creating-but-not-yet-persisted job
    // can never satisfy the pivot invariant — there's no housing_jobs row
    // for the FK to reference. The guard should reject this regardless of
    // whether the code "would have" been attached.
    $code = FundCode::factory()->create();

    $newJob = new HousingJob([
        'job_title' => 'Brand new project',
        'job_description' => 'Test',
        'priority' => 'medium',
        'status' => 'pending',
        'is_template' => false,
        'primary_fund_code_id' => $code->id,
    ]);

    expect(fn () => $newJob->save())
        ->toThrow(\InvalidArgumentException::class, 'not attached to the project');
})->group('regression');
