<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RegressionTestSeeder;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

/**
 * Hotfix regression — Livewire 3's render() must return a view; a
 * RedirectResponse from render() blows up at:
 *   "Method Livewire\\Features\\SupportRedirects\\Redirector::render
 *   does not exist."
 *
 * The housing-user redirect lives in mount() and is gated by the
 * dedicated 'housing_dashboard.view' permission. Admins explicitly
 * grant the permission to roles via the existing role-permission UI;
 * housing-program assignment is no longer the discriminator (many
 * programs have housing properties without being centrally housing-
 * focused).
 */

function attachHousingPermission(User $user): void
{
    $role = Role::firstOrCreate(
        ['name' => 'test-housing-dashboard-viewer'],
        ['display_name' => 'Test Housing Dashboard Viewer']
    );
    $permissionId = Permission::where('name', 'housing_dashboard.view')->value('id');
    $role->permissions()->syncWithoutDetaching([$permissionId]);
    $user->roles()->attach($role->id);
}

it('a user with the housing_dashboard.view permission is redirected from /dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    attachHousingPermission($user);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('dashboard.housing'));
})->group('regression');

it('a user without the housing_dashboard.view permission is NOT redirected', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    expect($user->hasPermission('housing_dashboard.view'))->toBeFalse();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Housing Dashboard');
})->group('regression');

it('an admin without housing_dashboard.view explicitly attached is NOT redirected', function () {
    // The bug we fixed: admin role auto-grants every permission. An
    // admin who hasn't been granted the new permission specifically
    // should NOT be redirected. The migration creates the permission
    // row but does NOT auto-attach to any role; only admins who hold
    // a role with this permission attached get the redirect.
    $adminRole = Role::firstOrCreate(
        ['name' => 'admin-without-housing-dashboard'],
        ['display_name' => 'Admin Without Housing Dashboard']
    );
    // Attach every permission EXCEPT housing_dashboard.view.
    $adminRole->permissions()->syncWithoutDetaching(
        Permission::where('name', '!=', 'housing_dashboard.view')->pluck('id')->all()
    );

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->roles()->attach($adminRole->id);

    expect($admin->hasPermission('projects.view'))->toBeTrue();
    expect($admin->hasPermission('housing_dashboard.view'))->toBeFalse();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Housing Dashboard');
})->group('regression');

it('a contract-dashboard user lands on /dashboard without redirecting, even with the housing permission', function () {
    // contract-database-dashboard role takes precedence over the
    // housing-permission predicate.
    $contractRole = Role::firstOrCreate(
        ['name' => 'contract-database-dashboard'],
        ['display_name' => 'Contract Database Dashboard']
    );

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->attach($contractRole->id);
    attachHousingPermission($user);

    expect($user->hasPermission('housing_dashboard.view'))->toBeTrue();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
})->group('regression');

it('removing housing_dashboard.view from the role stops the redirect on the next request', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    attachHousingPermission($user);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('dashboard.housing'));

    // Detach the permission from the role — simulates the admin UI's
    // "remove permission from role" action.
    $role = Role::where('name', 'test-housing-dashboard-viewer')->firstOrFail();
    $permissionId = Permission::where('name', 'housing_dashboard.view')->value('id');
    $role->permissions()->detach($permissionId);

    // Subsequent request: no redirect.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Housing Dashboard');
})->group('regression');

it('direct access to /dashboard/housing without the permission returns 403', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard.housing'))
        ->assertStatus(403);
})->group('regression');

it('direct access to /dashboard/housing with the permission renders 200', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    attachHousingPermission($user);

    $this->actingAs($user)
        ->get(route('dashboard.housing'))
        ->assertOk();
})->group('regression');
