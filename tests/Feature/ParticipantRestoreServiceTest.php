<?php

use App\Models\Household;
use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use App\Services\ParticipantDeletionService;
use App\Services\ParticipantRestoreService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->deletion = app(ParticipantDeletionService::class);
    $this->restore = app(ParticipantRestoreService::class);
});

it('restores a deleted participant and its associations from the snapshot', function () {
    $household = Household::factory()->create();
    $participant = Participant::factory()->headOfHousehold()->create(['household_id' => $household->id]);
    $program = Program::factory()->create();
    $enrollment = ProgramEnrollment::create([
        'participant_id' => $participant->id,
        'program_id' => $program->id,
        'enrollment_date' => now(),
        'status' => 'active',
    ]);
    $job = \App\Models\HousingJob::factory()->create([
        'participant_id' => $participant->id,
        'household_id' => $household->id,
    ]);

    // Delete everything.
    $summary = $this->deletion->delete($participant);

    expect(Participant::find($participant->id))->toBeNull();
    expect(Household::find($household->id))->toBeNull();

    // Restore from the snapshot.
    $restored = $this->restore->restore($summary['snapshot_path']);

    // The participant, household and associations are back with their original ids.
    expect(Participant::find($participant->id))->not->toBeNull();
    expect(Household::find($household->id))->not->toBeNull();
    expect(ProgramEnrollment::find($enrollment->id))->not->toBeNull();
    expect(\App\Models\HousingJob::find($job->id))->not->toBeNull();
    expect($restored['participants'])->toBe(1);
});

it('is idempotent — skips rows that already exist', function () {
    $participant = Participant::factory()->create();
    $summary = $this->deletion->delete($participant);

    $first = $this->restore->restore($summary['snapshot_path']);
    expect($first['participants'])->toBe(1);

    // Running again restores nothing (already present).
    $second = $this->restore->restore($summary['snapshot_path']);
    expect($second)->not->toHaveKey('participants');
});

it('rejects a file that is not a deletion snapshot', function () {
    Storage::disk('local')->put('bad.json', json_encode(['nope' => true]));

    expect(fn () => $this->restore->restore('bad.json'))
        ->toThrow(InvalidArgumentException::class);
});
