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
     * The 100%-of-poverty level for a family size, or null when no
     * guideline is published for that year/region.
     *
     * Family sizes larger than the seeded table are derived from the
     * size-4 row plus the standard per-person increment — the same
     * fallback calculatePovertyPercentage() has always applied, lifted
     * out so callers can show the threshold alongside the percentage.
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

        // Not published at this size — extrapolate from size 4.
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
