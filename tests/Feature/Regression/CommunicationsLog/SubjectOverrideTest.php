<?php

use App\Models\CommunicationLog;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('subject_overridden_at defaults to null on factory create', function () {
    $log = CommunicationLog::factory()->create();

    expect($log->subject_overridden_at)->toBeNull();
})->group('regression');

it('subject_overridden_at column exists and is fillable', function () {
    $log = CommunicationLog::factory()->create([
        'subject_overridden_at' => '2026-05-23 12:00:00',
    ]);

    expect($log->fresh()->subject_overridden_at)->not->toBeNull();
    expect($log->subject_overridden_at->format('Y-m-d'))->toBe('2026-05-23');
})->group('regression');
