<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReferenceData;
use App\Models\ReferenceDataSet;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $versionCounter = 1;
        $referenceData = [
            'gender' => [
                ['value' => 'Male', 'label' => 'Male', 'sort_order' => 1],
                ['value' => 'Female', 'label' => 'Female', 'sort_order' => 2],
                ['value' => 'Non-binary', 'label' => 'Non-binary', 'sort_order' => 3],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 4],
                ['value' => 'Prefer not to say', 'label' => 'Prefer not to say', 'sort_order' => 5],
            ],
            
            'education_level' => [
                ['value' => 'Less than high school', 'label' => 'Less than high school', 'sort_order' => 1],
                ['value' => 'High school diploma/GED', 'label' => 'High school diploma/GED', 'sort_order' => 2],
                ['value' => 'Some college', 'label' => 'Some college', 'sort_order' => 3],
                ['value' => 'Associate degree', 'label' => 'Associate degree', 'sort_order' => 4],
                ['value' => 'Bachelor degree', 'label' => 'Bachelor degree', 'sort_order' => 5],
                ['value' => 'Graduate degree', 'label' => 'Graduate degree', 'sort_order' => 6],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 7],
            ],
            
            'ethnicity' => [
                ['value' => 'Hispanic or Latino', 'label' => 'Hispanic or Latino', 'sort_order' => 1],
                ['value' => 'Not Hispanic or Latino', 'label' => 'Not Hispanic or Latino', 'sort_order' => 2],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 3],
            ],
            
            'race' => [
                ['value' => 'American Indian or Alaska Native', 'label' => 'American Indian or Alaska Native', 'sort_order' => 1],
                ['value' => 'Asian', 'label' => 'Asian', 'sort_order' => 2],
                ['value' => 'Black or African American', 'label' => 'Black or African American', 'sort_order' => 3],
                ['value' => 'Native Hawaiian or Other Pacific Islander', 'label' => 'Native Hawaiian or Other Pacific Islander', 'sort_order' => 4],
                ['value' => 'White', 'label' => 'White', 'sort_order' => 5],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 6],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 7],
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
                ['value' => 'Medicaid', 'label' => 'Medicaid', 'sort_order' => 1],
                ['value' => 'Medicare', 'label' => 'Medicare', 'sort_order' => 2],
                ['value' => 'Employer-provided', 'label' => 'Employer-provided', 'sort_order' => 3],
                ['value' => 'Private insurance', 'label' => 'Private insurance', 'sort_order' => 4],
                ['value' => 'ACA Marketplace', 'label' => 'ACA Marketplace', 'sort_order' => 5],
                ['value' => 'Veterans Affairs', 'label' => 'Veterans Affairs', 'sort_order' => 6],
                ['value' => 'Indian Health Service', 'label' => 'Indian Health Service', 'sort_order' => 7],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 8],
            ],
            
            'military_status' => [
                ['value' => 'Currently serving', 'label' => 'Currently serving', 'sort_order' => 1],
                ['value' => 'Veteran', 'label' => 'Veteran', 'sort_order' => 2],
                ['value' => 'Not applicable', 'label' => 'Not applicable', 'sort_order' => 3],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 4],
            ],
            
            'work_status' => [
                ['value' => 'Employed full-time', 'label' => 'Employed full-time', 'sort_order' => 1],
                ['value' => 'Employed part-time', 'label' => 'Employed part-time', 'sort_order' => 2],
                ['value' => 'Unemployed', 'label' => 'Unemployed', 'sort_order' => 3],
                ['value' => 'Not in labor force', 'label' => 'Not in labor force', 'sort_order' => 4],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 5],
            ],
            
            'income_sources' => [
                ['value' => 'Employment income', 'label' => 'Employment income', 'sort_order' => 1],
                ['value' => 'Self-employment income', 'label' => 'Self-employment income', 'sort_order' => 2],
                ['value' => 'Social Security', 'label' => 'Social Security', 'sort_order' => 3],
                ['value' => 'SSI/SSDI', 'label' => 'SSI/SSDI', 'sort_order' => 4],
                ['value' => 'Unemployment benefits', 'label' => 'Unemployment benefits', 'sort_order' => 5],
                ['value' => 'Workers compensation', 'label' => 'Workers compensation', 'sort_order' => 6],
                ['value' => 'Veterans benefits', 'label' => 'Veterans benefits', 'sort_order' => 7],
                ['value' => 'Pension/retirement', 'label' => 'Pension/retirement', 'sort_order' => 8],
                ['value' => 'Investment income', 'label' => 'Investment income', 'sort_order' => 9],
                ['value' => 'Child support', 'label' => 'Child support', 'sort_order' => 10],
                ['value' => 'Alimony', 'label' => 'Alimony', 'sort_order' => 11],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 12],
            ],
            
            'other_income_sources' => [
                ['value' => 'TANF', 'label' => 'TANF (Temporary Assistance for Needy Families)', 'sort_order' => 1],
                ['value' => 'General Assistance', 'label' => 'General Assistance', 'sort_order' => 2],
                ['value' => 'Financial assistance from family/friends', 'label' => 'Financial assistance from family/friends', 'sort_order' => 3],
                ['value' => 'Other government assistance', 'label' => 'Other government assistance', 'sort_order' => 4],
                ['value' => 'Private charity assistance', 'label' => 'Private charity assistance', 'sort_order' => 5],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 6],
            ],
            
            'non_cash_benefits' => [
                ['value' => 'SNAP/Food Stamps', 'label' => 'SNAP/Food Stamps', 'sort_order' => 1],
                ['value' => 'WIC', 'label' => 'WIC (Women, Infants, Children)', 'sort_order' => 2],
                ['value' => 'School meals', 'label' => 'School meals', 'sort_order' => 3],
                ['value' => 'Senior meals', 'label' => 'Senior meals', 'sort_order' => 4],
                ['value' => 'Housing assistance', 'label' => 'Housing assistance', 'sort_order' => 5],
                ['value' => 'LIHEAP', 'label' => 'LIHEAP (Energy assistance)', 'sort_order' => 6],
                ['value' => 'Lifeline phone', 'label' => 'Lifeline phone', 'sort_order' => 7],
                ['value' => 'Transportation assistance', 'label' => 'Transportation assistance', 'sort_order' => 8],
                ['value' => 'Child care assistance', 'label' => 'Child care assistance', 'sort_order' => 9],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 10],
            ],
            
            'housing_status' => [
                ['value' => 'Own', 'label' => 'Own', 'sort_order' => 1],
                ['value' => 'Rent', 'label' => 'Rent', 'sort_order' => 2],
                ['value' => 'Other permanent housing', 'label' => 'Other permanent housing', 'sort_order' => 3],
                ['value' => 'Homeless', 'label' => 'Homeless', 'sort_order' => 4],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 5],
                ['value' => 'Unknown/not reported', 'label' => 'Unknown/not reported', 'sort_order' => 6],
            ],
            
            'housing_type' => [
                ['value' => 'Single-family house', 'label' => 'Single-family house', 'sort_order' => 1],
                ['value' => 'Apartment', 'label' => 'Apartment', 'sort_order' => 2],
                ['value' => 'Townhouse/Condo', 'label' => 'Townhouse/Condo', 'sort_order' => 3],
                ['value' => 'Mobile home', 'label' => 'Mobile home', 'sort_order' => 4],
                ['value' => 'Shelter', 'label' => 'Shelter', 'sort_order' => 5],
                ['value' => 'RV/Vehicle', 'label' => 'RV/Vehicle', 'sort_order' => 6],
                ['value' => 'Other', 'label' => 'Other', 'sort_order' => 7],
            ],
            
            'household_type' => [
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

        foreach ($referenceData as $category => $items) {
            // Create or get the reference data set for this category
            $dataSet = ReferenceDataSet::updateOrCreate(
                ['name' => $category],
                [
                    'description' => 'Reference data for ' . ucwords(str_replace('_', ' ', $category)),
                    'version' => 'v1.' . $versionCounter++,
                    'is_active' => true,
                    'is_draft' => false,
                    'activated_at' => now()
                ]
            );

            foreach ($items as $item) {
                ReferenceData::updateOrCreate(
                    ['category' => $category, 'value' => $item['value']],
                    [
                        'label' => $item['label'],
                        'sort_order' => $item['sort_order'],
                        'active' => true,
                        'reference_data_set_id' => $dataSet->id
                    ]
                );
            }
        }
    }
}