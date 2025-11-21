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

        // Get all field mappings from production database
        $mappings = \DB::table('sage_intacct_field_mappings')
            ->orderBy('order')
            ->get();

        $this->command->info("Seeding {$mappings->count()} field mappings for setting ID {$setting->id}...");

        foreach ($mappings as $mapping) {
            SageIntacctFieldMapping::updateOrCreate(
                [
                    'setting_id' => $setting->id,
                    'donor_perfect_field' => $mapping->donor_perfect_field,
                    'intacct_field' => $mapping->intacct_field,
                ],
                [
                    'is_required' => $mapping->is_required,
                    'default_value' => $mapping->default_value,
                    'order' => $mapping->order,
                ]
            );
        }

        $this->command->info("✓ Field mappings seeded successfully");
        $this->command->info("  - Total: {$mappings->count()} field mappings");
    }
}
