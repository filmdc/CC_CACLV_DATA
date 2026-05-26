<?php

/*
| Phase 3: Communication Log v2 edit-form state machine.
|
| Asserts that the form correctly switches between INTAKE and PROJECT
| composition based on housing_job_id, that the anchor radio + picker
| update both ends in sync, that fund-codes and program-services are
| hidden by default in INTAKE (revealable via opt-in links), and that
| existing save behavior survives the state-machine layering.
*/

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\HousingJob;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('showCreateForm puts a new entry into INTAKE state by default', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->assertSet('anchor', 'intake')
        ->assertSet('housing_job_id', '');
})->group('regression');

it('showEditForm derives anchor from the loaded log housing_job_id', function () {
    $job = HousingJob::factory()->create();
    $logWithProject = CommunicationLog::factory()->create(['housing_job_id' => $job->id]);
    $logNoProject = CommunicationLog::factory()->create(['housing_job_id' => null]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $logWithProject->id)
        ->assertSet('anchor', 'project')
        ->assertSet('housing_job_id', $job->id);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $logNoProject->id)
        ->assertSet('anchor', 'intake')
        ->assertSet('housing_job_id', '');
})->group('regression');

it('state computed property derives from housing_job_id', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm');

    expect($component->instance()->state)->toBe('intake');

    $job = HousingJob::factory()->create();
    $component->set('housing_job_id', $job->id);

    expect($component->instance()->state)->toBe('project');
})->group('regression');

it('setAnchor("intake") clears the housing_job_id and snaps to intake', function () {
    $job = HousingJob::factory()->create();
    $log = CommunicationLog::factory()->create(['housing_job_id' => $job->id]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showEditForm', $log->id)
        ->assertSet('anchor', 'project')
        ->call('setAnchor', 'intake')
        ->assertSet('anchor', 'intake')
        ->assertSet('housing_job_id', '')
        ->assertSet('showFundCodesInIntake', false)
        ->assertSet('showServicesInIntake', false);
})->group('regression');

it('setAnchor("project") opens the picker via dispatched event', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->call('setAnchor', 'project')
        ->assertDispatched('open-project-picker');
})->group('regression');

it('selectProject(id) attaches the project and flips anchor to project', function () {
    $job = HousingJob::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->call('selectProject', $job->id)
        ->assertSet('anchor', 'project')
        ->assertSet('housing_job_id', $job->id);
})->group('regression');

it('selectProject(null) detaches and returns to intake', function () {
    $job = HousingJob::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('housing_job_id', $job->id)
        ->set('anchor', 'project')
        ->call('selectProject', null)
        ->assertSet('anchor', 'intake')
        ->assertSet('housing_job_id', '');
})->group('regression');

it('the create form does NOT render the legacy Create Project and Enrollment checkbox', function () {
    // Phase 12.5: this shortcut was removed because conversion now flows
    // through the viewer (attach service → satisfy Phase 1 → gated bottom
    // bar Convert button). The component property still exists for
    // back-compat with save() and tests but the UI affordance is gone.
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->assertDontSee('Create Project and Enrollment')
        ->assertDontSee('Automatically create property');
})->group('regression');

it('the edit form never renders the fund-codes section (managed from the viewer Focus 2)', function () {
    // UX shift: fund codes + housing services are managed from the
    // detail viewer's Focus 2 section, not from the edit form. The
    // form is for logging the communication only.
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->assertSet('anchor', 'intake')
        ->assertDontSee('Fund Codes (Optional)')
        ->assertDontSee('Show fund codes');
})->group('regression');

it('INTAKE state hides program-services section by default; revealServices shows it', function () {
    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->assertSet('showServicesInIntake', false)
        ->assertDontSee('Associate with Program Services (Optional)')
        ->call('revealServices')
        ->assertSet('showServicesInIntake', true)
        ->assertSee('Associate with Program Services (Optional)');
})->group('regression');

it('PROJECT state shows program services unconditionally (fund codes managed from viewer)', function () {
    $job = HousingJob::factory()->create();

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('housing_job_id', $job->id)
        // Program services (legacy CSBG-tied) still appear in PROJECT state.
        // Fund codes are NOT in the form anymore — viewer Focus 2 owns them.
        ->assertSee('Associate with Program Services (Optional)')
        ->assertDontSee('Fund Codes (Optional)');
})->group('regression');

it('resetForm restores the anchor + reveal defaults', function () {
    $job = HousingJob::factory()->create();

    $component = Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('showCreateForm')
        ->set('housing_job_id', $job->id)
        ->set('anchor', 'project')
        ->set('showFundCodesInIntake', true);

    $component->call('showCreateForm')
        ->assertSet('anchor', 'intake')
        ->assertSet('housing_job_id', '')
        ->assertSet('showFundCodesInIntake', false)
        ->assertSet('showServicesInIntake', false);
})->group('regression');
