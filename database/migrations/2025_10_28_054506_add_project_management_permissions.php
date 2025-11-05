<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Housing Jobs permissions (required for PM module)
        $housingPermissions = [
            ['name' => 'housing_jobs.view', 'description' => 'View housing jobs and projects'],
            ['name' => 'housing_jobs.create', 'description' => 'Create new housing jobs'],
            ['name' => 'housing_jobs.edit', 'description' => 'Edit housing job details'],
            ['name' => 'housing_jobs.delete', 'description' => 'Delete housing jobs'],
        ];

        foreach ($housingPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                [
                    'description' => $permissionData['description'],
                    'display_name' => ucwords(str_replace(['.', '_'], ' ', $permissionData['name']))
                ]
            );
        }

        // Create Project Management permissions
        $permissions = [
            // Project viewing and management
            ['name' => 'projects.view', 'description' => 'View project pipeline and project details'],
            ['name' => 'projects.create', 'description' => 'Create new projects (housing jobs with PM enabled)'],
            ['name' => 'projects.manage', 'description' => 'Full project management access'],

            // Milestone permissions
            ['name' => 'milestones.create', 'description' => 'Create milestones in projects'],
            ['name' => 'milestones.edit', 'description' => 'Edit milestone details'],
            ['name' => 'milestones.delete', 'description' => 'Delete milestones'],
            ['name' => 'milestones.complete', 'description' => 'Mark milestones as complete'],

            // Task permissions
            ['name' => 'tasks.create', 'description' => 'Create tasks within milestones'],
            ['name' => 'tasks.edit', 'description' => 'Edit task details'],
            ['name' => 'tasks.delete', 'description' => 'Delete tasks'],
            ['name' => 'tasks.assign', 'description' => 'Assign tasks to team members'],
            ['name' => 'tasks.complete', 'description' => 'Mark tasks as complete'],
            ['name' => 'tasks.approve', 'description' => 'Approve tasks requiring approval'],

            // Document permissions
            ['name' => 'documents.create', 'description' => 'Create document requirements'],
            ['name' => 'documents.upload', 'description' => 'Upload documents'],
            ['name' => 'documents.delete', 'description' => 'Delete documents'],
            ['name' => 'documents.approve', 'description' => 'Approve uploaded documents'],
            ['name' => 'documents.reject', 'description' => 'Reject uploaded documents'],

            // Budget permissions
            ['name' => 'budget.view', 'description' => 'View project budgets'],
            ['name' => 'budget.edit', 'description' => 'Create and edit budget categories'],
            ['name' => 'budget.delete', 'description' => 'Delete budget categories'],

            // Expense permissions
            ['name' => 'expenses.create', 'description' => 'Record project expenses'],
            ['name' => 'expenses.view', 'description' => 'View project expenses'],
            ['name' => 'expenses.delete', 'description' => 'Delete expense records'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                [
                    'description' => $permissionData['description'],
                    'display_name' => ucwords(str_replace(['.', '_'], ' ', $permissionData['name']))
                ]
            );
        }

        echo "Created " . count($housingPermissions) . " Housing Jobs permissions\n";
        echo "Created " . count($permissions) . " Project Management permissions\n";

        // Assign all PM + housing permissions to Administrator role
        $adminRole = Role::where('name', 'Administrator')->first();
        if ($adminRole) {
            $allPermissions = array_merge(array_column($housingPermissions, 'name'), array_column($permissions, 'name'));
            $permissionIds = Permission::whereIn('name', $allPermissions)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                $adminRole->permissions()->syncWithoutDetaching([$permissionId]);
            }
            echo "✓ Granted all PM + Housing permissions to Administrator role\n";
        }

        // Create Project Manager role with appropriate permissions
        $projectManagerRole = Role::firstOrCreate(
            ['name' => 'Project Manager'],
            [
                'description' => 'Manages construction projects with full milestone/task/budget access',
                'display_name' => 'Project Manager'
            ]
        );

        $pmPermissions = Permission::whereIn('name', [
            'projects.view',
            'projects.create',
            'projects.manage',
            'milestones.create',
            'milestones.edit',
            'milestones.delete',
            'milestones.complete',
            'tasks.create',
            'tasks.edit',
            'tasks.delete',
            'tasks.assign',
            'tasks.complete',
            'tasks.approve',
            'documents.create',
            'documents.upload',
            'documents.delete',
            'documents.approve',
            'documents.reject',
            'budget.view',
            'budget.edit',
            'budget.delete',
            'expenses.create',
            'expenses.view',
            'expenses.delete',
            'housing_jobs.view',
            'housing_jobs.create',
            'housing_jobs.edit',
            'properties.view',
            'contractors.view',
            'participants.view',
        ])->pluck('id');

        $projectManagerRole->permissions()->sync($pmPermissions);

        echo "✓ Created/updated 'Project Manager' role with PM permissions\n";

        // Create Team Member role with limited permissions
        $teamMemberRole = Role::firstOrCreate(
            ['name' => 'Project Team Member'],
            [
                'description' => 'Team member who can complete assigned tasks and upload documents',
                'display_name' => 'Project Team Member'
            ]
        );

        $teamPermissions = Permission::whereIn('name', [
            'projects.view',
            'tasks.complete',
            'documents.upload',
            'budget.view',
            'expenses.view',
        ])->pluck('id');

        $teamMemberRole->permissions()->sync($teamPermissions);

        echo "✓ Created/updated 'Project Team Member' role\n";

        echo "\n✅ Project Management permissions setup complete!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'projects.view', 'projects.create', 'projects.manage',
            'milestones.create', 'milestones.edit', 'milestones.delete', 'milestones.complete',
            'tasks.create', 'tasks.edit', 'tasks.delete', 'tasks.assign', 'tasks.complete', 'tasks.approve',
            'documents.create', 'documents.upload', 'documents.delete', 'documents.approve', 'documents.reject',
            'budget.view', 'budget.edit', 'budget.delete',
            'expenses.create', 'expenses.view', 'expenses.delete',
        ];

        Permission::whereIn('name', $permissions)->delete();

        Role::whereIn('name', ['Project Manager', 'Project Team Member'])->delete();
    }
};
