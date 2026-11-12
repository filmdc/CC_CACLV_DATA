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
|
| Housing intakes (any log with a housing service attached) are
| measured against the HUD HOME income limits instead of the HHS
| guidelines — region routing, the HOME dataset itself, and HUD's
| over-8 extrapolation are covered here too.
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
use Database\Seeders\HomeIncomeLimitsSeeder;
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

    // FY2026 HOME income limits (housing intakes) — the real seeder,
    // so its figures are pinned by these tests too.
    $this->seed(HomeIncomeLimitsSeeder::class);
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
// HOME income limits (housing intakes)
// -----------------------------------------------------------------

it('places a household against the HOME income limits when asked for that region', function () {
    // FY2026 LOW INCOME (80% AMI) ceiling for a household of 4 is $83,500,
    // which is 80% of the $104,375 area median — a household earning
    // exactly the ceiling sits at 80% of AMI and still qualifies.
    $result = $this->calc->assess(83500, 4, 2026, PovertyGuideline::REGION_HOME_ABE);

    expect($result['available'])->toBeTrue()
        ->and($result['threshold'])->toBe(83500.0)
        ->and($result['ami'])->toBe(104375.0)
        ->and($result['percentage'])->toBe(80.0)
        ->and($result['bracket'])->toBe(80)
        ->and($result['tier_label'])->toBe('Low income')
        ->and($result['eligible'])->toBeTrue()
        ->and($result['guideline_region'])->toBe(PovertyGuideline::REGION_HOME_ABE)
        ->and($result['label'])->toBe('80% of the FY2026 area median income');
})->group('regression');

it('reports a HOME percentage against area median income, not the ceiling', function () {
    // The case that surfaced this: $20,112 for a household of 2. The
    // 2-person ceiling is $66,800 and the 2-person median $83,500, so the
    // figure has to read 24.09% (extremely low income), not 30.11%.
    $result = $this->calc->assess(20112, 2, 2026, PovertyGuideline::REGION_HOME_ABE);

    expect($result['threshold'])->toBe(66800.0)
        ->and($result['ami'])->toBe(83500.0)
        ->and($result['percentage'])->toBe(24.09)
        ->and($result['bracket'])->toBe(30)
        ->and($result['tier_label'])->toBe('Extremely low income')
        ->and($result['eligible'])->toBeTrue();
})->group('regression');

it('flags a household over the HOME ceiling as over income', function () {
    // $90,000 for a household of 2: over the $66,800 ceiling, and above
    // the top 80% band, so there is no tier to report.
    $result = $this->calc->assess(90000, 2, 2026, PovertyGuideline::REGION_HOME_ABE);

    expect($result['percentage'])->toBe(107.78)
        ->and($result['bracket'])->toBeNull()
        ->and($result['tier_label'])->toBeNull()
        ->and($result['eligible'])->toBeFalse();
})->group('regression');

it('prefers a published median family income over the derived one', function () {
    // HUD caps the 80% limit in some areas; when it does, ceiling ÷ 0.8
    // understates the median and the published figure has to win.
    PovertyGuideline::setHomeMedianFamilyIncome(2026, 120000);

    $result = $this->calc->assess(30000, 4, 2026, PovertyGuideline::REGION_HOME_ABE);

    expect($result['ami'])->toBe(120000.0)
        ->and($result['percentage'])->toBe(25.0)
        // The ceiling is untouched by the median override.
        ->and($result['threshold'])->toBe(83500.0);

    PovertyGuideline::setHomeMedianFamilyIncome(2026, null);

    expect($this->calc->assess(30000, 4, 2026, PovertyGuideline::REGION_HOME_ABE)['ami'])
        ->toBe(104375.0);
})->group('regression');

it('leaves FPL determinations on the poverty level and its bracket ladder', function () {
    // The HOME rebasing must not leak into non-housing intakes.
    $result = $this->calc->assess(20112, 2);

    expect($result['ami'])->toBeNull()
        ->and($result['eligible'])->toBeNull()
        ->and($result['tier_label'])->toBeNull()
        ->and($result['bracket_unit'])->toBe('FPL')
        ->and($result['label'])->toContain('federal poverty level');
})->group('regression');

