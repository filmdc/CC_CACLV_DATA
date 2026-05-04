<?php

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\User;
use App\Notifications\RequirementUpstreamUploadedNotification;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

/*
|----------------------------------------------------------------------
| Phase 3B Step 3 — upstream-upload trigger via the milestone graph.
|
| Trigger: an attachment is added to an upstream requirement whose
| source milestone is depended on by a milestone holding open
| requirements. Recipient: each downstream requirement's owner_user_id.
| Channels: in-app + email (mail gated on assignment_email opt-out).
|
| The fixture pattern below builds two milestones with a dependency
| edge and one open task on each, then exercises the upload via the
| Requirement::notifyDownstreamOfUpload entry point. Tests at the
| Livewire level (saveRequirementCompletion) cover the integrated
| Spatie media path and confirm single-fire across multi-file uploads.
|----------------------------------------------------------------------
*/

function makeUpstreamDownstreamPair(?User $owner = null): array
{
    $job = HousingJob::factory()->create();
    $upstreamMilestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'name' => 'Scope',
    ]);
    $downstreamMilestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'name' => 'Review',
    ]);
    $downstreamMilestone->dependencies()->attach($upstreamMilestone->id);

    $upstream = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $upstreamMilestone->id,
        'owner_user_id' => null,
    ]);
    $downstream = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $downstreamMilestone->id,
        'owner_user_id' => $owner?->id,
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);

    return [$job, $upstreamMilestone, $downstreamMilestone, $upstream, $downstream];
}

it('notifies the downstream owner when an upstream milestone-source requirement gets an upload', function () {
    Notification::fake();

    $tony = User::factory()->create();
    $kaitlyn = User::factory()->create();
    [, , , $upstream] = makeUpstreamDownstreamPair($kaitlyn);

    $upstream->notifyDownstreamOfUpload($tony);

    Notification::assertSentTo($kaitlyn, RequirementUpstreamUploadedNotification::class, function ($n) use ($upstream, $tony) {
        return $n->upstreamRequirement->id === $upstream->id
            && $n->uploader->id === $tony->id;
    });
})->group('regression');

it('is a no-op when the upstream requirement is fund-code-source (no milestone graph)', function () {
    Notification::fake();

    $tony = User::factory()->create();
    $kaitlyn = User::factory()->create();
    [$job, , , , ] = makeUpstreamDownstreamPair($kaitlyn);

    // Build a separate fund-code-source upstream on the same project.
    $fundUpstream = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_FUND_CODE,
        'source_id' => 99,
    ]);

    $fundUpstream->notifyDownstreamOfUpload($tony);

    Notification::assertNothingSent();
})->group('regression');

it('is a no-op when the upstream requirement is ad-hoc (no milestone graph)', function () {
    Notification::fake();

    $tony = User::factory()->create();
    $kaitlyn = User::factory()->create();
    [$job, , , , ] = makeUpstreamDownstreamPair($kaitlyn);

    $adHocUpstream = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'source_id' => null,
    ]);

    $adHocUpstream->notifyDownstreamOfUpload($tony);

    Notification::assertNothingSent();
})->group('regression');

it('is a no-op when the upstream milestone has no dependents', function () {
    Notification::fake();

    $tony = User::factory()->create();
    $job = HousingJob::factory()->create();
    $isolatedMilestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $upstream = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $isolatedMilestone->id,
    ]);

    $upstream->notifyDownstreamOfUpload($tony);

    Notification::assertNothingSent();
})->group('regression');

it('is a no-op when the source_id is null (milestone deleted via keep-branch)', function () {
    Notification::fake();

    $tony = User::factory()->create();
    $upstream = Requirement::factory()->document()->create([
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => null,
    ]);

    $upstream->notifyDownstreamOfUpload($tony);

    Notification::assertNothingSent();
})->group('regression');

it('does not notify the uploader if they happen to own a downstream requirement (no self-pings)', function () {
    Notification::fake();

    $tony = User::factory()->create();
    [, , , $upstream] = makeUpstreamDownstreamPair($tony); // Tony owns the downstream

    $upstream->notifyDownstreamOfUpload($tony);

    Notification::assertNotSentTo($tony, RequirementUpstreamUploadedNotification::class);
})->group('regression');

it('skips downstream requirements that are already closed', function () {
    Notification::fake();

    $tony = User::factory()->create();
    $kaitlyn = User::factory()->create();
    [, , $downstreamMilestone, $upstream, $downstream] = makeUpstreamDownstreamPair($kaitlyn);

    // Close the downstream — the upstream upload should NOT ping.
    $downstream->update(['status' => Requirement::STATUS_COMPLETE]);

    $upstream->notifyDownstreamOfUpload($tony);

    Notification::assertNothingSent();
})->group('regression');

it('routes the mail channel via assignment_email opt-out (downstream owner opted in)', function () {
    Notification::fake();

    $tony = User::factory()->create();
    $kaitlyn = User::factory()->create(['notification_preferences' => null]); // null = opt-in
    [, , , $upstream] = makeUpstreamDownstreamPair($kaitlyn);

    $upstream->notifyDownstreamOfUpload($tony);

    Notification::assertSentTo($kaitlyn, RequirementUpstreamUploadedNotification::class, function ($notification, array $channels) {
        return in_array('database', $channels) && in_array('mail', $channels);
    });
})->group('regression');

it('suppresses the mail channel when downstream owner opts out of assignment_email', function () {
    Notification::fake();

    $tony = User::factory()->create();
    $kaitlyn = User::factory()->create([
        'notification_preferences' => ['assignment_email' => false],
    ]);
    [, , , $upstream] = makeUpstreamDownstreamPair($kaitlyn);

    $upstream->notifyDownstreamOfUpload($tony);

    Notification::assertSentTo($kaitlyn, RequirementUpstreamUploadedNotification::class, function ($notification, array $channels) {
        return in_array('database', $channels) && ! in_array('mail', $channels);
    });
})->group('regression');

it('fires once per save action even when multiple files land in a single upload', function () {
    Notification::fake();

    $kaitlyn = User::factory()->create();
    [$job, $upstreamMilestone, $downstreamMilestone, $upstream] = makeUpstreamDownstreamPair($kaitlyn);

    // Make the upstream a document-type with no media yet, so the
    // saveRequirementCompletion path takes the "complete with files"
    // branch.
    $upstream->update(['type' => Requirement::TYPE_DOCUMENT]);

    // Use createWithContent so the fake file actually has bytes;
    // UploadedFile::fake()->create() returns size-only fakes that
    // Spatie's media-library rejects with mime=application/x-empty
    // because the bytes-on-disk are zero. The PDF magic header is
    // enough to satisfy the mime sniff.
    $pdfContent = "%PDF-1.4\n".str_repeat('x', 1024);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('showCompleteRequirementModal', $upstream->id)
        ->set('completionFiles', [
            UploadedFile::fake()->createWithContent('a.pdf', $pdfContent),
            UploadedFile::fake()->createWithContent('b.pdf', $pdfContent),
            UploadedFile::fake()->createWithContent('c.pdf', $pdfContent),
        ])
        ->call('saveRequirementCompletion');

    // Three files uploaded; exactly one upstream-upload notification
    // dispatched (downstream has one open requirement owned by Kaitlyn).
    Notification::assertSentToTimes($kaitlyn, RequirementUpstreamUploadedNotification::class, 1);
})->group('regression');
