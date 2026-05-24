<?php

/*
|----------------------------------------------------------------------
| P-39b: IntakeRequirement model mutating actions.
|
| Direct tests of markReceived, markWaived, reopen, markOrphaned,
| unmarkOrphaned. Asserts the audit contract that each respective state
| transition preserves or appends notes correctly.
|----------------------------------------------------------------------
*/

use App\Models\IntakeRequirement;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->actor = RegressionHelpers::adminUser();
});

it('markReceived() stamps received_at and received_by_user_id', function () {
    $req = IntakeRequirement::factory()->create();

    $req->markReceived($this->actor);

    expect($req->fresh()->status)->toBe(IntakeRequirement::STATUS_RECEIVED)
        ->and($req->fresh()->received_at)->not->toBeNull()
        ->and($req->fresh()->received_by_user_id)->toBe($this->actor->id);
})->group('regression');

it('markReceived() preserves first-receipt time on re-mark', function () {
    $req = IntakeRequirement::factory()->create();

    $req->markReceived($this->actor);
    $originalReceivedAt = $req->fresh()->received_at;

    // Simulate a re-mark some time later (different actor, would-be different stamp).
    sleep(1);
    $other = RegressionHelpers::adminUser();
    $req->fresh()->markReceived($other);

    expect($req->fresh()->received_at->equalTo($originalReceivedAt))->toBeTrue();
    // received_by_user_id should also be preserved.
    expect($req->fresh()->received_by_user_id)->toBe($this->actor->id);
})->group('regression');

it('markWaived() requires a reason', function () {
    $req = IntakeRequirement::factory()->create();

    expect(fn () => $req->markWaived($this->actor, ''))->toThrow(\InvalidArgumentException::class);
    expect(fn () => $req->markWaived($this->actor, '   '))->toThrow(\InvalidArgumentException::class);
})->group('regression');

it('markWaived() sets status, stamps received fields, and appends the reason to notes', function () {
    $req = IntakeRequirement::factory()->create(['notes' => null]);

    $req->markWaived($this->actor, 'Documented via federal records');

    $fresh = $req->fresh();
    expect($fresh->status)->toBe(IntakeRequirement::STATUS_WAIVED)
        ->and($fresh->received_at)->not->toBeNull()
        ->and($fresh->received_by_user_id)->toBe($this->actor->id)
        ->and($fresh->notes)->toContain('[WAIVED] Documented via federal records');
})->group('regression');

it('reopen() clears the receipt stamp and appends a [REOPENED ...] audit line', function () {
    $req = IntakeRequirement::factory()->received()->create();
    expect($req->received_at)->not->toBeNull();

    $req->reopen($this->actor);

    $fresh = $req->fresh();
    expect($fresh->status)->toBe(IntakeRequirement::STATUS_OUTSTANDING)
        ->and($fresh->received_at)->toBeNull()
        ->and($fresh->received_by_user_id)->toBeNull()
        ->and($fresh->notes)->toContain('[REOPENED by '.$this->actor->name);
})->group('regression');

it('markOrphaned() / unmarkOrphaned() flip the boolean and stamp updated_by', function () {
    $req = IntakeRequirement::factory()->create();
    $other = RegressionHelpers::adminUser();

    $req->markOrphaned($other);
    expect($req->fresh()->is_orphaned)->toBeTrue();
    expect($req->fresh()->updated_by_user_id)->toBe($other->id);

    $req->fresh()->unmarkOrphaned($this->actor);
    expect($req->fresh()->is_orphaned)->toBeFalse();
    expect($req->fresh()->updated_by_user_id)->toBe($this->actor->id);
})->group('regression');
