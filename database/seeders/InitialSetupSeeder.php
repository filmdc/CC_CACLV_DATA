<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Household;
use App\Models\Participant;
use App\Models\Permission;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceEnrollment;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WorkPlan;
use App\Models\WorkPlanGoal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InitialSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks for SQLite
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        // Clear existing data
        DB::table('service_enrollments')->delete();
        DB::table('program_enrollments')->delete();
        DB::table('work_plan_goals')->delete();
        DB::table('work_plans')->delete();
        DB::table('participants')->delete();
        DB::table('households')->delete();
        DB::table('services')->delete();
        DB::table('contracts')->delete();
        DB::table('programs')->delete();
        DB::table('user_role')->delete();
        DB::table('role_permission')->delete();
        DB::table('permissions')->delete();
        DB::table('roles')->delete();
        DB::table('site_settings')->delete();
        DB::table('users')->delete();

        // Seed Users
        $users = [
            [
                'id' => 1,
                'name' => 'David Chevalier',
                'email' => 'filmdc@gmail.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'David Chevalier',
                'email' => 'dchevalier@caclv.org',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        // Seed Roles
        $roles = [
            [
                'id' => 1,
                'name' => 'Administrator',
                'display_name' => 'Administrator',
                'description' => 'Admin for the entire application',
            ],
            [
                'id' => 2,
                'name' => 'program-staff',
                'display_name' => 'Program Staff',
                'description' => 'Program staff member with limited access',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }

        // Seed all permissions
        $this->seedPermissions();

        // Assign permissions to Administrator role (all permissions)
        $adminRole = Role::find(1);
        $adminRole->permissions()->sync(Permission::pluck('id'));

        // Assign specific permissions to program-staff role
        $staffRole = Role::find(2);
        $staffPermissions = Permission::whereIn('name', [
            'participants.view',
            'participants.create',
            'participants.edit',
            'participants.export',
            'participants.import',
            'households.view',
            'households.create',
            'households.edit',
            'programs.view',
            'services.view',
            'enrollments.view',
            'enrollments.create',
            'enrollments.edit',
        ])->pluck('id');
        $staffRole->permissions()->sync($staffPermissions);

        // Assign roles to users
        User::find(1)->roles()->sync([2]); // filmdc@gmail.com -> program-staff
        User::find(2)->roles()->sync([1]); // dchevalier@caclv.org -> Administrator

        // Seed Programs
        $programs = [
            [
                'id' => 1,
                'name' => 'Housing Counseling',
                'description' => 'Provides counseling services to help individuals and families secure and maintain housing.',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Housing Rehab Program',
                'description' => 'Offers rehabilitation services to improve housing conditions for low-income families.',
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'active' => true,
            ],
        ];

        foreach ($programs as $programData) {
            Program::create($programData);
        }

        // Seed Services
        Service::create([
            'id' => 1,
            'program_id' => 1,
            'name' => 'First-Time Homebuyer Education',
            'description' => 'Educational workshops for first-time homebuyers covering financing, home selection, and maintenance.',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);

        // Seed Contracts
        Contract::create([
            'id' => 1,
            'title' => 'CSBG 2025 Contract',
            'contract_number' => 'CSBG-2025-001',
            'cfda_number' => '93.569',
            'purpose' => 'Community Services Block Grant funding for comprehensive anti-poverty services',
            'program_id' => 1,
            'funder' => 'Federal - CSBG',
            'amount' => 500000.00,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'fund_code' => 'CSBG-2025',
        ]);

        // Seed Households
        $households = [
            [
                'id' => 1,
                'household_code' => 'CC6F7A0',
                'household_type' => 'Two Parent Household',
                'household_size' => 3,
                'housing_status' => 'Rent',
                'monthly_income' => 2500.00,
                'annual_income' => 30000.00,
                'street_address' => '123 Main St',
                'city' => 'Las Vegas',
                'state' => 'NV',
                'zip_code' => '89101',
            ],
            [
                'id' => 2,
                'household_code' => 'A30F8B1',
                'household_type' => 'Single Parent Female',
                'household_size' => 2,
                'housing_status' => 'Rent',
                'monthly_income' => 1800.00,
                'annual_income' => 21600.00,
                'street_address' => '456 Oak Ave',
                'city' => 'Las Vegas',
                'state' => 'NV',
                'zip_code' => '89102',
            ],
            [
                'id' => 3,
                'household_code' => '2E5D9C2',
                'household_type' => 'Two Parent Household',
                'household_size' => 4,
                'housing_status' => 'Own',
                'monthly_income' => 3200.00,
                'annual_income' => 38400.00,
                'street_address' => '789 Pine Blvd',
                'city' => 'Henderson',
                'state' => 'NV',
                'zip_code' => '89015',
            ],
            [
                'id' => 4,
                'household_code' => '9A7C2D3',
                'household_type' => 'Single Person',
                'household_size' => 1,
                'housing_status' => 'Rent',
                'monthly_income' => 1500.00,
                'annual_income' => 18000.00,
                'street_address' => '321 Elm St',
                'city' => 'North Las Vegas',
                'state' => 'NV',
                'zip_code' => '89030',
            ],
            [
                'id' => 5,
                'household_code' => 'F1B3E64',
                'household_type' => 'Single Parent Female',
                'household_size' => 5,
                'housing_status' => 'Rent',
                'monthly_income' => 2800.00,
                'annual_income' => 33600.00,
                'street_address' => '654 Maple Dr',
                'city' => 'Las Vegas',
                'state' => 'NV',
                'zip_code' => '89103',
            ],
        ];

        foreach ($households as $householdData) {
            Household::create($householdData);
        }

        // Seed Participants
        $participants = [
            [
                'id' => 1,
                'household_id' => 1,
                'is_head_of_household' => true,
                'first_name' => 'John',
                'last_name' => 'Smith',
                'date_of_birth' => '1985-03-15',
                'gender' => 'Male',
                'telephone' => '(702) 555-0101',
                'email' => 'john.smith@example.com',
                'education_level' => 'Bachelor degree',
                'disability_status' => 'No',
                'health_insurance_status' => 'Yes',
                'ethnicity' => 'Not Hispanic or Latino',
                'race' => json_encode(['White']),
                'military_status' => 'Not applicable',
                'work_status' => 'Employed full-time',
                'employment_income' => 2500.00,
            ],
            [
                'id' => 2,
                'household_id' => 2,
                'is_head_of_household' => true,
                'first_name' => 'Maria',
                'last_name' => 'Garcia',
                'date_of_birth' => '1990-07-22',
                'gender' => 'Female',
                'telephone' => '(702) 555-0202',
                'email' => 'maria.garcia@example.com',
                'education_level' => 'High school diploma/GED',
                'disability_status' => 'No',
                'health_insurance_status' => 'Yes',
                'ethnicity' => 'Hispanic or Latino',
                'race' => json_encode(['White']),
                'military_status' => 'Not applicable',
                'work_status' => 'Employed part-time',
                'employment_income' => 1800.00,
            ],
            [
                'id' => 3,
                'household_id' => 3,
                'is_head_of_household' => true,
                'first_name' => 'Michael',
                'last_name' => 'Johnson',
                'date_of_birth' => '1978-11-10',
                'gender' => 'Male',
                'telephone' => '(702) 555-0303',
                'email' => 'michael.johnson@example.com',
                'education_level' => 'Associate degree',
                'disability_status' => 'No',
                'health_insurance_status' => 'Yes',
                'ethnicity' => 'Not Hispanic or Latino',
                'race' => json_encode(['Black or African American']),
                'military_status' => 'Veteran',
                'work_status' => 'Employed full-time',
                'employment_income' => 3200.00,
            ],
            [
                'id' => 4,
                'household_id' => 4,
                'is_head_of_household' => true,
                'first_name' => 'Susan',
                'last_name' => 'Williams',
                'date_of_birth' => '1955-05-30',
                'gender' => 'Female',
                'telephone' => '(702) 555-0404',
                'email' => 'susan.williams@example.com',
                'education_level' => 'High school diploma/GED',
                'disability_status' => 'Yes',
                'health_insurance_status' => 'Yes',
                'ethnicity' => 'Not Hispanic or Latino',
                'race' => json_encode(['White']),
                'military_status' => 'Not applicable',
                'work_status' => 'Not in labor force',
                'employment_income' => 0.00,
            ],
            [
                'id' => 5,
                'household_id' => 5,
                'is_head_of_household' => true,
                'first_name' => 'Jennifer',
                'last_name' => 'Brown',
                'date_of_birth' => '1992-09-18',
                'gender' => 'Female',
                'telephone' => '(702) 555-0505',
                'email' => 'jennifer.brown@example.com',
                'education_level' => 'Some college',
                'disability_status' => 'No',
                'health_insurance_status' => 'Yes',
                'ethnicity' => 'Not Hispanic or Latino',
                'race' => json_encode(['Asian']),
                'military_status' => 'Not applicable',
                'work_status' => 'Employed full-time',
                'employment_income' => 2000.00,
            ],
        ];

        foreach ($participants as $participantData) {
            Participant::create($participantData);
        }

        // Seed Work Plans
        WorkPlan::create([
            'id' => 1,
            'name' => '2025 Housing Counseling Work Plan',
            'program_id' => 1,
            'year' => 2025,
            'description' => 'Provide comprehensive housing counseling services to 500 families, with focus on first-time homebuyer education and foreclosure prevention.',
            'active' => true,
        ]);

        // Seed Work Plan Goals
        WorkPlanGoal::create([
            'id' => 1,
            'work_plan_id' => 1,
            'service_id' => 1,
            'goal_name' => 'First-Time Homebuyer Education Workshops',
            'description' => 'Conduct 12 first-time homebuyer education workshops serving 240 participants',
            'target_number' => 240,
            'actual_number' => 45,
        ]);

        // Seed Site Settings
        $siteSettings = [
            ['key' => 'site_name', 'value' => 'CAP TRACK'],
            ['key' => 'site_logo', 'value' => ''],
            ['key' => 'site_favicon', 'value' => ''],
        ];

        foreach ($siteSettings as $setting) {
            SiteSetting::create($setting);
        }

        // Seed Program Enrollments
        $programEnrollments = [
            [
                'id' => 1,
                'participant_id' => 1,
                'program_id' => 1,
                'enrollment_date' => '2025-06-01',
                'status' => 'active',
            ],
            [
                'id' => 2,
                'participant_id' => 2,
                'program_id' => 1,
                'enrollment_date' => '2025-06-01',
                'status' => 'active',
            ],
            [
                'id' => 3,
                'participant_id' => 3,
                'program_id' => 2,
                'enrollment_date' => '2025-06-01',
                'status' => 'active',
            ],
        ];

        foreach ($programEnrollments as $enrollment) {
            ProgramEnrollment::create($enrollment);
        }

        // Seed Service Enrollments
        $serviceEnrollments = [
            [
                'participant_id' => 1,
                'service_id' => 1,
                'program_enrollment_id' => 1,
                'enrollment_date' => '2025-06-01',
                'status' => 'in_progress',
            ],
            [
                'participant_id' => 2,
                'service_id' => 1,
                'program_enrollment_id' => 2,
                'enrollment_date' => '2025-06-01',
                'status' => 'in_progress',
            ],
        ];

        foreach ($serviceEnrollments as $enrollment) {
            ServiceEnrollment::create($enrollment);
        }

        // Re-enable foreign key checks
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->command->info('Initial setup seeding completed successfully!');
    }

    /**
     * Seed all permissions
     */
    private function seedPermissions(): void
    {
        $permissions = [
            // Access permissions
            ['name' => 'access-admin-panel', 'display_name' => 'Access Admin Panel', 'category' => 'Access'],

            // Read Only permissions
            ['name' => 'read-all', 'display_name' => 'Read All', 'category' => 'Read Only'],

            // Edit permissions
            ['name' => 'edit-all', 'display_name' => 'Edit All', 'category' => 'Edit'],
            ['name' => 'edit-participants', 'display_name' => 'Edit Participants', 'category' => 'Participants'],

            // Participant permissions
            ['name' => 'participants.view', 'display_name' => 'View Participants', 'category' => 'Participants'],
            ['name' => 'participants.create', 'display_name' => 'Create Participants', 'category' => 'Participants'],
            ['name' => 'participants.edit', 'display_name' => 'Edit Participants', 'category' => 'Participants'],
            ['name' => 'participants.delete', 'display_name' => 'Delete Participants', 'category' => 'Participants'],
            ['name' => 'participants.merge', 'display_name' => 'Merge Participants', 'category' => 'Participants'],
            ['name' => 'participants.export', 'display_name' => 'Export Participants', 'category' => 'Participants'],
            ['name' => 'participants.import', 'display_name' => 'Import Participants', 'category' => 'Participants'],

            // Household permissions
            ['name' => 'households.view', 'display_name' => 'View Households', 'category' => 'Households'],
            ['name' => 'households.create', 'display_name' => 'Create Households', 'category' => 'Households'],
            ['name' => 'households.edit', 'display_name' => 'Edit Households', 'category' => 'Households'],
            ['name' => 'households.delete', 'display_name' => 'Delete Households', 'category' => 'Households'],

            // Program permissions
            ['name' => 'programs.view', 'display_name' => 'View Programs', 'category' => 'Programs'],
            ['name' => 'programs.create', 'display_name' => 'Create Programs', 'category' => 'Programs'],
            ['name' => 'programs.edit', 'display_name' => 'Edit Programs', 'category' => 'Programs'],
            ['name' => 'programs.delete', 'display_name' => 'Delete Programs', 'category' => 'Programs'],

            // Service permissions
            ['name' => 'services.view', 'display_name' => 'View Services', 'category' => 'Services'],
            ['name' => 'services.create', 'display_name' => 'Create Services', 'category' => 'Services'],
            ['name' => 'services.edit', 'display_name' => 'Edit Services', 'category' => 'Services'],
            ['name' => 'services.delete', 'display_name' => 'Delete Services', 'category' => 'Services'],

            // Contract permissions
            ['name' => 'contracts.view', 'display_name' => 'View Contracts', 'category' => 'Contracts'],
            ['name' => 'contracts.create', 'display_name' => 'Create Contracts', 'category' => 'Contracts'],
            ['name' => 'contracts.edit', 'display_name' => 'Edit Contracts', 'category' => 'Contracts'],
            ['name' => 'contracts.delete', 'display_name' => 'Delete Contracts', 'category' => 'Contracts'],
            ['name' => 'contracts.manage_notifications', 'display_name' => 'Manage Contract Notification Recipients', 'category' => 'Contracts'],

            // Work Plan permissions
            ['name' => 'work_plans.view', 'display_name' => 'View Work Plans', 'category' => 'Work Plans'],
            ['name' => 'work_plans.create', 'display_name' => 'Create Work Plans', 'category' => 'Work Plans'],
            ['name' => 'work_plans.edit', 'display_name' => 'Edit Work Plans', 'category' => 'Work Plans'],
            ['name' => 'work_plans.delete', 'display_name' => 'Delete Work Plans', 'category' => 'Work Plans'],

            // Work Plan Goal permissions
            ['name' => 'work_plan_goals.view', 'display_name' => 'View Work Plan Goals', 'category' => 'Work Plan Goals'],
            ['name' => 'work_plan_goals.create', 'display_name' => 'Create Work Plan Goals', 'category' => 'Work Plan Goals'],
            ['name' => 'work_plan_goals.edit', 'display_name' => 'Edit Work Plan Goals', 'category' => 'Work Plan Goals'],
            ['name' => 'work_plan_goals.delete', 'display_name' => 'Delete Work Plan Goals', 'category' => 'Work Plan Goals'],

            // Enrollment permissions
            ['name' => 'enrollments.view', 'display_name' => 'View Enrollments', 'category' => 'Enrollments'],
            ['name' => 'enrollments.create', 'display_name' => 'Create Enrollments', 'category' => 'Enrollments'],
            ['name' => 'enrollments.edit', 'display_name' => 'Edit Enrollments', 'category' => 'Enrollments'],
            ['name' => 'enrollments.delete', 'display_name' => 'Delete Enrollments', 'category' => 'Enrollments'],

            // Poverty Guideline permissions
            ['name' => 'poverty-guidelines.view', 'display_name' => 'View Poverty Guidelines', 'category' => 'Poverty Guidelines'],
            ['name' => 'poverty-guidelines.create', 'display_name' => 'Create Poverty Guidelines', 'category' => 'Poverty Guidelines'],
            ['name' => 'poverty-guidelines.edit', 'display_name' => 'Edit Poverty Guidelines', 'category' => 'Poverty Guidelines'],
            ['name' => 'poverty-guidelines.delete', 'display_name' => 'Delete Poverty Guidelines', 'category' => 'Poverty Guidelines'],

            // Reference Data permissions
            ['name' => 'reference-data.view', 'display_name' => 'View Reference Data', 'category' => 'Reference Data'],
            ['name' => 'reference-data.create', 'display_name' => 'Create Reference Data', 'category' => 'Reference Data'],
            ['name' => 'reference-data.edit', 'display_name' => 'Edit Reference Data', 'category' => 'Reference Data'],
            ['name' => 'reference-data.delete', 'display_name' => 'Delete Reference Data', 'category' => 'Reference Data'],

            // User Management permissions
            ['name' => 'users.view', 'display_name' => 'View Users', 'category' => 'User Management'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'category' => 'User Management'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'category' => 'User Management'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'category' => 'User Management'],

            // Role permissions
            ['name' => 'roles.view', 'display_name' => 'View Roles', 'category' => 'User Management'],
            ['name' => 'roles.create', 'display_name' => 'Create Roles', 'category' => 'User Management'],
            ['name' => 'roles.edit', 'display_name' => 'Edit Roles', 'category' => 'User Management'],
            ['name' => 'roles.delete', 'display_name' => 'Delete Roles', 'category' => 'User Management'],

            // Site Settings permissions
            ['name' => 'site-settings.view', 'display_name' => 'View Site Settings', 'category' => 'Site Settings'],
            ['name' => 'site-settings.edit', 'display_name' => 'Edit Site Settings', 'category' => 'Site Settings'],

            // DonorPerfect / Accounting & Fiscal permissions
            ['name' => 'donor_gifts.view', 'display_name' => 'View DonorPerfect Gifts', 'description' => 'View the DonorPerfect gifts feed and data', 'category' => 'Accounting & Fiscal'],
            ['name' => 'donor_gifts.configure', 'display_name' => 'Configure DonorPerfect API', 'description' => 'Configure DonorPerfect API settings and field mappings', 'category' => 'Accounting & Fiscal'],
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'description' => $permission['description'] ?? '',
                'category' => $permission['category'],
            ]);
        }
    }
}
