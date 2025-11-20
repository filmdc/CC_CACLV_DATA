<?php

namespace Database\Seeders;

use App\Models\SageIntacctSyncFilter;
use Illuminate\Database\Seeder;

class SageIntacctSyncFilterSeeder extends Seeder
{
    /**
     * Seed default sync filters for Sage Intacct
     *
     * These filters exclude non-monetary gift types that should not create
     * Other Receipts in Sage Intacct (soft credits, notifications, in-kind
     * donations, declined transactions, etc.)
     */
    public function run(): void
    {
        // Clear existing filters
        SageIntacctSyncFilter::truncate();

        $filters = [
            // ================================================================
            // NON-MONETARY / RECOGNITION ONLY
            // ================================================================
            [
                'name' => 'Exclude Soft Credits',
                'field_name' => 'gift_type',
                'operator' => 'equals',
                'value' => 'SC',
                'action' => 'exclude',
                'filter_group' => 1,
                'group_logic' => 'OR',
                'is_active' => true,
                'priority' => 100,
                'description' => 'Soft credits are recognition only with no actual money transfer - should not create receipts in Sage Intacct',
            ],
            [
                'name' => 'Exclude Send Notifications',
                'field_name' => 'gift_type',
                'operator' => 'equals',
                'value' => 'SN',
                'action' => 'exclude',
                'filter_group' => 1,
                'group_logic' => 'OR',
                'is_active' => true,
                'priority' => 99,
                'description' => 'Send notification records are just notifications, not actual donations',
            ],
            [
                'name' => 'Exclude Declined Credit Card Donations',
                'field_name' => 'gift_type',
                'operator' => 'equals',
                'value' => 'DECLINECREDITCARDDONATION',
                'action' => 'exclude',
                'filter_group' => 1,
                'group_logic' => 'OR',
                'is_active' => true,
                'priority' => 98,
                'description' => 'Declined credit card transactions did not result in received funds',
            ],

            // ================================================================
            // IN-KIND / NON-CASH DONATIONS
            // ================================================================
            [
                'name' => 'Exclude In-Kind Donations',
                'field_name' => 'gift_type',
                'operator' => 'equals',
                'value' => 'IK',
                'action' => 'exclude',
                'filter_group' => 2,
                'group_logic' => 'OR',
                'is_active' => true,
                'priority' => 90,
                'description' => 'In-kind donations are goods/services, not cash receipts - require different accounting treatment',
            ],
            [
                'name' => 'Exclude Amazon In-Kind',
                'field_name' => 'gift_type',
                'operator' => 'equals',
                'value' => 'AMAZONIN-KIND',
                'action' => 'exclude',
                'filter_group' => 2,
                'group_logic' => 'OR',
                'is_active' => true,
                'priority' => 89,
                'description' => 'Amazon in-kind donations are donated items, not cash transactions',
            ],
            [
                'name' => 'Exclude Gift Cards',
                'field_name' => 'gift_type',
                'operator' => 'equals',
                'value' => 'GC',
                'action' => 'exclude',
                'filter_group' => 2,
                'group_logic' => 'OR',
                'is_active' => true,
                'priority' => 88,
                'description' => 'Gift cards are prepaid instruments requiring different accounting treatment',
            ],
            [
                'name' => 'Exclude Gift Card (alternate code)',
                'field_name' => 'gift_type',
                'operator' => 'equals',
                'value' => 'GIFTCARD',
                'action' => 'exclude',
                'filter_group' => 2,
                'group_logic' => 'OR',
                'is_active' => true,
                'priority' => 87,
                'description' => 'Gift cards are prepaid instruments requiring different accounting treatment',
            ],

            // ================================================================
            // SECURITIES / PROPERTY
            // ================================================================
            [
                'name' => 'Exclude Stock Donations',
                'field_name' => 'gift_type',
                'operator' => 'equals',
                'value' => 'ST',
                'action' => 'exclude',
                'filter_group' => 3,
                'group_logic' => 'OR',
                'is_active' => true,
                'priority' => 80,
                'description' => 'Stock donations are securities, not cash - require special accounting treatment and timing',
            ],
            [
                'name' => 'Exclude Real Estate',
                'field_name' => 'gift_type',
                'operator' => 'equals',
                'value' => 'RE',
                'action' => 'exclude',
                'filter_group' => 3,
                'group_logic' => 'OR',
                'is_active' => true,
                'priority' => 79,
                'description' => 'Real estate donations are property transfers, not cash receipts',
            ],

            // ================================================================
            // COMPLEX / ESTATE PLANNING
            // ================================================================
            [
                'name' => 'Exclude Bequests',
                'field_name' => 'gift_type',
                'operator' => 'equals',
                'value' => 'BEQUEST',
                'action' => 'exclude',
                'filter_group' => 4,
                'group_logic' => 'OR',
                'is_active' => true,
                'priority' => 70,
                'description' => 'Bequests are estate gifts requiring complex accounting - often non-cash and multi-year',
            ],
            [
                'name' => 'Exclude Charitable Trusts',
                'field_name' => 'gift_type',
                'operator' => 'equals',
                'value' => 'CHARITABLETRST',
                'action' => 'exclude',
                'filter_group' => 4,
                'group_logic' => 'OR',
                'is_active' => true,
                'priority' => 69,
                'description' => 'Charitable trusts are complex planned giving vehicles, not simple cash receipts',
            ],
        ];

        foreach ($filters as $filterData) {
            SageIntacctSyncFilter::create($filterData);
        }

        $this->command->info('Sage Intacct sync filters seeded successfully!');
        $this->command->info('Total filters created: '.SageIntacctSyncFilter::count());
    }
}
