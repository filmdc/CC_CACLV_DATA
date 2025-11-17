<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mappings = [
            // Common DonorPerfect payment type variations
            ['dp_payment_type' => 'Check', 'intacct_payment_method' => 'Printed Check', 'order' => 1],
            ['dp_payment_type' => 'CHK', 'intacct_payment_method' => 'Printed Check', 'order' => 2],
            ['dp_payment_type' => 'Cash', 'intacct_payment_method' => 'Cash', 'order' => 3],
            ['dp_payment_type' => 'CASH', 'intacct_payment_method' => 'Cash', 'order' => 4],
            ['dp_payment_type' => 'Credit Card', 'intacct_payment_method' => 'Credit Card', 'order' => 5],
            ['dp_payment_type' => 'CC', 'intacct_payment_method' => 'Credit Card', 'order' => 6],
            ['dp_payment_type' => 'Visa', 'intacct_payment_method' => 'Credit Card', 'order' => 7],
            ['dp_payment_type' => 'MasterCard', 'intacct_payment_method' => 'Credit Card', 'order' => 8],
            ['dp_payment_type' => 'ACH', 'intacct_payment_method' => 'EFT', 'order' => 9],
            ['dp_payment_type' => 'EFT', 'intacct_payment_method' => 'EFT', 'order' => 10],
            ['dp_payment_type' => 'Wire Transfer', 'intacct_payment_method' => 'EFT', 'order' => 11],
            ['dp_payment_type' => 'Bank Transfer', 'intacct_payment_method' => 'EFT', 'order' => 12],

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
