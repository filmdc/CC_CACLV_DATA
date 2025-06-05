<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Console\Command;

class SetupAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:setup-permissions {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup admin permissions for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        // Create missing permissions that the sidebar expects
        $requiredPermissions = [
            // Participants
            ['name' => 'participants.view', 'display_name' => 'View Participants', 'category' => 'Participants'],
            ['name' => 'participants.create', 'display_name' => 'Create Participants', 'category' => 'Participants'],
            ['name' => 'participants.edit', 'display_name' => 'Edit Participants', 'category' => 'Participants'],
            ['name' => 'participants.delete', 'display_name' => 'Delete Participants', 'category' => 'Participants'],
            
            // Households
            ['name' => 'households.view', 'display_name' => 'View Households', 'category' => 'Households'],
            ['name' => 'households.create', 'display_name' => 'Create Households', 'category' => 'Households'],
            ['name' => 'households.edit', 'display_name' => 'Edit Households', 'category' => 'Households'],
            ['name' => 'households.delete', 'display_name' => 'Delete Households', 'category' => 'Households'],
            
            // Programs
            ['name' => 'programs.view', 'display_name' => 'View Programs', 'category' => 'Programs'],
            ['name' => 'programs.create', 'display_name' => 'Create Programs', 'category' => 'Programs'],
            ['name' => 'programs.edit', 'display_name' => 'Edit Programs', 'category' => 'Programs'],
            ['name' => 'programs.delete', 'display_name' => 'Delete Programs', 'category' => 'Programs'],
            
            // Services
            ['name' => 'services.view', 'display_name' => 'View Services', 'category' => 'Services'],
            ['name' => 'services.create', 'display_name' => 'Create Services', 'category' => 'Services'],
            ['name' => 'services.edit', 'display_name' => 'Edit Services', 'category' => 'Services'],
            ['name' => 'services.delete', 'display_name' => 'Delete Services', 'category' => 'Services'],
            
            // Enrollments
            ['name' => 'enrollments.view', 'display_name' => 'View Enrollments', 'category' => 'Enrollments'],
            ['name' => 'enrollments.create', 'display_name' => 'Create Enrollments', 'category' => 'Enrollments'],
            ['name' => 'enrollments.edit', 'display_name' => 'Edit Enrollments', 'category' => 'Enrollments'],
            ['name' => 'enrollments.delete', 'display_name' => 'Delete Enrollments', 'category' => 'Enrollments'],
            
            // Contracts
            ['name' => 'contracts.view', 'display_name' => 'View Contracts', 'category' => 'Contracts'],
            ['name' => 'contracts.create', 'display_name' => 'Create Contracts', 'category' => 'Contracts'],
            ['name' => 'contracts.edit', 'display_name' => 'Edit Contracts', 'category' => 'Contracts'],
            ['name' => 'contracts.delete', 'display_name' => 'Delete Contracts', 'category' => 'Contracts'],
            
            // Work Plans
            ['name' => 'work_plans.view', 'display_name' => 'View Work Plans', 'category' => 'Work Plans'],
            ['name' => 'work_plans.create', 'display_name' => 'Create Work Plans', 'category' => 'Work Plans'],
            ['name' => 'work_plans.edit', 'display_name' => 'Edit Work Plans', 'category' => 'Work Plans'],
            ['name' => 'work_plans.delete', 'display_name' => 'Delete Work Plans', 'category' => 'Work Plans'],
            
            // Work Plan Goals
            ['name' => 'work_plan_goals.view', 'display_name' => 'View Work Plan Goals', 'category' => 'Work Plan Goals'],
            ['name' => 'work_plan_goals.create', 'display_name' => 'Create Work Plan Goals', 'category' => 'Work Plan Goals'],
            ['name' => 'work_plan_goals.edit', 'display_name' => 'Edit Work Plan Goals', 'category' => 'Work Plan Goals'],
            ['name' => 'work_plan_goals.delete', 'display_name' => 'Delete Work Plan Goals', 'category' => 'Work Plan Goals'],
            
            // Users
            ['name' => 'users.view', 'display_name' => 'View Users', 'category' => 'Users'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'category' => 'Users'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'category' => 'Users'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'category' => 'Users'],
            
            // Roles
            ['name' => 'roles.view', 'display_name' => 'View Roles', 'category' => 'Roles'],
            ['name' => 'roles.create', 'display_name' => 'Create Roles', 'category' => 'Roles'],
            ['name' => 'roles.edit', 'display_name' => 'Edit Roles', 'category' => 'Roles'],
            ['name' => 'roles.delete', 'display_name' => 'Delete Roles', 'category' => 'Roles'],
            
            // Reference Data
            ['name' => 'reference_data.view', 'display_name' => 'View Reference Data', 'category' => 'Reference Data'],
            ['name' => 'reference_data.create', 'display_name' => 'Create Reference Data', 'category' => 'Reference Data'],
            ['name' => 'reference_data.edit', 'display_name' => 'Edit Reference Data', 'category' => 'Reference Data'],
            ['name' => 'reference_data.delete', 'display_name' => 'Delete Reference Data', 'category' => 'Reference Data'],
            
            // Reference Data Sets
            ['name' => 'reference_data_sets.view', 'display_name' => 'View Reference Data Sets', 'category' => 'Reference Data Sets'],
            ['name' => 'reference_data_sets.create', 'display_name' => 'Create Reference Data Sets', 'category' => 'Reference Data Sets'],
            ['name' => 'reference_data_sets.edit', 'display_name' => 'Edit Reference Data Sets', 'category' => 'Reference Data Sets'],
            ['name' => 'reference_data_sets.delete', 'display_name' => 'Delete Reference Data Sets', 'category' => 'Reference Data Sets'],
            
            // Poverty Guidelines
            ['name' => 'poverty_guidelines.view', 'display_name' => 'View Poverty Guidelines', 'category' => 'Poverty Guidelines'],
            ['name' => 'poverty_guidelines.create', 'display_name' => 'Create Poverty Guidelines', 'category' => 'Poverty Guidelines'],
            ['name' => 'poverty_guidelines.edit', 'display_name' => 'Edit Poverty Guidelines', 'category' => 'Poverty Guidelines'],
            ['name' => 'poverty_guidelines.delete', 'display_name' => 'Delete Poverty Guidelines', 'category' => 'Poverty Guidelines'],
            
            // Admin
            ['name' => 'admin.access', 'display_name' => 'Access Admin Panel', 'category' => 'Administration'],
        ];

