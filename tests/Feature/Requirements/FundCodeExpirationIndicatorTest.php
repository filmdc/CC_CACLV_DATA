<?php

use App\Models\FundCode;
use Database\Seeders\RegressionTestSeeder;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

it('returns null when expires_at is null', function () {
    $code = FundCode::factory()->create(['expires_at' => null]);
    expect($code->expirationIndicator())->toBeNull();
});

it('returns urgent at the day-30 boundary (≤30 days)', function () {
    $code = FundCode::factory()->expiringIn(30)->create();
    expect($code->expirationIndicator())->toBe('urgent');
});

it('returns urgent at any day inside the 30-day window', function () {
    $code = FundCode::factory()->expiringIn(0)->create();
    expect($code->expirationIndicator())->toBe('urgent');

    $code = FundCode::factory()->expiringIn(15)->create();
    expect($code->expirationIndicator())->toBe('urgent');
});

it('returns warning at the day-31 boundary (just outside urgent)', function () {
    $code = FundCode::factory()->expiringIn(31)->create();
    expect($code->expirationIndicator())->toBe('warning');
});

it('returns warning at the day-90 boundary (≤90 days)', function () {
    $code = FundCode::factory()->expiringIn(90)->create();
    expect($code->expirationIndicator())->toBe('warning');
});

it('returns null at the day-91 boundary (just outside warning)', function () {
    $code = FundCode::factory()->expiringIn(91)->create();
    expect($code->expirationIndicator())->toBeNull();
});

it('returns expired when the date is in the past', function () {
    $code = FundCode::factory()->expiredSince(1)->create();
    expect($code->expirationIndicator())->toBe('expired');

    $code = FundCode::factory()->expiredSince(365)->create();
    expect($code->expirationIndicator())->toBe('expired');
});

it('isStartingInFuture is false when starts_at is null', function () {
    $code = FundCode::factory()->create(['starts_at' => null]);
    expect($code->isStartingInFuture())->toBeFalse();
});

it('isStartingInFuture is false when starts_at is today', function () {
    $code = FundCode::factory()->create(['starts_at' => now()->startOfDay()->toDateString()]);
    expect($code->isStartingInFuture())->toBeFalse();
});

it('isStartingInFuture is true when starts_at is tomorrow or later', function () {
    $code = FundCode::factory()->startingIn(1)->create();
    expect($code->isStartingInFuture())->toBeTrue();

    $code = FundCode::factory()->startingIn(60)->create();
    expect($code->isStartingInFuture())->toBeTrue();
});

it('isStartingInFuture is false when starts_at is in the past', function () {
    $code = FundCode::factory()->create(['starts_at' => now()->subDay()->toDateString()]);
    expect($code->isStartingInFuture())->toBeFalse();
});
