<?php

namespace Database\Seeders;

use App\Models\ReferenceData;
use App\Models\ReferenceDataSet;
use Illuminate\Database\Seeder;

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
                'activated_at' => now(),
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
                'activated_at' => now(),
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
                    'value' => $propertyType['value'],
                ],
                array_merge($propertyType, [
                    'reference_data_set_id' => $propertyTypeSet->id,
                    'category' => 'property_type',
                    'active' => true,
                    'sort_order' => $index,
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
                    'value' => $jobType['value'],
                ],
                array_merge($jobType, [
                    'reference_data_set_id' => $jobTypeSet->id,
                    'category' => 'housing_job_type',
                    'active' => true,
                    'sort_order' => $index,
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
                'activated_at' => now(),
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
                    'value' => $service['value'],
                ],
                array_merge($service, [
                    'reference_data_set_id' => $contractorServiceSet->id,
                    'category' => 'contractor_service',
                    'active' => true,
                    'sort_order' => $index,
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
                'activated_at' => now(),
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
                    'value' => $contactType['value'],
                ],
                array_merge($contactType, [
                    'reference_data_set_id' => $contactTypeSet->id,
                    'category' => 'contact_type',
                    'active' => true,
                    'sort_order' => $index,
                ])
            );
        }

        // Note: Permissions and roles are now managed by ProjectHousingPermissionsSeeder
        // This seeder only handles reference data (property types, job types, contractor services, contact types)

        $this->command->info('Housing module reference data seeded successfully.');
    }
}
