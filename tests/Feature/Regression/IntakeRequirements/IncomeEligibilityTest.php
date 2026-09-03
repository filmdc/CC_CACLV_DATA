<?php

/*
|----------------------------------------------------------------------
| Income eligibility: the standing requirement stamped on every intake,
| the monthly/annual capture, and the percent-of-poverty calculator that
| reads the app's existing HHS guideline table.
|
| Covers: automatic stamping on graduation, idempotency, survival of a
| service detach, the calculator's arithmetic (lump total, itemized,
| monthly annualization, >4-person extrapolation), the persisted
| determination snapshot, and the requirement transition on save.
|----------------------------------------------------------------------
*/

use App\Livewire\Homes\CallDetail;
use App\Models\CommunicationLog;
use App\Models\HousingService;
use App\Models\IntakeIncomeAssessment;
use App\Models\IntakeRequirement;
use App\Models\Permission;
use App\Models\PovertyGuideline;
use App\Models\RequirementDefinition;
use App\Models\Role;
use App\Models\User;
use App\Services\Intake\IncomeEligibilityCalculator;
use App\Services\IntakeRequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->actor = RegressionHelpers::adminUser();
    $this->service = app(IntakeRequirementInstantiator::class);
    $this->calc = app(IncomeEligibilityCalculator::class);

    // 2025 annual guidelines, family sizes 1-4 (mirrors PovertyGuidelinesSeeder).
    foreach ([1 => 15650, 2 => 21150, 3 => 26650, 4 => 32150] as $size => $hundred) {
        PovertyGuideline::updateOrCreate(
            ['year' => 2025, 'region' => '48_contiguous', 'family_size' => $size, 'period' => 'annual'],
            [
                'poverty_50' => $hundred * 0.5, 'poverty_75' => $hundred * 0.75,
                'poverty_100' => $hundred, 'poverty_125' => $hundred * 1.25,
                'poverty_130' => $hundred * 1.30, 'poverty_150' => $hundred * 1.5,
                'poverty_200' => $hundred * 2, 'poverty_250' => $hundred * 2.5,
                'poverty_300' => $hundred * 3, 'poverty_400' => $hundred * 4,
                'poverty_500' => $hundred * 5,
            ]
        );
    }
});

// -----------------------------------------------------------------
// Stamping: graduation to an intake
// -----------------------------------------------------------------

it('stamps income eligibility when a housing service graduates a log to an intake', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();

    $this->service->forHousingService($log, $svc, $this->actor);

    $req = IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)
        ->first();

    expect($req)->not->toBeNull()
        ->and($req->type)->toBe(IntakeRequirement::TYPE_DOCUMENT)
        ->and($req->status)->toBe(IntakeRequirement::STATUS_PENDING)
        ->and($req->is_orphaned)->toBeFalse()
        ->and($req->via)->toContain('standard');
})->group('regression');

it('self-heals the library atom so environments predating the feature pick it up', function () {
    RequirementDefinition::where('key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)->forceDelete();

    $log = CommunicationLog::factory()->create();
    $req = $this->service->incomeEligibility($log, $this->actor);

    $atom = RequirementDefinition::where('key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)->first();

    expect($atom)->not->toBeNull()
        ->and($atom->category)->toBe(RequirementDefinition::CATEGORY_ELIGIBILITY)
        ->and($req->source_definition_id)->toBe($atom->id);
})->group('regression');

it('is idempotent — a second graduation does not duplicate the row', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();

    $this->service->forHousingService($log, $svc, $this->actor);
    $this->service->forHousingService($log, $svc, $this->actor);
    $this->service->incomeEligibility($log, $this->actor);

    expect(IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)
        ->count())->toBe(1);
})->group('regression');

it('counts toward the Phase 1 conversion gate', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();

    // toggleCounty is the real graduation path: it attaches the pivot
    // AND stamps requirements, which is what computeAutoCategory reads.
    Livewire::actingAs(incomeUser())
        ->test(CallDetail::class, ['log' => $log])
        ->call('toggleCounty', $svc->id);

    // Unmet income eligibility keeps the log in 'intake', not 'scheduling'.
    expect($log->fresh()->computeAutoCategory())->toBe('intake');

    IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)
        ->first()
        ->markReceived($this->actor);

    // ...and satisfying it (with nothing else outstanding) releases the gate.
    expect($log->fresh()->computeAutoCategory())->toBe('scheduling');
})->group('regression');

// -----------------------------------------------------------------
// Calculator arithmetic
// -----------------------------------------------------------------

it('annualizes a monthly lump total', function () {
    expect($this->calc->annualTotal([], 1000, 'monthly'))->toBe(12000.0)
        ->and($this->calc->annualTotal([], 1000, 'annual'))->toBe(1000.0);
})->group('regression');

it('prefers itemized income lines over the lump total', function () {
    $sources = [
        ['type' => 'wages', 'amount' => 1200],
        ['type' => 'social_security', 'amount' => 800],
    ];

    expect($this->calc->annualTotal($sources, 99999, 'monthly'))->toBe(24000.0);
})->group('regression');

it('places an income at a percent of poverty against the guideline table', function () {
    $result = $this->calc->assess(32150, 4, 2025);

    expect($result['available'])->toBeTrue()
        ->and($result['threshold'])->toBe(32150.0)
        ->and($result['percentage'])->toBe(100.0)
        ->and($result['bracket'])->toBe(100)
        ->and($result['monthly_income'])->toBe(2679.17);
})->group('regression');

