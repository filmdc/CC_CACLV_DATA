<?php

namespace Database\Seeders;

use App\Models\SageIntacctSyncFilter;
use Illuminate\Database\Seeder;

class ProductionSyncFiltersSeeder extends Seeder
{
    /**
     * Seed production Sage Intacct sync filters.
     *
     * Sync filters define rules for excluding or including gifts during sync.
     * These prevent problematic gifts from being synced to Sage Intacct.
     *
     * Generated from production database on: 2025-11-21
     * Total filters: 12
     */
    public function run(): void
    {
        $filters = [
            ['name' => 'Exclude Soft Credits', 'field_name' => 'gift_type', 'operator' => 'equals', 'value' => 'SC', 'action' => 'exclude', 'filter_group' => 1, 'group_logic' => 'OR', 'priority' => 100, 'description' => 'Soft credits are recognition only with no actual money transfer - should not create receipts in Sage Intacct', 'is_active' => true],
            ['name' => 'Exclude Zero Amount Gifts', 'field_name' => 'amount', 'operator' => 'equals', 'value' => '0', 'action' => 'exclude', 'filter_group' => 0, 'group_logic' => 'OR', 'priority' => 100, 'description' => 'Automatically exclude gifts with $0 amount from Sage Intacct sync (Sage rejects zero-value receipts)', 'is_active' => true],
            ['name' => 'Exclude Send Notifications', 'field_name' => 'gift_type', 'operator' => 'equals', 'value' => 'SN', 'action' => 'exclude', 'filter_group' => 1, 'group_logic' => 'OR', 'priority' => 99, 'description' => 'Send notification records are just notifications, not actual donations', 'is_active' => true],
            ['name' => 'Exclude Declined Credit Card Donations', 'field_name' => 'gift_type', 'operator' => 'equals', 'value' => 'DECLINECREDITCARDDONATION', 'action' => 'exclude', 'filter_group' => 1, 'group_logic' => 'OR', 'priority' => 98, 'description' => 'Declined credit card transactions did not result in received funds', 'is_active' => true],
            ['name' => 'Exclude In-Kind Donations', 'field_name' => 'gift_type', 'operator' => 'equals', 'value' => 'IK', 'action' => 'exclude', 'filter_group' => 2, 'group_logic' => 'OR', 'priority' => 90, 'description' => 'In-kind donations are goods/services, not cash receipts - require different accounting treatment', 'is_active' => true],
            ['name' => 'Exclude Amazon In-Kind', 'field_name' => 'gift_type', 'operator' => 'equals', 'value' => 'AMAZONIN-KIND', 'action' => 'exclude', 'filter_group' => 2, 'group_logic' => 'OR', 'priority' => 89, 'description' => 'Amazon in-kind donations are donated items, not cash transactions', 'is_active' => true],
            ['name' => 'Exclude Gift Cards', 'field_name' => 'gift_type', 'operator' => 'equals', 'value' => 'GC', 'action' => 'exclude', 'filter_group' => 2, 'group_logic' => 'OR', 'priority' => 88, 'description' => 'Gift cards are prepaid instruments requiring different accounting treatment', 'is_active' => true],
            ['name' => 'Exclude Gift Card (alternate code)', 'field_name' => 'gift_type', 'operator' => 'equals', 'value' => 'GIFTCARD', 'action' => 'exclude', 'filter_group' => 2, 'group_logic' => 'OR', 'priority' => 87, 'description' => 'Gift cards are prepaid instruments requiring different accounting treatment', 'is_active' => true],
            ['name' => 'Exclude Stock Donations', 'field_name' => 'gift_type', 'operator' => 'equals', 'value' => 'ST', 'action' => 'exclude', 'filter_group' => 3, 'group_logic' => 'OR', 'priority' => 80, 'description' => 'Stock donations are securities, not cash - require special accounting treatment and timing', 'is_active' => true],
            ['name' => 'Exclude Real Estate', 'field_name' => 'gift_type', 'operator' => 'equals', 'value' => 'RE', 'action' => 'exclude', 'filter_group' => 3, 'group_logic' => 'OR', 'priority' => 79, 'description' => 'Real estate donations are property transfers, not cash receipts', 'is_active' => true],
            ['name' => 'Exclude Bequests', 'field_name' => 'gift_type', 'operator' => 'equals', 'value' => 'BEQUEST', 'action' => 'exclude', 'filter_group' => 4, 'group_logic' => 'OR', 'priority' => 70, 'description' => 'Bequests are estate gifts requiring complex accounting - often non-cash and multi-year', 'is_active' => true],
            ['name' => 'Exclude Charitable Trusts', 'field_name' => 'gift_type', 'operator' => 'equals', 'value' => 'CHARITABLETRST', 'action' => 'exclude', 'filter_group' => 4, 'group_logic' => 'OR', 'priority' => 69, 'description' => 'Charitable trusts are complex planned giving vehicles, not simple cash receipts', 'is_active' => true],
        ];

        $this->command->info("Seeding " . count($filters) . " sync filters...");

        foreach ($filters as $filter) {
            SageIntacctSyncFilter::updateOrCreate(
                [
                    'name' => $filter['name'],
                ],
                [
                    'field_name' => $filter['field_name'],
                    'operator' => $filter['operator'],
                    'value' => $filter['value'],
                    'action' => $filter['action'],
                    'filter_group' => $filter['filter_group'],
                    'group_logic' => $filter['group_logic'],
                    'priority' => $filter['priority'],
                    'description' => $filter['description'],
                    'is_active' => $filter['is_active'],
                ]
            );
        }

        $this->command->info("✓ Sync filters seeded successfully");

        // Show active vs inactive
        $activeCount = array_filter($filters, fn($f) => $f['is_active'])?count(array_filter($filters, fn($f) => $f['is_active'])):0;
        $this->command->info("  - Active: {$activeCount} filters");
        $this->command->info("  - Inactive: " . (count($filters) - $activeCount) . " filters");
    }
}
