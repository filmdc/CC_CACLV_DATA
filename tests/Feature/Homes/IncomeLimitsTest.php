<?php

use App\Livewire\Homes\Settings\IncomeLimits;
use App\Models\IntakeIncomeAssessment;
use App\Models\Permission;
use App\Models\PovertyGuideline;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\HomeIncomeLimitsSeeder;
use Livewire\Livewire;

function incomeLimitsUser(array $permissionNames = []): User
{
    $role = Role::firstOrCreate(['name' => 'income_limits_test'], ['display_name' => 'Income Limits Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function seedHomeYear(int $year, array $ceilings): void
{
    foreach ($ceilings as $size => $annual) {
        PovertyGuideline::upsertHomeCeiling($year, $size, (float) $annual);
    }
}

function fullHomeBlock(int $base = 50000): array
{
    return collect(range(1, 8))->mapWithKeys(fn ($s) => [$s => $base + ($s * 5000)])->all();
}

// ── Access ────────────────────────────────────────────────────────

it('gates the income limits surface at the route', function () {
    $this->actingAs(incomeLimitsUser([]));

    $this->get(route('homes.settings.income-limits'))->assertForbidden();
});

it('lets a viewer in and shows the surface on the settings hub', function () {
    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view']));

    $this->get(route('homes.settings.income-limits'))->assertOk();
    $this->get(route('homes.settings'))->assertOk()->assertSee('HUD ceiling a housing intake');
});

it('hides the hub card from someone without the view permission', function () {
    $this->actingAs(incomeLimitsUser(['fund_codes.view']));

    $this->get(route('homes.settings'))->assertOk()->assertDontSee('HUD ceiling a housing intake');
});

// ── Reading ───────────────────────────────────────────────────────

it('shows the published ceilings for the latest year', function () {
    seedHomeYear(2025, fullHomeBlock(40000));
    seedHomeYear(2026, fullHomeBlock(50000));

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view']));

    Livewire::test(IncomeLimits::class)
        ->assertSet('year', 2026)
        ->assertSet('ceilings.1', '55000')
        ->assertSet('ceilings.8', '90000');
});

it('switches years without editing', function () {
    seedHomeYear(2025, fullHomeBlock(40000));
    seedHomeYear(2026, fullHomeBlock(50000));

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view']));

    Livewire::test(IncomeLimits::class)
        ->set('year', 2025)
        ->assertSet('ceilings.1', '45000')
        ->assertSet('editing', false);
});

it('offers the full 1-8 block even when the year is empty', function () {
    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view']));

    Livewire::test(IncomeLimits::class)
        ->set('year', 2030)
        ->assertCount('ceilings', 8);
});

// ── Editing ───────────────────────────────────────────────────────

it('saves an edited ceiling and derives the monthly row', function () {
    seedHomeYear(2026, fullHomeBlock());

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view', 'poverty-guidelines.edit']));

    Livewire::test(IncomeLimits::class)
        ->call('startEdit')
        ->set('ceilings.3', 61200)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('editing', false);

    $annual = PovertyGuideline::where('region', PovertyGuideline::REGION_HOME_ABE)
        ->where('year', 2026)->where('family_size', 3)->where('period', 'annual')->first();
    $monthly = PovertyGuideline::where('region', PovertyGuideline::REGION_HOME_ABE)
        ->where('year', 2026)->where('family_size', 3)->where('period', 'monthly')->first();

    expect((float) $annual->poverty_100)->toBe(61200.0)
        ->and((float) $monthly->poverty_100)->toBe(5100.0)
        // Brackets are scaled off the ceiling, not left stale.
        ->and((float) $annual->poverty_50)->toBe(30600.0)
        ->and((float) $annual->poverty_200)->toBe(122400.0);
});

it('refuses to save a blank published size', function () {
    seedHomeYear(2026, fullHomeBlock());

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view', 'poverty-guidelines.edit']));

    Livewire::test(IncomeLimits::class)
        ->call('startEdit')
        ->set('ceilings.4', '')
        ->call('save')
        ->assertHasErrors(['ceilings.4' => 'required']);

    // The stored figure is untouched.
    expect(PovertyGuideline::homeCeilings(2026)[4])->toBe(70000.0);
});

it('blocks editing without the edit permission', function () {
    seedHomeYear(2026, fullHomeBlock());

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view']));

    Livewire::test(IncomeLimits::class)
        ->call('startEdit')
        ->assertForbidden();
});

// ── Adding a year ─────────────────────────────────────────────────

it('copies a year forward without writing anything until saved', function () {
    seedHomeYear(2026, fullHomeBlock());

    $this->actingAs(incomeLimitsUser([
        'poverty-guidelines.view', 'poverty-guidelines.create', 'poverty-guidelines.edit',
    ]));

    $component = Livewire::test(IncomeLimits::class)
        ->call('openAddYear')
        ->assertSet('newYear', 2027)
        ->call('addYear')
        ->assertHasNoErrors()
        ->assertSet('year', 2027)
        ->assertSet('editing', true)
        ->assertSet('unsavedYear', true)
        ->assertSet('ceilings.1', '55000');

    expect(PovertyGuideline::homeYears())->toBe([2026]);

    $component->set('ceilings.1', 57000)->call('save')->assertHasNoErrors();

    expect(PovertyGuideline::homeYears())->toBe([2027, 2026])
        ->and(PovertyGuideline::homeCeilings(2027)[1])->toBe(57000.0);
});

it('rejects a year that is already loaded', function () {
    seedHomeYear(2026, fullHomeBlock());

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view', 'poverty-guidelines.create']));

    Livewire::test(IncomeLimits::class)
        ->call('openAddYear')
        ->set('newYear', 2026)
        ->call('addYear')
        ->assertHasErrors('newYear');
});

it('drops a staged year on cancel', function () {
    seedHomeYear(2026, fullHomeBlock());

    $this->actingAs(incomeLimitsUser([
        'poverty-guidelines.view', 'poverty-guidelines.create', 'poverty-guidelines.edit',
    ]));

    Livewire::test(IncomeLimits::class)
        ->call('openAddYear')
        ->call('addYear')
        ->call('cancelEdit')
        ->assertSet('year', 2026)
        ->assertSet('unsavedYear', false);

    expect(PovertyGuideline::homeYears())->toBe([2026]);
});

// ── Removing ──────────────────────────────────────────────────────

it('removes a year and falls back to the remaining one', function () {
    seedHomeYear(2025, fullHomeBlock(40000));
    seedHomeYear(2026, fullHomeBlock(50000));

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view', 'poverty-guidelines.delete']));

    Livewire::test(IncomeLimits::class)
        ->assertSet('year', 2026)
        ->call('deleteYear')
        ->assertSet('year', 2025);

    expect(PovertyGuideline::homeYears())->toBe([2025]);
});

it('blocks removing without the delete permission', function () {
    seedHomeYear(2026, fullHomeBlock());

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view', 'poverty-guidelines.edit']));

    Livewire::test(IncomeLimits::class)->call('deleteYear')->assertForbidden();
});

// ── Parity with the seeder ────────────────────────────────────────

it('writes rows identical to the seeder', function () {
    // Seed HUD's real FY2026 block, snapshot it, wipe it, then rebuild
    // the same figures through the editor. The two paths share
    // upsertHomeCeiling(), and this pins that they stay shared.
    (new HomeIncomeLimitsSeeder)->run();

    $seeded = PovertyGuideline::where('region', PovertyGuideline::REGION_HOME_ABE)
        ->orderBy('family_size')->orderBy('period')
        ->get(['year', 'family_size', 'period', 'poverty_50', 'poverty_100', 'poverty_500'])
        ->toArray();

    expect($seeded)->not->toBeEmpty();

    $ceilings = PovertyGuideline::homeCeilings(2026);

    PovertyGuideline::where('region', PovertyGuideline::REGION_HOME_ABE)->delete();

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view', 'poverty-guidelines.create', 'poverty-guidelines.edit']));

    $component = Livewire::test(IncomeLimits::class)
        ->call('openAddYear')
        ->set('newYear', 2026)
        ->set('copyFromYear', null)
        ->call('addYear');

    foreach ($ceilings as $size => $annual) {
        $component->set('ceilings.'.$size, $annual);
    }

    $component->call('save')->assertHasNoErrors();

    $viaEditor = PovertyGuideline::where('region', PovertyGuideline::REGION_HOME_ABE)
        ->orderBy('family_size')->orderBy('period')
        ->get(['year', 'family_size', 'period', 'poverty_50', 'poverty_100', 'poverty_500'])
        ->toArray();

    expect($viaEditor)->toEqual($seeded);
})->group('regression');

it('extrapolates over-8 households through the same rule the calculator uses', function () {
    seedHomeYear(2026, fullHomeBlock());

    // 8-person = 90000, 4-person = 70000, +8% of 70000 per extra person.
    expect(PovertyGuideline::thresholdFor(9, 'annual', 2026, PovertyGuideline::REGION_HOME_ABE))
        ->toBe(95600.0);

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view']));

    Livewire::test(IncomeLimits::class)->assertSee('95,600');
});

// ── Area median income ────────────────────────────────────────────

it('derives the median family income from the 4-person ceiling', function () {
    seedHomeYear(2026, fullHomeBlock());   // 4-person = 70000

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view']));

    Livewire::test(IncomeLimits::class)
        ->assertSet('mfi', '')                 // blank means "derived"
        ->assertSee('87,500')                  // 70000 / 0.8
        ->assertSee('derived from the 4-person limit');
});

it('saves a published median and prefers it over the derived one', function () {
    seedHomeYear(2026, fullHomeBlock());

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view', 'poverty-guidelines.edit']));

    Livewire::test(IncomeLimits::class)
        ->call('startEdit')
        ->set('mfi', 92000)
        ->call('save')
        ->assertHasNoErrors();

    expect(PovertyGuideline::homeMedianFamilyIncome(2026))->toBe(92000.0)
        ->and(PovertyGuideline::homeMfiIsDerived(2026))->toBeFalse()
        // Family-size adjusted: 2-person is 80% of the 4-person median.
        ->and(PovertyGuideline::homeAmiFor(2, 2026))->toBe(73600.0);
});

it('clearing the median goes back to deriving it', function () {
    seedHomeYear(2026, fullHomeBlock());
    PovertyGuideline::setHomeMedianFamilyIncome(2026, 92000);

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view', 'poverty-guidelines.edit']));

    Livewire::test(IncomeLimits::class)
        ->assertSet('mfi', '92000')
        ->call('startEdit')
        ->set('mfi', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(PovertyGuideline::homeMfiIsDerived(2026))->toBeTrue()
        ->and(PovertyGuideline::homeMedianFamilyIncome(2026))->toBe(87500.0);
});

it('drops the median override when the year is removed', function () {
    seedHomeYear(2026, fullHomeBlock());
    PovertyGuideline::setHomeMedianFamilyIncome(2026, 92000);

    $this->actingAs(incomeLimitsUser(['poverty-guidelines.view', 'poverty-guidelines.delete']));

    Livewire::test(IncomeLimits::class)->call('deleteYear');

    expect(PovertyGuideline::homeMfiIsDerived(2026))->toBeTrue();
});

// ── Recompute command ─────────────────────────────────────────────

it('recomputes a stale ceiling-based determination onto AMI', function () {
    (new HomeIncomeLimitsSeeder)->run();

    // A determination written the old way: 20112 / 66800 = 30.11%.
    $log = \App\Models\CommunicationLog::factory()->create();
    $assessment = IntakeIncomeAssessment::create([
        'communication_log_id' => $log->id,
        'household_size' => 2,
        'annual_income' => 20112,
        'guideline_year' => 2026,
        'guideline_region' => PovertyGuideline::REGION_HOME_ABE,
        'poverty_threshold' => 66800,
        'poverty_percentage' => 30.11,
        'poverty_bracket' => 50,
    ]);

    $this->artisan('intake:recompute-home-determinations --dry-run')->assertSuccessful();

    // Dry run writes nothing.
    expect((float) $assessment->fresh()->poverty_percentage)->toBe(30.11);

    $this->artisan('intake:recompute-home-determinations')->assertSuccessful();

    $fresh = $assessment->fresh();
    expect((float) $fresh->poverty_percentage)->toBe(24.09)
        ->and((float) $fresh->area_median_income)->toBe(83500.0)
        ->and($fresh->poverty_bracket)->toBe(30)
        ->and($fresh->tier_label)->toBe('Extremely low income')
        // The ceiling it was judged against is left exactly as it was.
        ->and((float) $fresh->poverty_threshold)->toBe(66800.0);
})->group('regression');

it('leaves FPL determinations alone when recomputing', function () {
    (new HomeIncomeLimitsSeeder)->run();

    $log = \App\Models\CommunicationLog::factory()->create();
    $fpl = IntakeIncomeAssessment::create([
        'communication_log_id' => $log->id,
        'household_size' => 2,
        'annual_income' => 20112,
        'guideline_year' => 2025,
        'guideline_region' => '48_contiguous',
        'poverty_threshold' => 21150,
        'poverty_percentage' => 95.09,
        'poverty_bracket' => 100,
    ]);

    $this->artisan('intake:recompute-home-determinations')->assertSuccessful();

    expect((float) $fpl->fresh()->poverty_percentage)->toBe(95.09)
        ->and($fpl->fresh()->area_median_income)->toBeNull();
})->group('regression');
