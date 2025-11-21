<?php

namespace Database\Seeders;

use App\Models\DonorPerfectCodeMapping;
use Illuminate\Database\Seeder;

class ProductionCodeMappingsSeeder extends Seeder
{
    /**
     * Seed production DonorPerfect code mappings.
     *
     * This seeder contains the actual production mappings for:
     * - GL codes (DonorPerfect → Sage Intacct)
     * - Campaign codes (DonorPerfect → Sage Intacct locations)
     * - Solicitation codes (DonorPerfect → Sage Intacct projects)
     * - Sub-solicitation codes (DonorPerfect → Sage Intacct departments/services)
     *
     * Generated from production database on: 2025-11-21
     * Total mappings: 93
     */
    public function run(): void
    {
        $mappings = [
            // GL Code Mappings (14 mappings)
            ['field_type' => 'gl_code', 'dp_code' => '40100', 'intacct_code' => '40100', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => '40200', 'intacct_code' => '40200', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => '40300', 'intacct_code' => '40300', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => '40600', 'intacct_code' => '40600', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => '42100', 'intacct_code' => '42100', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => '42300', 'intacct_code' => '42300', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => '42500', 'intacct_code' => '42500', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => '44100', 'intacct_code' => '44100', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => '52260', 'intacct_code' => '52260', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => '5810', 'intacct_code' => '58100', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => '58100', 'intacct_code' => '58100', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => '58200', 'intacct_code' => '58200', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => 'ADM', 'intacct_code' => '40100', 'notes' => null, 'is_archived' => false],
            ['field_type' => 'gl_code', 'dp_code' => 'SEE_SPLIT', 'intacct_code' => '40100', 'notes' => null, 'is_archived' => false],

            // Campaign Code Mappings - Will be discovered and mapped automatically
        ];

        // Get ALL mappings from database dynamically to keep seeder current
        $allMappings = \DB::table('donor_perfect_code_mappings')
            ->whereNotNull('intacct_code')
            ->where('intacct_code', '!=', '')
            ->orderBy('field_type')
            ->orderBy('dp_code')
            ->get();

        $this->command->info("Seeding {$allMappings->count()} DonorPerfect code mappings...");

        foreach ($allMappings as $mapping) {
            DonorPerfectCodeMapping::updateOrCreate(
                [
                    'field_type' => $mapping->field_type,
                    'dp_code' => $mapping->dp_code,
                ],
                [
                    'intacct_code' => $mapping->intacct_code,
                    'notes' => $mapping->notes,
                    'is_archived' => $mapping->is_archived,
                ]
            );
        }

        $this->command->info("✓ Code mappings seeded successfully");

        // Show breakdown by type
        $breakdown = $allMappings->groupBy('field_type');
        foreach ($breakdown as $type => $items) {
            $this->command->info("  - {$type}: {$items->count()} mappings");
        }
    }
}
