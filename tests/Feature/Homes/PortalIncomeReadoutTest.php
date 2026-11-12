<?php

/*
|----------------------------------------------------------------------
| The housing portal (homes.caclv.org) is the project manager's primary
| interface, so the income qualification has to be legible THERE, not
| only in the main app: the determination reads on the call detail
| without opening anything, and the modal states the band and whether
| the household clears the ceiling.
|----------------------------------------------------------------------
*/

use App\Livewire\Homes\CallDetail;
use App\Livewire\Homes\Settings\IncomeLimits;
use App\Models\CommunicationLog;
use App\Models\HousingService;
use App\Models\Permission;
use App\Models\PovertyGuideline;
use App\Models\Role;
use App\Models\User;
use App\Services\IntakeRequirementInstantiator;
use Database\Seeders\HomeIncomeLimitsSeeder;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->seed(HomeIncomeLimitsSeeder::class);
    $this->actor = RegressionHelpers::adminUser();
    $this->service = app(IntakeRequirementInstantiator::class);

    // 2025 HHS guidelines, so a non-housing intake has something to read.
    foreach ([1 => 15650, 2 => 21150, 3 => 26650, 4 => 32150] as $size => $hundred) {
        PovertyGuideline::updateOrCreate(
            ['year' => 2025, 'region' => '48_contiguous', 'family_size' => $size, 'period' => 'annual'],
            collect(PovertyGuideline::BRACKETS)
                ->mapWithKeys(fn ($b) => ['poverty_'.$b => $hundred * $b / 100])
                ->all()
        );
    }
});

function portalUser(array $extra = []): User
{
    $role = Role::firstOrCreate(['name' => 'portal_income_test'], ['display_name' => 'Portal Income Test']);
    $ids = collect(array_merge(['communications_log.view', 'communications_log.edit'], $extra))
        ->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

/** A housing intake — the housing service is what routes it to HOME. */
function housingIntake(IntakeRequirementInstantiator $service, User $actor): CommunicationLog
{
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $service->forHousingService($log, $svc, $actor);
    $log->housingServices()->attach($svc->id, [
        'created_by_user_id' => $actor->id, 'created_at' => now(),
    ]);

    return $log->fresh();
}

// ── Call detail: the determination is visible without opening anything ──

it('shows the qualification on the portal call detail once saved', function () {
    $log = housingIntake($this->service, $this->actor);

    Livewire::actingAs(portalUser())
        ->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 2)
        ->set('incomeForm.entry_period', 'annual')
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '20112')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    // Re-render the page the PM lands on — the chips must be right there.
    Livewire::actingAs(portalUser())
        ->test(CallDetail::class, ['log' => $log->fresh()])
        ->assertSee('24.1% of AMI')
        ->assertSee('Extremely low income')
        ->assertSee('Income eligible')
        ->assertSee('$20,112/yr')
        ->assertSee('household of 2')
        ->assertSee('FY2026 limit $66,800')
        ->assertSee('area median $83,500');
});

it('shows over income on the portal when the household clears no band', function () {
    $log = housingIntake($this->service, $this->actor);

    Livewire::actingAs(portalUser())
        ->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 2)
        ->set('incomeForm.entry_period', 'annual')
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '90000')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    Livewire::actingAs(portalUser())
        ->test(CallDetail::class, ['log' => $log->fresh()])
        ->assertSee('Over income')
        ->assertDontSee('Income eligible');
});

it('states the band and eligibility inside the portal modal', function () {
    $log = housingIntake($this->service, $this->actor);

    Livewire::actingAs(portalUser())
        ->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 2)
        ->set('incomeForm.entry_period', 'annual')
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '20112')
        ->assertSee('of the FY2026 area median income')
        ->assertSee('Extremely low income')
        ->assertSee('Income eligible')
        ->assertSee('area median income for that size is $83,500.00')
        ->assertSee('FY2026 eligibility limit is $66,800.00');
});

it('keeps a non-housing intake on the poverty level in the portal', function () {
    // No housing service attached — must not acquire AMI language.
    $log = CommunicationLog::factory()->create();
    $this->service->incomeEligibility($log, $this->actor);

    Livewire::actingAs(portalUser())
        ->test(CallDetail::class, ['log' => $log->fresh()])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 2)
        ->set('incomeForm.entry_period', 'annual')
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '20112')
        ->assertSee('federal poverty level')
        ->assertDontSee('area median income');
});

// ── Settings surface renders for the PM ───────────────────────────

it('renders the income limits settings screen with AMI alongside the ceilings', function () {
    $this->actingAs(portalUser(['poverty-guidelines.view']));

    Livewire::test(IncomeLimits::class)
        ->assertSee('HOME income limits')
        // FY2026 ceilings and their medians, side by side.
        ->assertSee('58,450')     // 1-person ceiling
        ->assertSee('73,063')     // 1-person AMI (104,375 × 0.70, rounded)
        ->assertSee('104,375')    // 4-person median
        ->assertSee('derived from the 4-person limit');
});

it('reflects a corrected published median on the settings screen', function () {
    $this->actingAs(portalUser(['poverty-guidelines.view', 'poverty-guidelines.edit']));

    Livewire::test(IncomeLimits::class)
        ->call('startEdit')
        ->set('mfi', 110000)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('110,000')
        ->assertSee("HUD's published figure");

    // And the determination follows the corrected median.
    expect(PovertyGuideline::homeAmiFor(2, 2026))->toBe(88000.0);
});

// ── Project detail: the qualification travels with the work ───────

it('shows the intake income qualification read-only on the project', function () {
    $log = housingIntake($this->service, $this->actor);

    Livewire::actingAs(portalUser())
        ->test(CallDetail::class, ['log' => $log])
        ->call('openIncomeEligibility')
        ->set('incomeForm.household_size', 2)
        ->set('incomeForm.entry_period', 'annual')
        ->set('incomeSources', [])
        ->set('incomeForm.entered_amount', '20112')
        ->call('saveIncomeEligibility')
        ->assertHasNoErrors();

    $job = \App\Models\HousingJob::factory()->create();
    $log->forceFill(['converted_to_job_id' => $job->id])->save();

    Livewire::actingAs(portalUser(['projects.view']))
        ->test(\App\Livewire\Homes\ProjectDetail::class, ['job' => $job])
        ->assertSee('Income qualification')
        ->assertSee('24.1% of AMI')
        ->assertSee('Extremely low income')
        ->assertSee('Income eligible')
        ->assertSee('household of 2')
        ->assertSee('Determined at intake')
        // Read-only on the project: the only affordance is a link back.
        ->assertSee('edit on the intake')
        ->assertDontSee('Save income');
});

it('says plainly when the intake recorded no income qualification', function () {
    // An intake converted before the income feature existed.
    $log = housingIntake($this->service, $this->actor);
    $job = \App\Models\HousingJob::factory()->create();
    $log->forceFill(['converted_to_job_id' => $job->id])->save();

    Livewire::actingAs(portalUser(['projects.view']))
        ->test(\App\Livewire\Homes\ProjectDetail::class, ['job' => $job])
        ->assertSee('Income qualification')
        ->assertSee('No income qualification was recorded on this intake')
        ->assertSee('Record it on the intake');
});
