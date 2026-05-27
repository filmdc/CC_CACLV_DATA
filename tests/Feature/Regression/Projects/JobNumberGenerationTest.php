<?php

/*
| Convert-to-Project regression: HousingJob::generateJobNumber must not
| collide with an existing number.
|
| Bug: the generator picked the most-recent job by id for the year and
| regex-matched its number. When that latest row was a template
| ("TEMPLATE-<uuid>") or a legacy-format number, the regex missed and the
| sequence reset to 00001 — colliding with the existing JOB-YYYY-00001 and
| throwing a unique-key violation that silently aborted conversion.
*/

use App\Models\HousingJob;
use Database\Seeders\RegressionTestSeeder;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

it('increments past the highest JOB-YYYY number even when a template is the newest row', function () {
    $year = date('Y');

    HousingJob::factory()->create(['job_number' => "JOB-{$year}-00001"]);
    // A template created afterwards (higher id) — its TEMPLATE- number
    // used to reset the counter back to 00001.
    HousingJob::factory()->create([
        'job_number' => 'TEMPLATE-'.\Illuminate\Support\Str::uuid(),
        'is_template' => true,
    ]);

    expect(HousingJob::generateJobNumber())->toBe("JOB-{$year}-00002");
})->group('regression');

it('never returns an already-taken number', function () {
    $year = date('Y');

    foreach (['00001', '00002', '00003'] as $seq) {
        HousingJob::factory()->create(['job_number' => "JOB-{$year}-{$seq}"]);
    }

    $next = HousingJob::generateJobNumber();

    expect($next)->toBe("JOB-{$year}-00004");
    expect(HousingJob::where('job_number', $next)->exists())->toBeFalse();
})->group('regression');

it('starts at 00001 for a year with no JOB-numbered jobs', function () {
    $year = date('Y');

    // Only a template exists — should not count toward the sequence.
    HousingJob::factory()->create([
        'job_number' => 'TEMPLATE-'.\Illuminate\Support\Str::uuid(),
        'is_template' => true,
    ]);

    expect(HousingJob::generateJobNumber())->toBe("JOB-{$year}-00001");
})->group('regression');

it('auto-assigns a non-colliding number when creating a job after a template', function () {
    $year = date('Y');

    HousingJob::factory()->create(['job_number' => "JOB-{$year}-00001"]);
    HousingJob::factory()->create([
        'job_number' => 'TEMPLATE-'.\Illuminate\Support\Str::uuid(),
        'is_template' => true,
    ]);

    // No explicit job_number → boot() generator runs. Must not throw.
    $job = HousingJob::create([
        'job_title' => 'Post-template job',
        'status' => 'pending',
        'priority' => 'medium',
        'request_date' => now(),
    ]);

    expect($job->job_number)->toBe("JOB-{$year}-00002");
})->group('regression');
