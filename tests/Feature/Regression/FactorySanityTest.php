<?php

use App\Models\CommunicationLog;
use App\Models\Contract;
use App\Models\Contractor;
use App\Models\FundCode;
use App\Models\Household;
use App\Models\HousingJob;
use App\Models\Participant;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use App\Models\ProjectMilestone;
use App\Models\Property;
use Database\Seeders\RegressionTestSeeder;

it('all 11 factories instantiate via Model::factory()->create()', function () {
    Household::factory()->create();
    Participant::factory()->create();
    Property::factory()->create();
    Contractor::factory()->create();
    Contract::factory()->create();
    FundCode::factory()->create();
    HousingJob::factory()->create();
    CommunicationLog::factory()->create();
    ProjectBudget::factory()->create();
    ProjectExpense::factory()->create();
    ProjectMilestone::factory()->create();

    expect(Household::count())->toBeGreaterThanOrEqual(1)
        ->and(Participant::count())->toBeGreaterThanOrEqual(1)
        ->and(Property::count())->toBeGreaterThanOrEqual(1)
        ->and(Contractor::count())->toBe(1)
        ->and(Contract::count())->toBeGreaterThanOrEqual(1)
        ->and(FundCode::count())->toBe(1)
        ->and(HousingJob::count())->toBeGreaterThanOrEqual(1)
        ->and(CommunicationLog::count())->toBe(1)
        ->and(ProjectBudget::count())->toBeGreaterThanOrEqual(1)
        ->and(ProjectExpense::count())->toBeGreaterThanOrEqual(1)
        ->and(ProjectMilestone::count())->toBeGreaterThanOrEqual(1);
})->group('regression');

it('RegressionTestSeeder runs and produces the expected fixture shape', function () {
    $this->seed(RegressionTestSeeder::class);

    expect(Property::count())->toBe(12)
        ->and(Participant::count())->toBe(12)
        ->and(Household::count())->toBe(12)
        ->and(Contractor::count())->toBe(3)
        ->and(Contract::count())->toBe(2)
        ->and(FundCode::count())->toBe(3)
        ->and(HousingJob::count())->toBe(5) // HJ-1..4 + template
        ->and(HousingJob::where('is_template', true)->count())->toBe(1)
        ->and(HousingJob::where('is_template', false)->count())->toBe(4)
        ->and(CommunicationLog::count())->toBe(5)
        ->and(CommunicationLog::whereNotNull('converted_to_job_id')->count())->toBe(2)
        ->and(ProjectBudget::count())->toBe(2)
        ->and(ProjectExpense::count())->toBe(3)
        ->and(ProjectMilestone::count())->toBe(7); // 3 (HJ-2) + 4 (HJ-3)

    // Fund code attachment counts.
    $cl2 = CommunicationLog::where('subject', 'like', 'CL-2:%')->first();
    $cl3 = CommunicationLog::where('subject', 'like', 'CL-3:%')->first();
    expect($cl2->fundCodes)->toHaveCount(1)
        ->and($cl3->fundCodes)->toHaveCount(2);

    // Running-total target: HJ-4 Roofing expenses sum to 7000.
    $hj4 = HousingJob::where('job_number', 'JOB-FIX-00004')->first();
    expect((float) $hj4->expenses()->where('category', 'Roofing')->sum('amount'))->toBe(7000.0);
})->group('regression');
