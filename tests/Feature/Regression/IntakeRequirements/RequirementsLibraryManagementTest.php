<?php

/*
| Comm Log v2 (Option A): regression coverage for the Requirements
| Library Livewire component (atom + bundle CRUD, soft-delete +
| restore, bundle ↔ atom membership, bundle ↔ service auto-apply).
*/

use App\Livewire\RequirementsLibraryManagement;
use App\Models\RequirementDefinition;
use App\Models\RequirementTemplateSet;
use App\Models\Service;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('mount denies users without requirements_library.view', function () {
    $stranger = \App\Models\User::factory()->create();

    Livewire::actingAs($stranger)
        ->test(RequirementsLibraryManagement::class)
        ->assertForbidden();
})->group('regression');

it('admin can render the library page on the atoms tab by default', function () {
    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->assertSet('activeTab', 'atoms')
        ->assertSet('canManage', true);
})->group('regression');

it('saveAtom creates an atom with sanitized key + audit columns', function () {
    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('showCreateAtom')
        ->set('atomLabel', 'Photo ID')
        ->set('atomKey', 'photo-id')
        ->set('atomKind', 'doc')
        ->set('atomDefaultRequired', true)
        ->set('atomHint', "Driver's license, state ID, or passport.")
        ->call('saveAtom')
        ->assertHasNoErrors()
        ->assertSet('showAtomForm', false);

    $atom = RequirementDefinition::where('key', 'photo-id')->firstOrFail();
    expect($atom->label)->toBe('Photo ID');
    expect($atom->kind)->toBe('doc');
    expect((bool) $atom->default_required)->toBeTrue();
    expect($atom->created_by_user_id)->toBe($this->user->id);
})->group('regression');

it('saveAtom rejects an invalid key format', function () {
    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('showCreateAtom')
        ->set('atomLabel', 'Bad Key')
        ->set('atomKey', 'Bad Key With Spaces!')
        ->call('saveAtom')
        ->assertHasErrors(['atomKey']);
})->group('regression');

it('saveAtom rejects a duplicate key on the same tier', function () {
    RequirementDefinition::factory()->create(['key' => 'photo-id']);

    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('showCreateAtom')
        ->set('atomLabel', 'Photo ID v2')
        ->set('atomKey', 'photo-id')
        ->call('saveAtom')
        ->assertHasErrors(['atomKey']);
})->group('regression');

it('deleteAtom soft-deletes; restoreAtom brings it back', function () {
    $atom = RequirementDefinition::factory()->create();

    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('deleteAtom', $atom->id);

    expect($atom->fresh()->trashed())->toBeTrue();

    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('restoreAtom', $atom->id);

    expect($atom->fresh()->trashed())->toBeFalse();
})->group('regression');

it('saveBundle creates a bundle and immediately opens it for item management', function () {
    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('setTab', 'bundles')
        ->call('showCreateBundle')
        ->set('bundleName', 'HCP First-Time Homebuyer')
        ->set('bundleDescription', 'Standard intake bundle for HCP FTHB applicants.')
        ->call('saveBundle')
        ->assertHasNoErrors()
        ->assertSet('showBundleForm', false)
        ->assertNotSet('editingBundleId', null);

    $bundle = RequirementTemplateSet::where('name', 'HCP First-Time Homebuyer')->firstOrFail();
    expect($bundle->created_by_user_id)->toBe($this->user->id);
})->group('regression');

it('addItem links an atom to a bundle with auto-positioning', function () {
    $atom1 = RequirementDefinition::factory()->create(['label' => 'First atom']);
    $atom2 = RequirementDefinition::factory()->create(['label' => 'Second atom']);
    $bundle = RequirementTemplateSet::factory()->create();

    $component = Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('setTab', 'bundles')
        ->call('openBundle', $bundle->id);

    $component->set('newItemAtomId', $atom1->id)->call('addItem');
    $component->set('newItemAtomId', $atom2->id)->call('addItem');

    $items = $bundle->fresh()->items()->orderBy('requirement_template_set_items.position')->get();
    expect($items->pluck('id')->all())->toBe([$atom1->id, $atom2->id]);
    expect((int) $items[0]->pivot->position)->toBe(0);
    expect((int) $items[1]->pivot->position)->toBe(1);
})->group('regression');

it('moveItem swaps positions in-bundle', function () {
    $atom1 = RequirementDefinition::factory()->create();
    $atom2 = RequirementDefinition::factory()->create();
    $bundle = RequirementTemplateSet::factory()->create();
    $bundle->items()->attach([$atom1->id => ['position' => 0], $atom2->id => ['position' => 1]]);

    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('openBundle', $bundle->id)
        ->call('moveItem', $atom2->id, 'up');

    $order = \Illuminate\Support\Facades\DB::table('requirement_template_set_items')
        ->where('template_set_id', $bundle->id)
        ->orderBy('position')
        ->pluck('requirement_definition_id')
        ->map(fn ($v) => (int) $v)
        ->all();
    expect($order)->toBe([$atom2->id, $atom1->id]);
})->group('regression');

it('toggleItemRequired records the per-bundle override', function () {
    $atom = RequirementDefinition::factory()->create(['default_required' => true]);
    $bundle = RequirementTemplateSet::factory()->create();
    $bundle->items()->attach($atom->id, ['position' => 0]);

    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('openBundle', $bundle->id)
        ->call('toggleItemRequired', $atom->id, false);

    $pivot = $bundle->fresh()->items->first()->pivot;
    expect((bool) $pivot->required_override)->toBeFalse();
})->group('regression');

it('saveServiceLinks syncs the bundle ↔ service many-to-many', function () {
    $bundle = RequirementTemplateSet::factory()->create();
    $service = Service::query()->first() ?? Service::factory()->create();

    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('openBundle', $bundle->id)
        ->set('linkedServiceIds', [$service->id])
        ->call('saveServiceLinks');

    expect($bundle->fresh()->services->pluck('id')->all())->toContain($service->id);

    // Unticking the service strips the link cleanly.
    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('openBundle', $bundle->id)
        ->set('linkedServiceIds', [])
        ->call('saveServiceLinks');

    expect($bundle->fresh()->services->pluck('id')->all())->toBeEmpty();
})->group('regression');

it('removeItem detaches an atom from a bundle but leaves the atom in the library', function () {
    $atom = RequirementDefinition::factory()->create();
    $bundle = RequirementTemplateSet::factory()->create();
    $bundle->items()->attach($atom->id, ['position' => 0]);

    Livewire::actingAs($this->user)
        ->test(RequirementsLibraryManagement::class)
        ->call('openBundle', $bundle->id)
        ->call('removeItem', $atom->id);

    expect($bundle->fresh()->items)->toBeEmpty();
    expect($atom->fresh())->not->toBeNull();
})->group('regression');

it('non-manager user cannot mutate; manage methods abort', function () {
    $viewer = \App\Models\User::factory()->create();
    // Grant view but not manage
    $perm = \App\Models\Permission::where('name', 'requirements_library.view')->first();
    $role = \App\Models\Role::firstOrCreate(['name' => 'viewer_only'], ['display_name' => 'Viewer only']);
    $role->permissions()->syncWithoutDetaching([$perm->id]);
    $viewer->roles()->syncWithoutDetaching([$role->id]);

    Livewire::actingAs($viewer)
        ->test(RequirementsLibraryManagement::class)
        ->call('showCreateAtom')
        ->assertForbidden();
})->group('regression');
