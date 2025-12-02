<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DocumentationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Documentation Permissions
        $documentationPermissions = [
            ['name' => 'documentation.view', 'display_name' => 'View Documentation', 'description' => 'View published documentation articles'],
            ['name' => 'documentation.view_internal', 'display_name' => 'View Internal Documentation', 'description' => 'View internal-only documentation articles'],
            ['name' => 'documentation.manage', 'display_name' => 'Manage Documentation', 'description' => 'Create, edit, and delete documentation articles and categories'],
            ['name' => 'documentation.publish', 'display_name' => 'Publish Documentation', 'description' => 'Publish and unpublish documentation articles'],
        ];

        foreach ($documentationPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Assign all permissions to Administrator role
        $adminRole = Role::where('name', 'Administrator')->first();
        if ($adminRole) {
            $allDocPermissions = Permission::where('name', 'like', 'documentation.%')->get();

            foreach ($allDocPermissions as $permission) {
                if (! $adminRole->permissions->contains($permission->id)) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }

        // Assign view permission to all non-admin roles
        // Note: documentation.view_internal and documentation.manage are admin-only
        $viewPermission = Permission::where('name', 'documentation.view')->first();
        if ($viewPermission) {
            $nonAdminRoles = Role::where('name', '!=', 'Administrator')->get();
            foreach ($nonAdminRoles as $role) {
                if (! $role->permissions->contains($viewPermission->id)) {
                    $role->permissions()->attach($viewPermission->id);
                }
            }
        }

        $this->command->info('Documentation permissions created and assigned successfully.');
    }
}
