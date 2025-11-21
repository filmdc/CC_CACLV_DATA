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
        // Get all sync filters from production database
        $filters = \DB::table('sage_intacct_sync_filters')
            ->orderBy('priority', 'desc')
            ->get();

        $this->command->info("Seeding {$filters->count()} sync filters...");

        foreach ($filters as $filter) {
            SageIntacctSyncFilter::updateOrCreate(
                [
                    'name' => $filter->name,
                ],
                [
                    'field_name' => $filter->field_name,
                    'operator' => $filter->operator,
                    'value' => $filter->value,
                    'action' => $filter->action,
                    'filter_group' => $filter->filter_group,
                    'group_logic' => $filter->group_logic,
                    'priority' => $filter->priority,
                    'description' => $filter->description,
                    'is_active' => $filter->is_active,
                ]
            );
        }

        $this->command->info("✓ Sync filters seeded successfully");

        // Show active vs inactive
        $activeCount = $filters->where('is_active', 1)->count();
        $this->command->info("  - Active: {$activeCount} filters");
        $this->command->info("  - Inactive: " . ($filters->count() - $activeCount) . " filters");
    }
}
