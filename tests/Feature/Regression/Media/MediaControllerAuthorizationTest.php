<?php

/*
|----------------------------------------------------------------------
| P-39a: end-to-end MediaController + MediaPolicy authorization.
|
| Exercises the full route binding, Spatie media resolution, policy
| dispatch, and HTTP response codes for the /media/{media}/preview and
| /media/{media}/download routes.
|
| Per-parent rule correctness is covered exhaustively in
| MediaAuthorizationRuleTest. This file confirms the wiring: the
| policy IS invoked, returns 403 / 200 as expected, and admins
| short-circuit. Uses Requirement as the representative parent — the
| controller and policy are parent-type-agnostic, so a single happy/
| failure/admin matrix here is sufficient evidence the integration
| works.
|
| Orphaned media (parent deleted) and unregistered-model default-deny
| are also covered here because both require the policy/controller
| stack to be live.
|----------------------------------------------------------------------
*/

use App\Models\HousingJob;
use App\Models\Requirement;
use App\Models\User;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    Storage::fake('private_media');
    Storage::fake('local');
    Storage::fake('public');
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

function attachMediaToRequirement(Requirement $req): Media
{
    $file = UploadedFile::fake()->createWithContent('p39a-test.pdf', '%PDF-1.4 test');

    return $req->addMedia($file->getRealPath())
        ->usingName('p39a-test.pdf')
        ->usingFileName('p39a-test.pdf')
        ->toMediaCollection('attachments');
}

function userWithProjectsView(): User
{
    $user = User::factory()->create();
    $role = \App\Models\Role::firstOrCreate(
        ['name' => 'p39a_projects_view'],
        ['display_name' => 'P-39a projects.view role', 'description' => 'Fixture role.']
    );
    $permId = \App\Models\Permission::where('name', 'projects.view')->value('id');
    if ($permId) {
        $role->permissions()->syncWithoutDetaching([$permId]);
    }
    $user->roles()->attach($role->id);

    return $user;
}

it('returns 403 when an authenticated user lacks projects.view on a Requirement media', function () {
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'source_id' => null,
    ]);
    $media = attachMediaToRequirement($req);

    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('media.download', $media))
        ->assertForbidden();
})->group('regression');

it('returns 200 to a user with projects.view on Requirement media', function () {
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'source_id' => null,
    ]);
    $media = attachMediaToRequirement($req);

    $user = userWithProjectsView();

    $this->actingAs($user)
        ->get(route('media.download', $media))
        ->assertOk();
})->group('regression');

it('admins can download any Requirement media regardless of permission', function () {
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'source_id' => null,
    ]);
    $media = attachMediaToRequirement($req);

    $this->actingAs($this->admin)
        ->get(route('media.download', $media))
        ->assertOk();
})->group('regression');

it('admins can preview Requirement media', function () {
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'source_id' => null,
    ]);
    $media = attachMediaToRequirement($req);

    $this->actingAs($this->admin)
        ->get(route('media.preview', $media))
        ->assertOk();
})->group('regression');

it('returns 403 on preview when user lacks permission', function () {
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'source_id' => null,
    ]);
    $media = attachMediaToRequirement($req);

    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('media.preview', $media))
        ->assertForbidden();
})->group('regression');

// -----------------------------------------------------------------
// Orphaned media: parent record deleted while media row remains.
// Policy must default-deny for non-admins; admins still pass via
// short-circuit (which happens BEFORE the orphan check).
// -----------------------------------------------------------------

it('returns 403 to non-admins for media whose parent has been deleted', function () {
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'source_id' => null,
    ]);
    $media = attachMediaToRequirement($req);

    // Wipe the parent row but leave the media row.
    Requirement::query()->whereKey($req->id)->delete();

    $user = userWithProjectsView();

    $this->actingAs($user)
        ->get(route('media.download', $media))
        ->assertForbidden();
})->group('regression');

// -----------------------------------------------------------------
// Unregistered model: parent implements HasMedia but NOT
// HasMediaAuthorization. MediaPolicy must default-deny.
//
// All production HasMedia models now implement HasMediaAuthorization
// (asserted in MediaAuthorizationRuleTest's contract test). To exercise
// the unregistered-model branch, we point a Media row's morph fields
// at a User row (User does not implement HasMedia at all, and
// importantly does not implement HasMediaAuthorization). The policy
// reaches `instanceof HasMediaAuthorization` and returns false.
// -----------------------------------------------------------------

it('returns 403 when the parent does not implement HasMediaAuthorization', function () {
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'source_id' => null,
    ]);
    $media = attachMediaToRequirement($req);

    // Re-point the media row to a User (a model that does not implement
    // the contract). Bypass mass-assignment via direct DB update so we
    // don't have to fight $fillable on Spatie's Media model.
    $bystander = User::factory()->create();
    Media::query()->whereKey($media->id)->update([
        'model_type' => User::class,
        'model_id' => $bystander->id,
    ]);

    $user = userWithProjectsView();

    $this->actingAs($user)
        ->get(route('media.download', $media->fresh()))
        ->assertForbidden();
})->group('regression');

// -----------------------------------------------------------------
// Query-count guard: the happy path must not balloon into N+1 territory.
// Generous ceiling (10) covers route binding, session warmup, the
// parent fetch through Spatie's morphTo, the user's permission/role
// joins, and the policy dispatch. Pre-39a behavior was ~4 queries;
// this should land at 6-8.
// -----------------------------------------------------------------

it('happy path stays within a query budget', function () {
    $job = HousingJob::factory()->create();
    $req = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_AD_HOC,
        'source_id' => null,
    ]);
    $media = attachMediaToRequirement($req);
    $user = userWithProjectsView();

    \DB::connection()->enableQueryLog();

    $this->actingAs($user)
        ->get(route('media.download', $media))
        ->assertOk();

    $queries = collect(\DB::connection()->getQueryLog());

    expect($queries->count())->toBeLessThanOrEqual(10);
})->group('regression');
