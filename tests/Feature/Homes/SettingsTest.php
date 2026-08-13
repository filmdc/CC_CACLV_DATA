<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

function settingsPortalUser(array $permissionNames = []): User
{
    $role = Role::firstOrCreate(['name' => 'settings_portal_test'], ['display_name' => 'Settings Portal Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

it('shows the always-available surfaces to any authenticated user', function () {
    $this->actingAs(settingsPortalUser([]));

    $this->get(route('homes.settings'))
        ->assertOk()
        ->assertSee('Fund codes')
        ->assertSee('Contractors')
        ->assertSee('Properties')
        ->assertDontSee('Requirements library')
        ->assertDontSee('PDF form templates');
});

it('shows permission-gated surfaces when the user holds them', function () {
    $this->actingAs(settingsPortalUser(['requirements_library.view', 'pdf_templates.view']));

    $this->get(route('homes.settings'))
        ->assertOk()
        ->assertSee('Requirements library')
        ->assertSee('PDF form templates');
});