it('extrapolates HOME limits above eight people by HUD\'s +8%-of-the-4-person-limit rule', function () {
    // Size 8 ($110,250) + 2 × 8% × $83,500 = $123,610 — NOT the HHS increment.
    $threshold = PovertyGuideline::thresholdFor(10, 'annual', 2026, PovertyGuideline::REGION_HOME_ABE);

    expect($threshold)->toBe(123610.0);
})->group('regression');

it('falls back to the newest HOME year loaded when the requested year is not seeded', function () {
    $result = $this->calc->assess(50000, 6, 1999, PovertyGuideline::REGION_HOME_ABE);

    expect($result['guideline_year'])->toBe(2026)
        ->and($result['available'])->toBeTrue()
        ->and($result['threshold'])->toBe(96900.0);
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
    // forHousingService() stamps the requirements; attaching the pivot is
    // what toggleCounty does — and what incomeGuidelineRegion() reads.
    $log->housingServices()->attach($svc->id, ['created_by_user_id' => $this->actor->id, 'created_at' => now()]);

    Livewire::actingAs(incomeUser())
        ->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 4)
        ->set('incomeForm.entry_period', 'monthly')
        ->set('incomeSources', [['type' => 'wages', 'label' => 'Acme', 'amount' => '2000']])
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    $assessment = IntakeIncomeAssessment::where('communication_log_id', $log->id)->first();

    // A housing service is attached, so the snapshot reads against the
    // FY2026 HOME limits (4-person ceiling $83,500), not the HHS table.
    expect($assessment)->not->toBeNull()
        ->and((float) $assessment->annual_income)->toBe(24000.0)
        ->and((float) $assessment->monthly_income)->toBe(2000.0)
        ->and($assessment->household_size)->toBe(4)
        ->and($assessment->guideline_year)->toBe(2026)
        ->and($assessment->guideline_region)->toBe(PovertyGuideline::REGION_HOME_ABE)
        ->and((float) $assessment->poverty_threshold)->toBe(83500.0)
        // $24,000 against the $104,375 four-person median — the ceiling
        // stays in poverty_threshold, the median in area_median_income.
        ->and((float) $assessment->area_median_income)->toBe(104375.0)
        ->and((float) $assessment->poverty_percentage)->toBe(22.99)
        ->and($assessment->poverty_bracket)->toBe(30)
        ->and($assessment->tier_label)->toBe('Extremely low income')
        ->and($assessment->is_income_eligible)->toBeTrue()
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
    $log->housingServices()->attach($svc->id, ['created_by_user_id' => $this->actor->id, 'created_at' => now()]);

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

    // Same intake, same dataset as the portal: the housing service
    // routes the main app's viewer to the HOME limits too
    // ($26,650 against the 3-person median of $93,937.50).
    expect((float) $assessment->poverty_percentage)->toBe(28.37)
        ->and($assessment->guideline_region)->toBe(PovertyGuideline::REGION_HOME_ABE)
        ->and((float) $assessment->area_median_income)->toBe(93937.50)
        ->and((float) $assessment->poverty_threshold)->toBe(75150.0)
        ->and($assessment->poverty_bracket)->toBe(30)
        ->and((float) $assessment->entered_amount)->toBe(26650.0);
})->group('regression');

it('keeps non-housing intakes on the HHS poverty guidelines', function () {
    $log = CommunicationLog::factory()->create(); // no housing service attached

    Livewire::actingAs(incomeUser())
        ->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 4)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '32150')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    $assessment = IntakeIncomeAssessment::where('communication_log_id', $log->id)->firstOrFail();

    expect($assessment->guideline_region)->toBe(IncomeEligibilityCalculator::REGION_DEFAULT)
        ->and($assessment->guideline_year)->toBe(2025)
        ->and((float) $assessment->poverty_threshold)->toBe(32150.0)
        ->and((float) $assessment->poverty_percentage)->toBe(100.0);
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

// -----------------------------------------------------------------
// The snapshot holds: editing figures never restates the year
// -----------------------------------------------------------------