        $this->info('Creating missing permissions...');
        $createdCount = 0;
        foreach ($requiredPermissions as $permData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'display_name' => $permData['display_name'],
                    'category' => $permData['category'],
                    'description' => 'Permission to ' . strtolower($permData['display_name'])
                ]
            );
            
            if ($permission->wasRecentlyCreated) {
                $createdCount++;
                $this->line("Created permission: {$permission->name}");
            }
        }
        
        $this->info("Created {$createdCount} new permissions.");

        // Get or create Administrator role
        $adminRole = Role::firstOrCreate(
            ['name' => 'Administrator'],
            [
                'display_name' => 'Administrator',
                'description' => 'Full access to all system features'
            ]
        );

        // Assign all permissions to Administrator role
        $allPermissions = Permission::all();
        $adminRole->permissions()->sync($allPermissions->pluck('id'));
        
        $this->info("Assigned all {$allPermissions->count()} permissions to Administrator role.");

        // Assign Administrator role to user if not already assigned
        if (!$user->roles()->where('role_id', $adminRole->id)->exists()) {
            $user->roles()->attach($adminRole->id);
            $this->info("Assigned Administrator role to {$email}.");
        } else {
            $this->info("User {$email} already has Administrator role.");
        }

        $this->info('Setup complete! The user should now have full access to all areas.');
        
        return 0;
    }
}