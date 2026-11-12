<?php

use App\Livewire\Homes\CallDetail;
use App\Models\CommunicationLog;
use App\Models\IntakeRequirement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\MediaValidationService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Uploads keep the name the file already had.
 *
 * Several upload paths used to store Livewire's random temp-file hash as
 * `file_name`, so the checklist surfaced "8f2c…c1a9.jpg" — meaningless to
 * the person who had just picked "Pay Stub March.pdf" off her desktop.
 */
function namingUser(): User
{
    $role = Role::firstOrCreate(['name' => 'file_naming_test'], ['display_name' => 'File Naming Test']);
    $ids = collect(['communications_log.view', 'communications_log.edit'])
        ->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

it('stores an uploaded file under its own name, not a random hash', function () {
    $this->actingAs(namingUser());

    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'status' => IntakeRequirement::STATUS_PENDING,
    ]);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openUpload', $req->id)
        ->set('requirementFiles', [UploadedFile::fake()->image('Pay Stub March.jpg')])
        ->call('uploadRequirementFiles')
        ->assertHasNoErrors();

    $media = $req->fresh()->getMedia('documents')->first();

    // On disk: sanitized, but still recognisable — no 40-character hash.
    expect($media->file_name)->toBe('pay-stub-march.jpg');
    // On screen and on download: exactly what she picked.
    expect($media->name)->toBe('Pay Stub March.jpg');
    expect(MediaValidationService::displayName($media))->toBe('Pay Stub March.jpg');
});

it('surfaces the original name on the call screen', function () {
    $this->actingAs(namingUser());

    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
    ]);

    Livewire::test(CallDetail::class, ['log' => $log])
        ->call('openUpload', $req->id)
        ->set('requirementFiles', [UploadedFile::fake()->image('Roof Estimate.png')])
        ->call('uploadRequirementFiles');

    Livewire::test(CallDetail::class, ['log' => $log])
        ->assertSee('Roof Estimate.png');
});

it('repairs the display of files already stored under a hash', function () {
    // What the old upload path left behind: a hashed file_name, with the
    // real name sitting unused in `name`.
    $media = new Media([
        'name' => 'Tax Return 2025',
        'file_name' => 'k3Jd8sLpQw2mNx9vRt4yBc7hZa1eFg6uI0oP5nMs.pdf',
    ]);

    expect(MediaValidationService::displayName($media))->toBe('Tax Return 2025.pdf');
});

it('falls back to the stored name when nothing better exists', function () {
    $media = new Media(['name' => '', 'file_name' => 'scan-0001.pdf']);

    expect(MediaValidationService::displayName($media))->toBe('scan-0001.pdf');
});
