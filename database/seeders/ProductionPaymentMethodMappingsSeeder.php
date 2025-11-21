<?php

namespace Database\Seeders;

use App\Models\DonorPerfectPaymentMethodMapping;
use Illuminate\Database\Seeder;

class ProductionPaymentMethodMappingsSeeder extends Seeder
{
    /**
     * Seed production payment method mappings.
     *
     * Payment method mappings translate DonorPerfect payment types
     * to Sage Intacct payment methods for Other Receipts.
     *
     * Generated from production database on: 2025-11-21
     * Total mappings: 66
     */
    public function run(): void
    {
        // Get all payment method mappings from production database
        $mappings = \DB::table('donor_perfect_payment_method_mappings')
            ->orderBy('dp_payment_type')
            ->get();

        $this->command->info("Seeding {$mappings->count()} payment method mappings...");

        foreach ($mappings as $mapping) {
            DonorPerfectPaymentMethodMapping::updateOrCreate(
                [
                    'dp_payment_type' => $mapping->dp_payment_type,
                ],
                [
                    'intacct_payment_method' => $mapping->intacct_payment_method,
                    'is_active' => $mapping->is_active ?? true,
                ]
            );
        }

        $this->command->info("✓ Payment method mappings seeded successfully");
        $this->command->info("  - Total: {$mappings->count()} payment methods");
    }
}
