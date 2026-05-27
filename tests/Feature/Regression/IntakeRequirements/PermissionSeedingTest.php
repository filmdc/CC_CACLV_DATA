<?php

/*
|----------------------------------------------------------------------
| P-39b: IntakeRequirementsPermissionsSeeder behavior (addendum D-4).
|
| Asserts the six P-39 permissions exist after seeding, the documented
| role grants are in place, and re-running the seeder is idempotent.
|----------------------------------------------------------------------
*/

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\IntakeRequirementsPermissionsSeeder;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    // RegressionTestSeeder does NOT call InitialSetupSeeder, so the
    // Administrator/admin role doesn't auto-exist in the regression DB.
    // RegressionHelpers::adminUser() firstOrCreates the `admin` role and
    // syncs every existing Permission onto it (including the six new
    // intake permissions, since RegressionTestSeeder seeded them above).
    RegressionHelpers::adminUser();
});

it('seeds all six intake permissions', function () {
    $names = [
        'intake_dashboard.view',
        'intake_requirements.create',
        'intake_requirements.update',
        'intake_requirements.delete',
        'intake_requirements.upload',
        'comm_log_pins.manage',
    ];

    foreach ($names as $name) {
        expect(Permission::where('name', $name)->exists())->toBeTrue();
    }
})->group('regression');

it('grants 5 of the 6 permissions to housing_manager (admin-only delete is the carve-out)', function () {
    $role = Role::where('name', 'housing_manager')->firstOrFail();

    $heldNames = $role->permissions()
        ->whereIn('permissions.name', [
            'intake_dashboard.view',
            'intake_requirements.create',
            'intake_requirements.update',
            'intake_requirements.delete',
            'intake_requirements.upload',
            'comm_log_pins.manage',
        ])
        ->pluck('permissions.name')
        ->all();

    expect($heldNames)->toHaveCount(5);
    expect($heldNames)->not->toContain('intake_requirements.delete');
})->group('regression');

it('grants all six permissions to admin', function () {
    $admin = Role::where('name', 'admin')->first() ?? Role::where('name', 'Administrator')->first();
    expect($admin)->not->toBeNull('admin or Administrator role must exist for this test');

    $heldNames = $admin->permissions()
        ->whereIn('permissions.name', [
            'intake_dashboard.view',
            'intake_requirements.create',
            'intake_requirements.update',
            'intake_requirements.delete',
            'intake_requirements.upload',
            'comm_log_pins.manage',
        ])
        ->pluck('permissions.name')
        ->all();

    expect($heldNames)->toHaveCount(6);
})->group('regression');

it('grants comm_log_pins.manage to every non-system role', function () {
    $pinPermId = Permission::where('name', 'comm_log_pins.manage')->value('id');
    expect($pinPermId)->not->toBeNull();

    Role::all()->each(function (Role $role) use ($pinPermId) {
        expect($role->permissions->contains($pinPermId))
            ->toBeTrue("Role '{$role->name}' should hold comm_log_pins.manage");
    });
})->group('regression');

it('is idempotent — re-running the seeder does not change row counts', function () {
    $permsBefore = Permission::count();
    $roleHasPermBefore = \DB::table('role_permission')->count();
    $rolesBefore = Role::count();

    (new IntakeRequirementsPermissionsSeeder)->run();
    (new IntakeRequirementsPermissionsSeeder)->run();

    expect(Permission::count())->toBe($permsBefore);
    expect(\DB::table('role_permission')->count())->toBe($roleHasPermBefore);
    expect(Role::count())->toBe($rolesBefore);
})->group('regression');
