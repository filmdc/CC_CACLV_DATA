<?php

/*
| Phase 12.2: Housing Dashboard intake-pipeline card.
|
| Surfaces non-converted communication logs ranked by Phase 1 intake
| requirement completion so Kaitlyn can prioritize follow-ups on the
| cases closest to convertible.
*/

use App\Livewire\HousingDashboard;
use App\Models\CommunicationLog;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

function makeIntakeReq(CommunicationLog $log, string $status, string $key, string $category = 'eligibility'): IntakeRequirement
{
    return IntakeRequirement::create([
        'communication_log_id' => $log->id,
        'type' => IntakeRequirement::TYPE_DOCUMENT,
        'label' => 'Req '.$key,
        'key' => $key,
        'kind' => 'doc',
        'status' => $status,
        'source_type' => IntakeRequirement::SOURCE_AD_HOC,
    ]);
}

it('intakePipeline ranks logs by Phase 1 completion % (highest first)', function () {
    // High: 2/3 met = 67%
    $highLog = CommunicationLog::factory()->create(['housing_job_id' => null, 'converted_to_job_id' => null]);
    makeIntakeReq($highLog, IntakeRequirement::STATUS_RECEIVED, 'h1-'.uniqid());
    makeIntakeReq($highLog, IntakeRequirement::STATUS_WAIVED, 'h2-'.uniqid());
    makeIntakeReq($highLog, IntakeRequirement::STATUS_PENDING, 'h3-'.uniqid());

    // Low: 1/4 met = 25%
    $lowLog = CommunicationLog::factory()->create(['housing_job_id' => null, 'converted_to_job_id' => null]);
    makeIntakeReq($lowLog, IntakeRequirement::STATUS_RECEIVED, 'l1-'.uniqid());
    makeIntakeReq($lowLog, IntakeRequirement::STATUS_PENDING, 'l2-'.uniqid());
    makeIntakeReq($lowLog, IntakeRequirement::STATUS_PENDING, 'l3-'.uniqid());
    makeIntakeReq($lowLog, IntakeRequirement::STATUS_PENDING, 'l4-'.uniqid());

    $component = Livewire::actingAs($this->admin)->test(HousingDashboard::class);
    $pipeline = $component->instance()->intakePipeline();

    expect($pipeline->count())->toBe(2);
    expect($pipeline->first()['id'])->toBe($highLog->id)
        ->and($pipeline->first()['pct'])->toBe(67)
        ->and($pipeline->last()['id'])->toBe($lowLog->id)
        ->and($pipeline->last()['pct'])->toBe(25);
})->group('regression');

it('intakePipeline excludes converted comm logs', function () {
    $job = HousingJob::factory()->create();
    $convertedLog = CommunicationLog::factory()->create([
        'housing_job_id' => $job->id,
        'converted_to_job_id' => $job->id,
    ]);
    makeIntakeReq($convertedLog, IntakeRequirement::STATUS_RECEIVED, 'c1-'.uniqid());

    $openLog = CommunicationLog::factory()->create(['housing_job_id' => null, 'converted_to_job_id' => null]);
    makeIntakeReq($openLog, IntakeRequirement::STATUS_PENDING, 'o1-'.uniqid());

    $pipeline = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->instance()
        ->intakePipeline();

    expect($pipeline->pluck('id')->all())->toBe([$openLog->id]);
})->group('regression');

it('intakePipeline excludes logs with zero non-orphaned Phase 1 requirements', function () {
    $bareLog = CommunicationLog::factory()->create(['housing_job_id' => null, 'converted_to_job_id' => null]);
    // No requirements at all.

    $orphanedOnlyLog = CommunicationLog::factory()->create(['housing_job_id' => null, 'converted_to_job_id' => null]);
    $r = makeIntakeReq($orphanedOnlyLog, IntakeRequirement::STATUS_PENDING, 'orph-'.uniqid());
    $r->is_orphaned = true;
    $r->save();

    $hasOneLog = CommunicationLog::factory()->create(['housing_job_id' => null, 'converted_to_job_id' => null]);
    makeIntakeReq($hasOneLog, IntakeRequirement::STATUS_PENDING, 'has-'.uniqid());

    $pipeline = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->instance()
        ->intakePipeline();

    expect($pipeline->pluck('id')->all())->toBe([$hasOneLog->id]);
})->group('regression');

it('intakePipeline marks 100%-met rows as ready and surfaces days_idle', function () {
    $log = CommunicationLog::factory()->create(['housing_job_id' => null, 'converted_to_job_id' => null]);
    makeIntakeReq($log, IntakeRequirement::STATUS_RECEIVED, 'r1-'.uniqid());
    makeIntakeReq($log, IntakeRequirement::STATUS_WAIVED, 'r2-'.uniqid());

    // Force the underlying timestamps to a known past point so days_idle
    // isn't a function of test wall-clock drift.
    $log->forceFill(['updated_at' => now()->subDays(10)])->saveQuietly();

    $row = Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->instance()
        ->intakePipeline()
        ->firstWhere('id', $log->id);

    expect($row)->not->toBeNull()
        ->and($row['pct'])->toBe(100)
        ->and($row['met'])->toBe(2)
        ->and($row['total'])->toBe(2)
        ->and($row['days_idle'])->toBeGreaterThanOrEqual(9);
})->group('regression');

it('housing dashboard view renders the intake-pipeline card when pipeline is non-empty', function () {
    $log = CommunicationLog::factory()->create([
        'housing_job_id' => null,
        'converted_to_job_id' => null,
        'contact_first_name' => 'Maria',
        'contact_last_name' => 'Lopez',
    ]);
    makeIntakeReq($log, IntakeRequirement::STATUS_RECEIVED, 'm1-'.uniqid());

    Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->assertSee('Intake pipeline')
        ->assertSee('Maria Lopez');
})->group('regression');

it('housing dashboard view omits the card entirely when pipeline is empty', function () {
    // No comm logs with intake requirements at all.
    Livewire::actingAs($this->admin)
        ->test(HousingDashboard::class)
        ->assertDontSee('Intake pipeline');
})->group('regression');

it('CommunicationsLogManagement opens the detail viewer when mounted with ?view=N', function () {
    $log = CommunicationLog::factory()->create();

    Livewire::actingAs($this->admin)
        ->withQueryParams(['view' => $log->id])
        ->test(\App\Livewire\CommunicationsLogManagement::class)
        ->assertSet('viewingId', $log->id)
        ->assertSet('showDetailModal', true);
})->group('regression');
