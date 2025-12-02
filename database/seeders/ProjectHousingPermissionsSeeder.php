<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ProjectHousingPermissionsSeeder extends Seeder
{
    /**
     * Consolidated seeder for all project and housing permissions.
     * This replaces permission definitions from:
     * - 2025_10_28_054506_add_project_management_permissions migration
     * - HousingModuleSeeder.php
     * - ApprovalPermissionsSeeder.php
     */
    public function run(): void
    {
        $this->command->info('Starting ProjectHousingPermissionsSeeder...');

        // Group 1: Projects (unified namespace - replaces housing_jobs.*)
        $projectsPermissions = [
            ['name' => 'projects.view', 'display_name' => 'View Projects', 'description' => 'View project pipeline and details'],
            ['name' => 'projects.create', 'display_name' => 'Create Projects', 'description' => 'Create new projects'],
            ['name' => 'projects.edit', 'display_name' => 'Edit Projects', 'description' => 'Edit project details'],
            ['name' => 'projects.delete', 'display_name' => 'Delete Projects', 'description' => 'Delete projects'],
            ['name' => 'projects.manage', 'display_name' => 'Manage Projects', 'description' => 'Full project management (admin-level)'],
            ['name' => 'projects.approve', 'display_name' => 'Approve Projects', 'description' => 'Approve projects for work'],
            ['name' => 'projects.contacts', 'display_name' => 'Manage Project Contacts', 'description' => 'Manage project contact ledger'],
            ['name' => 'projects.documents', 'display_name' => 'Manage Project Documents', 'description' => 'Manage project documents'],
            ['name' => 'projects.verify_documents', 'display_name' => 'Verify Project Documents', 'description' => 'Verify uploaded project documents'],
            ['name' => 'projects.approve_tasks', 'display_name' => 'Approve Tasks', 'description' => 'Approve task completion'],
        ];

        // Group 2: Milestones
        $milestonesPermissions = [
            ['name' => 'milestones.view', 'display_name' => 'View Milestones', 'description' => 'View milestones'],
            ['name' => 'milestones.create', 'display_name' => 'Create Milestones', 'description' => 'Create milestones'],
            ['name' => 'milestones.edit', 'display_name' => 'Edit Milestones', 'description' => 'Edit milestone details'],
            ['name' => 'milestones.delete', 'display_name' => 'Delete Milestones', 'description' => 'Delete milestones'],
            ['name' => 'milestones.complete', 'display_name' => 'Complete Milestones', 'description' => 'Mark milestones complete'],
        ];

        // Group 3: Tasks
        $tasksPermissions = [
            ['name' => 'tasks.view', 'display_name' => 'View Tasks', 'description' => 'View tasks'],
            ['name' => 'tasks.create', 'display_name' => 'Create Tasks', 'description' => 'Create tasks'],
            ['name' => 'tasks.edit', 'display_name' => 'Edit Tasks', 'description' => 'Edit tasks'],
            ['name' => 'tasks.delete', 'display_name' => 'Delete Tasks', 'description' => 'Delete tasks'],
            ['name' => 'tasks.assign', 'display_name' => 'Assign Tasks', 'description' => 'Assign tasks to users'],
            ['name' => 'tasks.complete', 'display_name' => 'Complete Tasks', 'description' => 'Mark tasks complete'],
            ['name' => 'tasks.approve', 'display_name' => 'Approve Tasks', 'description' => 'Approve tasks requiring approval'],
        ];

        // Group 4: Documents (milestone-level)
        $documentsPermissions = [
            ['name' => 'documents.view', 'display_name' => 'View Documents', 'description' => 'View documents'],
            ['name' => 'documents.create', 'display_name' => 'Create Documents', 'description' => 'Create document requirements'],
            ['name' => 'documents.upload', 'display_name' => 'Upload Documents', 'description' => 'Upload document files'],
            ['name' => 'documents.delete', 'display_name' => 'Delete Documents', 'description' => 'Delete documents'],
            ['name' => 'documents.approve', 'display_name' => 'Approve Documents', 'description' => 'Approve documents'],
            ['name' => 'documents.reject', 'display_name' => 'Reject Documents', 'description' => 'Reject documents'],
        ];

        // Group 5: Properties
        $propertiesPermissions = [
            ['name' => 'properties.view', 'display_name' => 'View Properties', 'description' => 'View property records'],
            ['name' => 'properties.create', 'display_name' => 'Create Properties', 'description' => 'Create new property records'],
            ['name' => 'properties.edit', 'display_name' => 'Edit Properties', 'description' => 'Edit existing property records'],
            ['name' => 'properties.delete', 'display_name' => 'Delete Properties', 'description' => 'Delete property records'],
            ['name' => 'properties.documents', 'display_name' => 'Manage Property Documents', 'description' => 'Upload and manage property documents'],
            ['name' => 'properties.notes', 'display_name' => 'Manage Property Notes', 'description' => 'Add and manage property notes'],
            ['name' => 'properties.approve_documents', 'display_name' => 'Approve Property Documents', 'description' => 'Approve property documents'],
        ];

        // Group 6: Contractors
        $contractorsPermissions = [
            ['name' => 'contractors.view', 'display_name' => 'View Contractors', 'description' => 'View contractor records'],
            ['name' => 'contractors.create', 'display_name' => 'Create Contractors', 'description' => 'Create new contractor records'],
            ['name' => 'contractors.edit', 'display_name' => 'Edit Contractors', 'description' => 'Edit existing contractor records'],
            ['name' => 'contractors.delete', 'display_name' => 'Delete Contractors', 'description' => 'Delete contractor records'],
            ['name' => 'contractors.documents', 'display_name' => 'Manage Contractor Documents', 'description' => 'Upload and manage contractor documents'],
            ['name' => 'contractors.approve_documents', 'display_name' => 'Approve Contractor Documents', 'description' => 'Approve contractor documents'],
        ];

        // Group 7: Budget & Expenses
        $budgetExpensesPermissions = [
            ['name' => 'budget.view', 'display_name' => 'View Budgets', 'description' => 'View budgets'],
            ['name' => 'budget.edit', 'display_name' => 'Edit Budgets', 'description' => 'Edit budgets'],
            ['name' => 'budget.delete', 'display_name' => 'Delete Budgets', 'description' => 'Delete budgets'],
            ['name' => 'expenses.view', 'display_name' => 'View Expenses', 'description' => 'View expenses'],
            ['name' => 'expenses.create', 'display_name' => 'Create Expenses', 'description' => 'Create expenses'],
            ['name' => 'expenses.delete', 'display_name' => 'Delete Expenses', 'description' => 'Delete expenses'],
        ];

        // Group 8: Call Logs & Communications
        $communicationsPermissions = [
            ['name' => 'call_logs.view', 'display_name' => 'View Call Logs', 'description' => 'View call logs'],
            ['name' => 'call_logs.create', 'display_name' => 'Create Call Logs', 'description' => 'Create call logs'],
            ['name' => 'call_logs.edit', 'display_name' => 'Edit Call Logs', 'description' => 'Edit call logs'],
            ['name' => 'call_logs.delete', 'display_name' => 'Delete Call Logs', 'description' => 'Delete call logs'],
            ['name' => 'communications_log.view', 'display_name' => 'View Communications Log', 'description' => 'View communications log'],
            ['name' => 'communications_log.create', 'display_name' => 'Create Communications', 'description' => 'Create communications'],
            ['name' => 'communications_log.edit', 'display_name' => 'Edit Communications', 'description' => 'Edit communications'],
            ['name' => 'communications_log.delete', 'display_name' => 'Delete Communications', 'description' => 'Delete communications'],
        ];

        // Group 9: Approvals Dashboard
        $approvalsPermissions = [
            ['name' => 'approvals.view_dashboard', 'display_name' => 'Access Approval Dashboard', 'description' => 'Access approval dashboard'],
            ['name' => 'approvals.approve_reject', 'display_name' => 'Approve or Reject Items', 'description' => 'Approve or reject items'],
            ['name' => 'approvals.bulk_approve', 'display_name' => 'Bulk Approve Items', 'description' => 'Bulk approve items'],
        ];

        // Group 10: PDF Templates
        $pdfTemplatesPermissions = [
            ['name' => 'pdf_templates.view', 'display_name' => 'View PDF Templates', 'description' => 'View templates'],
            ['name' => 'pdf_templates.create', 'display_name' => 'Create PDF Templates', 'description' => 'Create templates'],
            ['name' => 'pdf_templates.edit', 'display_name' => 'Edit PDF Templates', 'description' => 'Edit templates'],
            ['name' => 'pdf_templates.delete', 'display_name' => 'Delete PDF Templates', 'description' => 'Delete templates'],
            ['name' => 'pdf_templates.generate', 'display_name' => 'Generate PDFs', 'description' => 'Generate PDFs from templates'],
        ];

        // Group 11: Housing Module Admin
        $housingAdminPermissions = [
            ['name' => 'housing.reports.view', 'display_name' => 'View Housing Reports', 'description' => 'View housing reports'],
            ['name' => 'housing.reports.export', 'display_name' => 'Export Housing Reports', 'description' => 'Export housing reports'],
            ['name' => 'housing.module.manage', 'display_name' => 'Manage Housing Module', 'description' => 'Manage housing module settings'],
        ];

        // Combine all permissions
        $allPermissions = array_merge(
            $projectsPermissions,
            $milestonesPermissions,
            $tasksPermissions,
            $documentsPermissions,
            $propertiesPermissions,
            $contractorsPermissions,
            $budgetExpensesPermissions,
            $communicationsPermissions,
            $approvalsPermissions,
            $pdfTemplatesPermissions,
            $housingAdminPermissions
        );

        // Create all permissions
        foreach ($allPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        $this->command->info('Created '.count($allPermissions).' permissions');

        // Get permission names by group for role assignments
        $projectPermNames = array_column($projectsPermissions, 'name');
        $milestonePermNames = array_column($milestonesPermissions, 'name');
        $taskPermNames = array_column($tasksPermissions, 'name');
        $documentPermNames = array_column($documentsPermissions, 'name');
        $propertyPermNames = array_column($propertiesPermissions, 'name');
        $contractorPermNames = array_column($contractorsPermissions, 'name');
        $budgetExpensePermNames = array_column($budgetExpensesPermissions, 'name');
        $communicationPermNames = array_column($communicationsPermissions, 'name');
        $approvalPermNames = array_column($approvalsPermissions, 'name');
        $pdfTemplatePermNames = array_column($pdfTemplatesPermissions, 'name');
        $housingAdminPermNames = array_column($housingAdminPermissions, 'name');

        // All permission names for admin
        $allPermissionNames = array_column($allPermissions, 'name');

        // Assign all permissions to Admin/Administrator role
        $adminRole = Role::whereIn('name', ['admin', 'Administrator'])->first();
        if ($adminRole) {
            $adminPermIds = Permission::whereIn('name', $allPermissionNames)->pluck('id');
            foreach ($adminPermIds as $permId) {
                if (! $adminRole->permissions->contains($permId)) {
                    $adminRole->permissions()->attach($permId);
                }
            }
            $this->command->info('Granted all permissions to Administrator role');
        }

        // Create/Update project_manager role (standardized with underscore)
        $projectManagerRole = Role::firstOrCreate(
            ['name' => 'project_manager'],
            [
                'display_name' => 'Project Manager',
                'description' => 'Full project management with approval authority',
            ]
        );

        $pmPermissions = Permission::whereIn('name', array_merge(
            $projectPermNames,
            $milestonePermNames,
            $taskPermNames,
            $documentPermNames,
            $budgetExpensePermNames,
            $approvalPermNames,
            $pdfTemplatePermNames,
            ['properties.view', 'properties.documents', 'properties.notes', 'properties.approve_documents'],
            ['contractors.view', 'contractors.documents', 'contractors.approve_documents'],
            ['housing.reports.view'],
            ['participants.view', 'households.view']
        ))->pluck('id');

        $projectManagerRole->permissions()->syncWithoutDetaching($pmPermissions);
        $this->command->info('Created/updated project_manager role');

        // Create/Update project_team_member role
        $teamMemberRole = Role::firstOrCreate(
            ['name' => 'project_team_member'],
            [
                'display_name' => 'Project Team Member',
                'description' => 'Limited access - complete tasks, upload docs, view',
            ]
        );

        $teamPermissions = Permission::whereIn('name', [
            'projects.view',
            'milestones.view',
            'tasks.view',
            'tasks.complete',
            'documents.view',
            'documents.upload',
            'budget.view',
            'expenses.view',
        ])->pluck('id');

        $teamMemberRole->permissions()->syncWithoutDetaching($teamPermissions);
        $this->command->info('Created/updated project_team_member role');

        // Create/Update housing_coordinator role
        $housingCoordinatorRole = Role::firstOrCreate(
            ['name' => 'housing_coordinator'],
            [
                'display_name' => 'Housing Coordinator',
                'description' => 'Property/contractor management without delete',
            ]
        );

        $coordinatorPermissions = Permission::whereIn('name', [
            'properties.view',
            'properties.create',
            'properties.edit',
            'properties.documents',
            'properties.notes',
            'contractors.view',
            'contractors.create',
            'contractors.edit',
            'contractors.documents',
            'projects.view',
            'projects.create',
            'projects.edit',
            'projects.contacts',
            'projects.documents',
            'housing.reports.view',
            'participants.view',
            'households.view',
        ])->pluck('id');

        $housingCoordinatorRole->permissions()->syncWithoutDetaching($coordinatorPermissions);
        $this->command->info('Created/updated housing_coordinator role');

        // Create/Update housing_manager role
        $housingManagerRole = Role::firstOrCreate(
            ['name' => 'housing_manager'],
            [
                'display_name' => 'Housing Manager',
                'description' => 'Full housing access including approvals and reports',
            ]
        );

        $managerPermissions = Permission::whereIn('name', array_merge(
            $propertyPermNames,
            $contractorPermNames,
            $projectPermNames,
            $housingAdminPermNames,
            $approvalPermNames,
            ['participants.view', 'households.view']
        ))->pluck('id');

        $housingManagerRole->permissions()->syncWithoutDetaching($managerPermissions);
        $this->command->info('Created/updated housing_manager role');

        $this->command->info('ProjectHousingPermissionsSeeder completed successfully!');
    }
}
