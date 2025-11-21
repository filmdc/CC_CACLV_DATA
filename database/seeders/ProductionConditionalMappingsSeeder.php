<?php

namespace Database\Seeders;

use App\Models\ConditionalCodeMapping;
use Illuminate\Database\Seeder;

class ProductionConditionalMappingsSeeder extends Seeder
{
    /**
     * Seed production conditional code mappings.
     *
     * Conditional mappings define location-specific code rules for Sage Intacct sync.
     * These specify which solicitation and sub-solicitation codes are allowed for
     * each Sage Intacct location (program).
     *
     * Generated from production database on: 2025-11-21
     * Total mappings: 79
     */
    public function run(): void
    {
        // Get all conditional mappings from production database
        $mappings = \DB::table('conditional_code_mappings')
            ->orderBy('intacct_location_code')
            ->orderBy('field_type')
            ->orderBy('priority', 'desc')
            ->get();

        $this->command->info("Seeding {$mappings->count()} conditional code mappings...");

        foreach ($mappings as $mapping) {
            ConditionalCodeMapping::updateOrCreate(
                [
                    'intacct_location_code' => $mapping->intacct_location_code,
                    'field_type' => $mapping->field_type,
                    'dp_code' => $mapping->dp_code,
                ],
                [
                    'intacct_code' => $mapping->intacct_code,
                    'priority' => $mapping->priority,
                    'notes' => $mapping->notes,
                    'is_active' => $mapping->is_active,
                ]
            );
        }

        $this->command->info("✓ Conditional mappings seeded successfully");

        // Show breakdown by location
        $breakdown = $mappings->groupBy('intacct_location_code');
        foreach ($breakdown as $location => $items) {
            $this->command->info("  - Location {$location}: {$items->count()} rules");
        }
    }
}
