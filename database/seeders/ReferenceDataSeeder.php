<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReferenceData;
use App\Models\ReferenceDataSet;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create ONE unified CSBG-compliant reference data set
        $dataSet = ReferenceDataSet::updateOrCreate(
            ['name' => 'CSBG 2025 Standard'],
            [
                'description' => 'Unified reference data set for CSBG Module 4 Section C reporting compliance',
                'version' => 'v1.0.0',
                'is_active' => true,
                'is_draft' => false,
                'activated_at' => now(),
                'created_by' => 1 // Assuming admin user ID is 1
            ]
        );

        // CSBG-compliant reference data - all categories in ONE set
        $referenceData = [
            'gender' => [
                ['value' => 'Male', 'label' => 'Male', 'sort_order' => 1],
                ['value' => 'Female', 'label' => 'Female', 'sort_order' => 2],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 3],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 4],
            ],
            
            'education_level' => [
                // CSBG Module 4 Section C education levels
                ['value' => 'Grades 0-8', 'label' => 'Grades 0-8', 'sort_order' => 1],
                ['value' => 'Grades 9-12/Non-Graduate', 'label' => 'Grades 9-12/Non-Graduate', 'sort_order' => 2],
                ['value' => 'High School Graduate', 'label' => 'High School Graduate', 'sort_order' => 3],
                ['value' => 'GED/Equivalency Diploma', 'label' => 'GED/Equivalency Diploma', 'sort_order' => 4],
                ['value' => '12th Grade + Some Post-Secondary', 'label' => '12th Grade + Some Post-Secondary', 'sort_order' => 5],
                ['value' => '2 or 4 Year College Graduate', 'label' => '2 or 4 Year College Graduate', 'sort_order' => 6],
                ['value' => 'Graduate of Other Post-Secondary School', 'label' => 'Graduate of Other Post-Secondary School', 'sort_order' => 7],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 8],
            ],
            
            'ethnicity' => [
                // CSBG Module 4 Section C ethnicity options
                ['value' => 'Hispanic, Latino or Spanish Origins', 'label' => 'Hispanic, Latino or Spanish Origins', 'sort_order' => 1],
                ['value' => 'Not Hispanic, Latino or Spanish Origins', 'label' => 'Not Hispanic, Latino or Spanish Origins', 'sort_order' => 2],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 3],
            ],
            
            'race' => [
                // CSBG Module 4 Section C race options
                ['value' => 'American Indian or Alaska Native', 'label' => 'American Indian or Alaska Native', 'sort_order' => 1],
                ['value' => 'Asian', 'label' => 'Asian', 'sort_order' => 2],
                ['value' => 'Black or African American', 'label' => 'Black or African American', 'sort_order' => 3],
                ['value' => 'Native Hawaiian and Other Pacific Islander', 'label' => 'Native Hawaiian and Other Pacific Islander', 'sort_order' => 4],
                ['value' => 'White', 'label' => 'White', 'sort_order' => 5],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 6],
                ['value' => 'Multi-race (two or more of the above)', 'label' => 'Multi-race (two or more of the above)', 'sort_order' => 7],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 8],
            ],
            
            'military_status' => [
                // CSBG Module 4 Section C military status options
                ['value' => 'Veteran', 'label' => 'Veteran', 'sort_order' => 1],
                ['value' => 'Active Military', 'label' => 'Active Military', 'sort_order' => 2],
                ['value' => 'Never Served in the Military', 'label' => 'Never Served in the Military', 'sort_order' => 3],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 4],
            ],
            
            'work_status' => [
                // CSBG Module 4 Section C work status - includes missing "Migrant Seasonal Farm Worker"
                ['value' => 'Employed Full-Time', 'label' => 'Employed Full-Time', 'sort_order' => 1],
                ['value' => 'Employed Part-Time', 'label' => 'Employed Part-Time', 'sort_order' => 2],
                ['value' => 'Migrant Seasonal Farm Worker', 'label' => 'Migrant Seasonal Farm Worker', 'sort_order' => 3],
                ['value' => 'Unemployed (Short-Term, 6 months or less)', 'label' => 'Unemployed (Short-Term, 6 months or less)', 'sort_order' => 4],
                ['value' => 'Unemployed (Long-Term, more than 6 months)', 'label' => 'Unemployed (Long-Term, more than 6 months)', 'sort_order' => 5],
                ['value' => 'Unemployed (Not in Labor Force)', 'label' => 'Unemployed (Not in Labor Force)', 'sort_order' => 6],
                ['value' => 'Retired', 'label' => 'Retired', 'sort_order' => 7],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 8],
            ],
            
            'disability_status' => [
                ['value' => 'Yes', 'label' => 'Yes', 'sort_order' => 1],
                ['value' => 'No', 'label' => 'No', 'sort_order' => 2],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 3],
            ],
            
            'health_insurance_status' => [
                ['value' => 'Yes', 'label' => 'Yes', 'sort_order' => 1],
                ['value' => 'No', 'label' => 'No', 'sort_order' => 2],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 3],
            ],
            
            'health_insurance_sources' => [
                // CSBG Module 4 Section C health insurance sources
                ['value' => 'Medicaid', 'label' => 'Medicaid', 'sort_order' => 1],
                ['value' => 'Medicare', 'label' => 'Medicare', 'sort_order' => 2],
                ['value' => 'State Children\'s Health Insurance Program', 'label' => 'State Children\'s Health Insurance Program', 'sort_order' => 3],
                ['value' => 'State Health Insurance for Adults', 'label' => 'State Health Insurance for Adults', 'sort_order' => 4],
                ['value' => 'Military Health Care', 'label' => 'Military Health Care', 'sort_order' => 5],
                ['value' => 'Direct-Purchase', 'label' => 'Direct-Purchase', 'sort_order' => 6],
                ['value' => 'Employment Based', 'label' => 'Employment Based', 'sort_order' => 7],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 8],
            ],
            
            'income_sources' => [
                // CSBG Module 4 Section C income source categories
                ['value' => 'Income from Employment Only', 'label' => 'Income from Employment Only', 'sort_order' => 1],
                ['value' => 'Income from Employment and Other Income Source', 'label' => 'Income from Employment and Other Income Source', 'sort_order' => 2],
                ['value' => 'Income from Employment, Other Income Source, and Non-Cash Benefits', 'label' => 'Income from Employment, Other Income Source, and Non-Cash Benefits', 'sort_order' => 3],
                ['value' => 'Income from Employment and Non-Cash Benefits', 'label' => 'Income from Employment and Non-Cash Benefits', 'sort_order' => 4],
                ['value' => 'Other Income Source Only', 'label' => 'Other Income Source Only', 'sort_order' => 5],
                ['value' => 'Other Income Source and Non-Cash Benefits', 'label' => 'Other Income Source and Non-Cash Benefits', 'sort_order' => 6],
                ['value' => 'No Income', 'label' => 'No Income', 'sort_order' => 7],
                ['value' => 'Non-Cash Benefits Only', 'label' => 'Non-Cash Benefits Only', 'sort_order' => 8],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 9],
            ],
            
            'other_income_sources' => [
                // CSBG Module 4 Section C other income sources
                ['value' => 'TANF', 'label' => 'TANF', 'sort_order' => 1],
                ['value' => 'Supplemental Security Income (SSI)', 'label' => 'Supplemental Security Income (SSI)', 'sort_order' => 2],
                ['value' => 'Social Security Disability Income (SSDI)', 'label' => 'Social Security Disability Income (SSDI)', 'sort_order' => 3],
                ['value' => 'VA Service-Connected Disability Compensation', 'label' => 'VA Service-Connected Disability Compensation', 'sort_order' => 4],
                ['value' => 'VA Non-Service Connected Disability Pension', 'label' => 'VA Non-Service Connected Disability Pension', 'sort_order' => 5],
                ['value' => 'Private Disability Insurance', 'label' => 'Private Disability Insurance', 'sort_order' => 6],
                ['value' => 'Worker\'s Compensation', 'label' => 'Worker\'s Compensation', 'sort_order' => 7],
                ['value' => 'Retirement Income from Social Security', 'label' => 'Retirement Income from Social Security', 'sort_order' => 8],
                ['value' => 'Pension', 'label' => 'Pension', 'sort_order' => 9],
                ['value' => 'Child Support', 'label' => 'Child Support', 'sort_order' => 10],
                ['value' => 'Alimony or other Spousal Support', 'label' => 'Alimony or other Spousal Support', 'sort_order' => 11],
                ['value' => 'Unemployment Insurance', 'label' => 'Unemployment Insurance', 'sort_order' => 12],
                ['value' => 'EITC', 'label' => 'EITC', 'sort_order' => 13],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 14],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 15],
            ],
            
            'non_cash_benefits' => [
                // CSBG Module 4 Section C non-cash benefits
                ['value' => 'SNAP', 'label' => 'SNAP', 'sort_order' => 1],
                ['value' => 'WIC', 'label' => 'WIC', 'sort_order' => 2],
                ['value' => 'LIHEAP', 'label' => 'LIHEAP', 'sort_order' => 3],
                ['value' => 'Housing Choice Voucher', 'label' => 'Housing Choice Voucher', 'sort_order' => 4],
                ['value' => 'Public Housing', 'label' => 'Public Housing', 'sort_order' => 5],
                ['value' => 'Permanent Supportive Housing', 'label' => 'Permanent Supportive Housing', 'sort_order' => 6],
                ['value' => 'HUD-VASH', 'label' => 'HUD-VASH', 'sort_order' => 7],
                ['value' => 'Childcare Voucher', 'label' => 'Childcare Voucher', 'sort_order' => 8],
                ['value' => 'Affordable Care Act Subsidy', 'label' => 'Affordable Care Act Subsidy', 'sort_order' => 9],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 10],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 11],
            ],
            
            'housing_status' => [
                // CSBG Module 4 Section C housing status
                ['value' => 'Own', 'label' => 'Own', 'sort_order' => 1],
                ['value' => 'Rent', 'label' => 'Rent', 'sort_order' => 2],
                ['value' => 'Other permanent housing', 'label' => 'Other permanent housing', 'sort_order' => 3],
                ['value' => 'Homeless', 'label' => 'Homeless', 'sort_order' => 4],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 5],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 6],
            ],
            
            'household_type' => [
                // CSBG Module 4 Section C household types
                ['value' => 'Single Person', 'label' => 'Single Person', 'sort_order' => 1],
                ['value' => 'Two Adults NO Children', 'label' => 'Two Adults NO Children', 'sort_order' => 2],
                ['value' => 'Single Parent Female', 'label' => 'Single Parent Female', 'sort_order' => 3],
                ['value' => 'Single Parent Male', 'label' => 'Single Parent Male', 'sort_order' => 4],
                ['value' => 'Two Parent Household', 'label' => 'Two Parent Household', 'sort_order' => 5],
                ['value' => 'Non-related Adults with Children', 'label' => 'Non-related Adults with Children', 'sort_order' => 6],
                ['value' => 'Multigenerational Household', 'label' => 'Multigenerational Household', 'sort_order' => 7],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 8],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 9],
            ],
        ];

        // Insert all reference data into the ONE unified data set
        foreach ($referenceData as $category => $items) {
            foreach ($items as $item) {
                ReferenceData::updateOrCreate(
                    [
                        'reference_data_set_id' => $dataSet->id,
                        'category' => $category, 
                        'value' => $item['value']
                    ],
                    [
                        'label' => $item['label'],
                        'sort_order' => $item['sort_order'],
                        'active' => true,
                        'description' => null
                    ]
                );
            }
        }

        // Deactivate any other reference data sets that might exist
        ReferenceDataSet::where('id', '!=', $dataSet->id)->update(['is_active' => false]);
    }
}