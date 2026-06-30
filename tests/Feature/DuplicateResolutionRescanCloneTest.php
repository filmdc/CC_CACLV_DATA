<?php

use App\Livewire\DuplicateResolutionManagement;
use App\Models\Household;
use App\Models\Participant;
use App\Models\Permission;
use App\Models\PotentialDuplicate;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

/**
 * Regression: the duplicate-resolution "approve as separate" action used to
 * CLONE an existing participant whenever the PotentialDuplicate came from
 * `rescanExistingParticipants()` (source_type = 'duplicate_rescan'), because
 * that rescan seeds proposed_data from rows already in the database. Working /
 * bulk-resolving the rescan queue therefore spawned hundreds of duplicate
 * participants. The fix skips the create for rescan-sourced rows while still
 * resolving the flag — but must NOT break the legitimate create path for
 * import/intake holds, whose proposed_data is a genuinely new record.
 */
function resolutionUser(): User
{
    $role = Role::firstOrCreate(['name' => 'dup_rescan_test'], ['display_name' => 'Dup Rescan Test']);
    $ids = collect(['participants.view', 'participants.import', 'participants.merge'])
        ->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user;
}

it('does NOT create a participant when approving a rescan-sourced duplicate as separate', function () {
    $this->actingAs(resolutionUser());

    $existing = Participant::factory()->create([
        'first_name' => 'Juliana',
        'last_name' => 'Colon',
    ]);

    // rescanExistingParticipants() builds proposed_data from an EXISTING row.
    $dup = PotentialDuplicate::create([
        'source_type' => 'duplicate_rescan',
        'source_identifier' => (string) $existing->id,
        'proposed_data' => [
            'first_name' => $existing->first_name,
            'last_name' => $existing->last_name,
            'date_of_birth' => optional($existing->date_of_birth)->format('Y-m-d'),
        ],
        'matched_participant_id' => $existing->id,
        'matching_criteria' => [],
            'confidence_score' => 68.75,
        'status' => 'pending',
        'created_by' => Auth::id(),
    ]);

    $countBefore = Participant::count();

    Livewire::test(DuplicateResolutionManagement::class)
        ->call('approveSeparate', $dup->id)
        ->assertHasNoErrors();

    // No clone created …
    expect(Participant::count())->toBe($countBefore);
    // … but the flag is still resolved.
    expect($dup->fresh()->status)->toBe('approved_separate');
})->group('regression');

it('bulk-approving rescan-sourced duplicates as separate creates no clones', function () {
    $this->actingAs(resolutionUser());

    $people = Participant::factory()->count(5)->create();
    foreach ($people as $p) {
        PotentialDuplicate::create([
            'source_type' => 'duplicate_rescan',
            'source_identifier' => (string) $p->id,
            'proposed_data' => [
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'date_of_birth' => optional($p->date_of_birth)->format('Y-m-d'),
            ],
            'matched_participant_id' => $p->id,
            'matching_criteria' => [],
            'confidence_score' => 68.75,
            'status' => 'pending',
            'created_by' => Auth::id(),
        ]);
    }

    $countBefore = Participant::count();

    Livewire::test(DuplicateResolutionManagement::class)
        ->call('bulkApprove', 'approved_separate')
        ->assertHasNoErrors();

    expect(Participant::count())->toBe($countBefore);
    expect(PotentialDuplicate::where('status', 'approved_separate')->count())->toBe(5);
})->group('regression');

it('STILL creates a participant when approving an import/intake-hold duplicate as separate', function () {
    // Guards the legitimate path: a manual/import hold's proposed_data is a
    // genuinely new, not-yet-persisted person and SHOULD be created.
    $this->actingAs(resolutionUser());

    $household = Household::factory()->create();
    $matched = Participant::factory()->create([
        'household_id' => $household->id,
        'first_name' => 'Robert',
        'last_name' => 'Existing',
    ]);

    $dup = PotentialDuplicate::create([
        'source_type' => 'manual',
        'source_identifier' => 'intake-session-abc',
        'proposed_data' => [
            'first_name' => 'Roberto',       // a different, new person
            'last_name' => 'Newcomer',
            'date_of_birth' => '1990-05-05',
            // no household_id -> falls back to matched participant's household
        ],
        'matched_participant_id' => $matched->id,
        'matching_criteria' => [],
            'confidence_score' => 72.0,
        'status' => 'pending',
        'created_by' => Auth::id(),
    ]);

    $countBefore = Participant::count();

    Livewire::test(DuplicateResolutionManagement::class)
        ->call('approveSeparate', $dup->id)
        ->assertHasNoErrors();

    expect(Participant::count())->toBe($countBefore + 1);
    expect($dup->fresh()->status)->toBe('approved_separate');
    expect(Participant::where('first_name', 'Roberto')->where('last_name', 'Newcomer')->exists())->toBeTrue();
})->group('regression');
