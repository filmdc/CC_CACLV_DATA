<?php

namespace Database\Seeders;

use App\Models\ReferenceData;
use App\Models\ReferenceDataSet;
use Illuminate\Database\Seeder;

class CountyReferenceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the active reference data set
        $activeDataSet = ReferenceDataSet::getActive();
        
        if (!$activeDataSet) {
            $this->command->error('No active reference data set found. Please create and activate a reference data set first.');
            return;
        }

        $counties = [
            [
                'value' => 'northampton',
                'label' => 'Northampton County',
                'sort_order' => 1,
            ],
            [
                'value' => 'lehigh',
                'label' => 'Lehigh County', 
                'sort_order' => 2,
            ],
        ];

        foreach ($counties as $county) {
            ReferenceData::updateOrCreate([
                'reference_data_set_id' => $activeDataSet->id,
                'category' => 'county',
                'value' => $county['value'],
            ], [
                'label' => $county['label'],
                'sort_order' => $county['sort_order'],
                'active' => true,
                'description' => 'Pennsylvania county for CACLV service area',
            ]);
        }

        $this->command->info('County reference data seeded successfully.');
    }
}