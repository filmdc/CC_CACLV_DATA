<?php

use App\Livewire\Homes\CallDetail;
use App\Models\CommunicationLog;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\CommunicationLogMergeService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function mergeUser(array $permissionNames = []): User
{
    $role = Role::firstOrCreate(['name' => 'merge_test'], ['display_name' => 'Merge Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function mergeFullUser(): User
{
    return mergeUser(['communications_log.view', 'communications_log.edit', 'communications_log.delete']);
}

// ── The service ───────────────────────────────────────────────────────

it('moves the duplicate timeline onto the survivor and logs the original call', function () {
    $survivor = CommunicationLog::factory()->create(['summary' => 'First call about the roof.']);
    $duplicate = CommunicationLog::factory()->create([
        'summary' => 'Called again about the same roof.',
        'communication_date' => '2026-08-01',
    ]);
    $duplicate->addUpdate('Mailed the application packet.', 'note');
    $duplicate->addUpdate('Left a voicemail.', 'follow_up');

    $summary = app(CommunicationLogMergeService::class)->merge($survivor, $duplicate);

    expect($summary['timeline_entries_moved'])->toBe(2);

    $contents = $survivor->fresh()->updates->pluck('update_content');

    expect($contents)->toContain('Mailed the application packet.')
        ->and($contents)->toContain('Left a voicemail.')
        ->and($contents->filter(fn ($c) => str_contains($c, "Merged from COMM-{$duplicate->id}"))->count())->toBe(1)
        ->and($contents->filter(fn ($c) => str_contains($c, 'Called again about the same roof.'))->count())->toBe(1);
});

it('backdates the folded-in root call so the timeline stays chronological', function () {
    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->create(['communication_date' => '2026-08-01']);

    app(CommunicationLogMergeService::class)->merge($survivor, $duplicate);

    $root = $survivor->fresh()->updates
        ->first(fn ($u) => str_contains($u->update_content, "Merged from COMM-{$duplicate->id}"));

    expect($root->created_at->toDateString())->toBe('2026-08-01');
});

it('soft-deletes the duplicate and tombstones where it went', function () {
    $actor = mergeFullUser();
    $this->actingAs($actor);

    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->create();

    app(CommunicationLogMergeService::class)->merge($survivor, $duplicate, $actor);

    $fresh = CommunicationLog::withTrashed()->find($duplicate->id);

    expect($fresh->trashed())->toBeTrue()
        ->and($fresh->merged_into_id)->toBe($survivor->id)
        ->and($fresh->merged_by)->toBe($actor->id)
        ->and($fresh->merged_at)->not->toBeNull()
        ->and(CommunicationLog::find($duplicate->id))->toBeNull()
        ->and($survivor->fresh()->mergedFrom->pluck('id')->all())->toBe([$duplicate->id]);
});

it('moves requirements the survivor does not have', function () {
    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->create();

    IntakeRequirement::factory()->create([
        'communication_log_id' => $duplicate->id,
        'label' => 'Deed',
        'key' => 'deed',
    ]);

    $summary = app(CommunicationLogMergeService::class)->merge($survivor, $duplicate);

    expect($summary['requirements_moved'])->toBe(1)
        ->and($survivor->fresh()->intakeRequirements->pluck('label')->all())->toBe(['Deed']);
});

it('folds a requirement both entries track into one, keeping the met status', function () {
    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->create();

    $kept = IntakeRequirement::factory()->create([
        'communication_log_id' => $survivor->id,
        'label' => 'Photo ID',
        'key' => 'photo-id',
        'status' => IntakeRequirement::STATUS_PENDING,
    ]);

    IntakeRequirement::factory()->received()->create([
        'communication_log_id' => $duplicate->id,
        'label' => 'Photo ID',
        'key' => 'photo-id',
    ]);

    $summary = app(CommunicationLogMergeService::class)->merge($survivor, $duplicate);

    expect($summary['requirements_deduped'])->toBe(1)
        ->and($summary['requirements_moved'])->toBe(0)
        ->and($survivor->fresh()->intakeRequirements)->toHaveCount(1)
        ->and($kept->fresh()->status)->toBe(IntakeRequirement::STATUS_RECEIVED);
});

it('never downgrades a requirement the survivor has already received', function () {
    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->create();

    $kept = IntakeRequirement::factory()->received()->create([
        'communication_log_id' => $survivor->id,
        'key' => 'deed',
        'label' => 'Deed',
    ]);

    IntakeRequirement::factory()->create([
        'communication_log_id' => $duplicate->id,
        'key' => 'deed',
        'label' => 'Deed',
        'status' => IntakeRequirement::STATUS_PENDING,
    ]);

    app(CommunicationLogMergeService::class)->merge($survivor, $duplicate);

    expect($kept->fresh()->status)->toBe(IntakeRequirement::STATUS_RECEIVED);
});

it('unions the via sources when two copies of a requirement fold together', function () {
    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->create();

    $kept = IntakeRequirement::factory()->create([
        'communication_log_id' => $survivor->id,
        'key' => 'deed',
        'via' => ['service:1'],
    ]);

    IntakeRequirement::factory()->create([
        'communication_log_id' => $duplicate->id,
        'key' => 'deed',
        'via' => ['service:2'],
    ]);

    app(CommunicationLogMergeService::class)->merge($survivor, $duplicate);

    expect($kept->fresh()->via)->toEqualCanonicalizing(['service:1', 'service:2']);
});

it('fills only the blank fields on the survivor', function () {
    $survivor = CommunicationLog::factory()->create([
        'contact_phone' => '610-555-1111',
        'contact_email' => null,
    ]);
    $duplicate = CommunicationLog::factory()->create([
        'contact_phone' => '610-555-9999',
        'contact_email' => 'homeowner@example.com',
    ]);

    app(CommunicationLogMergeService::class)->merge($survivor, $duplicate);

    $fresh = $survivor->fresh();

    expect($fresh->contact_phone)->toBe('610-555-1111')
        ->and($fresh->contact_email)->toBe('homeowner@example.com');
});

it('adopts an open follow-up when the survivor has none', function () {
    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->withFollowUp()->create();

    $summary = app(CommunicationLogMergeService::class)->merge($survivor, $duplicate);

    $fresh = $survivor->fresh();

    expect($summary['follow_up_adopted'])->toBeTrue()
        ->and($fresh->follow_up_date->toDateString())->toBe('2026-05-01')
        ->and($fresh->follow_up_action)->toBe('Call back about contractor estimate.');
});

it('keeps the survivor follow-up and records the other one on the timeline', function () {
    $survivor = CommunicationLog::factory()->create([
        'follow_up_date' => '2026-04-01',
        'follow_up_action' => 'Survivor callback.',
    ]);
    $duplicate = CommunicationLog::factory()->withFollowUp()->create();

    $summary = app(CommunicationLogMergeService::class)->merge($survivor, $duplicate);

    $fresh = $survivor->fresh();

    expect($summary['follow_up_adopted'])->toBeFalse()
        ->and($fresh->follow_up_action)->toBe('Survivor callback.')
        ->and($fresh->updates->pluck('update_content')->filter(
            fn ($c) => str_contains($c, 'Follow-up carried over')
        ))->not->toBeEmpty();
});

it('moves pivot rows the survivor is missing without duplicating shared ones', function () {
    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->create();

    $shared = Service::factory()->create();
    $onlyOnDuplicate = Service::factory()->create();

    DB::table('communication_log_service')->insert([
        ['communication_log_id' => $survivor->id, 'service_id' => $shared->id, 'created_at' => now(), 'updated_at' => now()],
        ['communication_log_id' => $duplicate->id, 'service_id' => $shared->id, 'created_at' => now(), 'updated_at' => now()],
        ['communication_log_id' => $duplicate->id, 'service_id' => $onlyOnDuplicate->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $summary = app(CommunicationLogMergeService::class)->merge($survivor, $duplicate);

    expect($summary['services_moved'])->toBe(1)
        ->and(DB::table('communication_log_service')->where('communication_log_id', $survivor->id)->pluck('service_id')->sort()->values()->all())
        ->toEqualCanonicalizing([$shared->id, $onlyOnDuplicate->id]);
});

it('refuses to merge a record into itself', function () {
    $log = CommunicationLog::factory()->create();

    expect(app(CommunicationLogMergeService::class)->blockedReason($log, $log))
        ->toContain('same entry');
});

it('refuses to merge away an entry that became a live project', function () {
    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->create();

    $job = HousingJob::factory()->create();
    $duplicate->update(['converted_to_job_id' => $job->id]);

    $merger = app(CommunicationLogMergeService::class);

    expect($merger->canMerge($survivor, $duplicate->fresh()))->toBeFalse()
        ->and($merger->blockedReason($survivor, $duplicate->fresh()))->toContain($job->job_number);

    expect(fn () => $merger->merge($survivor, $duplicate->fresh()))
        ->toThrow(RuntimeException::class);
});

it('leaves nothing half-merged when the merge is blocked', function () {
    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->create();
    $duplicate->addUpdate('Should stay put.', 'note');

    $job = HousingJob::factory()->create();
    $duplicate->update(['converted_to_job_id' => $job->id]);

    try {
        app(CommunicationLogMergeService::class)->merge($survivor, $duplicate->fresh());
    } catch (RuntimeException) {
        // expected
    }

    expect($survivor->fresh()->updates)->toHaveCount(0)
        ->and($duplicate->fresh()->updates)->toHaveCount(1);
});

// ── Candidate suggestions ─────────────────────────────────────────────

it('suggests entries that share a last name and first initial', function () {
    $log = CommunicationLog::factory()->create([
        'contact_first_name' => 'Maria',
        'contact_last_name' => 'Gonzalez',
        'contact_phone' => null,
        'contact_email' => null,
    ]);

    $match = CommunicationLog::factory()->create([
        'contact_first_name' => 'M',
        'contact_last_name' => 'Gonzalez',
        'contact_phone' => null,
    ]);

    CommunicationLog::factory()->create([
        'contact_first_name' => 'Bob',
        'contact_last_name' => 'Smith',
        'contact_phone' => null,
    ]);

    $ids = app(CommunicationLogMergeService::class)->findCandidates($log)->pluck('id');

    expect($ids)->toContain($match->id)->toHaveCount(1);
});

it('suggests entries with the same phone number written differently', function () {
    $log = CommunicationLog::factory()->create([
        'contact_last_name' => 'Onlyphone',
        'contact_first_name' => 'A',
        'contact_phone' => '(610) 555-1234',
    ]);

    $match = CommunicationLog::factory()->create([
        'contact_last_name' => 'Different',
        'contact_first_name' => 'Z',
        'contact_phone' => '6105551234',
    ]);

    expect(app(CommunicationLogMergeService::class)->findCandidates($log)->pluck('id'))
        ->toContain($match->id);
});

it('does not suggest an entry that has already been merged away', function () {
    $log = CommunicationLog::factory()->create(['contact_last_name' => 'Gonzalez', 'contact_first_name' => 'Maria']);
    $gone = CommunicationLog::factory()->create(['contact_last_name' => 'Gonzalez', 'contact_first_name' => 'Maria']);
    $keeper = CommunicationLog::factory()->create(['contact_last_name' => 'Gonzalez', 'contact_first_name' => 'Maria']);

    app(CommunicationLogMergeService::class)->merge($keeper, $gone);

    expect(app(CommunicationLogMergeService::class)->findCandidates($log)->pluck('id'))
        ->not->toContain($gone->id);
});

// ── The portal screen ─────────────────────────────────────────────────

it('hides merge from a user who cannot delete entries', function () {
    $this->actingAs(mergeUser(['communications_log.view', 'communications_log.edit']));

    $log = CommunicationLog::factory()->create();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->assertSet('showMerge', false)
        ->assertDontSee('Merge duplicate');
});

it('offers merge to a user who can edit and delete', function () {
    $this->actingAs(mergeFullUser());

    $log = CommunicationLog::factory()->create();

    Livewire::test(CallDetail::class, ['log' => $log])
        ->assertSee('Merge duplicate')
        ->call('openMerge')
        ->assertSet('showMerge', true)
        ->assertSet('mergeStep', 1);
});

it('walks pick → preview → merge from the call screen', function () {
    $this->actingAs(mergeFullUser());

    $survivor = CommunicationLog::factory()->create([
        'contact_first_name' => 'Maria',
        'contact_last_name' => 'Gonzalez',
    ]);
    $duplicate = CommunicationLog::factory()->create([
        'contact_first_name' => 'Maria',
        'contact_last_name' => 'Gonzalez',
    ]);
    $duplicate->addUpdate('Second call about the furnace.', 'note');

    Livewire::test(CallDetail::class, ['log' => $survivor])
        ->call('openMerge')
        ->call('selectMergeCandidate', $duplicate->id)
        ->assertHasNoErrors()
        ->assertSet('mergeStep', 2)
        ->call('doMerge')
        ->assertHasNoErrors()
        ->assertSet('showMerge', false);

    expect(CommunicationLog::find($duplicate->id))->toBeNull()
        ->and($survivor->fresh()->updates->pluck('update_content'))
        ->toContain('Second call about the furnace.');
});

it('surfaces the blocked reason instead of merging', function () {
    $this->actingAs(mergeFullUser());

    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->create();
    $job = HousingJob::factory()->create();
    $duplicate->update(['converted_to_job_id' => $job->id]);

    Livewire::test(CallDetail::class, ['log' => $survivor])
        ->call('openMerge')
        ->call('selectMergeCandidate', $duplicate->id)
        ->assertHasErrors('merge')
        ->assertSet('mergeStep', 1);
});

it('blocks doMerge for a user without permission', function () {
    $this->actingAs(mergeUser(['communications_log.view']));

    $survivor = CommunicationLog::factory()->create();

    Livewire::test(CallDetail::class, ['log' => $survivor])
        ->call('doMerge')
        ->assertForbidden();
});

it('tells the recycle bin the record was merged, not plainly deleted', function () {
    $survivor = CommunicationLog::factory()->create();
    $duplicate = CommunicationLog::factory()->create();

    app(CommunicationLogMergeService::class)->merge($survivor, $duplicate);

    $note = app(App\Services\Homes\HousingRecordDeleter::class)
        ->restoreCallNote(CommunicationLog::withTrashed()->find($duplicate->id));

    expect($note)->toContain("Merged into COMM-{$survivor->id}");
});
