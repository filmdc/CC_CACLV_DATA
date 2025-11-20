<?php

namespace Database\Seeders;

use App\Models\SageIntacctSyncFilter;
use Illuminate\Database\Seeder;

class ZeroAmountGiftFilterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update the zero amount filter
        $filter = SageIntacctSyncFilter::firstOrCreate(
            ['name' => 'Exclude Zero Amount Gifts'],
            [
                'field_name' => 'amount',
                'operator' => 'equals',
                'value' => '0',
                'action' => 'exclude',
                'filter_group' => 0,
                'group_logic' => 'OR',
                'priority' => 100,
                'description' => 'Automatically exclude gifts with $0 amount from Sage Intacct sync (Sage rejects zero-value receipts)',
                'is_active' => true,
            ]
        );

        $this->command->info("Filter '{$filter->name}' created/updated.");
        $this->command->info("  - Field: {$filter->field_name}");
        $this->command->info("  - Operator: {$filter->operator}");
        $this->command->info("  - Value: {$filter->value}");
        $this->command->info("  - Action: {$filter->action}");
        $this->command->info("  - Status: " . ($filter->is_active ? 'Active' : 'Inactive'));
    }
}
