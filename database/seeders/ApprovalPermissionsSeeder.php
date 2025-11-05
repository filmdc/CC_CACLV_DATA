<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class ApprovalPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Approval Permissions
        $approvalPermissions = [
            ['name' => 'properties.approve_documents', 'display_name' => 'Approve Property Documents', 'description' => 'Approve property documents'],
            ['name' => 'contractors.approve_documents', 'display_name' => 'Approve Contractor Documents', 'description' => 'Approve contractor documents'],
            ['name' => 'housing_jobs.approve_tasks', 'display_name' => 'Approve Task Completion', 'description' => 'Approve task completion'],
            ['name' => 'approvals.view_dashboard', 'display_name' => 'Access Approval Dashboard', 'description' => 'Access approval dashboard'],
            ['name' => 'approvals.approve_reject', 'display_name' => 'Approve or Reject Items', 'description' => 'Approve or reject submitted items'],
            ['name' => 'approvals.bulk_approve', 'display_name' => 'Bulk Approve Multiple Items', 'description' => 'Bulk approve multiple items'],
        ];

        // Create PDF Template Permissions
        $pdfTemplatePermissions = [
            ['name' => 'pdf_templates.view', 'display_name' => 'View PDF Templates', 'description' => 'View PDF form templates'],
            ['name' => 'pdf_templates.create', 'display_name' => 'Upload PDF Templates', 'description' => 'Upload new PDF form templates'],
            ['name' => 'pdf_templates.edit', 'display_name' => 'Edit PDF Templates', 'description' => 'Edit PDF template metadata and field mappings'],
            ['name' => 'pdf_templates.delete', 'display_name' => 'Delete PDF Templates', 'description' => 'Delete PDF form templates'],
            ['name' => 'pdf_templates.generate', 'display_name' => 'Generate PDFs from Templates', 'description' => 'Generate filled PDFs from templates'],
        ];

        $allPermissions = array_merge($approvalPermissions, $pdfTemplatePermissions);

        foreach ($allPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Create Project Manager role
        $projectManagerRole = Role::firstOrCreate(
            ['name' => 'project_manager'],
            [
                'display_name' => 'Project Manager',
                'description' => 'Manages housing projects with approval authority'
            ]
        );

        // Assign permissions to Project Manager role
        $projectManagerPermissions = Permission::whereIn('name', [
            // Approval permissions
            'properties.approve_documents',
            'contractors.approve_documents',
            'housing_jobs.verify_documents',
            'housing_jobs.approve_tasks',
            'approvals.view_dashboard',
            'approvals.approve_reject',
            'approvals.bulk_approve',

            // PDF Template permissions
            'pdf_templates.view',
            'pdf_templates.create',
            'pdf_templates.edit',
            'pdf_templates.generate',

            // Document viewing/managing permissions
            'properties.view',
            'properties.documents',
            'properties.notes',
            'contractors.view',
            'contractors.documents',
            'housing_jobs.view',
            'housing_jobs.update',
            'housing_jobs.contacts',
            'housing_jobs.documents',

            // Job management permissions
            'housing_jobs.approve',
            'housing.reports.view',
            'participants.view',
            'households.view',
        ])->get();

        foreach ($projectManagerPermissions as $permission) {
            if (!$projectManagerRole->permissions->contains($permission->id)) {
                $projectManagerRole->permissions()->attach($permission->id);
            }
        }

        // Update Admin role with all approval and PDF template permissions
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminApprovalPermissions = Permission::whereIn('name', [
                'properties.approve_documents',
                'contractors.approve_documents',
                'housing_jobs.approve_tasks',
                'approvals.view_dashboard',
                'approvals.approve_reject',
                'approvals.bulk_approve',
                'pdf_templates.view',
                'pdf_templates.create',
                'pdf_templates.edit',
                'pdf_templates.delete',
                'pdf_templates.generate',
            ])->get();

            foreach ($adminApprovalPermissions as $permission) {
                if (!$adminRole->permissions->contains($permission->id)) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }

        $this->command->info('Approval permissions and Project Manager role created successfully.');
    }
}
