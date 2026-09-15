<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PovertyGuideline extends Model
{
    /**
     * The percent-of-poverty columns this table carries, low to high.
     * Used to place an income at the nearest published bracket.
     */
    public const BRACKETS = [50, 75, 100, 125, 130, 150, 200, 250, 300, 400, 500];

    /**
     * HHS publishes a flat per-person increment above family size 8;
     * the seeded table only goes to 4, so anything larger is derived.
     * 2025 guidelines: $4,480 annually / $374 monthly per extra person.
     */
    public const INCREMENT_ANNUAL = 4480;

    public const INCREMENT_MONTHLY = 374;

    /**
     * HUD HOME Program income limits for the Allentown-Bethlehem-Easton,
     * PA HUD Metro FMR Area — the housing projects module's own income
     * qualification dataset, kept in this same table under its own
     * region key (seeded by HomeIncomeLimitsSeeder). The LOW INCOME
     * (80% of area median) ceiling sits in poverty_100, so at-or-under
     * 100% reads as income-eligible for CACLV's housing programs.
     */
    public const REGION_HOME_ABE = 'home_abe_metro';

    /**
     * HUD's family-size adjustment above 8 people: each additional
     * person adds 8% of the area's 4-person limit.
     */
    public const HOME_INCREMENT_OVER_8 = 0.08;

    protected $fillable = [
        'year',
        'region',
        'family_size',
        'poverty_50',
        'poverty_75',
        'poverty_100',
        'poverty_125',
        'poverty_130',
        'poverty_150',
        'poverty_200',
        'poverty_250',
        'poverty_300',
        'poverty_400',
        'poverty_500',
        'period',
    ];

    protected $casts = [
        'year' => 'integer',
        'family_size' => 'integer',
        'poverty_50' => 'decimal:2',
        'poverty_75' => 'decimal:2',
        'poverty_100' => 'decimal:2',
        'poverty_125' => 'decimal:2',
        'poverty_130' => 'decimal:2',
        'poverty_150' => 'decimal:2',
        'poverty_200' => 'decimal:2',
        'poverty_250' => 'decimal:2',
        'poverty_300' => 'decimal:2',
        'poverty_400' => 'decimal:2',
        'poverty_500' => 'decimal:2',
    ];

    /**
     * The 100% level for a family size, or null when no guideline is
     * published for that year/region.
     *
     * Family sizes larger than the seeded table are derived using the
     * dataset's own rule: HHS guidelines add the flat per-person
     * increment above size 4 (the same fallback
     * calculatePovertyPercentage() has always applied, lifted out so
     * callers can show the threshold alongside the percentage); the
     * HOME limits add 8% of the 4-person limit per person above 8.
     */
    public static function thresholdFor($familySize, $period = 'annual', $year = null, $region = '48_contiguous')
    {
        $year = $year ?? date('Y');
        $familySize = (int) $familySize;

        $guideline = static::where('year', $year)
            ->where('region', $region)
            ->where('family_size', $familySize)
            ->where('period', $period)
            ->first();

        if ($guideline) {
            return (float) $guideline->poverty_100;
        }

        // Not published at this size — extrapolate per the dataset's
        // own rule. HOME limits: HUD publishes sizes 1-8 and adds 8% of
        // the 4-person limit per person above 8. A missing size 1-8
        // means the year isn't seeded, so stay null ("no guidelines
        // loaded") rather than borrowing the HHS increment.
        if ($region === self::REGION_HOME_ABE) {
            if ($familySize <= 8) {
                return null;
            }

            $size8 = static::where('year', $year)
                ->where('region', $region)
                ->where('family_size', 8)
                ->where('period', $period)
                ->first();
            $size4 = static::where('year', $year)
                ->where('region', $region)
                ->where('family_size', 4)
                ->where('period', $period)
                ->first();

            if ($size8 && $size4) {
                return (float) $size8->poverty_100
                    + round((float) $size4->poverty_100 * self::HOME_INCREMENT_OVER_8 * ($familySize - 8), 2);
            }

            return null;
        }

        // HHS guidelines: flat per-person increment above size 4.
        if ($familySize > 4) {
            $baseLine = static::where('year', $year)
                ->where('region', $region)
                ->where('family_size', 4)
                ->where('period', $period)
                ->first();

            if ($baseLine) {
                $increment = ($period === 'annual') ? self::INCREMENT_ANNUAL : self::INCREMENT_MONTHLY;

                return (float) $baseLine->poverty_100 + ($increment * ($familySize - 4));
            }
        }

        return null;
    }

    /**
     * The lowest published bracket the given percentage falls at or
     * under (e.g. 143% → 150). Null when the income exceeds the highest
     * published bracket.
     */
    public static function bracketFor($percentage)
    {
        if ($percentage === null) {
            return null;
        }

        foreach (self::BRACKETS as $bracket) {
            if ($percentage <= $bracket) {
                return $bracket;
            }
        }

        return null;
    }

    public static function calculatePovertyPercentage($income, $familySize, $period = 'annual', $year = null, $region = '48_contiguous')
    {
        $povertyLevel = static::thresholdFor($familySize, $period, $year, $region);

        if ($povertyLevel === null) {
            return null;
        }

        return $povertyLevel > 0 ? round(($income / $povertyLevel) * 100, 2) : 0;
    }

    /**
     * The most recent guideline year available for a region, so callers
     * can pin a determination to real published data instead of assuming
     * the current calendar year has been loaded yet.
     */
    public static function latestYear($region = '48_contiguous', $period = 'annual')
    {
        return static::where('region', $region)
            ->where('period', $period)
            ->max('year');
    }
}
