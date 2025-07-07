<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReferenceDataSet;
use App\Models\ReferenceData;
use App\Models\Permission;
use App\Models\Role;

class HousingModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Reference Data Sets for Housing Module
        $propertyTypeSet = ReferenceDataSet::firstOrCreate(
            ['name' => 'Property Types'],
            [
                'name' => 'Property Types',
                'description' => 'Types of properties in the housing module',
                'version' => 'HOUSING_1.0',
                'is_active' => true,
                'is_draft' => false,
                'activated_at' => now()
            ]
        );

        $jobTypeSet = ReferenceDataSet::firstOrCreate(
            ['name' => 'Housing Job Types'],
            [
                'name' => 'Housing Job Types',
                'description' => 'Types of housing rehabilitation jobs',
                'version' => 'HOUSING_JOB_1.0',
                'is_active' => true,
                'is_draft' => false,
                'activated_at' => now()
            ]
        );

        // Property Types
        $propertyTypes = [
            ['value' => 'OWNER_OCCUPIED', 'label' => 'Owner Occupied', 'description' => 'Property owned and occupied by participant'],
            ['value' => 'RENTAL', 'label' => 'Rental Property', 'description' => 'Rental property occupied by participant'],
            ['value' => 'FACADE', 'label' => 'Facade', 'description' => 'Property facade improvements only'],
            ['value' => 'VACANT', 'label' => 'Vacant Property', 'description' => 'Currently vacant property'],
            ['value' => 'COMMERCIAL', 'label' => 'Commercial Property', 'description' => 'Commercial or mixed-use property'],
        ];

        foreach ($propertyTypes as $index => $propertyType) {
            ReferenceData::firstOrCreate(
                [
                    'reference_data_set_id' => $propertyTypeSet->id,
                    'category' => 'property_type',
                    'value' => $propertyType['value']
                ],
                array_merge($propertyType, [
                    'reference_data_set_id' => $propertyTypeSet->id,
                    'category' => 'property_type',
                    'active' => true,
                    'sort_order' => $index
                ])
            );
        }

        // Housing Job Types
        $jobTypes = [
            ['value' => 'EMERGENCY_REPAIR', 'label' => 'Emergency Repair', 'description' => 'Urgent repairs for health and safety'],
            ['value' => 'WEATHERIZATION', 'label' => 'Weatherization', 'description' => 'Energy efficiency improvements'],
            ['value' => 'ROOF_REPAIR', 'label' => 'Roof Repair/Replacement', 'description' => 'Roof repairs or full replacement'],
            ['value' => 'PLUMBING', 'label' => 'Plumbing', 'description' => 'Plumbing repairs or upgrades'],
            ['value' => 'ELECTRICAL', 'label' => 'Electrical', 'description' => 'Electrical repairs or upgrades'],
            ['value' => 'HVAC', 'label' => 'HVAC', 'description' => 'Heating, ventilation, and air conditioning'],
            ['value' => 'ACCESSIBILITY', 'label' => 'Accessibility Modifications', 'description' => 'ADA compliance and accessibility improvements'],
            ['value' => 'FOUNDATION', 'label' => 'Foundation Repair', 'description' => 'Foundation and structural repairs'],
            ['value' => 'WINDOWS_DOORS', 'label' => 'Windows and Doors', 'description' => 'Window and door replacement'],
            ['value' => 'SIDING', 'label' => 'Siding', 'description' => 'Exterior siding repair or replacement'],
            ['value' => 'INTERIOR', 'label' => 'Interior Repairs', 'description' => 'General interior improvements'],
            ['value' => 'LEAD_ABATEMENT', 'label' => 'Lead Abatement', 'description' => 'Lead paint remediation'],
            ['value' => 'MOLD_REMEDIATION', 'label' => 'Mold Remediation', 'description' => 'Mold removal and prevention'],
            ['value' => 'GENERAL_REHAB', 'label' => 'General Rehabilitation', 'description' => 'Comprehensive property rehabilitation'],
        ];

        foreach ($jobTypes as $index => $jobType) {
            ReferenceData::firstOrCreate(
                [
                    'reference_data_set_id' => $jobTypeSet->id,
                    'category' => 'housing_job_type',
                    'value' => $jobType['value']
                ],
                array_merge($jobType, [
                    'reference_data_set_id' => $jobTypeSet->id,
                    'category' => 'housing_job_type',
                    'active' => true,
                    'sort_order' => $index
                ])
            );
        }

        // Create Contractor Service Types
        $contractorServiceSet = ReferenceDataSet::firstOrCreate(
            ['name' => 'Contractor Service Types'],
            [
                'name' => 'Contractor Service Types',
                'description' => 'Types of services offered by contractors',
                'version' => 'CONTR_SVC_1.0',
                'is_active' => true,
                'is_draft' => false,
                'activated_at' => now()
            ]
        );

        $contractorServices = [
            ['value' => 'GENERAL_CONTRACTOR', 'label' => 'General Contractor', 'description' => 'General construction services'],
            ['value' => 'ROOFING', 'label' => 'Roofing', 'description' => 'Roof repair and replacement'],
            ['value' => 'PLUMBING', 'label' => 'Plumbing', 'description' => 'Plumbing services'],
            ['value' => 'ELECTRICAL', 'label' => 'Electrical', 'description' => 'Electrical services'],
            ['value' => 'HVAC', 'label' => 'HVAC', 'description' => 'Heating, ventilation, and air conditioning'],
            ['value' => 'CARPENTRY', 'label' => 'Carpentry', 'description' => 'Carpentry and woodwork'],
            ['value' => 'PAINTING', 'label' => 'Painting', 'description' => 'Interior and exterior painting'],
            ['value' => 'FLOORING', 'label' => 'Flooring', 'description' => 'Flooring installation and repair'],
            ['value' => 'SIDING', 'label' => 'Siding', 'description' => 'Siding installation and repair'],
            ['value' => 'WINDOWS_DOORS', 'label' => 'Windows & Doors', 'description' => 'Window and door installation'],
            ['value' => 'FOUNDATION', 'label' => 'Foundation', 'description' => 'Foundation repair'],
            ['value' => 'LANDSCAPING', 'label' => 'Landscaping', 'description' => 'Landscaping services'],
            ['value' => 'DEMOLITION', 'label' => 'Demolition', 'description' => 'Demolition services'],
            ['value' => 'LEAD_ABATEMENT', 'label' => 'Lead Abatement', 'description' => 'Lead paint remediation'],
            ['value' => 'MOLD_REMEDIATION', 'label' => 'Mold Remediation', 'description' => 'Mold removal services'],
        ];

        foreach ($contractorServices as $index => $service) {
            ReferenceData::firstOrCreate(
                [
                    'reference_data_set_id' => $contractorServiceSet->id,
                    'category' => 'contractor_service',
                    'value' => $service['value']
                ],
                array_merge($service, [
                    'reference_data_set_id' => $contractorServiceSet->id,
                    'category' => 'contractor_service',
                    'active' => true,
                    'sort_order' => $index
                ])
            );
        }

        // Create Contact Types
        $contactTypeSet = ReferenceDataSet::firstOrCreate(
            ['name' => 'Contact Types'],
            [
                'name' => 'Contact Types',
                'description' => 'Types of contact for job tracking',
                'version' => 'CONTACT_TYPE_1.0',
                'is_active' => true,
                'is_draft' => false,
                'activated_at' => now()
            ]
        );

        $contactTypes = [
            ['value' => 'PHONE_CALL', 'label' => 'Phone Call', 'description' => 'Phone call contact'],
            ['value' => 'EMAIL', 'label' => 'Email', 'description' => 'Email communication'],
            ['value' => 'IN_PERSON', 'label' => 'In Person', 'description' => 'In-person meeting'],
            ['value' => 'SITE_VISIT', 'label' => 'Site Visit', 'description' => 'Property site visit'],
            ['value' => 'INSPECTION', 'label' => 'Inspection', 'description' => 'Property inspection'],
            ['value' => 'SYSTEM', 'label' => 'System', 'description' => 'System generated entry'],
        ];

        foreach ($contactTypes as $index => $contactType) {
            ReferenceData::firstOrCreate(
                [
                    'reference_data_set_id' => $contactTypeSet->id,
                    'category' => 'contact_type',
                    'value' => $contactType['value']
                ],
                array_merge($contactType, [
                    'reference_data_set_id' => $contactTypeSet->id,
                    'category' => 'contact_type',
                    'active' => true,
                    'sort_order' => $index
                ])
            );
        }

        // Create Housing Module Permissions
        $housingPermissions = [
            // Properties
            ['name' => 'properties.view', 'display_name' => 'View Properties', 'description' => 'View property records'],
            ['name' => 'properties.create', 'display_name' => 'Create Properties', 'description' => 'Create new property records'],
            ['name' => 'properties.update', 'display_name' => 'Update Properties', 'description' => 'Update existing property records'],
            ['name' => 'properties.delete', 'display_name' => 'Delete Properties', 'description' => 'Delete property records'],
            ['name' => 'properties.documents', 'display_name' => 'Manage Property Documents', 'description' => 'Upload and manage property documents'],
            ['name' => 'properties.notes', 'display_name' => 'Manage Property Notes', 'description' => 'Add and manage property notes'],
            
            // Contractors
            ['name' => 'contractors.view', 'display_name' => 'View Contractors', 'description' => 'View contractor records'],
            ['name' => 'contractors.create', 'display_name' => 'Create Contractors', 'description' => 'Create new contractor records'],
            ['name' => 'contractors.update', 'display_name' => 'Update Contractors', 'description' => 'Update existing contractor records'],
            ['name' => 'contractors.delete', 'display_name' => 'Delete Contractors', 'description' => 'Delete contractor records'],
            ['name' => 'contractors.documents', 'display_name' => 'Manage Contractor Documents', 'description' => 'Upload and manage contractor documents'],
            
            // Jobs
            ['name' => 'housing_jobs.view', 'display_name' => 'View Housing Jobs', 'description' => 'View housing job records'],
            ['name' => 'housing_jobs.create', 'display_name' => 'Create Housing Jobs', 'description' => 'Create new housing job records'],
            ['name' => 'housing_jobs.update', 'display_name' => 'Update Housing Jobs', 'description' => 'Update existing housing job records'],
            ['name' => 'housing_jobs.delete', 'display_name' => 'Delete Housing Jobs', 'description' => 'Delete housing job records'],
            ['name' => 'housing_jobs.approve', 'display_name' => 'Approve Housing Jobs', 'description' => 'Approve housing jobs for work'],
            ['name' => 'housing_jobs.contacts', 'display_name' => 'Manage Job Contacts', 'description' => 'Add and manage job contact ledger entries'],
            ['name' => 'housing_jobs.documents', 'display_name' => 'Manage Job Documents', 'description' => 'Upload and manage job document checklist'],
            ['name' => 'housing_jobs.verify_documents', 'display_name' => 'Verify Job Documents', 'description' => 'Verify uploaded job documents'],
            
            // Reports
            ['name' => 'housing.reports.view', 'display_name' => 'View Housing Reports', 'description' => 'View housing module reports'],
            ['name' => 'housing.reports.export', 'display_name' => 'Export Housing Reports', 'description' => 'Export housing module reports'],
            
            // Module Management
            ['name' => 'housing.module.manage', 'display_name' => 'Manage Housing Module', 'description' => 'Enable/disable housing module for programs'],
        ];

        foreach ($housingPermissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Assign all housing permissions to Admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $housingPermissionModels = Permission::whereIn('name', [
                'properties.view', 'properties.create', 'properties.update', 'properties.delete', 'properties.documents', 'properties.notes',
                'contractors.view', 'contractors.create', 'contractors.update', 'contractors.delete', 'contractors.documents',
                'housing_jobs.view', 'housing_jobs.create', 'housing_jobs.update', 'housing_jobs.delete', 'housing_jobs.approve', 
                'housing_jobs.contacts', 'housing_jobs.documents', 'housing_jobs.verify_documents',
                'housing.reports.view', 'housing.reports.export', 'housing.module.manage'
            ])->get();
            foreach ($housingPermissionModels as $permission) {
                if (!$adminRole->permissions->contains($permission->id)) {
                    $adminRole->permissions()->attach($permission->id);
                }
            }
        }

        // Create Housing Coordinator role with specific permissions
        $housingCoordinatorRole = Role::firstOrCreate(
            ['name' => 'housing_coordinator'],
            [
                'display_name' => 'Housing Coordinator',
                'description' => 'Manages housing rehabilitation programs and jobs'
            ]
        );

        $coordinatorPermissions = Permission::whereIn('name', [
            'properties.view',
            'properties.create',
            'properties.update',
            'properties.documents',
            'properties.notes',
            'contractors.view',
            'contractors.create',
            'contractors.update',
            'contractors.documents',
            'housing_jobs.view',
            'housing_jobs.create',
            'housing_jobs.update',
            'housing_jobs.contacts',
            'housing_jobs.documents',
            'housing.reports.view',
            'participants.view',
            'households.view',
        ])->get();

        foreach ($coordinatorPermissions as $permission) {
            if (!$housingCoordinatorRole->permissions->contains($permission->id)) {
                $housingCoordinatorRole->permissions()->attach($permission->id);
            }
        }

        // Create Housing Manager role with additional permissions
        $housingManagerRole = Role::firstOrCreate(
            ['name' => 'housing_manager'],
            [
                'display_name' => 'Housing Manager',
                'description' => 'Supervises housing rehabilitation programs with approval authority'
            ]
        );

        $managerPermissions = Permission::whereIn('name', [
            'properties.view', 'properties.create', 'properties.update', 'properties.delete', 'properties.documents', 'properties.notes',
            'contractors.view', 'contractors.create', 'contractors.update', 'contractors.delete', 'contractors.documents',
            'housing_jobs.view', 'housing_jobs.create', 'housing_jobs.update', 'housing_jobs.delete', 'housing_jobs.approve', 
            'housing_jobs.contacts', 'housing_jobs.documents', 'housing_jobs.verify_documents',
            'housing.reports.view', 'housing.reports.export', 'housing.module.manage',
            'participants.view', 'households.view'
        ])->get();

        foreach ($managerPermissions as $permission) {
            if (!$housingManagerRole->permissions->contains($permission->id)) {
                $housingManagerRole->permissions()->attach($permission->id);
            }
        }

        $this->command->info('Housing module reference data and permissions seeded successfully.');
    }
}