/**
 * A second HOME year, as if HUD had reissued the limits — the 4-person
 * ceiling of $88,000 derives a $110,000 median family income.
 */
function incomeLoadHome2027(): void
{
    $ceilings = [
        1 => 61600, 2 => 70400, 3 => 79200, 4 => 88000,
        5 => 95040, 6 => 102080, 7 => 109120, 8 => 116160,
    ];

    foreach ($ceilings as $size => $annual) {
        PovertyGuideline::upsertHomeCeiling(2027, $size, (float) $annual);
    }
}

/** A housing intake with the pivot attached, so it reads the HOME limits. */
function incomeYearIntake(): CommunicationLog
{
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    app(IntakeRequirementInstantiator::class)->forHousingService($log, $svc, RegressionHelpers::adminUser());
    $log->housingServices()->attach($svc->id, [
        'created_by_user_id' => RegressionHelpers::adminUser()->id,
        'created_at' => now(),
    ]);

    return $log->fresh();
}

it('keeps an edited determination on the guideline year it was taken against', function () {
    $log = incomeYearIntake();

    $component = Livewire::actingAs(incomeUser())->test(CallDetail::class, ['log' => $log]);

    $component->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 4)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '24000')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    expect(IntakeIncomeAssessment::where('communication_log_id', $log->id)->value('guideline_year'))->toBe(2026);

    // HUD reissues: FY2027 lands in the table.
    incomeLoadHome2027();

    // She corrects the income — a typo on a pay stub, not a new
    // determination. The year, ceiling and median must not move.
    $component->call('openIncomeEligibility')
        ->set('incomeForm.entered_amount', '26000')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    $assessment = IntakeIncomeAssessment::where('communication_log_id', $log->id)->first();

    expect($assessment->guideline_year)->toBe(2026)
        ->and((float) $assessment->annual_income)->toBe(26000.0)
        ->and((float) $assessment->poverty_threshold)->toBe(83500.0)
        ->and((float) $assessment->area_median_income)->toBe(104375.0);
})->group('regression');

it('pins only an existing determination — a fresh intake resolves the year normally', function () {
    // An older HOME year is on file alongside FY2026. A first
    // determination carries no pin, so it resolves the way it always
    // has (IncomeEligibilityCalculator::resolveYear: the current
    // calendar year when loaded, otherwise the newest year) rather
    // than inheriting a year from some other record.
    foreach ([1 => 55000, 2 => 62800, 3 => 70650, 4 => 78500] as $size => $annual) {
        PovertyGuideline::upsertHomeCeiling(2025, $size, (float) $annual);
    }

    $log = incomeYearIntake();

    Livewire::actingAs(incomeUser())
        ->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 4)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '24000')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    $assessment = IntakeIncomeAssessment::where('communication_log_id', $log->id)->first();

    expect($assessment->guideline_year)->toBe(2026)
        ->and((float) $assessment->poverty_threshold)->toBe(83500.0)
        ->and((float) $assessment->area_median_income)->toBe(104375.0);
})->group('regression');

it('keeps the original author when someone else edits the figures', function () {
    $log = incomeYearIntake();
    $first = incomeUser();
    $second = incomeUser();

    Livewire::actingAs($first)->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 2)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '20000')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    Livewire::actingAs($second)->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.entered_amount', '21000')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    $assessment = IntakeIncomeAssessment::where('communication_log_id', $log->id)->first();

    expect($assessment->created_by_user_id)->toBe($first->id)
        ->and($assessment->updated_by_user_id)->toBe($second->id)
        ->and((float) $assessment->annual_income)->toBe(21000.0);
})->group('regression');

// -----------------------------------------------------------------
// Checking the figures against another year (read-only)
// -----------------------------------------------------------------

it('offers no year comparison when only one guideline year is loaded', function () {
    $log = incomeYearIntake();

    $component = Livewire::actingAs(incomeUser())->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 4)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '24000')
        ->call('saveIncomeEligibility');

    expect($component->instance()->incomeComparisonYears())->toBe([]);
})->group('regression');

