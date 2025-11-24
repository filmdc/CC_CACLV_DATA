<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CommunicationsLogPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Communications Log Permissions
        $communicationsPermissions = [
            ['name' => 'communications_log.view', 'display_name' => 'View Communications Log', 'description' => 'View communications log entries'],
            ['name' => 'communications_log.create', 'display_name' => 'Create Communications Log', 'description' => 'Create new communications log entries'],
            ['name' => 'communications_log.update', 'display_name' => 'Update Communications Log', 'description' => 'Update existing communications log entries'],
            ['name' => 'communications_log.delete', 'display_name' => 'Delete Communications Log', 'description' => 'Delete communications log entries'],
        ];

        foreach ($communicationsPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Assign all communications permissions to Administrator role
        $adminRole = Role::where('name', 'Administrator')->first();
        if ($adminRole) {
            $allCommunicationsPermissions = Permission::whereIn('name', [
                'communications_log.view',
                'communications_log.create',
                'communications_log.update',
                'communications_log.delete',
            ])->get();

            foreach ($allCommunicationsPermissions as $permission) {
                if (! $adminRole->permissions->contains($permission->id)) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }

        // Also assign to Project Manager role if it exists
        $projectManagerRole = Role::where('name', 'project_manager')->first();
        if ($projectManagerRole) {
            $projectManagerPermissions = Permission::whereIn('name', [
                'communications_log.view',
                'communications_log.create',
                'communications_log.update',
            ])->get();

            foreach ($projectManagerPermissions as $permission) {
                if (! $projectManagerRole->permissions->contains($permission->id)) {
                    $projectManagerRole->permissions()->attach($permission->id);
                }
            }
        }

        $this->command->info('Communications Log permissions created and assigned successfully.');
    }
}
