<?php

/*
|----------------------------------------------------------------------
| Central Files view — one place per side to see/manage uploaded docs.
|
|   - Phase 2 / Project: ProjectManagement "Files" tab aggregates
|     project-requirement attachments + milestone documents.
|   - Phase 1 / Intake: CommunicationsLogManagement "Documents on file"
|     section aggregates intake-requirement documents.
|
| Both render through the shared <x-files-table> with View/Download/Remove.
|----------------------------------------------------------------------
*/

use App\Livewire\CommunicationsLogManagement;
use App\Livewire\ProjectManagement;
use App\Models\CommunicationLog;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\MilestoneDocument;
use App\Models\Permission;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

function userWith(array $permissionNames): User
{
    $user = User::factory()->create();
    if ($permissionNames === []) {
        return $user;
    }

    $role = Role::firstOrCreate(
        ['name' => 'files-test-'.implode('-', $permissionNames)],
        ['display_name' => 'Files test role', 'description' => 'scoped test role']
    );
    $ids = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();
    $role->permissions()->syncWithoutDetaching($ids);
    $user->roles()->attach($role->id);

    return $user;
}

// ---- Phase 2 / Project ---------------------------------------------

it('project Files tab aggregates requirement attachments and milestone documents', function () {
    $job = HousingJob::factory()->create(['project_manager_id' => $this->admin->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    $doc = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
        'title' => 'Signed scope of work',
    ]);
    $doc->addMediaFromString("%PDF-1.4\nx")->usingFileName('scope.pdf')->toMediaCollection('attachments');

    $mdoc = MilestoneDocument::create([
        'milestone_id' => $milestone->id,
        'document_type' => 'building_permit',
        'status' => 'pending_review',
    ]);
    $mdoc->addMediaFromString("%PDF-1.4\ny")->usingFileName('permit.pdf')->toMediaCollection('documents');

    $component = Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('activeTab', 'files');

    $rows = $component->instance()->fileRows();
    expect(collect($rows)->pluck('name'))->toContain('scope.pdf')->toContain('permit.pdf');

    $html = $component->html();
    expect($html)->toContain('scope.pdf')
        ->toContain('permit.pdf')
        ->toContain('Signed scope of work')
        ->toContain('Building Permit')                       // document_type humanized
        ->toContain(route('media.download', $doc->getMedia('attachments')->first()->id));
});

it('project Files tab offers Remove to projects.manage users only', function () {
    $job = HousingJob::factory()->create(['project_manager_id' => $this->admin->id]);
    $milestone = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);
    $doc = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'source_type' => Requirement::SOURCE_MILESTONE,
        'source_id' => $milestone->id,
    ]);
    $doc->addMediaFromString("%PDF-1.4\nx")->usingFileName('proof.pdf')->toMediaCollection('attachments');
    $mediaId = $doc->getMedia('attachments')->first()->id;

    $rows = Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->instance()
        ->fileRows();

    expect($rows[0]['remove_call'])->toBe("deleteRequirementMedia({$doc->id}, {$mediaId})");
});

// ---- Phase 1 / Intake ----------------------------------------------

it('intake viewer Documents-on-file section lists intake requirement files', function () {
    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->document()->create([
        'communication_log_id' => $log->id,
        'label' => 'Proof of income',
    ]);
    $req->addMediaFromString("%PDF-1.4\nx")->usingFileName('paystub.pdf')->toMediaCollection('documents');

    $component = Livewire::actingAs($this->admin)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id);

    $rows = $component->instance()->documentFileRows();
    expect(collect($rows)->pluck('name'))->toContain('paystub.pdf');
    expect($rows[0]['source'])->toContain('Proof of income');

    $html = $component->html();
    expect($html)->toContain('Documents on file')
        ->toContain('paystub.pdf')
        ->toContain(route('media.download', $req->getMedia('documents')->first()->id));
});

// ---- Media authorization contract (closes P-39a gap) ---------------

it('IntakeRequirement authorizes media view for users with communications_log.view', function () {
    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->document()->create(['communication_log_id' => $log->id]);
    $req->addMediaFromString("%PDF-1.4\nx")->usingFileName('doc.pdf')->toMediaCollection('documents');
    $media = $req->getMedia('documents')->first();

    expect($req)->toBeInstanceOf(\App\Contracts\HasMediaAuthorization::class);

    $viewer = userWith(['communications_log.view']);
    $stranger = userWith([]);

    expect($req->canViewMedia($viewer, $media))->toBeTrue();
    expect($req->canViewMedia($stranger, $media))->toBeFalse();
});
