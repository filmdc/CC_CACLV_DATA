<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ApprovalPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Note: Approval and PDF template permissions are now managed by
     * ProjectHousingPermissionsSeeder for centralized permission management.
     * This seeder is kept for backwards compatibility but no longer creates
     * permissions or roles.
     */
    public function run(): void
    {
        $this->command->info('Approval permissions are now managed by ProjectHousingPermissionsSeeder.');
    }
}
