<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Recycle bin permission.
 *
 * `recycle_bin.manage` gates /admin/recycle-bin, where an admin can restore
 * a call log or project the housing project manager deleted from the portal.
 *
 * Deliberately admin-only and separate from communications_log.delete /
 * projects.delete: the PM holds those (she deletes her own records), while
 * undoing a delete is the escalation path she calls an admin for.
 *
 * Idempotent (firstOrCreate + contains guard) — safe to re-run.
 */
class RecycleBinPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'recycle_bin.manage'],
            [
                'display_name' => 'Manage Recycle Bin',
                'description' => 'View and restore call logs and projects deleted from the housing portal.',
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
            $this->command->info('Recycle bin permission (recycle_bin.manage) created and assigned.');
        }
    }
}
