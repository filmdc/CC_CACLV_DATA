<?php

use App\Livewire\Homes\Settings\Contractors;
use App\Livewire\Homes\Settings\FundCodes;
use App\Livewire\Homes\Settings\Properties;
use App\Livewire\Homes\Settings\RequirementsLibrary;
use App\Models\Contractor;
use App\Models\FundCode;
use App\Models\Household;
use App\Models\HousingJob;
use App\Models\PdfFormTemplate;
use App\Models\Permission;
use App\Models\Property;
use App\Models\ReferenceData;
use App\Models\RequirementDefinition;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

function settingsPortalUser(array $permissionNames = []): User
{
    $role = Role::firstOrCreate(['name' => 'settings_portal_test'], ['display_name' => 'Settings Portal Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

// ── Hub ───────────────────────────────────────────────────────────

it('shows hub cards only for surfaces the user can view', function () {
    $this->actingAs(settingsPortalUser(['fund_codes.view', 'properties.view']));

    // Assert on the card SUBTITLES — the sidebar footer names all five
    // surfaces on every page, so the labels themselves always appear.
    $this->get(route('homes.settings'))
        ->assertOk()
        ->assertSee('Funding sources, expiry dates')
        ->assertSee('Addresses, property types')
        ->assertDontSee('reusable checklist items behind')
        ->assertDontSee('Fillable forms generated')
        ->assertDontSee('Licensing, insurance, and contact');
});

it('gates each settings surface at the route', function () {
    $this->actingAs(settingsPortalUser([]));

    $this->get(route('homes.settings.fund-codes'))->assertForbidden();
    $this->get(route('homes.settings.requirements'))->assertForbidden();
    $this->get(route('homes.settings.contractors'))->assertForbidden();
    $this->get(route('homes.settings.properties'))->assertForbidden();
    $this->get(route('homes.settings.pdf-templates'))->assertForbidden();
});

// ── Fund codes ────────────────────────────────────────────────────

it('creates and edits a fund code with expiry dates', function () {
    $this->actingAs(settingsPortalUser(['fund_codes.view', 'fund_codes.create', 'fund_codes.edit']));

    Livewire::test(FundCodes::class)
        ->call('openCreate')
        ->set('code', 'f9999')
        ->set('description', 'Test fund')
        ->set('expiresAt', now()->addDays(20)->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $fund = FundCode::where('code', 'F9999')->first(); // uppercased on save
    expect($fund)->not->toBeNull();
    expect($fund->expiry_state)->toBe('soon');

    Livewire::test(FundCodes::class)
        ->call('openEdit', $fund->id)
        ->set('description', 'Renamed fund')
        ->call('save')
        ->assertHasNoErrors();

    expect($fund->fresh()->description)->toBe('Renamed fund');
});

it('links requirement atoms to a fund code', function () {
    $this->actingAs(settingsPortalUser(['fund_codes.view', 'fund_codes.edit']));

    $fund = FundCode::factory()->create();
    $atom = RequirementDefinition::factory()->create();

    Livewire::test(FundCodes::class)
        ->call('openAtomLinks', $fund->id)
        ->call('toggleAtom', $atom->id)
        ->call('saveAtomLinks')
        ->assertHasNoErrors();

    expect($fund->requirementDefinitions()->pluck('requirement_definitions.id')->all())->toContain($atom->id);
});

it('refuses to delete a fund code attached to communication logs', function () {
    $this->actingAs(settingsPortalUser(['fund_codes.view', 'fund_codes.delete']));

    $fund = FundCode::factory()->create();
    $log = \App\Models\CommunicationLog::factory()->create();
    $log->anticipatedFundCodes()->attach($fund->id, ['created_by_user_id' => auth()->id(), 'created_at' => now()]);

    Livewire::test(FundCodes::class)->call('delete', $fund->id);

    expect(FundCode::find($fund->id))->not->toBeNull();
});

// ── Requirements library ──────────────────────────────────────────

it('creates an atom with an auto-slugged key and retires it softly', function () {
    $this->actingAs(settingsPortalUser(['requirements_library.view', 'requirements_library.manage']));

    // set() fires the updatedAtomLabel hook, which auto-slugs the key.
    Livewire::test(RequirementsLibrary::class)
        ->call('openCreate')
        ->set('atomLabel', 'Benefit Letter (SS)')
        ->assertSet('atomKey', 'benefit-letter-ss')
        ->call('save')
        ->assertHasNoErrors();

    $atom = RequirementDefinition::where('key', 'benefit-letter-ss')->first();
    expect($atom)->not->toBeNull();

    Livewire::test(RequirementsLibrary::class)->call('delete', $atom->id);
    expect(RequirementDefinition::withTrashed()->find($atom->id)->trashed())->toBeTrue();

    Livewire::test(RequirementsLibrary::class)->call('restore', $atom->id);
    expect(RequirementDefinition::find($atom->id))->not->toBeNull();
});

it('blocks library writes without the manage permission', function () {
    $this->actingAs(settingsPortalUser(['requirements_library.view']));

    Livewire::test(RequirementsLibrary::class)
        ->call('openCreate')
        ->assertForbidden();
});

// ── Contractors ───────────────────────────────────────────────────

it('creates a contractor and guards delete when they have projects', function () {
    $this->actingAs(settingsPortalUser(['contractors.view', 'contractors.create', 'contractors.edit', 'contractors.delete']));

    Livewire::test(Contractors::class)
        ->call('openCreate')
        ->set('form.company_name', 'McAllister HVAC')
        ->set('form.phone', '484-880-4421')
        ->set('form.address_line_1', '10 Main St')
        ->set('form.city', 'Allentown')
        ->set('form.state', 'pa')
        ->set('form.zip_code', '18101')
        ->call('save')
        ->assertHasNoErrors();

    $contractor = Contractor::where('company_name', 'McAllister HVAC')->first();
    expect($contractor)->not->toBeNull();
    expect($contractor->state)->toBe('PA');

    HousingJob::factory()->create(['contractor_id' => $contractor->id]);

    Livewire::test(Contractors::class)->call('delete', $contractor->id);
    expect(Contractor::find($contractor->id))->not->toBeNull();
});

// ── Properties ────────────────────────────────────────────────────

it('creates a property tied to a household and adds a note', function () {
    $this->actingAs(settingsPortalUser(['properties.view', 'properties.create', 'properties.edit', 'properties.notes']));

    $household = Household::factory()->create();
    \App\Models\Participant::factory()->create([
        'household_id' => $household->id,
        'is_head_of_household' => true,
    ]);
    $type = ReferenceData::first() ?? null;

    $test = Livewire::test(Properties::class)
        ->call('openCreate')
        ->set('form.household_id', $household->id)
        ->set('form.address_line_1', '916 S Poplar St')
        ->set('form.city', 'Allentown')
        ->set('form.state', 'pa')
        ->set('form.zip_code', '18103')
        ->set('form.county', 'Lehigh');

    if ($type) {
        $test->set('form.property_type_id', $type->id);
    }

    $test->call('save');

    if ($type) {
        $test->assertHasNoErrors();

        $property = Property::where('address_line_1', '916 S Poplar St')->first();
        expect($property)->not->toBeNull();
        expect($property->household_id)->toBe($household->id);

        Livewire::test(Properties::class)
            ->call('openNote', $property->id)
            ->set('noteText', 'Roof vent leaks into the kitchen.')
            ->call('saveNote')
            ->assertHasNoErrors();

        expect($property->notes()->count())->toBe(1);
    } else {
        // No reference data seeded — the type is required, so save errors.
        $test->assertHasErrors(['form.property_type_id']);
    }
});

// ── PDF templates ─────────────────────────────────────────────────

it('edits template metadata and guards delete for used templates', function () {
    $this->actingAs(settingsPortalUser(['pdf_templates.view', 'pdf_templates.edit', 'pdf_templates.delete']));

    $template = PdfFormTemplate::create([
        'name' => 'WHR Application',
        'file_path' => 'pdf-templates/fake.pdf',
        'original_filename' => 'whr.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 1000,
        'field_count' => 4,
        'is_active' => true,
        'usage_count' => 3,
    ]);

    Livewire::test(\App\Livewire\Homes\Settings\PdfTemplates::class)
        ->call('openEdit', $template->id)
        ->set('name', 'WHR Application v2')
        ->set('category', 'housing')
        ->call('save')
        ->assertHasNoErrors();

    expect($template->fresh()->name)->toBe('WHR Application v2');

    // Used templates can't be deleted.
    Livewire::test(\App\Livewire\Homes\Settings\PdfTemplates::class)->call('delete', $template->id);
    expect(PdfFormTemplate::find($template->id))->not->toBeNull();
});

// ── Counties (housing services) ───────────────────────────────────

it('creates, links, and retires a county', function () {
    $this->actingAs(settingsPortalUser(['fund_codes.view', 'fund_codes.edit']));

    // Create.
    Livewire::test(\App\Livewire\Homes\Settings\Counties::class)
        ->call('openCreate')
        ->set('serviceName', 'Northampton County')
        ->set('serviceDescription', 'Northampton rehab pipeline')
        ->call('save')
        ->assertHasNoErrors();

    $county = \App\Models\HousingService::where('name', 'Northampton County')->first();
    expect($county)->not->toBeNull();

    // Link a fund + an atom (mirrors the main app's saveServiceLinks pivots).
    $fund = FundCode::factory()->create();
    $atom = RequirementDefinition::factory()->create();

    Livewire::test(\App\Livewire\Homes\Settings\Counties::class)
        ->call('openLinks', $county->id)
        ->call('toggleFund', $fund->id)
        ->call('toggleAtom', $atom->id)
        ->call('saveLinks')
        ->assertHasNoErrors();

    expect($county->fundCodes()->pluck('fund_codes.id')->all())->toContain($fund->id);
    expect($county->requirementDefinitions()->pluck('requirement_definitions.id')->all())->toContain($atom->id);

    // Unlink the atom — sync removes it.
    Livewire::test(\App\Livewire\Homes\Settings\Counties::class)
        ->call('openLinks', $county->id)
        ->call('toggleAtom', $atom->id)
        ->call('saveLinks');

    expect($county->requirementDefinitions()->count())->toBe(0);
    expect($county->fundCodes()->count())->toBe(1);

    // Retire (soft) + restore.
    Livewire::test(\App\Livewire\Homes\Settings\Counties::class)->call('delete', $county->id);
    expect(\App\Models\HousingService::withTrashed()->find($county->id)->trashed())->toBeTrue();

    Livewire::test(\App\Livewire\Homes\Settings\Counties::class)->call('restore', $county->id);
    expect(\App\Models\HousingService::find($county->id))->not->toBeNull();
});

it('county links drive what a new intake receives when the county is attached', function () {
    $this->actingAs(settingsPortalUser([
        'fund_codes.view', 'fund_codes.edit',
        'communications_log.view', 'communications_log.edit',
    ]));

    $county = \App\Models\HousingService::create(['name' => 'Lehigh County', 'is_active' => true]);
    $fund = FundCode::factory()->create();
    $atom = RequirementDefinition::factory()->create(['category' => RequirementDefinition::CATEGORY_ELIGIBILITY]);

    // Configure the county in Settings.
    Livewire::test(\App\Livewire\Homes\Settings\Counties::class)
        ->call('openLinks', $county->id)
        ->call('toggleFund', $fund->id)
        ->call('toggleAtom', $atom->id)
        ->call('saveLinks')
        ->assertHasNoErrors();

    // Attach the county to a call — the configured set stamps through.
    $log = \App\Models\CommunicationLog::factory()->create();

    Livewire::test(\App\Livewire\Homes\CallDetail::class, ['log' => $log])
        ->call('toggleCounty', $county->id)
        ->assertHasNoErrors();

    expect($log->anticipatedFundCodes()->pluck('fund_codes.id')->all())->toContain($fund->id);
    expect($log->intakeRequirements()->where('is_orphaned', false)->count())->toBeGreaterThanOrEqual(1);
});

it('blocks county writes without fund_codes.edit', function () {
    $this->actingAs(settingsPortalUser(['fund_codes.view']));

    Livewire::test(\App\Livewire\Homes\Settings\Counties::class)
        ->call('openCreate')
        ->assertForbidden();
});
