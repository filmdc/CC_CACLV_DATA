<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class FundCodePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create fund code permissions
        $permissions = [
            ['name' => 'fund_codes.view', 'display_name' => 'View Fund Codes', 'description' => 'Can view fund codes'],
            ['name' => 'fund_codes.create', 'display_name' => 'Create Fund Codes', 'description' => 'Can create new fund codes'],
            ['name' => 'fund_codes.edit', 'display_name' => 'Edit Fund Codes', 'description' => 'Can edit existing fund codes'],
            ['name' => 'fund_codes.delete', 'display_name' => 'Delete Fund Codes', 'description' => 'Can delete fund codes'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                ]
            );
        }

        // Grant all fund code permissions to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $fundCodePermissions = Permission::where('name', 'like', 'fund_codes.%')->pluck('id');
            $adminRole->permissions()->syncWithoutDetaching($fundCodePermissions);
        }

        // Grant fund code permissions to project_manager role if it exists
        $projectManagerRole = Role::where('name', 'project_manager')->first();
        if ($projectManagerRole) {
            $fundCodePermissions = Permission::where('name', 'like', 'fund_codes.%')->pluck('id');
            $projectManagerRole->permissions()->syncWithoutDetaching($fundCodePermissions);
        }

        $this->command->info('Fund code permissions created and assigned to admin and project_manager roles.');
    }
}
