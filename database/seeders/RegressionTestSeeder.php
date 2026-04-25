<?php

namespace Database\Seeders;

use App\Models\CommunicationLog;
use App\Models\Contract;
use App\Models\Contractor;
use App\Models\FundCode;
use App\Models\Household;
use App\Models\HousingJob;
use App\Models\Participant;
use App\Models\Program;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use App\Models\ProjectMilestone;
use App\Models\Property;
use Database\Seeders\CommunicationsLogPermissionsSeeder;
use Database\Seeders\FundCodePermissionsSeeder;
use Database\Seeders\ProjectHousingPermissionsSeeder;
use Illuminate\Database\Seeder;

class RegressionTestSeeder extends Seeder
{
    public function run(): void
    {
        // Permission rows the housing module's components check at mount().
        // Idempotent: each of these seeders uses firstOrCreate / updateOrCreate.
        $this->call([
            CommunicationsLogPermissionsSeeder::class,
            FundCodePermissionsSeeder::class,
            ProjectHousingPermissionsSeeder::class,
        ]);

        $program = Program::factory()->create([
            'name' => 'Fixture Housing Program',
            'description' => 'Deterministic seed program for P-33 regression harness.',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'active' => true,
        ]);

        $contractA = Contract::factory()->create([
            'title' => 'Fixture Contract A — Housing Rehab',
            'program_id' => $program->id,
            'contract_number' => 'FIX-A-0001',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'amount' => 250000.00,
        ]);

        $contractB = Contract::factory()->create([
            'title' => 'Fixture Contract B — Emergency Repair',
            'program_id' => $program->id,
            'contract_number' => 'FIX-B-0002',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'amount' => 75000.00,
        ]);

        $fcA1 = FundCode::factory()->create([
            'code' => 'FC-FIX-001',
            'description' => 'Fixture Fund — Roofing (Contract A)',
            'contract_id' => $contractA->id,
            'is_active' => true,
        ]);

        $fcA2 = FundCode::factory()->create([
            'code' => 'FC-FIX-002',
            'description' => 'Fixture Fund — Plumbing (Contract A)',
            'contract_id' => $contractA->id,
            'is_active' => true,
        ]);

        $fcStandalone = FundCode::factory()->create([
            'code' => 'FC-FIX-003',
            'description' => 'Fixture Fund — Standalone (no contract)',
            'contract_id' => null,
            'is_active' => true,
        ]);

        $contractor1 = Contractor::factory()->create([
            'company_name' => 'Acme Fixture Construction',
            'contact_first_name' => 'Pat',
            'contact_last_name' => 'Fixture',
            'email' => 'pat@acme.fixture.test',
            'phone' => '555-555-0100',
            'license_number' => 'PA-FIX-10001',
            'insurance_policy_number' => 'POL-FIX-100001',
        ]);

        $contractor2 = Contractor::factory()->create([
            'company_name' => 'Beta Fixture Roofing',
            'contact_first_name' => 'Jamie',
            'contact_last_name' => 'Fixture',
            'email' => 'jamie@beta.fixture.test',
            'phone' => '555-555-0101',
            'license_number' => 'PA-FIX-10002',
            'insurance_policy_number' => 'POL-FIX-100002',
        ]);

        $contractor3 = Contractor::factory()->create([
            'company_name' => 'Gamma Fixture Plumbing',
            'contact_first_name' => 'Sam',
            'contact_last_name' => 'Fixture',
            'email' => 'sam@gamma.fixture.test',
            'phone' => '555-555-0102',
            'license_number' => 'PA-FIX-10003',
            'insurance_policy_number' => 'POL-FIX-100003',
        ]);

        $names = [
            'Ada', 'Brad', 'Carla', 'Dan', 'Eve', 'Frank',
            'Gina', 'Hank', 'Iris', 'Joe', 'Kim', 'Leo',
        ];

        $households = [];
        $participants = [];
        $properties = [];

        foreach ($names as $i => $first) {
            $idx = $i + 1;
            $households[$idx] = Household::factory()->create([
                'household_code' => 'FIX' . str_pad((string) $idx, 4, '0', STR_PAD_LEFT),
                'household_type' => 'Single Person',
                'household_size' => 1,
                'housing_status' => 'Own',
                'monthly_income' => 1200 + ($idx * 50),
                'annual_income' => 14400 + ($idx * 600),
                'hhs_poverty_percentage' => 95 + $idx,
            ]);

            $participants[$idx] = Participant::factory()->headOfHousehold()->create([
                'household_id' => $households[$idx]->id,
                'first_name' => $first,
                'last_name' => 'Fixture',
                'date_of_birth' => '1965-01-' . str_pad((string) $idx, 2, '0', STR_PAD_LEFT),
                'telephone' => '555-555-0' . str_pad((string) (200 + $idx), 3, '0', STR_PAD_LEFT),
            ]);

            $properties[$idx] = Property::factory()->create([
                'participant_id' => $participants[$idx]->id,
                'household_id' => $households[$idx]->id,
                'address_line_1' => $idx . ' Test Lane',
                'city' => 'Allentown',
                'state' => 'PA',
                'zip_code' => '18101',
                'county' => 'Lehigh',
                'year_built' => 1950 + $idx,
                'bedrooms' => ($idx % 4) + 1,
                'bathrooms' => 1.5,
                'square_footage' => 1000 + ($idx * 100),
                'status' => 'active',
            ]);
        }

        // Communications Log entries — variation of converted vs not, fund-code attachment counts.

        $cl1 = CommunicationLog::factory()->create([
            'communication_date' => '2026-03-01 09:00:00',
            'subject' => 'CL-1: General inquiry, no fund code, not converted',
            'communication_type' => 'phone_call',
            'direction' => 'incoming',
            'category' => 'inquiry',
            'priority' => 'normal',
            'participant_id' => $participants[1]->id,
            'household_id' => $households[1]->id,
            'property_id' => $properties[1]->id,
            'contact_first_name' => 'Ada',
            'contact_last_name' => 'Fixture',
            'contact_phone' => '555-555-0201',
        ]);

        $cl2 = CommunicationLog::factory()->create([
            'communication_date' => '2026-03-02 09:00:00',
            'subject' => 'CL-2: Single fund code, not converted',
            'communication_type' => 'email',
            'direction' => 'incoming',
            'category' => 'intake',
            'priority' => 'normal',
            'participant_id' => $participants[2]->id,
            'household_id' => $households[2]->id,
            'property_id' => $properties[2]->id,
            'contact_first_name' => 'Brad',
            'contact_last_name' => 'Fixture',
            'contact_phone' => '555-555-0202',
        ]);
        $cl2->fundCodes()->attach($fcA1->id);

        $cl3 = CommunicationLog::factory()->create([
            'communication_date' => '2026-03-03 09:00:00',
            'subject' => 'CL-3: Multiple fund codes, not converted',
            'communication_type' => 'in_person',
            'direction' => 'incoming',
            'category' => 'intake',
            'priority' => 'high',
            'participant_id' => $participants[3]->id,
            'household_id' => $households[3]->id,
            'property_id' => $properties[3]->id,
            'contact_first_name' => 'Carla',
            'contact_last_name' => 'Fixture',
            'contact_phone' => '555-555-0203',
        ]);
        $cl3->fundCodes()->attach([$fcA1->id, $fcA2->id]);

        // HJ-1 — converted from CL-4, status pending.
        $hj1 = HousingJob::factory()->create([
            'participant_id' => $participants[4]->id,
            'household_id' => $households[4]->id,
            'property_id' => $properties[4]->id,
            'program_id' => $program->id,
            'job_number' => 'JOB-FIX-00001',
            'job_title' => 'HJ-1: Pending (just converted from comm log)',
            'job_description' => 'Pending project for regression coverage.',
            'status' => 'pending',
            'priority' => 'medium',
            'request_date' => '2026-03-04',
            'estimated_cost' => 12000.00,
            'is_template' => false,
        ]);

        $cl4 = CommunicationLog::factory()->create([
            'communication_date' => '2026-03-04 09:00:00',
            'subject' => 'CL-4: Converted to project (HJ-1), single fund code',
            'communication_type' => 'phone_call',
            'direction' => 'incoming',
            'category' => 'intake',
            'priority' => 'high',
            'participant_id' => $participants[4]->id,
            'household_id' => $households[4]->id,
            'property_id' => $properties[4]->id,
            'housing_job_id' => $hj1->id,
            'converted_to_job_id' => $hj1->id,
            'converted_at' => '2026-03-04 10:30:00',
            'contact_first_name' => 'Dan',
            'contact_last_name' => 'Fixture',
            'contact_phone' => '555-555-0204',
        ]);
        $cl4->fundCodes()->attach($fcStandalone->id);

        // HJ-2 — in_progress, mid-milestone.
        $hj2 = HousingJob::factory()->create([
            'participant_id' => $participants[5]->id,
            'household_id' => $households[5]->id,
            'property_id' => $properties[5]->id,
            'program_id' => $program->id,
            'contractor_id' => $contractor2->id,
            'job_number' => 'JOB-FIX-00002',
            'job_title' => 'HJ-2: In progress, mid-milestone',
            'job_description' => 'Mid-milestone project for regression coverage.',
            'status' => 'in_progress',
            'priority' => 'medium',
            'request_date' => '2026-01-15',
            'start_date' => '2026-02-01',
            'estimated_cost' => 18500.00,
            'is_template' => false,
        ]);

        ProjectMilestone::factory()->completed()->create([
            'housing_job_id' => $hj2->id,
            'name' => 'Initial Inspection',
            'description' => 'Property walkthrough and assessment.',
            'sequence_order' => 1,
            'target_date' => '2026-02-05',
            'completed_date' => '2026-02-05',
        ]);

        ProjectMilestone::factory()->inProgress()->create([
            'housing_job_id' => $hj2->id,
            'name' => 'Estimate Approval',
            'description' => 'Contractor estimate review.',
            'sequence_order' => 2,
            'target_date' => '2026-03-15',
        ]);

        ProjectMilestone::factory()->create([
            'housing_job_id' => $hj2->id,
            'name' => 'Construction',
            'description' => 'On-site work.',
            'sequence_order' => 3,
            'status' => 'not_started',
            'target_date' => '2026-05-01',
        ]);

        // CL-5 — converted to HJ-2, no fund code.
        CommunicationLog::factory()->create([
            'communication_date' => '2026-01-15 11:00:00',
            'subject' => 'CL-5: Converted to project (HJ-2), no fund code',
            'communication_type' => 'phone_call',
            'direction' => 'incoming',
            'category' => 'intake',
            'priority' => 'high',
            'participant_id' => $participants[5]->id,
            'household_id' => $households[5]->id,
            'property_id' => $properties[5]->id,
            'housing_job_id' => $hj2->id,
            'converted_to_job_id' => $hj2->id,
            'converted_at' => '2026-01-15 12:00:00',
            'contact_first_name' => 'Eve',
            'contact_last_name' => 'Fixture',
            'contact_phone' => '555-555-0205',
        ]);

        // HJ-3 — completed.
        $hj3 = HousingJob::factory()->create([
            'participant_id' => $participants[6]->id,
            'household_id' => $households[6]->id,
            'property_id' => $properties[6]->id,
            'program_id' => $program->id,
            'contractor_id' => $contractor1->id,
            'job_number' => 'JOB-FIX-00003',
            'job_title' => 'HJ-3: Completed',
            'job_description' => 'Completed project for regression coverage.',
            'status' => 'completed',
            'priority' => 'medium',
            'request_date' => '2025-10-01',
            'start_date' => '2025-11-01',
            'actual_completion_date' => '2026-02-15',
            'estimated_cost' => 22000.00,
            'actual_cost' => 21500.00,
            'is_template' => false,
        ]);

        foreach (['Inspection', 'Permits', 'Construction', 'Final Walkthrough'] as $j => $name) {
            ProjectMilestone::factory()->completed()->create([
                'housing_job_id' => $hj3->id,
                'name' => $name,
                'description' => "$name milestone.",
                'sequence_order' => $j + 1,
                'target_date' => '2025-' . str_pad((string) (11 + $j), 2, '0', STR_PAD_LEFT) . '-15',
                'completed_date' => '2025-' . str_pad((string) (11 + $j), 2, '0', STR_PAD_LEFT) . '-15',
            ]);
        }

        // HJ-4 — has budget categories with contracts and multiple expenses (running total target).
        $hj4 = HousingJob::factory()->create([
            'participant_id' => $participants[7]->id,
            'household_id' => $households[7]->id,
            'property_id' => $properties[7]->id,
            'program_id' => $program->id,
            'contractor_id' => $contractor1->id,
            'job_number' => 'JOB-FIX-00004',
            'job_title' => 'HJ-4: Budget + expenses for running-total verification',
            'job_description' => 'Budget+expense project for regression coverage.',
            'status' => 'in_progress',
            'priority' => 'medium',
            'request_date' => '2026-02-01',
            'start_date' => '2026-02-15',
            'estimated_cost' => 30000.00,
            'is_template' => false,
        ]);

        ProjectBudget::factory()->create([
            'housing_job_id' => $hj4->id,
            'contract_id' => $contractA->id,
            'category' => 'Roofing',
            'budgeted_amount' => 15000.00,
            'spent_amount' => 0,
        ]);

        ProjectBudget::factory()->create([
            'housing_job_id' => $hj4->id,
            'contract_id' => $contractB->id,
            'category' => 'Plumbing',
            'budgeted_amount' => 8000.00,
            'spent_amount' => 0,
        ]);

        // 3 expenses against Roofing — running total verifiable: 1500 + 2500 + 3000 = 7000.
        ProjectExpense::factory()->create([
            'housing_job_id' => $hj4->id,
            'contract_id' => $contractA->id,
            'category' => 'Roofing',
            'amount' => 1500.00,
            'expense_date' => '2026-02-20',
            'description' => 'Roofing materials — initial delivery.',
        ]);

        ProjectExpense::factory()->create([
            'housing_job_id' => $hj4->id,
            'contract_id' => $contractA->id,
            'category' => 'Roofing',
            'amount' => 2500.00,
            'expense_date' => '2026-02-27',
            'description' => 'Roofing labor — week 1.',
        ]);

        ProjectExpense::factory()->create([
            'housing_job_id' => $hj4->id,
            'contract_id' => $contractA->id,
            'category' => 'Roofing',
            'amount' => 3000.00,
            'expense_date' => '2026-03-06',
            'description' => 'Roofing labor — week 2.',
        ]);

        // HJ-Template — flagged is_template=true.
        HousingJob::factory()->template()->create([
            'participant_id' => $participants[8]->id,
            'household_id' => $households[8]->id,
            'property_id' => $properties[8]->id,
            'program_id' => $program->id,
            'job_number' => 'JOB-FIX-TPL-00001',
            'job_title' => 'HJ-Template: Standard Roofing Workflow',
            'job_description' => 'Reusable template for roofing projects.',
            'template_name' => 'Standard Roofing Workflow',
            'template_description' => 'Use this template for typical roofing rehab cases.',
            'request_date' => '2025-12-01',
            'estimated_cost' => 20000.00,
        ]);
    }
}
