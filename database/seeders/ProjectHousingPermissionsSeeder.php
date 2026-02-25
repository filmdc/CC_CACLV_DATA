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
            ['name' => 'projects.view', 'display_name' => 'View Projects', 'description' => 'View project pipeline and details', 'category' => 'Projects'],
            ['name' => 'projects.create', 'display_name' => 'Create Projects', 'description' => 'Create new projects', 'category' => 'Projects'],
            ['name' => 'projects.edit', 'display_name' => 'Edit Projects', 'description' => 'Edit project details', 'category' => 'Projects'],
            ['name' => 'projects.delete', 'display_name' => 'Delete Projects', 'description' => 'Delete projects', 'category' => 'Projects'],
            ['name' => 'projects.manage', 'display_name' => 'Manage Projects', 'description' => 'Full project management (admin-level)', 'category' => 'Projects'],
            ['name' => 'projects.approve', 'display_name' => 'Approve Projects', 'description' => 'Approve projects for work', 'category' => 'Projects'],
            ['name' => 'projects.contacts', 'display_name' => 'Manage Project Contacts', 'description' => 'Manage project contact ledger', 'category' => 'Projects'],
            ['name' => 'projects.documents', 'display_name' => 'Manage Project Documents', 'description' => 'Manage project documents', 'category' => 'Projects'],
            ['name' => 'projects.verify_documents', 'display_name' => 'Verify Project Documents', 'description' => 'Verify uploaded project documents', 'category' => 'Projects'],
            ['name' => 'projects.approve_tasks', 'display_name' => 'Approve Tasks', 'description' => 'Approve task completion', 'category' => 'Projects'],
        ];

        // Group 2: Milestones
        $milestonesPermissions = [
            ['name' => 'milestones.view', 'display_name' => 'View Milestones', 'description' => 'View milestones', 'category' => 'Milestones'],
            ['name' => 'milestones.create', 'display_name' => 'Create Milestones', 'description' => 'Create milestones', 'category' => 'Milestones'],
            ['name' => 'milestones.edit', 'display_name' => 'Edit Milestones', 'description' => 'Edit milestone details', 'category' => 'Milestones'],
            ['name' => 'milestones.delete', 'display_name' => 'Delete Milestones', 'description' => 'Delete milestones', 'category' => 'Milestones'],
            ['name' => 'milestones.complete', 'display_name' => 'Complete Milestones', 'description' => 'Mark milestones complete', 'category' => 'Milestones'],
        ];

        // Group 3: Tasks
        $tasksPermissions = [
            ['name' => 'tasks.view', 'display_name' => 'View Tasks', 'description' => 'View tasks', 'category' => 'Tasks'],
            ['name' => 'tasks.create', 'display_name' => 'Create Tasks', 'description' => 'Create tasks', 'category' => 'Tasks'],
            ['name' => 'tasks.edit', 'display_name' => 'Edit Tasks', 'description' => 'Edit tasks', 'category' => 'Tasks'],
            ['name' => 'tasks.delete', 'display_name' => 'Delete Tasks', 'description' => 'Delete tasks', 'category' => 'Tasks'],
            ['name' => 'tasks.assign', 'display_name' => 'Assign Tasks', 'description' => 'Assign tasks to users', 'category' => 'Tasks'],
            ['name' => 'tasks.complete', 'display_name' => 'Complete Tasks', 'description' => 'Mark tasks complete', 'category' => 'Tasks'],
            ['name' => 'tasks.approve', 'display_name' => 'Approve Tasks', 'description' => 'Approve tasks requiring approval', 'category' => 'Tasks'],
        ];

        // Group 4: Documents (milestone-level)
        $documentsPermissions = [
            ['name' => 'documents.view', 'display_name' => 'View Documents', 'description' => 'View documents', 'category' => 'Documents'],
            ['name' => 'documents.create', 'display_name' => 'Create Documents', 'description' => 'Create document requirements', 'category' => 'Documents'],
            ['name' => 'documents.upload', 'display_name' => 'Upload Documents', 'description' => 'Upload document files', 'category' => 'Documents'],
            ['name' => 'documents.delete', 'display_name' => 'Delete Documents', 'description' => 'Delete documents', 'category' => 'Documents'],
            ['name' => 'documents.approve', 'display_name' => 'Approve Documents', 'description' => 'Approve documents', 'category' => 'Documents'],
            ['name' => 'documents.reject', 'display_name' => 'Reject Documents', 'description' => 'Reject documents', 'category' => 'Documents'],
        ];

        // Group 5: Properties
        $propertiesPermissions = [
            ['name' => 'properties.view', 'display_name' => 'View Properties', 'description' => 'View property records', 'category' => 'Properties'],
            ['name' => 'properties.create', 'display_name' => 'Create Properties', 'description' => 'Create new property records', 'category' => 'Properties'],
            ['name' => 'properties.edit', 'display_name' => 'Edit Properties', 'description' => 'Edit existing property records', 'category' => 'Properties'],
            ['name' => 'properties.delete', 'display_name' => 'Delete Properties', 'description' => 'Delete property records', 'category' => 'Properties'],
            ['name' => 'properties.documents', 'display_name' => 'Manage Property Documents', 'description' => 'Upload and manage property documents', 'category' => 'Properties'],
            ['name' => 'properties.notes', 'display_name' => 'Manage Property Notes', 'description' => 'Add and manage property notes', 'category' => 'Properties'],
            ['name' => 'properties.approve_documents', 'display_name' => 'Approve Property Documents', 'description' => 'Approve property documents', 'category' => 'Properties'],
        ];

        // Group 6: Contractors
        $contractorsPermissions = [
            ['name' => 'contractors.view', 'display_name' => 'View Contractors', 'description' => 'View contractor records', 'category' => 'Contractors'],
            ['name' => 'contractors.create', 'display_name' => 'Create Contractors', 'description' => 'Create new contractor records', 'category' => 'Contractors'],
            ['name' => 'contractors.edit', 'display_name' => 'Edit Contractors', 'description' => 'Edit existing contractor records', 'category' => 'Contractors'],
            ['name' => 'contractors.delete', 'display_name' => 'Delete Contractors', 'description' => 'Delete contractor records', 'category' => 'Contractors'],
            ['name' => 'contractors.documents', 'display_name' => 'Manage Contractor Documents', 'description' => 'Upload and manage contractor documents', 'category' => 'Contractors'],
            ['name' => 'contractors.approve_documents', 'display_name' => 'Approve Contractor Documents', 'description' => 'Approve contractor documents', 'category' => 'Contractors'],
        ];

        // Group 7: Budget & Expenses
        $budgetExpensesPermissions = [
            ['name' => 'budget.view', 'display_name' => 'View Budgets', 'description' => 'View budgets', 'category' => 'Budget & Expenses'],
            ['name' => 'budget.edit', 'display_name' => 'Edit Budgets', 'description' => 'Edit budgets', 'category' => 'Budget & Expenses'],
            ['name' => 'budget.delete', 'display_name' => 'Delete Budgets', 'description' => 'Delete budgets', 'category' => 'Budget & Expenses'],
            ['name' => 'expenses.view', 'display_name' => 'View Expenses', 'description' => 'View expenses', 'category' => 'Budget & Expenses'],
            ['name' => 'expenses.create', 'display_name' => 'Create Expenses', 'description' => 'Create expenses', 'category' => 'Budget & Expenses'],
            ['name' => 'expenses.delete', 'display_name' => 'Delete Expenses', 'description' => 'Delete expenses', 'category' => 'Budget & Expenses'],
        ];

        // Group 8: Call Logs & Communications
        $communicationsPermissions = [
            ['name' => 'call_logs.view', 'display_name' => 'View Call Logs', 'description' => 'View call logs', 'category' => 'Communications'],
            ['name' => 'call_logs.create', 'display_name' => 'Create Call Logs', 'description' => 'Create call logs', 'category' => 'Communications'],
            ['name' => 'call_logs.edit', 'display_name' => 'Edit Call Logs', 'description' => 'Edit call logs', 'category' => 'Communications'],
            ['name' => 'call_logs.delete', 'display_name' => 'Delete Call Logs', 'description' => 'Delete call logs', 'category' => 'Communications'],
            ['name' => 'communications_log.view', 'display_name' => 'View Communications Log', 'description' => 'View communications log', 'category' => 'Communications'],
            ['name' => 'communications_log.create', 'display_name' => 'Create Communications', 'description' => 'Create communications', 'category' => 'Communications'],
            ['name' => 'communications_log.edit', 'display_name' => 'Edit Communications', 'description' => 'Edit communications', 'category' => 'Communications'],
            ['name' => 'communications_log.delete', 'display_name' => 'Delete Communications', 'description' => 'Delete communications', 'category' => 'Communications'],
        ];

        // Group 9: Approvals Dashboard
        $approvalsPermissions = [
            ['name' => 'approvals.view_dashboard', 'display_name' => 'Access Approval Dashboard', 'description' => 'Access approval dashboard', 'category' => 'Approvals'],
            ['name' => 'approvals.approve_reject', 'display_name' => 'Approve or Reject Items', 'description' => 'Approve or reject items', 'category' => 'Approvals'],
            ['name' => 'approvals.bulk_approve', 'display_name' => 'Bulk Approve Items', 'description' => 'Bulk approve items', 'category' => 'Approvals'],
            ['name' => 'approvals.submit', 'display_name' => 'Submit Approval Requests', 'description' => 'Submit items for approval', 'category' => 'Approvals'],
            ['name' => 'approvals.view_audit_log', 'display_name' => 'View Approval Audit Log', 'description' => 'View approval audit trail and reports', 'category' => 'Approvals'],
        ];

        // Group 10: PDF Templates
        $pdfTemplatesPermissions = [
            ['name' => 'pdf_templates.view', 'display_name' => 'View PDF Templates', 'description' => 'View templates', 'category' => 'PDF Templates'],
            ['name' => 'pdf_templates.create', 'display_name' => 'Create PDF Templates', 'description' => 'Create templates', 'category' => 'PDF Templates'],
            ['name' => 'pdf_templates.edit', 'display_name' => 'Edit PDF Templates', 'description' => 'Edit templates', 'category' => 'PDF Templates'],
            ['name' => 'pdf_templates.delete', 'display_name' => 'Delete PDF Templates', 'description' => 'Delete templates', 'category' => 'PDF Templates'],
            ['name' => 'pdf_templates.generate', 'display_name' => 'Generate PDFs', 'description' => 'Generate PDFs from templates', 'category' => 'PDF Templates'],
        ];

        // Group 11: Housing Module Admin
        $housingAdminPermissions = [
            ['name' => 'housing.reports.view', 'display_name' => 'View Housing Reports', 'description' => 'View housing reports', 'category' => 'Housing Admin'],
            ['name' => 'housing.reports.export', 'display_name' => 'Export Housing Reports', 'description' => 'Export housing reports', 'category' => 'Housing Admin'],
            ['name' => 'housing.module.manage', 'display_name' => 'Manage Housing Module', 'description' => 'Manage housing module settings', 'category' => 'Housing Admin'],
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

        // Create or update all permissions (including category)
        foreach ($allPermissions as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        $this->command->info('Created/updated '.count($allPermissions).' permissions with categories');

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
