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
        $mappings = [
            ['dp_payment_type' => '', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'ACH', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'ACHEFT', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'AK', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'AMAZONIN-KIND', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'AMEXEFT', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'AMEXIC', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'Bank Transfer', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'BEQUEST', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'BUSINESS_CHECKING', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'BUSINESS_SAVINGS', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'CA', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'CASH', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'CC', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'CCEFT', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'CHARITABLEGF', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'CHARITABLETRST', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'Check', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'CHECKING', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'CHK', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'CI', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'CK', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'CKCSR', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'CKO', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'CKP', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'Credit Card', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'DAF', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'DECLINECREDITCARDDONATION', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'DISCOVEREFT', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'DISCOVERIC', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'DONRADVFUND', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'EFT', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'EITC', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'FDN', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'GC', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'GIFTCARD', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'GK', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'GRANT', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'GRT', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'IK', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'IRA', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'MasterCard', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'MCEFT', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'MCIC', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'MG', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'MO', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'NPP', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'PAYPAL', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'PERSONAL_CHECKING', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'PERSONAL_SAVINGS', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'RE', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'SAVINGS', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'SC', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'SHFB', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'SN', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'SPLITCHECKANDCASH', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'ST', 'intacct_payment_method' => 'Cash', 'is_active' => true],
            ['dp_payment_type' => 'TEXT', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'UW', 'intacct_payment_method' => 'Printed Check', 'is_active' => true],
            ['dp_payment_type' => 'UWPD', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'visa', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'VISAEFT', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'VISAIC', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'WEB', 'intacct_payment_method' => 'Credit Card', 'is_active' => true],
            ['dp_payment_type' => 'Wire Transfer', 'intacct_payment_method' => 'EFT', 'is_active' => true],
            ['dp_payment_type' => 'WS', 'intacct_payment_method' => 'Cash', 'is_active' => true],
        ];

        $this->command->info("Seeding " . count($mappings) . " payment method mappings...");

        foreach ($mappings as $mapping) {
            DonorPerfectPaymentMethodMapping::updateOrCreate(
                [
                    'dp_payment_type' => $mapping['dp_payment_type'],
                ],
                [
                    'intacct_payment_method' => $mapping['intacct_payment_method'],
                    'is_active' => $mapping['is_active'] ?? true,
                ]
            );
        }

        $this->command->info("✓ Payment method mappings seeded successfully");
        $this->command->info("  - Total: " . count($mappings) . " payment methods");
    }
}
