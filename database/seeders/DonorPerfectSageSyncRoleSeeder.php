<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DonorPerfectSageSyncRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get the DonorPerfect & Sage Sync role
        $role = Role::firstOrCreate(
            ['name' => 'DonorPerfect & Sage Sync'],
            [
                'display_name' => 'DonorPerfect & Sage Sync Manager',
                'description' => 'Can view sync configurations, perform syncs, and manage mappings/filters (but not API credentials)',
            ]
        );

        $this->command->info("Role '{$role->name}' created/updated.");

        // Get all the sync-related permissions
        $permissionNames = [
            // DonorPerfect
            'donorperfect.sync.view',
            'donorperfect.sync.refresh',
            'donor_gifts.view',

            // Sage Intacct
            'sage.sync.view',
            'sage.sync.perform',
            'sage.mappings.edit',
            'sage.code_mappings.edit',
            'sage.conditional_mappings.edit',
            'sage.filters.edit',
        ];

        $permissions = Permission::whereIn('name', $permissionNames)->get();

        if ($permissions->isEmpty()) {
            $this->command->error('No sync permissions found. Please run the permissions migration first.');
            return;
        }

        // Attach permissions to role
        $role->permissions()->syncWithoutDetaching($permissions->pluck('id'));

        $this->command->info("Assigned {$permissions->count()} permissions to role '{$role->name}':");
        foreach ($permissions as $permission) {
            $this->command->info("  - {$permission->name}: {$permission->description}");
        }

        // Also add these permissions to Administrator role so admins have full access
        $adminRole = Role::where('name', 'Administrator')->first();
        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching($permissions->pluck('id'));
            $this->command->info("\nAlso added sync permissions to Administrator role for full admin access.");
        }
    }
}
