<?php

use App\Models\HousingJob;
use App\Models\Program;
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
 * The housing-user redirect lives in mount() and uses a data-driven
 * predicate (user_program link to a housing-enabled program OR
 * project_manager on a housing job) rather than a permission check.
 * Permission-based predicates fail for admins because the admin role
 * auto-grants every permission; admins removed from a housing program
 * via the user_program pivot would still redirect under
 * hasPermission('projects.view').
 */

it('a user with a user_program link to a housing-enabled program is redirected from /dashboard', function () {
    $housingProgram = Program::factory()->create(['housing_module_enabled' => true]);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->programs()->attach($housingProgram->id);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('dashboard.housing'));
})->group('regression');

it('a user who is project_manager on a non-template housing job is redirected', function () {
    $program = Program::factory()->create(['housing_module_enabled' => true]);
    $user = User::factory()->create(['email_verified_at' => now()]);

    HousingJob::factory()->create([
        'program_id' => $program->id,
        'is_template' => false,
        'project_manager_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('dashboard.housing'));
})->group('regression');

it('an admin without housing program assignment or housing project_manager rows is NOT redirected', function () {
    // The original bug: admin role auto-grants projects.view, so a
    // permission-based predicate redirected admins regardless of
    // their actual housing involvement. The data-driven predicate
    // must NOT redirect this user.
    $adminRole = \App\Models\Role::firstOrCreate(
        ['name' => 'admin'],
        ['display_name' => 'Administrator']
    );
    $adminRole->permissions()->syncWithoutDetaching(
        \App\Models\Permission::pluck('id')->toArray()
    );

    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->roles()->attach($adminRole->id);
    expect($admin->isAdmin())->toBeTrue();
    expect($admin->hasPermission('projects.view'))->toBeTrue();

    // No user_program assignments to any housing-enabled program.
    expect($admin->programs()->where('programs.housing_module_enabled', true)->exists())->toBeFalse();
    // No project_manager_id rows.
    expect(HousingJob::where('project_manager_id', $admin->id)->exists())->toBeFalse();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Housing Dashboard');
})->group('regression');

it('a contract-dashboard user lands on /dashboard without redirecting, even with housing assignment', function () {
    // contract-database-dashboard role takes precedence over the
    // housing-user predicate. Even a user with housing program
    // assignment lands on the contract dashboard if they hold the
    // contract role.
    $contractRole = \App\Models\Role::firstOrCreate(
        ['name' => 'contract-database-dashboard'],
        ['display_name' => 'Contract Database Dashboard']
    );
    $housingProgram = Program::factory()->create(['housing_module_enabled' => true]);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->attach($contractRole->id);
    $user->programs()->attach($housingProgram->id);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
})->group('regression');

it('a user with no roles and no program assignments falls through to the existing programs default', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Housing Dashboard');
})->group('regression');

it('detaching a user_program row stops the redirect on the next request', function () {
    $housingProgram = Program::factory()->create(['housing_module_enabled' => true]);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->programs()->attach($housingProgram->id);

    // First request: redirected.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('dashboard.housing'));

    // Detach the pivot row — simulates the admin UI's "remove user from
    // program" action.
    $user->programs()->detach($housingProgram->id);

    // Subsequent request: no redirect.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Housing Dashboard');
})->group('regression');
