<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PovertyGuidelinesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $monthlyData = [
            ['family_size' => 1, 'poverty_50' => 652, 'poverty_75' => 978, 'poverty_100' => 1304, 'poverty_125' => 1630, 'poverty_130' => 1695, 'poverty_150' => 1956, 'poverty_200' => 2608, 'poverty_250' => 3260, 'poverty_300' => 3913, 'poverty_400' => 5217, 'poverty_500' => 6521],
            ['family_size' => 2, 'poverty_50' => 881, 'poverty_75' => 1322, 'poverty_100' => 1763, 'poverty_125' => 2203, 'poverty_130' => 2291, 'poverty_150' => 2644, 'poverty_200' => 3525, 'poverty_250' => 4406, 'poverty_300' => 5288, 'poverty_400' => 7050, 'poverty_500' => 8813],
            ['family_size' => 3, 'poverty_50' => 1110, 'poverty_75' => 1666, 'poverty_100' => 2221, 'poverty_125' => 2776, 'poverty_130' => 2887, 'poverty_150' => 3331, 'poverty_200' => 4442, 'poverty_250' => 5552, 'poverty_300' => 6663, 'poverty_400' => 8883, 'poverty_500' => 11104],
            ['family_size' => 4, 'poverty_50' => 1340, 'poverty_75' => 2009, 'poverty_100' => 2679, 'poverty_125' => 3349, 'poverty_130' => 3483, 'poverty_150' => 4019, 'poverty_200' => 5358, 'poverty_250' => 6698, 'poverty_300' => 8038, 'poverty_400' => 10717, 'poverty_500' => 13396],
        ];

        $annualData = [
            ['family_size' => 1, 'poverty_50' => 7825, 'poverty_75' => 11738, 'poverty_100' => 15650, 'poverty_125' => 19563, 'poverty_130' => 20345, 'poverty_150' => 23475, 'poverty_200' => 31300, 'poverty_250' => 39125, 'poverty_300' => 46950, 'poverty_400' => 62600, 'poverty_500' => 78250],
            ['family_size' => 2, 'poverty_50' => 10575, 'poverty_75' => 15863, 'poverty_100' => 21150, 'poverty_125' => 26438, 'poverty_130' => 27495, 'poverty_150' => 31725, 'poverty_200' => 42300, 'poverty_250' => 52875, 'poverty_300' => 63450, 'poverty_400' => 84600, 'poverty_500' => 105750],
            ['family_size' => 3, 'poverty_50' => 13325, 'poverty_75' => 19988, 'poverty_100' => 26650, 'poverty_125' => 33313, 'poverty_130' => 34645, 'poverty_150' => 39975, 'poverty_200' => 53300, 'poverty_250' => 66625, 'poverty_300' => 79950, 'poverty_400' => 106600, 'poverty_500' => 133250],
            ['family_size' => 4, 'poverty_50' => 16075, 'poverty_75' => 24113, 'poverty_100' => 32150, 'poverty_125' => 40188, 'poverty_130' => 41795, 'poverty_150' => 48225, 'poverty_200' => 64300, 'poverty_250' => 80375, 'poverty_300' => 96450, 'poverty_400' => 128600, 'poverty_500' => 160750],
        ];

        foreach ($monthlyData as $data) {
            \App\Models\PovertyGuideline::updateOrCreate([
                'year' => 2025,
                'region' => '48_contiguous',
                'family_size' => $data['family_size'],
                'period' => 'monthly',
            ], [
                'poverty_50' => $data['poverty_50'],
                'poverty_75' => $data['poverty_75'],
                'poverty_100' => $data['poverty_100'],
                'poverty_125' => $data['poverty_125'],
                'poverty_130' => $data['poverty_130'],
                'poverty_150' => $data['poverty_150'],
                'poverty_200' => $data['poverty_200'],
                'poverty_250' => $data['poverty_250'],
                'poverty_300' => $data['poverty_300'],
                'poverty_400' => $data['poverty_400'],
                'poverty_500' => $data['poverty_500'],
            ]);
        }

        foreach ($annualData as $data) {
            \App\Models\PovertyGuideline::updateOrCreate([
                'year' => 2025,
                'region' => '48_contiguous',
                'family_size' => $data['family_size'],
                'period' => 'annual',
            ], [
                'poverty_50' => $data['poverty_50'],
                'poverty_75' => $data['poverty_75'],
                'poverty_100' => $data['poverty_100'],
                'poverty_125' => $data['poverty_125'],
                'poverty_130' => $data['poverty_130'],
                'poverty_150' => $data['poverty_150'],
                'poverty_200' => $data['poverty_200'],
                'poverty_250' => $data['poverty_250'],
                'poverty_300' => $data['poverty_300'],
                'poverty_400' => $data['poverty_400'],
                'poverty_500' => $data['poverty_500'],
            ]);
        }
    }
}
