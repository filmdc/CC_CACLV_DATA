<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Activity audit log permission.
 *
 * `audit.view` gates the admin-wide Activity Log page (the before/after
 * change trail captured by spatie/laravel-activitylog on projects,
 * milestones, requirements, budgets, expenses, and comm logs).
 *
 * Distinct from the existing `audit.login_view` (login/session audit) —
 * different surface, different data. Attached to the admin roles; grant
 * to auditors/managers individually as needed.
 *
 * Idempotent (firstOrCreate + contains guard) — safe to re-run.
 */
class AuditLogPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'audit.view'],
            [
                'display_name' => 'View Activity Audit Log',
                'description' => 'View the system-wide activity audit log (who changed what, when, with before/after values) across projects, milestones, requirements, budgets, and communication logs.',
            ]
        );

        // Attach to whichever admin role names exist in this install.
        $adminRoles = Role::whereIn('name', ['Administrator', 'admin', 'administrator'])->get();

        foreach ($adminRoles as $role) {
            if (! $role->permissions->contains($permission->id)) {
                $role->permissions()->attach($permission->id);
            }
        }

        if ($this->command) {
            $this->command->info('Activity audit log permission (audit.view) created and assigned.');
        }
    }
}
