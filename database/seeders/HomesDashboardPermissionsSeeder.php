<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Dashboard-widget permission for the Housing portal.
 *
 * `homes.dashboard.manage` gates /settings/dashboard, where the Today home's
 * cards are switched on and off site-wide.
 *
 * OPTIONAL: the surface authorizes admin-OR-permission in its own mount(),
 * so administrators already reach it without this seeder. Running it lets
 * the job be delegated to a non-admin role later.
 *
 * Idempotent (firstOrCreate + contains guard) — safe to re-run.
 */
class HomesDashboardPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'homes.dashboard.manage'],
            [
                'display_name' => 'Manage Housing Dashboard Widgets',
                'description' => 'Choose which cards the housing portal Today dashboard shows (site-wide).',
            ]
        );

        $adminRoles = Role::whereIn('name', ['Administrator', 'admin', 'administrator'])->get();

        foreach ($adminRoles as $role) {
            if (! $role->permissions->contains($permission->id)) {
                $role->permissions()->attach($permission->id);
            }
        }

        if ($this->command) {
            $this->command->info('Housing dashboard permission (homes.dashboard.manage) created and assigned.');
        }
    }
}
