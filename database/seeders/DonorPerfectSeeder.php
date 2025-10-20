<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\DonorPerfectSetting;

class DonorPerfectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create DonorPerfect permissions
        $permissions = [
            [
                'name' => 'donor_gifts.view',
                'display_name' => 'View DonorPerfect Gifts',
                'description' => 'View the DonorPerfect gifts feed and data',
                'category' => 'Accounting & Fiscal',
            ],
            [
                'name' => 'donor_gifts.configure',
                'display_name' => 'Configure DonorPerfect API',
                'description' => 'Configure DonorPerfect API settings and field mappings',
                'category' => 'Accounting & Fiscal',
            ],
            [
                'name' => 'intacct.configure',
                'display_name' => 'Configure Sage Intacct',
                'description' => 'Configure Sage Intacct API settings, field mappings, and sync options',
                'category' => 'Accounting & Fiscal',
            ],
            [
                'name' => 'intacct.sync',
                'display_name' => 'Sync to Sage Intacct',
                'description' => 'Manually trigger synchronization of DonorPerfect gifts to Sage Intacct',
                'category' => 'Accounting & Fiscal',
            ],
            [
                'name' => 'intacct.view_history',
                'display_name' => 'View Intacct Sync History',
                'description' => 'View historical sync records and detailed sync item results',
                'category' => 'Accounting & Fiscal',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Create default DonorPerfect settings (inactive by default)
        DonorPerfectSetting::firstOrCreate(
            ['is_active' => false],
            [
                'api_url' => null,
                'api_key' => null,
                'api_login' => null,
                'api_password' => null,
                'cache_duration' => 15, // 15 minutes default
                'selected_fields' => [
                    // Default selected fields
                    'donor_id',
                    'first_name',
                    'last_name',
                    'email',
                    'gift_id',
                    'gift_date',
                    'amount',
                    'campaign',
                    'fund',
                ],
                'is_active' => false,
                'last_connection_test' => null,
                'connection_status' => null,
            ]
        );

        $this->command->info('DonorPerfect and Sage Intacct permissions seeded successfully!');
    }
}
