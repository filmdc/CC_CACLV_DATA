<?php

/*
| Dashboard split: the Housing Dashboard separates intake (CommunicationLog)
| content from project (HousingJob) content into two top-level tabs, each
| with an overview / per-requirement sub-view. Projects are pinnable and
| sortable (by met-requirement count and by age from start date); a per-user
| preference can default the sub-view to "requirements".
*/

use App\Livewire\HousingDashboard;
use App\Models\CommunicationLog;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\Program;
use App\Models\Requirement;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

function splitJob(array $overrides = []): HousingJob
{
    $program = Program::first()
        ?? Program::factory()->create(['housing_module_enabled' => true]);
    if (! $program->housing_module_enabled) {
        $program->update(['housing_module_enabled' => true]);
    }

    return HousingJob::factory()->create(array_merge([
        'program_id' => $program->id,
        'is_template' => false,
        'project_manager_id' => test()->admin->id,
    ], $overrides));
}

function splitOpenIntakeReq(string $label, string $status): IntakeRequirement
{
    $log = CommunicationLog::factory()->create([
        'housing_job_id' => null,
        'converted_to_job_id' => null,
        'category' => 'intake',
    ]);

    return IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => $label,
        'key' => \Illuminate\Support\Str::slug($label).'-'.uniqid(),
        'kind' => 'doc',
        'status' => $status,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);
}

it('keeps intake content off the Projects tab and project content off the Intakes tab', function () {
    $job = splitJob(['job_title' => 'SPLIT_PROJECT_ROW', 'property_id' => null]);
    splitOpenIntakeReq('SPLIT_INTAKE_REQ', IntakeRequirement::STATUS_RECEIVED);

    // Projects/overview (default): sees the project, not the intake cards.
    $projectsHtml = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->assertSet('section', 'projects')
        ->html();
    expect($projectsHtml)->toContain('SPLIT_PROJECT_ROW');
    expect($projectsHtml)->not->toContain('Closest to convert');
    expect($projectsHtml)->not->toContain('data-test="intake-pipeline-card"');

    // Intakes/overview: sees the intake card, not the project rows.
    $intakesHtml = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'intakes')
        ->html();
    expect($intakesHtml)->toContain('Closest to convert');
    expect($intakesHtml)->not->toContain('data-test="dashboard-project-row"');
})->group('regression');

it('togglePinProject attaches a project pin for the current user and detaches on second call', function () {
    $job = splitJob();

    $component = Livewire::actingAs($this->admin)->test(HousingDashboard::class);

    $component->call('togglePinProject', $job->id);
    expect($job->fresh()->pinnedByUsers()->where('users.id', $this->admin->id)->exists())->toBeTrue();

    $component->call('togglePinProject', $job->id);
    expect($job->fresh()->pinnedByUsers()->where('users.id', $this->admin->id)->exists())->toBeFalse();
})->group('regression');

it('floats a pinned project to the top of the per-project list regardless of sort', function () {
    // Alphabetically, AAA sorts before ZZZ. Pinning ZZZ must move it first.
    $first = splitJob(['job_title' => 'AAA_first_alpha', 'property_id' => null]);
    $pinned = splitJob(['job_title' => 'ZZZ_last_alpha', 'property_id' => null]);
    $pinned->pinnedByUsers()->attach($this->admin->id, ['pinned_at' => now()]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->html();

    expect(strpos($html, 'ZZZ_last_alpha'))->toBeLessThan(strpos($html, 'AAA_first_alpha'));
})->group('regression');

it('sorts the per-project list by number of met requirements (most first)', function () {
    $manyMet = splitJob(['job_title' => 'PROJ_many_met', 'property_id' => null]);
    Requirement::factory()->task()->complete()->count(2)->create(['housing_job_id' => $manyMet->id]);

    $noneMet = splitJob(['job_title' => 'PROJ_none_met', 'property_id' => null]);
    Requirement::factory()->task()->create([
        'housing_job_id' => $noneMet->id,
        'status' => Requirement::STATUS_OUTSTANDING,
    ]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->set('projectSort', 'met_desc')
        ->html();

    expect(strpos($html, 'PROJ_many_met'))->toBeLessThan(strpos($html, 'PROJ_none_met'));
})->group('regression');

it('sorts the per-project list by age from start date (oldest first), falling back to request then created', function () {
    $old = splitJob(['job_title' => 'PROJ_old_start', 'property_id' => null, 'start_date' => now()->subYear()->toDateString()]);
    $new = splitJob(['job_title' => 'PROJ_new_start', 'property_id' => null, 'start_date' => now()->subDays(2)->toDateString()]);

    $html = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->set('projectSort', 'age_oldest')
        ->html();

    expect(strpos($html, 'PROJ_old_start'))->toBeLessThan(strpos($html, 'PROJ_new_start'));
})->group('regression');

it('honours the users.housing_dashboard_default_view = requirements preference on mount', function () {
    $this->admin->update(['housing_dashboard_default_view' => 'requirements']);

    Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->assertSet('view', 'requirements');
})->group('regression');

it('defaults the sub-view to overview when no preference is set', function () {
    $this->admin->update(['housing_dashboard_default_view' => null]);

    Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->assertSet('view', 'overview');
})->group('regression');

it('Intakes per-requirement sub-view lists open intake requirements and respects the status filter', function () {
    splitOpenIntakeReq('OPEN_INTAKE_REQ', IntakeRequirement::STATUS_PENDING);
    splitOpenIntakeReq('MET_INTAKE_REQ', IntakeRequirement::STATUS_RECEIVED);

    $component = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->call('setSection', 'intakes')
        ->call('setView', 'requirements');

    // Default status filter = open: sees pending, not the met one.
    $html = $component->html();
    expect($html)->toContain('data-test="dashboard-intake-cut-b"');
    expect($html)->toContain('OPEN_INTAKE_REQ');
    expect($html)->not->toContain('MET_INTAKE_REQ');

    // Switch to met: the inverse.
    $component->set('intake_status', 'met');
    $html = $component->html();
    expect($html)->toContain('MET_INTAKE_REQ');
    expect($html)->not->toContain('OPEN_INTAKE_REQ');
})->group('regression');
