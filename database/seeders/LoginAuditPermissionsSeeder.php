<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class LoginAuditPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create login audit permissions
        $permissions = [
            [
                'name' => 'audit.login_view',
                'display_name' => 'View Login Audit',
                'description' => 'View user login audit log and active sessions',
            ],
            [
                'name' => 'audit.login_export',
                'display_name' => 'Export Login Audit',
                'description' => 'Export login audit data',
            ],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(
                ['name' => $permData['name']],
                $permData
            );
        }

        // Assign permissions to Administrator role
        $adminRole = Role::where('name', 'Administrator')->first();
        if ($adminRole) {
            $allPermissions = Permission::whereIn('name', ['audit.login_view', 'audit.login_export'])->get();
            foreach ($allPermissions as $permission) {
                if (! $adminRole->permissions->contains($permission->id)) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }

        // Note: Login audit is an admin-only feature - do not assign to non-admin roles

        $this->command->info('Login audit permissions created and assigned successfully.');
    }
}
