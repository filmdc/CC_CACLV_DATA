<?php

use App\Livewire\Homes\CallDetail;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\IntakeConversion\ConversionBlocked;
use App\Services\IntakeConversion\ConversionInput;
use App\Services\IntakeConversion\ConvertIntakeToProject;
use Livewire\Livewire;

function convertSvcUser(array $permissionNames = []): User
{
    $role = Role::firstOrCreate(['name' => 'convert_svc_test'], ['display_name' => 'Convert Svc Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function convertSvcInput(array $overrides = []): ConversionInput
{
    return new ConversionInput(...array_merge([
        'jobTitle' => 'Roof repair',
        'jobDescription' => 'Fix the roof',
        'programId' => null,
        'participantAction' => 'create_new',
        'propertyAction' => 'skip',
        'requirementOverride' => false,
        'overrideReason' => null,
    ], $overrides));
}

it('refuses a log with no requirements (nothing_to_track)', function () {
    $log = CommunicationLog::factory()->create();
    $actor = convertSvcUser();

    try {
        app(ConvertIntakeToProject::class)->convert($log, convertSvcInput(), $actor);
        $this->fail('Expected ConversionBlocked');
    } catch (ConversionBlocked $e) {
        expect($e->reasonCode)->toBe(ConversionBlocked::NOTHING_TO_TRACK);
    }

    expect(HousingJob::count())->toBe(0);
});

it('refuses an already-converted log', function () {
    $actor = convertSvcUser();
    $job = HousingJob::factory()->create();
    $log = CommunicationLog::factory()->create(['converted_to_job_id' => $job->id]);

    try {
        app(ConvertIntakeToProject::class)->convert($log, convertSvcInput(), $actor);
        $this->fail('Expected ConversionBlocked');
    } catch (ConversionBlocked $e) {
        expect($e->reasonCode)->toBe(ConversionBlocked::ALREADY_CONVERTED);
    }
});

it('refuses unmet Phase-1 requirements without an override, and rolls everything back', function () {
    $actor = convertSvcUser();
    $log = CommunicationLog::factory()->create();
    IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'status' => IntakeRequirement::STATUS_PENDING,
    ]);

    $participantsBefore = \App\Models\Participant::count();

    try {
        app(ConvertIntakeToProject::class)->convert($log, convertSvcInput(), $actor);
        $this->fail('Expected ConversionBlocked');
    } catch (ConversionBlocked $e) {
        expect($e->reasonCode)->toBe(ConversionBlocked::UNMET_REQUIREMENTS);
    }

    // The participant created earlier in the transaction must be rolled back.
    expect(\App\Models\Participant::count())->toBe($participantsBefore);
    expect(HousingJob::count())->toBe(0);
    expect($log->fresh()->converted_to_job_id)->toBeNull();
});

it('refuses an override without a reason', function () {
    $actor = convertSvcUser();
    $log = CommunicationLog::factory()->create();
    IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'status' => IntakeRequirement::STATUS_PENDING,
    ]);

    try {
        app(ConvertIntakeToProject::class)->convert(
            $log,
            convertSvcInput(['requirementOverride' => true, 'overrideReason' => '   ']),
            $actor
        );
        $this->fail('Expected ConversionBlocked');
    } catch (ConversionBlocked $e) {
        expect($e->reasonCode)->toBe(ConversionBlocked::OVERRIDE_REASON_REQUIRED);
    }
});

it('refuses create_new when the log has no contact name', function () {
    $actor = convertSvcUser();
    $log = CommunicationLog::factory()->create([
        'contact_first_name' => null,
        'contact_last_name' => null,
    ]);
    IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'status' => IntakeRequirement::STATUS_RECEIVED,
    ]);

    try {
        app(ConvertIntakeToProject::class)->convert($log, convertSvcInput(), $actor);
        $this->fail('Expected ConversionBlocked');
    } catch (ConversionBlocked $e) {
        expect($e->reasonCode)->toBe(ConversionBlocked::MISSING_CONTACT_NAME);
    }
});

it('converts a ready log: job attributes, stamp, and carried fund codes match the main app', function () {
    $actor = convertSvcUser();
    $fund = FundCode::factory()->create();
    $log = CommunicationLog::factory()->create(['priority' => 'urgent']);
    $log->anticipatedFundCodes()->attach($fund->id, ['created_by_user_id' => $actor->id, 'created_at' => now()]);
    IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'status' => IntakeRequirement::STATUS_RECEIVED,
    ]);

    $result = app(ConvertIntakeToProject::class)->convert($log, convertSvcInput(), $actor);

    $job = $result->job->fresh();
    expect($job->status)->toBe('pending');
    expect($job->priority)->toBe('emergency'); // urgent → emergency map
    expect($job->job_title)->toBe('Roof repair');
    expect($job->project_manager_id)->toBe($actor->id);
    expect($job->job_number)->not->toBeNull();

    // Log stamped + timeline entry written.
    $fresh = $log->fresh();
    expect($fresh->converted_to_job_id)->toBe($job->id);
    expect($fresh->converted_by)->toBe($actor->id);
    expect($fresh->updates()->where('update_type', 'status_change')->exists())->toBeTrue();

    // Parity: ALL anticipated fund codes carried onto the job.
    expect($job->fundCodes()->pluck('fund_codes.id')->all())->toContain($fund->id);

    // create_new breadcrumb present.
    expect(implode(' ', $result->createdResources))->toContain('created participant');
});

it('portal call-detail convert maps gate refusals onto field errors', function () {
    $user = convertSvcUser(['communications_log.view', 'communications_log.edit', 'projects.create']);
    $this->actingAs($user);

    $log = CommunicationLog::factory()->create();
    IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'status' => IntakeRequirement::STATUS_PENDING,
    ]);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openConvert')
        ->set('convertTitle', 'Blocked convert')
        ->call('doConvert')
        ->assertHasErrors(['requirementOverride']);

    expect($log->fresh()->converted_to_job_id)->toBeNull();
});

it('portal call-detail convert creates the job and redirects to the project page', function () {
    $user = convertSvcUser(['communications_log.view', 'communications_log.edit', 'projects.create']);
    $this->actingAs($user);

    $log = CommunicationLog::factory()->create();
    IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'status' => IntakeRequirement::STATUS_RECEIVED,
    ]);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openConvert')
        ->set('convertTitle', 'Portal conversion')
        ->set('propertyAction', 'skip')
        ->call('doConvert')
        ->assertHasNoErrors()
        ->assertRedirect(route('homes.project', $log->fresh()->converted_to_job_id));

    expect($log->fresh()->converted_to_job_id)->not->toBeNull();
});
