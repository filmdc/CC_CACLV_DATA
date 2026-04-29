<?php

use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use Database\Seeders\RegressionTestSeeder;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

it('returns null when target_date is null', function () {
    $milestone = ProjectMilestone::factory()->create(['target_date' => null]);
    expect($milestone->deadlineIndicator())->toBeNull();
});

it('returns null at the day-8 boundary (just outside approaching)', function () {
    $milestone = ProjectMilestone::factory()->create([
        'target_date' => now()->addDays(8)->toDateString(),
    ]);
    expect($milestone->deadlineIndicator())->toBeNull();
});

it('returns approaching at the day-7 boundary', function () {
    $milestone = ProjectMilestone::factory()->create([
        'target_date' => now()->addDays(7)->toDateString(),
    ]);
    expect($milestone->deadlineIndicator())->toBe('approaching');
});

it('returns approaching inside the 7-day window', function () {
    $milestone = ProjectMilestone::factory()->create([
        'target_date' => now()->addDays(3)->toDateString(),
    ]);
    expect($milestone->deadlineIndicator())->toBe('approaching');
});

it('returns approaching when target_date is today', function () {
    $milestone = ProjectMilestone::factory()->create([
        'target_date' => now()->startOfDay()->toDateString(),
    ]);
    expect($milestone->deadlineIndicator())->toBe('approaching');
});

it('returns overdue when target_date is yesterday', function () {
    $milestone = ProjectMilestone::factory()->create([
        'target_date' => now()->subDay()->toDateString(),
    ]);
    expect($milestone->deadlineIndicator())->toBe('overdue');
});

it('returns overdue for distant past target dates too', function () {
    $milestone = ProjectMilestone::factory()->create([
        'target_date' => now()->subYears(2)->toDateString(),
    ]);
    expect($milestone->deadlineIndicator())->toBe('overdue');
});
