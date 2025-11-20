<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PaymentMethodMappingSeeder extends Seeder
{
    /**
     * Seed payment method mappings for DonorPerfect → Sage Intacct sync
     *
     * This seeder creates mappings for both payment_type and gift_type fields.
     * When payment_type is blank, the sync service falls back to gift_type,
     * so we map common values from both fields.
     *
     * Gift Type Distribution (as of 2025-11-19):
     * - CK (438) - Checks
     * - CC (272) - Credit Cards
     * - WEB (262) - Web Donations (typically credit cards)
     * - VISAEFT (166) - Visa Electronic Funds Transfer
     * - MCEFT (88) - MasterCard EFT
     * - PAYPAL (78) - PayPal
     * - SC (72) - Unknown (Stock Certificate?)
     * - ACHEFT (34) - ACH EFT
     * - ACH (27) - ACH
     * - DISCOVEREFT (13) - Discover EFT
     * - SN (13) - Unknown
     * - AMEXEFT (9) - American Express EFT
     * - CA (6) - Cash
     */
    public function run(): void
    {
        $mappings = [
            // Common DonorPerfect payment_type values
            ['dp_payment_type' => 'Check', 'intacct_payment_method' => 'Printed Check', 'order' => 1],
            ['dp_payment_type' => 'CHK', 'intacct_payment_method' => 'Printed Check', 'order' => 2],
            ['dp_payment_type' => 'Cash', 'intacct_payment_method' => 'Cash', 'order' => 3],
            ['dp_payment_type' => 'CASH', 'intacct_payment_method' => 'Cash', 'order' => 4],
            ['dp_payment_type' => 'Credit Card', 'intacct_payment_method' => 'Credit Card', 'order' => 5],
            ['dp_payment_type' => 'CC', 'intacct_payment_method' => 'Credit Card', 'order' => 6],
            ['dp_payment_type' => 'Visa', 'intacct_payment_method' => 'Credit Card', 'order' => 7],
            ['dp_payment_type' => 'visa', 'intacct_payment_method' => 'Credit Card', 'order' => 8],
            ['dp_payment_type' => 'MasterCard', 'intacct_payment_method' => 'Credit Card', 'order' => 9],
            ['dp_payment_type' => 'ACH', 'intacct_payment_method' => 'EFT', 'order' => 10],
            ['dp_payment_type' => 'EFT', 'intacct_payment_method' => 'EFT', 'order' => 11],
            ['dp_payment_type' => 'Wire Transfer', 'intacct_payment_method' => 'EFT', 'order' => 12],
            ['dp_payment_type' => 'Bank Transfer', 'intacct_payment_method' => 'EFT', 'order' => 13],

            // Common DonorPerfect gift_type codes (used as fallback when payment_type is empty)
            ['dp_payment_type' => 'CK', 'intacct_payment_method' => 'Printed Check', 'order' => 20], // Check gift type
            ['dp_payment_type' => 'CA', 'intacct_payment_method' => 'Cash', 'order' => 21], // Cash gift type
            ['dp_payment_type' => 'MO', 'intacct_payment_method' => 'Cash', 'order' => 22], // Money Order
            ['dp_payment_type' => 'GK', 'intacct_payment_method' => 'Cash', 'order' => 23], // Gift in Kind (treat as cash)
            ['dp_payment_type' => 'ST', 'intacct_payment_method' => 'Cash', 'order' => 24], // Stock (treat as cash)

            // Credit Card gift_type codes (electronic payments)
            ['dp_payment_type' => 'CC', 'intacct_payment_method' => 'Credit Card', 'order' => 30], // Credit Card gift type
            ['dp_payment_type' => 'VISAEFT', 'intacct_payment_method' => 'Credit Card', 'order' => 31], // Visa EFT
            ['dp_payment_type' => 'MCEFT', 'intacct_payment_method' => 'Credit Card', 'order' => 32], // MasterCard EFT
            ['dp_payment_type' => 'AMEXEFT', 'intacct_payment_method' => 'Credit Card', 'order' => 33], // American Express EFT
            ['dp_payment_type' => 'DISCOVEREFT', 'intacct_payment_method' => 'Credit Card', 'order' => 34], // Discover EFT
            ['dp_payment_type' => 'WEB', 'intacct_payment_method' => 'Credit Card', 'order' => 35], // Web donation (typically CC)

            // EFT / Electronic payment types
            ['dp_payment_type' => 'ACHEFT', 'intacct_payment_method' => 'EFT', 'order' => 36], // ACH Electronic Funds Transfer
            ['dp_payment_type' => 'PAYPAL', 'intacct_payment_method' => 'EFT', 'order' => 37], // PayPal

            // Unknown types - need investigation but map conservatively to Cash for now
            ['dp_payment_type' => 'SC', 'intacct_payment_method' => 'Cash', 'order' => 50], // SC - Unknown, possibly Stock Certificate
            ['dp_payment_type' => 'SN', 'intacct_payment_method' => 'Cash', 'order' => 51], // SN - Unknown

            // Default fallback (NULL means any unmatched value)
            ['dp_payment_type' => null, 'intacct_payment_method' => 'Cash', 'is_default' => true, 'order' => 999],
        ];

        foreach ($mappings as $mapping) {
            \App\Models\DonorPerfectPaymentMethodMapping::updateOrCreate(
                ['dp_payment_type' => $mapping['dp_payment_type']],
                array_merge($mapping, ['is_active' => true])
            );
        }

        $this->command->info('Payment method mappings seeded successfully.');
    }
}
