<?php

namespace Database\Seeders;

use App\Models\SageIntacctFieldMapping;
use App\Models\SageIntacctSetting;
use Illuminate\Database\Seeder;

class ProductionFieldMappingsSeeder extends Seeder
{
    /**
     * Seed production Sage Intacct field mappings.
     *
     * Field mappings define how DonorPerfect gift fields map to
     * Sage Intacct custom object fields or Other Receipt fields.
     *
     * Generated from production database on: 2025-11-21
     * Total mappings: 19
     */
    public function run(): void
    {
        // Get or create Sage Intacct settings
        $setting = SageIntacctSetting::first();

        if (!$setting) {
            $this->command->warn('No Sage Intacct settings found. Field mappings require a setting record.');
            $this->command->warn('Please create Sage Intacct settings first, then run this seeder.');
            return;
        }

        $mappings = [
            ['donor_perfect_field' => 'gift_id', 'intacct_field' => 'donor_perfect_gift_id', 'is_required' => true, 'default_value' => null, 'order' => 1],
            ['donor_perfect_field' => 'gift_date', 'intacct_field' => 'paymentdate', 'is_required' => true, 'default_value' => null, 'order' => 2],
            ['donor_perfect_field' => 'amount', 'intacct_field' => 'amount', 'is_required' => true, 'default_value' => null, 'order' => 3],
            ['donor_perfect_field' => 'gl_code', 'intacct_field' => 'glaccountno', 'is_required' => true, 'default_value' => null, 'order' => 4],
            ['donor_perfect_field' => 'donor_id', 'intacct_field' => 'donor_id', 'is_required' => true, 'default_value' => null, 'order' => 5],
            ['donor_perfect_field' => 'first_name', 'intacct_field' => 'firstName', 'is_required' => false, 'default_value' => null, 'order' => 6],
            ['donor_perfect_field' => 'last_name', 'intacct_field' => 'lastName', 'is_required' => false, 'default_value' => null, 'order' => 7],
            ['donor_perfect_field' => 'email', 'intacct_field' => 'email', 'is_required' => false, 'default_value' => null, 'order' => 8],
            ['donor_perfect_field' => 'phone', 'intacct_field' => 'phone', 'is_required' => false, 'default_value' => null, 'order' => 9],
            ['donor_perfect_field' => 'address', 'intacct_field' => 'streetAddr1', 'is_required' => false, 'default_value' => null, 'order' => 10],
            ['donor_perfect_field' => 'city', 'intacct_field' => 'city', 'is_required' => false, 'default_value' => null, 'order' => 11],
            ['donor_perfect_field' => 'state', 'intacct_field' => 'state', 'is_required' => false, 'default_value' => null, 'order' => 12],
            ['donor_perfect_field' => 'zip', 'intacct_field' => 'zip', 'is_required' => false, 'default_value' => null, 'order' => 13],
            ['donor_perfect_field' => 'campaign', 'intacct_field' => 'locationid', 'is_required' => false, 'default_value' => null, 'order' => 14],
            ['donor_perfect_field' => 'solicit_code', 'intacct_field' => 'projectid', 'is_required' => false, 'default_value' => null, 'order' => 15],
            ['donor_perfect_field' => 'sub_solicit_code', 'intacct_field' => 'departmentid', 'is_required' => false, 'default_value' => null, 'order' => 16],
            ['donor_perfect_field' => 'payment_type', 'intacct_field' => 'payment_type', 'is_required' => false, 'default_value' => null, 'order' => 17],
            ['donor_perfect_field' => 'gift_type', 'intacct_field' => 'gift_type', 'is_required' => false, 'default_value' => null, 'order' => 18],
            ['donor_perfect_field' => 'IMAGE_CHECK', 'intacct_field' => 'check_image_path', 'is_required' => false, 'default_value' => null, 'order' => 19],
        ];

        $this->command->info("Seeding " . count($mappings) . " field mappings for setting ID {$setting->id}...");

        foreach ($mappings as $mapping) {
            SageIntacctFieldMapping::updateOrCreate(
                [
                    'setting_id' => $setting->id,
                    'donor_perfect_field' => $mapping['donor_perfect_field'],
                    'intacct_field' => $mapping['intacct_field'],
                ],
                [
                    'is_required' => $mapping['is_required'],
                    'default_value' => $mapping['default_value'],
                    'order' => $mapping['order'],
                ]
            );
        }

        $this->command->info("✓ Field mappings seeded successfully");
        $this->command->info("  - Total: " . count($mappings) . " field mappings");
    }
}
