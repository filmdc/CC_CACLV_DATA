<?php

use App\Models\Household;
use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use App\Services\HouseholdDecisionRequiredException;
use App\Services\ParticipantDeletionService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = app(ParticipantDeletionService::class);
    Storage::fake('local');
});

it('deletes a participant and cascades its enrollments and housing jobs', function () {
    $participant = Participant::factory()->create();
    $program = Program::factory()->create();

    ProgramEnrollment::create([
        'participant_id' => $participant->id,
        'program_id' => $program->id,
        'enrollment_date' => now(),
        'status' => 'active',
    ]);
    $job = \App\Models\HousingJob::factory()->create(['participant_id' => $participant->id]);

    $this->service->delete($participant);

    expect(Participant::find($participant->id))->toBeNull();
    expect(ProgramEnrollment::where('participant_id', $participant->id)->count())->toBe(0);
    expect(\App\Models\HousingJob::find($job->id))->toBeNull();
});

it('writes a recovery snapshot to storage', function () {
    $participant = Participant::factory()->create();

    $summary = $this->service->delete($participant);

    Storage::disk('local')->assertExists($summary['snapshot_path']);
    $contents = json_decode(Storage::disk('local')->get($summary['snapshot_path']), true);
    expect($contents['participant']['id'])->toBe($participant->id);
});

it('deletes the household when the participant was its only member', function () {
    $household = Household::factory()->create();
    $participant = Participant::factory()->headOfHousehold()->create(['household_id' => $household->id]);

    $summary = $this->service->delete($participant);

    expect($summary['household_deleted'])->toBeTrue();
    expect(Household::find($household->id))->toBeNull();
});

it('keeps the household and co-members when a non-head member is deleted', function () {
    $household = Household::factory()->create();
    $head = Participant::factory()->headOfHousehold()->create(['household_id' => $household->id]);
    $member = Participant::factory()->create(['household_id' => $household->id]);

    $this->service->delete($member);

    expect(Participant::find($head->id))->not->toBeNull();
    expect(Household::find($household->id))->not->toBeNull();
});

it('requires a decision when deleting a head with other members', function () {
    $household = Household::factory()->create();
    $head = Participant::factory()->headOfHousehold()->create(['household_id' => $household->id]);
    Participant::factory()->create(['household_id' => $household->id]);

    expect(fn () => $this->service->delete($head))
        ->toThrow(HouseholdDecisionRequiredException::class);
});

it('promotes a member to head before deleting when instructed', function () {
    $household = Household::factory()->create();
    $head = Participant::factory()->headOfHousehold()->create(['household_id' => $household->id]);
    $member = Participant::factory()->create(['household_id' => $household->id]);

    $this->service->delete($head, ['promote_to_head_id' => $member->id]);

    expect(Participant::find($head->id))->toBeNull();
    expect($member->fresh()->is_head_of_household)->toBeTrue();
});

it('deletes anyway when forced, leaving the household without a head', function () {
    $household = Household::factory()->create();
    $head = Participant::factory()->headOfHousehold()->create(['household_id' => $household->id]);
    $member = Participant::factory()->create(['household_id' => $household->id]);

    $this->service->delete($head, ['force' => true]);

    expect(Participant::find($head->id))->toBeNull();
    expect($member->fresh()->is_head_of_household)->toBeFalse();
    expect(Household::find($household->id))->not->toBeNull();
});
