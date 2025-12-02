<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class FeedbackPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Feedback Permissions
        $feedbackPermissions = [
            ['name' => 'feedback.submit', 'display_name' => 'Submit Feedback', 'description' => 'Submit bug reports and feature requests'],
            ['name' => 'feedback.view_own', 'display_name' => 'View Own Feedback', 'description' => 'View own feedback submissions'],
            ['name' => 'feedback.view_all', 'display_name' => 'View All Feedback', 'description' => 'View all feedback submissions'],
            ['name' => 'feedback.manage', 'display_name' => 'Manage Feedback', 'description' => 'Change status, assign, and respond to feedback'],
            ['name' => 'feedback.delete', 'display_name' => 'Delete Feedback', 'description' => 'Delete feedback items'],
            ['name' => 'feedback.configure', 'display_name' => 'Configure Feedback', 'description' => 'Manage feedback integration settings'],
        ];

        foreach ($feedbackPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Assign permissions to Administrator role (all permissions)
        $adminRole = Role::where('name', 'Administrator')->first();
        if ($adminRole) {
            $allFeedbackPermissions = Permission::whereIn('name', [
                'feedback.submit',
                'feedback.view_own',
                'feedback.view_all',
                'feedback.manage',
                'feedback.delete',
                'feedback.configure',
            ])->get();

            foreach ($allFeedbackPermissions as $permission) {
                if (! $adminRole->permissions->contains($permission->id)) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }

        // Assign basic permissions to all non-admin roles (submit and view own)
        // Note: feedback.view_all, feedback.manage are admin-only permissions
        $basicPermissions = Permission::whereIn('name', [
            'feedback.submit',
            'feedback.view_own',
        ])->get();

        $nonAdminRoles = Role::where('name', '!=', 'Administrator')->get();
        foreach ($nonAdminRoles as $role) {
            foreach ($basicPermissions as $permission) {
                if (! $role->permissions->contains($permission->id)) {
                    $role->permissions()->attach($permission->id);
                }
            }
        }

        $this->command->info('Feedback permissions created and assigned successfully.');
    }
}
