<?php

/*
| Phase 6: viewer enhancements.
| - update_type enum accepts the new 'action' and 'email' values
| - intake viewer shows the "General intake" sand banner + CTAs
| - project-attached viewer shows the teal project banner
| - the requirements panel renders inline when the log has requirements
*/

use App\Livewire\CommunicationsLogManagement;
use App\Models\CommunicationLog;
use App\Models\CommunicationLogUpdate;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('communication_log_updates.update_type now accepts action + email', function () {
    $log = CommunicationLog::factory()->create();

    foreach (['action', 'email'] as $kind) {
        $u = CommunicationLogUpdate::create([
            'communication_log_id' => $log->id,
            'update_content' => 'Test '.$kind,
            'update_type' => $kind,
            'created_by' => $this->user->id,
        ]);
        expect($u->fresh()->update_type)->toBe($kind);
    }
})->group('regression');

it('intake viewer shows the General intake banner + Convert CTA', function () {
    $log = CommunicationLog::factory()->create([
        'housing_job_id' => null,
        'contact_first_name' => 'Maria',
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->assertSee('General intake')
        ->assertSee('Convert to project')
        ->assertSee('Attach to existing');
})->group('regression');

it('project-attached viewer shows the teal project banner', function () {
    $job = HousingJob::factory()->create(['job_number' => 'JOB-2026-99999', 'job_title' => 'Test rehab']);
    $log = CommunicationLog::factory()->create(['housing_job_id' => $job->id]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->assertSee('JOB-2026-99999')
        ->assertSee('Test rehab')
        ->assertDontSee('General intake');
})->group('regression');

it('viewer renders the requirements-panel inline when the log has intake requirements', function () {
    $log = CommunicationLog::factory()->create(['housing_job_id' => null]);

    IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Hardship statement',
        'key' => 'hardship',
        'kind' => 'form',
        'status' => IntakeRequirement::STATUS_PENDING,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);

    Livewire::actingAs($this->user)
        ->test(CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->assertSee('Application requirements')
        ->assertSee('Hardship statement');
})->group('regression');
