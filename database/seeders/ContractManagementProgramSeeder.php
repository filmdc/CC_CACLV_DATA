<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ContractManagementProgramSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create contract management only programs
        $contractPrograms = [
            [
                'name' => 'Multi',
                'description' => 'Multi-program funding contract for various initiatives',
                'contract_management_only' => true,
                'active' => true,
            ],
            [
                'name' => 'Administrative',
                'description' => 'Administrative funding and operational contracts',
                'contract_management_only' => true,
                'active' => true,
            ],
            [
                'name' => 'Infrastructure',
                'description' => 'Infrastructure and facility management contracts',
                'contract_management_only' => true,
                'active' => true,
            ],
            [
                'name' => 'General Fund',
                'description' => 'General funding pool for multiple program areas',
                'contract_management_only' => true,
                'active' => true,
            ],
        ];

        foreach ($contractPrograms as $programData) {
            Program::firstOrCreate(
                ['name' => $programData['name']],
                $programData
            );
        }

        $this->command->info('Contract management programs created successfully.');
    }
}