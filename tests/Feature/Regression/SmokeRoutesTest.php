<?php

use App\Models\HousingJob;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

dataset('housingRoutes', [
    'communications-log' => ['/communications-log'],
    'property-management' => ['/property-management'],
    'contractor-management' => ['/contractor-management'],
    'projects' => ['/projects'],
]);

dataset('allHousingRoutes', [
    'communications-log' => ['/communications-log'],
    'property-management' => ['/property-management'],
    'contractor-management' => ['/contractor-management'],
    'projects' => ['/projects'],
    'fund-codes' => ['/fund-codes'],
]);

it('returns 200 for an authed admin on housing routes', function (string $path) {
    $response = $this->actingAs($this->user)->get($path);

    expect($response->status())->toBe(
        200,
        "GET {$path} returned {$response->status()}, expected 200. ".
        'Body excerpt: '.substr(strip_tags((string) $response->getContent()), 0, 200)
    );
})->with('housingRoutes')->group('regression');

it('returns 200 on the project detail page for a seeded job', function () {
    $job = HousingJob::where('job_number', 'JOB-FIX-00001')->firstOrFail();

    $response = $this->actingAs($this->user)->get("/project/{$job->id}/manage");

    expect($response->status())->toBe(
        200,
        "GET /project/{$job->id}/manage returned {$response->status()}, expected 200. ".
        'Body excerpt: '.substr(strip_tags((string) $response->getContent()), 0, 200)
    );
})->group('regression');

it('redirects guests on housing routes', function (string $path) {
    $response = $this->get($path);

    expect($response->status())->toBeIn([302, 401]);
})->with('allHousingRoutes')->group('regression');

// P-05 (Phase 2): the /fund-codes view used to reference an undefined
// route name (contracts.show vs the registered contract.show), which
// 500'd whenever any fund code had a contract_id set. Fixed in commit 7
// of phase-2/fund-code-system; this test exercises the path that was
// formerly skipped under P-33.
it('GET /fund-codes returns 200 with a contract-linked fund code present', function () {
    $response = $this->actingAs($this->user)->get('/fund-codes');
    expect($response->status())->toBe(200);
})->group('regression');
