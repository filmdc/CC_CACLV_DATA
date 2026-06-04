<?php

use App\Models\Household;
use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use App\Services\ParticipantMergeService;

beforeEach(function () {
    $this->service = app(ParticipantMergeService::class);
});

it('reassigns program enrollments to the survivor', function () {
    $survivor = Participant::factory()->create();
    $loser = Participant::factory()->create();
    $program = Program::factory()->create();

    $enrollment = ProgramEnrollment::create([
        'participant_id' => $loser->id,
        'program_id' => $program->id,
        'enrollment_date' => now(),
        'status' => 'active',
    ]);

    $this->service->merge($survivor, $loser);

    expect($enrollment->fresh()->participant_id)->toBe($survivor->id);
    expect(Participant::find($loser->id))->toBeNull();
});

it('dedupes a duplicate active program enrollment instead of creating two', function () {
    $survivor = Participant::factory()->create();
    $loser = Participant::factory()->create();
    $program = Program::factory()->create();

    ProgramEnrollment::create([
        'participant_id' => $survivor->id,
        'program_id' => $program->id,
        'enrollment_date' => now(),
        'status' => 'active',
    ]);
    ProgramEnrollment::create([
        'participant_id' => $loser->id,
        'program_id' => $program->id,
        'enrollment_date' => now(),
        'status' => 'active',
    ]);

    $this->service->merge($survivor, $loser);

    expect(ProgramEnrollment::where('participant_id', $survivor->id)->where('program_id', $program->id)->count())
        ->toBe(1);
});

it('reassigns housing jobs and communication logs to the survivor', function () {
    $survivor = Participant::factory()->create();
    $loser = Participant::factory()->create();

    $job = \App\Models\HousingJob::factory()->create([
        'participant_id' => $loser->id,
        'household_id' => $loser->household_id,
    ]);

    $log = \App\Models\CommunicationLog::factory()->create([
        'participant_id' => $loser->id,
        'household_id' => $loser->household_id,
    ]);

    $this->service->merge($survivor, $loser);

    expect($job->fresh()->participant_id)->toBe($survivor->id);
    expect($log->fresh()->participant_id)->toBe($survivor->id);
});

it('fills empty survivor fields from the loser but keeps existing survivor values', function () {
    $survivor = Participant::factory()->create(['email' => null, 'telephone' => '555-111-2222']);
    $loser = Participant::factory()->create(['email' => 'keep@example.com', 'telephone' => '555-999-0000']);

    $this->service->merge($survivor, $loser);

    $survivor->refresh();
    expect($survivor->email)->toBe('keep@example.com'); // filled from loser (was empty)
    expect($survivor->telephone)->toBe('555-111-2222'); // survivor wins (was present)
});

it('honours a use_loser field override', function () {
    $survivor = Participant::factory()->create(['telephone' => '555-111-2222']);
    $loser = Participant::factory()->create(['telephone' => '555-999-0000']);

    $this->service->merge($survivor, $loser, ['telephone' => 'use_loser']);

    expect($survivor->fresh()->telephone)->toBe('555-999-0000');
});

it('promotes a remaining member when the loser headed its own household', function () {
    $survivorHousehold = Household::factory()->create();
    $survivor = Participant::factory()->create(['household_id' => $survivorHousehold->id]);

    $loserHousehold = Household::factory()->create();
    $loser = Participant::factory()->headOfHousehold()->create(['household_id' => $loserHousehold->id]);
    $coMember = Participant::factory()->create(['household_id' => $loserHousehold->id]);

    $this->service->merge($survivor, $loser);

    expect($coMember->fresh()->is_head_of_household)->toBeTrue();
    expect(Household::find($loserHousehold->id))->not->toBeNull();
});

it('deletes the loser household when it becomes empty', function () {
    $survivorHousehold = Household::factory()->create();
    $survivor = Participant::factory()->create(['household_id' => $survivorHousehold->id]);

    $loserHousehold = Household::factory()->create();
    $loser = Participant::factory()->headOfHousehold()->create(['household_id' => $loserHousehold->id]);

    $this->service->merge($survivor, $loser);

    expect(Household::find($loserHousehold->id))->toBeNull();
});

it('refuses to merge a participant into itself', function () {
    $p = Participant::factory()->create();

    expect(fn () => $this->service->merge($p, $p))
        ->toThrow(InvalidArgumentException::class);
});