it('offers the other year once HUD reissues the limits', function () {
    $log = incomeYearIntake();

    $component = Livewire::actingAs(incomeUser())->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 4)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '24000')
        ->call('saveIncomeEligibility');

    incomeLoadHome2027();

    // The year of record is never offered as a comparison to itself.
    expect($component->instance()->incomeComparisonYears())->toBe([2027]);
})->group('regression');

it('measures the recorded figures against another year without writing anything', function () {
    $log = incomeYearIntake();

    $component = Livewire::actingAs(incomeUser())->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 4)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '24000')
        ->call('saveIncomeEligibility');

    incomeLoadHome2027();

    $before = IntakeIncomeAssessment::where('communication_log_id', $log->id)->first();

    $component->call('compareIncomeYear', 2027);

    $comparison = $component->instance()->getIncomeComparisonProperty();

    // $24,000 against the FY2027 $110,000 four-person median.
    expect($component->get('incomeCompareYear'))->toBe(2027)
        ->and($comparison['available'])->toBeTrue()
        ->and($comparison['guideline_year'])->toBe(2027)
        ->and((float) $comparison['threshold'])->toBe(88000.0)
        ->and((float) $comparison['ami'])->toBe(110000.0)
        ->and((float) $comparison['percentage'])->toBe(21.82)
        ->and($comparison['eligible'])->toBeTrue();

    // Nothing moved: the determination of record is still FY2026.
    $after = IntakeIncomeAssessment::where('communication_log_id', $log->id)->first();

    expect($after->guideline_year)->toBe(2026)
        ->and((float) $after->poverty_threshold)->toBe((float) $before->poverty_threshold)
        ->and((float) $after->area_median_income)->toBe((float) $before->area_median_income)
        ->and((float) $after->poverty_percentage)->toBe((float) $before->poverty_percentage)
        ->and($after->updated_at->eq($before->updated_at))->toBeTrue();

    $component->call('clearIncomeComparison');

    expect($component->get('incomeCompareYear'))->toBeNull()
        ->and($component->instance()->getIncomeComparisonProperty())->toBeNull();
})->group('regression');

it('ignores a comparison year that is not loaded', function () {
    $log = incomeYearIntake();

    $component = Livewire::actingAs(incomeUser())->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 4)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '24000')
        ->call('saveIncomeEligibility');

    $component->call('compareIncomeYear', 2099);

    expect($component->get('incomeCompareYear'))->toBeNull()
        ->and($component->instance()->getIncomeComparisonProperty())->toBeNull();
})->group('regression');

it('renders the year check on the portal card and names what is of record', function () {
    $log = incomeYearIntake();

    $component = Livewire::actingAs(incomeUser())->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 4)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '24000')
        ->call('saveIncomeEligibility');

    // Only one year loaded — the control stays off the screen rather
    // than offering a list of one.
    $component->assertOk()->assertDontSee('Check against');

    incomeLoadHome2027();

    $component->call('$refresh')->assertOk()->assertSee('Check against')->assertSee('FY2027');

    $component->call('compareIncomeYear', 2027)
        ->assertOk()
        ->assertSee('If measured against FY2027')
        ->assertSee('21.8% of AMI')
        ->assertSee('Would be income eligible')
        // The point of the whole control: it is a look, not a change.
        ->assertSee('the determination of record is still FY2026');
})->group('regression');

it('renders the same year check in the main app comm log viewer', function () {
    $log = incomeYearIntake();

    $component = Livewire::actingAs(RegressionHelpers::adminUser())
        ->test(\App\Livewire\CommunicationsLogManagement::class)
        ->call('viewDetail', $log->id)
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 4)
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '24000')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    $component->assertOk()->assertDontSee('Check against');

    incomeLoadHome2027();

    $component->call('$refresh')->assertOk()->assertSee('Check against')->assertSee('FY2027');

    $component->call('compareIncomeYear', 2027)
        ->assertOk()
        ->assertSee('If measured against FY2027')
        ->assertSee('21.8% of AMI')
        ->assertSee('the determination of record is still FY2026');
})->group('regression');
