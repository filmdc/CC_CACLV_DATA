<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomImportTemplate;
use App\Models\User;

class CustomImportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Get admin user for created_by field
        $adminUser = User::where('email', 'admin@caclv.org')->first();
        $createdBy = $adminUser ? $adminUser->id : 1;

        // HMIS Pennsylvania Template
        $hmisTemplate = CustomImportTemplate::firstOrCreate(
            ['source_system' => 'HMIS_Pennsylvania'],
            [
                'name' => 'HMIS Pennsylvania ClientTrack - Active Client List',
                'description' => 'Pre-configured template for importing HMIS Active Client List exports from Pennsylvania ClientTrack system. Supports Sixth Street Shelter program enrollments with automatic data transformation for names, dates, race/ethnicity, and other demographic fields.',
                'type' => 'preconfigured',
                'source_system' => 'HMIS_Pennsylvania',
                'active' => true,
                'field_mappings' => [
                    'OrganizationName' => null,
                    'CaseID' => 'household_code',
                    'ClientName' => 'full_name',
                    'DOB' => 'date_of_birth',
                    'Gender' => 'gender',
                    'RaceAndEthnicity' => 'race_ethnicity',
                    'VeteranStatus' => 'military_status',
                    'Relationship' => 'relationship',
                    'HouseholdType' => 'household_type',
                    'HealthInsurance' => 'health_insurance_status',
                    'DisablingCondition' => 'disability_status',
                    'CashIncome' => 'monthly_income',
                    'EnrollDate' => 'enrollment_date',
                    'ExitDate' => 'exit_date',
                    'ClientID' => 'client_id',
                    'EnrollID' => 'enroll_id',
                ],
                'transformation_rules' => [
                    'date_of_birth' => ['format' => 'm/d/Y'],
                    'enrollment_date' => ['format' => 'm/d/Y H:i:s A'],
                    'exit_date' => ['format' => 'm/d/Y H:i:s A'],
                    'gender' => ['type' => 'predefined_mapping'],
                    'military_status' => ['type' => 'predefined_mapping'],
                    'disability_status' => ['type' => 'predefined_mapping'],
                    'health_insurance_status' => ['type' => 'predefined_mapping'],
                    'household_type' => ['type' => 'predefined_mapping'],
                    'full_name' => ['type' => 'name_parsing', 'format' => 'last_first_middle'],
                    'race_ethnicity' => ['type' => 'race_ethnicity_parsing'],
                    'relationship' => ['type' => 'head_of_household_conversion'],
                ],
                'validation_rules' => [
                    'household_code' => ['required'],
                    'full_name' => ['required'],
                    'date_of_birth' => ['required', 'date'],
                    'gender' => ['required'],
                ],
                'import_settings' => [
                    'default_program_id' => 4, // Sixth Street Shelter
                    'auto_enroll_in_program' => true,
                    'update_existing_participants' => true,
                    'skip_duplicate_enrollments' => true,
                ],
                'created_by' => $createdBy,
            ]
        );

        $this->command->info('HMIS Pennsylvania template created/updated: ' . $hmisTemplate->name);
    }
}