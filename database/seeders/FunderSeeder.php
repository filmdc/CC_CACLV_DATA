<?php

namespace Database\Seeders;

use App\Models\Funder;
use Illuminate\Database\Seeder;

class FunderSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $funders = [
            [
                'name' => 'U.S. Department of Health and Human Services',
                'code' => 'HHS',
                'description' => 'Federal agency focused on protecting public health and providing human services',
                'contact_person' => 'Jane Smith',
                'contact_email' => 'jane.smith@hhs.gov',
                'contact_phone' => '(202) 690-7000',
                'website' => 'https://www.hhs.gov',
                'active' => true,
            ],
            [
                'name' => 'U.S. Department of Housing and Urban Development',
                'code' => 'HUD',
                'description' => 'Federal agency that oversees national policy and programs for housing and urban development',
                'contact_person' => 'Michael Johnson',
                'contact_email' => 'michael.johnson@hud.gov',
                'contact_phone' => '(202) 708-1112',
                'website' => 'https://www.hud.gov',
                'active' => true,
            ],
            [
                'name' => 'Nevada Department of Health and Human Services',
                'code' => 'NVDHHS',
                'description' => 'State agency providing health and human services to Nevada residents',
                'contact_person' => 'Sarah Davis',
                'contact_email' => 'sarah.davis@dhhs.nv.gov',
                'contact_phone' => '(775) 684-4000',
                'website' => 'https://dhhs.nv.gov',
                'active' => true,
            ],
            [
                'name' => 'Community Development Block Grant',
                'code' => 'CDBG',
                'description' => 'Federal program providing communities with resources to address housing and economic development needs',
                'contact_person' => 'Robert Wilson',
                'contact_email' => 'robert.wilson@hud.gov',
                'contact_phone' => '(202) 708-1577',
                'website' => 'https://www.hudexchange.info/programs/cdbg/',
                'active' => true,
            ],
            [
                'name' => 'United Way of Southern Nevada',
                'code' => 'UWSN',
                'description' => 'Local nonprofit organization fighting for health, education, and financial stability',
                'contact_person' => 'Lisa Martinez',
                'contact_email' => 'lisa.martinez@uwsn.org',
                'contact_phone' => '(702) 892-2300',
                'website' => 'https://www.uwsn.org',
                'active' => true,
            ],
        ];

        foreach ($funders as $funderData) {
            Funder::firstOrCreate(
                ['name' => $funderData['name']],
                $funderData
            );
        }

        $this->command->info('Sample funders created successfully.');
    }
}