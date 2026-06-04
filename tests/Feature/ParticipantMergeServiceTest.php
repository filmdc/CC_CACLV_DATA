<?php

use App\Models\Household;
use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use App\Services\ParticipantMergeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = app(ParticipantMergeService::class);
    Storage::fake('local');
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

it('moves the loser tags to the survivor as a union without duplicates', function () {
    $survivor = Participant::factory()->create();
    $loser = Participant::factory()->create();

    $shared = \App\Models\Tag::create(['name' => 'Shared Tag']);
    $loserOnly = \App\Models\Tag::create(['name' => 'Loser Only Tag']);

    $shared->participants()->attach($survivor->id);
    $shared->participants()->attach($loser->id);
    $loserOnly->participants()->attach($loser->id);

    $this->service->merge($survivor, $loser);

    $survivorTagIds = \App\Models\Tag::whereHas('participants', fn ($q) => $q->where('taggable_id', $survivor->id))
        ->pluck('id')->sort()->values()->all();

    expect($survivorTagIds)->toEqual(collect([$shared->id, $loserOnly->id])->sort()->values()->all());
    // The shared tag must not be linked to the survivor twice.
    expect(DB::table('taggables')->where('taggable_type', Participant::class)
        ->where('taggable_id', $survivor->id)->where('tag_id', $shared->id)->count())->toBe(1);
});

it('dedupes a duplicate service even when its program enrollment is deduped', function () {
    $survivor = Participant::factory()->create();
    $loser = Participant::factory()->create();
    $program = Program::factory()->create();
    $service = \App\Models\Service::factory()->create(['program_id' => $program->id]);

    $survivorPe = ProgramEnrollment::create([
        'participant_id' => $survivor->id, 'program_id' => $program->id,
        'enrollment_date' => now(), 'status' => 'active',
    ]);
    \App\Models\ServiceEnrollment::create([
        'participant_id' => $survivor->id, 'service_id' => $service->id,
        'program_enrollment_id' => $survivorPe->id, 'enrollment_date' => now(), 'status' => 'enrolled',
    ]);

    $loserPe = ProgramEnrollment::create([
        'participant_id' => $loser->id, 'program_id' => $program->id,
        'enrollment_date' => now(), 'status' => 'active',
    ]);
    \App\Models\ServiceEnrollment::create([
        'participant_id' => $loser->id, 'service_id' => $service->id,
        'program_enrollment_id' => $loserPe->id, 'enrollment_date' => now(), 'status' => 'enrolled',
    ]);

    $this->service->merge($survivor, $loser);

    expect(\App\Models\ServiceEnrollment::where('participant_id', $survivor->id)->where('service_id', $service->id)->count())
        ->toBe(1);
});

it('transfers headship to the survivor when merging within one household', function () {
    $household = Household::factory()->create();
    $loser = Participant::factory()->headOfHousehold()->create(['household_id' => $household->id]);
    $survivor = Participant::factory()->create(['household_id' => $household->id, 'is_head_of_household' => false]);

    $this->service->merge($survivor, $loser);

    expect($survivor->fresh()->is_head_of_household)->toBeTrue();
    expect(Household::find($household->id))->not->toBeNull();
});

it('writes a recovery snapshot of the merged-away participant', function () {
    $survivor = Participant::factory()->create();
    $loser = Participant::factory()->create();

    $summary = $this->service->merge($survivor, $loser);

    expect($summary['snapshot_path'])->not->toBeNull();
    Storage::disk('local')->assertExists($summary['snapshot_path']);
});

it('refuses to merge a participant into itself', function () {
    $p = Participant::factory()->create();

    expect(fn () => $this->service->merge($p, $p))
        ->toThrow(InvalidArgumentException::class);
});
