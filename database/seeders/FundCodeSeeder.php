<?php

namespace Database\Seeders;

use App\Models\FundCode;
use Illuminate\Database\Seeder;

class FundCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fundCodes = [
            ['code' => 'F7000', 'description' => 'WHR'],
            ['code' => 'F0531', 'description' => 'Allentown Better Homes'],
            ['code' => 'F4205', 'description' => 'LC Urgent need roof & HVAC (CDBG-CV)'],
            ['code' => 'F4206', 'description' => 'LC Urgent need roof & HVAC (CDBG)'],
            ['code' => 'F0621', 'description' => 'PHARE Healthy Homes'],
            ['code' => 'F0530', 'description' => '136 S. 8th St.'],
        ];

        foreach ($fundCodes as $fundCode) {
            FundCode::firstOrCreate(
                ['code' => $fundCode['code']],
                [
                    'description' => $fundCode['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Initial fund codes created.');
    }
}
