<?php

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
 * The housing-user redirect lives in mount(). These tests assert the
 * full HTTP flow (not the Livewire test harness, which doesn't
 * exercise the same render path) lands on the right destination per
 * role.
 */

it('a housing user with projects.view is redirected from /dashboard to /dashboard/housing', function () {
    $housingRole = \App\Models\Role::firstOrCreate(
        ['name' => 'test-housing-coordinator-redirect'],
        ['display_name' => 'Test Housing Coordinator (Redirect)']
    );
    $housingRole->permissions()->syncWithoutDetaching(
        \App\Models\Permission::where('name', 'projects.view')->pluck('id')->all()
    );

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->attach($housingRole->id);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('dashboard.housing'));
})->group('regression');

it('a contract-dashboard user lands on /dashboard without redirecting', function () {
    $contractRole = \App\Models\Role::firstOrCreate(
        ['name' => 'contract-database-dashboard'],
        ['display_name' => 'Contract Database Dashboard']
    );

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->attach($contractRole->id);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
})->group('regression');

it('a user with no special roles falls through to the existing programs dashboard at /dashboard', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    expect($user->hasPermission('projects.view'))->toBeFalse();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Housing Dashboard');
})->group('regression');