it('rounds to the nearest published bracket above the percentage', function () {
    // 143% of a household of 1 → next published bracket up is 150.
    $result = $this->calc->assess(22380, 1, 2025);

    expect($result['percentage'])->toBe(143.0)
        ->and($result['bracket'])->toBe(150);
})->group('regression');

it('extrapolates households larger than the published table', function () {
    // Size 4 ($32,150) + 2 × $4,480 = $41,110.
    $result = $this->calc->assess(41110, 6, 2025);

    expect($result['threshold'])->toBe(41110.0)
        ->and($result['percentage'])->toBe(100.0);
})->group('regression');

it('reports unavailable rather than 0% when no guideline year is loaded', function () {
    PovertyGuideline::query()->delete();

    $result = $this->calc->assess(20000, 2);

    expect($result['available'])->toBeFalse()
        ->and($result['percentage'])->toBeNull()
        ->and($result['annual_income'])->toBe(20000.0);
})->group('regression');

// -----------------------------------------------------------------
// Capture through the portal
// -----------------------------------------------------------------

function incomeUser(): User
{
    $role = Role::firstOrCreate(['name' => 'income_test'], ['display_name' => 'Income Test']);
    $ids = collect(['communications_log.view', 'communications_log.edit'])
        ->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

it('saves the figures, snapshots the determination, and marks the requirement received', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $this->service->forHousingService($log, $svc, $this->actor);

    Livewire::actingAs(incomeUser())
        ->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 4)
        ->set('incomeForm.entry_period', 'monthly')
        ->set('incomeSources', [['type' => 'wages', 'label' => 'Acme', 'amount' => '2000']])
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    $assessment = IntakeIncomeAssessment::where('communication_log_id', $log->id)->first();

    expect($assessment)->not->toBeNull()
        ->and((float) $assessment->annual_income)->toBe(24000.0)
        ->and((float) $assessment->monthly_income)->toBe(2000.0)
        ->and($assessment->household_size)->toBe(4)
        ->and($assessment->guideline_year)->toBe(2025)
        ->and((float) $assessment->poverty_threshold)->toBe(32150.0)
        ->and((float) $assessment->poverty_percentage)->toBe(74.65)
        ->and($assessment->poverty_bracket)->toBe(75)
        ->and($assessment->income_sources)->toHaveCount(1);

    $req = IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)
        ->first();

    expect($req->status)->toBe(IntakeRequirement::STATUS_RECEIVED)
        ->and($assessment->intake_requirement_id)->toBe($req->id);
})->group('regression');

it('refuses to save without an income figure', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $this->service->forHousingService($log, $svc, $this->actor);

    Livewire::actingAs(incomeUser())
        ->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 2)
        ->set('incomeSources', [['type' => 'wages', 'label' => '', 'amount' => '']])
        ->set('incomeForm.entered_amount', '')
        ->call('saveIncomeEligibility')
        ->assertHasErrors('incomeForm.entered_amount');

    expect(IntakeIncomeAssessment::where('communication_log_id', $log->id)->exists())->toBeFalse();
})->group('regression');

it('requires a household size', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $this->service->forHousingService($log, $svc, $this->actor);

    Livewire::actingAs(incomeUser())
        ->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', '')
        ->set('incomeForm.entered_amount', '30000')
        ->call('saveIncomeEligibility')
        ->assertHasErrors('incomeForm.household_size');
})->group('regression');

it('re-saving updates the single assessment rather than creating a second', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $this->service->forHousingService($log, $svc, $this->actor);

    $component = Livewire::actingAs(incomeUser())->test(CallDetail::class, ['log' => $log]);

    $component->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 2)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '20000')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    $component->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 2)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '25000')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    $assessments = IntakeIncomeAssessment::where('communication_log_id', $log->id)->get();

    expect($assessments)->toHaveCount(1)
        ->and((float) $assessments->first()->annual_income)->toBe(25000.0);
})->group('regression');

it('renders and saves the same calculator from the main app comm log viewer', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $this->service->forHousingService($log, $svc, $this->actor);

    Livewire::actingAs(RegressionHelpers::adminUser())
        ->test(\App\Livewire\CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('openIncomeEligibility')
        ->assertSet('showIncomeModal', true)
        ->assertOk()
        ->set('incomeForm.household_size', 3)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '26650')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors()
        ->assertSet('showIncomeModal', false);

    $assessment = IntakeIncomeAssessment::where('communication_log_id', $log->id)->firstOrFail();

    expect((float) $assessment->poverty_percentage)->toBe(100.0)
        ->and((float) $assessment->entered_amount)->toBe(26650.0);
})->group('regression');

it('keeps the income requirement when the service that triggered it is detached', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();

    $user = incomeUser();
    $component = Livewire::actingAs($user)->test(CallDetail::class, ['log' => $log]);

    $component->call('toggleCounty', $svc->id);   // attach — graduates to intake
    $component->call('toggleCounty', $svc->id);   // detach

    $req = IntakeRequirement::where('communication_log_id', $log->id)
        ->where('key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)
        ->first();

    expect($req)->not->toBeNull()
        ->and($req->is_orphaned)->toBeFalse();
})->group('regression');
