<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Comment out default seeding - we'll use InitialSetupSeeder instead
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            InitialSetupSeeder::class,
            PovertyGuidelinesSeeder::class,
            ReferenceDataSeeder::class,
            ProjectHousingPermissionsSeeder::class, // Consolidated permissions seeder - must run before HousingModuleSeeder
            HousingModuleSeeder::class,
            ApprovalPermissionsSeeder::class, // Now just a stub for backwards compatibility
            ApprovalTypesSeeder::class,
            DonorPerfectSeeder::class,
            PaymentMethodMappingSeeder::class,
            ConditionalCodeMappingSeeder::class,
            SageIntacctSyncFilterSeeder::class,
        ]);
    }
}
