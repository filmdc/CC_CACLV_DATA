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

        foreach ($approvalPermissions as $permissionData) {
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

        // Update Admin role with all approval permissions
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminApprovalPermissions = Permission::whereIn('name', [
                'properties.approve_documents',
                'contractors.approve_documents',
                'housing_jobs.approve_tasks',
                'approvals.view_dashboard',
                'approvals.approve_reject',
                'approvals.bulk_approve',
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
