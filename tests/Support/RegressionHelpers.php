<?php

namespace Tests\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class RegressionHelpers
{
    /**
     * Create a User with the `admin` role attached and every existing
     * permission granted to that role.
     *
     * Assumes RegressionTestSeeder (or the relevant permission seeders) has
     * already run, so housing-module permissions exist in the DB. Idempotent
     * — safe to call from multiple beforeEach hooks.
     */
    public static function adminUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator', 'description' => 'Regression test admin role.']
        );

        $role->permissions()->syncWithoutDetaching(Permission::pluck('id')->toArray());

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }
}
