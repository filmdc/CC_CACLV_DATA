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
            // ================================================================
            // CREDIT CARD TYPES → Credit Card
            // ================================================================
            ['dp_payment_type' => 'Credit Card', 'intacct_payment_method' => 'Credit Card', 'order' => 1],
            ['dp_payment_type' => 'CC', 'intacct_payment_method' => 'Credit Card', 'order' => 2],
            ['dp_payment_type' => 'CCEFT', 'intacct_payment_method' => 'Credit Card', 'order' => 3],
            ['dp_payment_type' => 'CI', 'intacct_payment_method' => 'Credit Card', 'order' => 4],
            ['dp_payment_type' => 'VISAEFT', 'intacct_payment_method' => 'Credit Card', 'order' => 5],
            ['dp_payment_type' => 'VISAIC', 'intacct_payment_method' => 'Credit Card', 'order' => 6],
            ['dp_payment_type' => 'Visa', 'intacct_payment_method' => 'Credit Card', 'order' => 7],
            ['dp_payment_type' => 'visa', 'intacct_payment_method' => 'Credit Card', 'order' => 8],
            ['dp_payment_type' => 'MCEFT', 'intacct_payment_method' => 'Credit Card', 'order' => 9],
            ['dp_payment_type' => 'MCIC', 'intacct_payment_method' => 'Credit Card', 'order' => 10],
            ['dp_payment_type' => 'MasterCard', 'intacct_payment_method' => 'Credit Card', 'order' => 11],
            ['dp_payment_type' => 'AMEXEFT', 'intacct_payment_method' => 'Credit Card', 'order' => 12],
            ['dp_payment_type' => 'AMEXIC', 'intacct_payment_method' => 'Credit Card', 'order' => 13],
            ['dp_payment_type' => 'DISCOVEREFT', 'intacct_payment_method' => 'Credit Card', 'order' => 14],
            ['dp_payment_type' => 'DISCOVERIC', 'intacct_payment_method' => 'Credit Card', 'order' => 15],
            ['dp_payment_type' => 'WEB', 'intacct_payment_method' => 'Credit Card', 'order' => 16],
            ['dp_payment_type' => 'TEXT', 'intacct_payment_method' => 'Credit Card', 'order' => 17],

            // ================================================================
            // CHECK TYPES → Printed Check
            // ================================================================
            ['dp_payment_type' => 'Check', 'intacct_payment_method' => 'Printed Check', 'order' => 20],
            ['dp_payment_type' => 'CHK', 'intacct_payment_method' => 'Printed Check', 'order' => 21],
            ['dp_payment_type' => 'CK', 'intacct_payment_method' => 'Printed Check', 'order' => 22],
            ['dp_payment_type' => 'CKP', 'intacct_payment_method' => 'Printed Check', 'order' => 23],
            ['dp_payment_type' => 'CKO', 'intacct_payment_method' => 'Printed Check', 'order' => 24],
            ['dp_payment_type' => 'CKCSR', 'intacct_payment_method' => 'Printed Check', 'order' => 25],
            ['dp_payment_type' => 'SPLITCHECKANDCASH', 'intacct_payment_method' => 'Printed Check', 'order' => 26],

            // ================================================================
            // CASH TYPES → Cash
            // ================================================================
            ['dp_payment_type' => 'Cash', 'intacct_payment_method' => 'Cash', 'order' => 30],
            ['dp_payment_type' => 'CASH', 'intacct_payment_method' => 'Cash', 'order' => 31],
            ['dp_payment_type' => 'CA', 'intacct_payment_method' => 'Cash', 'order' => 32],
            ['dp_payment_type' => 'MO', 'intacct_payment_method' => 'Cash', 'order' => 33],

            // ================================================================
            // EFT / ACH TYPES → EFT
            // ================================================================
            ['dp_payment_type' => 'ACH', 'intacct_payment_method' => 'EFT', 'order' => 40],
            ['dp_payment_type' => 'ACHEFT', 'intacct_payment_method' => 'EFT', 'order' => 41],
            ['dp_payment_type' => 'AK', 'intacct_payment_method' => 'EFT', 'order' => 42],
            ['dp_payment_type' => 'EFT', 'intacct_payment_method' => 'EFT', 'order' => 43],
            ['dp_payment_type' => 'Wire Transfer', 'intacct_payment_method' => 'EFT', 'order' => 44],
            ['dp_payment_type' => 'Bank Transfer', 'intacct_payment_method' => 'EFT', 'order' => 45],
            ['dp_payment_type' => 'PAYPAL', 'intacct_payment_method' => 'EFT', 'order' => 46],
            ['dp_payment_type' => 'BUSINESS_CHECKING', 'intacct_payment_method' => 'EFT', 'order' => 47],
            ['dp_payment_type' => 'BUSINESS_SAVINGS', 'intacct_payment_method' => 'EFT', 'order' => 48],
            ['dp_payment_type' => 'CHECKING', 'intacct_payment_method' => 'EFT', 'order' => 49],
            ['dp_payment_type' => 'PERSONAL_CHECKING', 'intacct_payment_method' => 'EFT', 'order' => 50],
            ['dp_payment_type' => 'PERSONAL_SAVINGS', 'intacct_payment_method' => 'EFT', 'order' => 51],
            ['dp_payment_type' => 'SAVINGS', 'intacct_payment_method' => 'EFT', 'order' => 52],

            // ================================================================
            // INSTITUTIONAL / GRANT TYPES → Printed Check
            // (Typically received as checks from organizations)
            // ================================================================
            ['dp_payment_type' => 'GRANT', 'intacct_payment_method' => 'Printed Check', 'order' => 60],
            ['dp_payment_type' => 'GRT', 'intacct_payment_method' => 'Printed Check', 'order' => 61],
            ['dp_payment_type' => 'FDN', 'intacct_payment_method' => 'Printed Check', 'order' => 62],
            ['dp_payment_type' => 'UW', 'intacct_payment_method' => 'Printed Check', 'order' => 63],
            ['dp_payment_type' => 'UWPD', 'intacct_payment_method' => 'EFT', 'order' => 64],
            ['dp_payment_type' => 'MG', 'intacct_payment_method' => 'Printed Check', 'order' => 65],
            ['dp_payment_type' => 'DAF', 'intacct_payment_method' => 'Printed Check', 'order' => 66],
            ['dp_payment_type' => 'DONRADVFUND', 'intacct_payment_method' => 'Printed Check', 'order' => 67],
            ['dp_payment_type' => 'CHARITABLEGF', 'intacct_payment_method' => 'Printed Check', 'order' => 68],
            ['dp_payment_type' => 'IRA', 'intacct_payment_method' => 'Printed Check', 'order' => 69],
            ['dp_payment_type' => 'EITC', 'intacct_payment_method' => 'Printed Check', 'order' => 70],
            ['dp_payment_type' => 'NPP', 'intacct_payment_method' => 'Printed Check', 'order' => 71],

            // ================================================================
            // SPECIAL / UNKNOWN TYPES → Cash
            // (Conservative mapping for gift types that appear in data)
            // ================================================================
            ['dp_payment_type' => 'SHFB', 'intacct_payment_method' => 'Cash', 'order' => 80],
            ['dp_payment_type' => 'WS', 'intacct_payment_method' => 'Cash', 'order' => 81],

            // ================================================================
            // NON-MONETARY TYPES (Should be filtered out, but mapped as fallback)
            // These gift types represent recognition, notifications, or non-cash assets
            // that should NOT sync to Sage Intacct. Default filters will exclude them.
            // ================================================================
            ['dp_payment_type' => 'SC', 'intacct_payment_method' => 'Cash', 'order' => 90],
            ['dp_payment_type' => 'SN', 'intacct_payment_method' => 'Cash', 'order' => 91],
            ['dp_payment_type' => 'DECLINECREDITCARDDONATION', 'intacct_payment_method' => 'Cash', 'order' => 92],
            ['dp_payment_type' => 'IK', 'intacct_payment_method' => 'Cash', 'order' => 93],
            ['dp_payment_type' => 'AMAZONIN-KIND', 'intacct_payment_method' => 'Cash', 'order' => 94],
            ['dp_payment_type' => 'ST', 'intacct_payment_method' => 'Cash', 'order' => 95],
            ['dp_payment_type' => 'RE', 'intacct_payment_method' => 'Cash', 'order' => 96],
            ['dp_payment_type' => 'BEQUEST', 'intacct_payment_method' => 'Cash', 'order' => 97],
            ['dp_payment_type' => 'GC', 'intacct_payment_method' => 'Cash', 'order' => 98],
            ['dp_payment_type' => 'GIFTCARD', 'intacct_payment_method' => 'Cash', 'order' => 99],
            ['dp_payment_type' => 'CHARITABLETRST', 'intacct_payment_method' => 'Cash', 'order' => 100],

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
