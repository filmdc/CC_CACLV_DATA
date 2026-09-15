<?php

namespace Database\Seeders;

use App\Models\PovertyGuideline;
use Illuminate\Database\Seeder;

/**
 * HUD HOME Program income limits — the housing projects module's own
 * income qualification dataset, SEPARATE from the HHS federal poverty
 * guidelines the rest of the app measures against.
 *
 * Source: U.S. Department of HUD, "FY2026 Adjusted HOME Income Limits",
 * State: Pennsylvania — Allentown-Bethlehem-Easton, PA HUD Metro FMR
 * Area, "LOW INCOME" (80% of area median) row. Effective June 1, 2026.
 *
 * That row is the MAXIMUM a household can earn and still be eligible
 * for CACLV's housing programs, so it is seeded as the poverty_100
 * column: the income qualification tool reads "at or under 100%" as
 * eligible, and the percent readout, brackets, and
 * IncomeEligibilityCalculator arithmetic all work unchanged. The other
 * bracket columns are straight multiples of that ceiling (HUD publishes
 * no such tiers), kept only so the admin guideline matrix renders a
 * complete row.
 *
 * Rows live under region PovertyGuideline::REGION_HOME_ABE alongside
 * the existing '48_contiguous' HHS data — nothing here touches the
 * CSBG/FPL determinations. Housing intakes (any log with a housing
 * service attached) are assessed against this dataset automatically —
 * see ManagesIncomeEligibility::incomeGuidelineRegion().
 *
 * Notes:
 *  - HUD publishes annual figures only; the monthly rows here are
 *    derived (annual / 12) for the guidelines matrix. The calculator
 *    always assesses against the annual table.
 *  - Sizes above 8 are derived at read time by
 *    PovertyGuideline::thresholdFor() using HUD's rule (+8% of the
 *    4-person limit per person over 8).
 *  - HUD reissues these limits every year; add the new year's block
 *    and re-run (updateOrCreate keys on year/region/size/period, so
 *    new years append and corrections overwrite in place).
 *
 * Run with: php artisan db:seed --class=HomeIncomeLimitsSeeder
 */
class HomeIncomeLimitsSeeder extends Seeder
{
    /**
     * "LOW INCOME" (80% AMI) limits by household size, annual dollars,
     * straight off the HUD PDF for the Allentown-Bethlehem-Easton, PA
     * HUD Metro FMR Area.
     *
     * @var array<int, array<int, int>> year => [family_size => annual ceiling]
     */
    private const ANNUAL_CEILINGS = [
        2026 => [
            1 => 58450,
            2 => 66800,
            3 => 75150,
            4 => 83500,
            5 => 90200,
            6 => 96900,
            7 => 103550,
            8 => 110250,
        ],
    ];

    public function run(): void
    {
        foreach (self::ANNUAL_CEILINGS as $year => $ceilings) {
            foreach ($ceilings as $familySize => $annualCeiling) {
                $this->seedRow($year, $familySize, 'annual', $annualCeiling);
                $this->seedRow($year, $familySize, 'monthly', round($annualCeiling / 12, 2));
            }
        }
    }

    /**
     * One guideline row: the ceiling lands in poverty_100; every other
     * bracket column is the ceiling scaled by its percentage.
     */
    private function seedRow(int $year, int $familySize, string $period, float $ceiling): void
    {
        $values = [];
        foreach (PovertyGuideline::BRACKETS as $bracket) {
            $values['poverty_'.$bracket] = round($ceiling * $bracket / 100, 2);
        }

        PovertyGuideline::updateOrCreate([
            'year' => $year,
            'region' => PovertyGuideline::REGION_HOME_ABE,
            'family_size' => $familySize,
            'period' => $period,
        ], $values);
    }
}
