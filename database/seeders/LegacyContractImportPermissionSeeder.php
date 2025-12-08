<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class LegacyContractImportPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the permission if it doesn't exist
        $permission = Permission::firstOrCreate(
            ['name' => 'contracts.import_legacy'],
            [
                'display_name' => 'Import Legacy Contracts',
                'description' => 'Ability to import contracts from the legacy database system',
                'category' => 'Contracts',
            ]
        );

        // Assign to Administrator role
        $adminRole = Role::where('name', 'Administrator')->first();
        if ($adminRole && ! $adminRole->permissions()->where('name', 'contracts.import_legacy')->exists()) {
            $adminRole->permissions()->attach($permission->id);
            $this->command->info('✓ Added contracts.import_legacy permission to Administrator role');
        }

        $this->command->info('✓ Legacy Contract Import permission seeded successfully');
    }
}
