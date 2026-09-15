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

    /**
     * HUD's family-size adjustments to an area's 4-person median family
     * income. The published income limits are these same factors applied
     * to the 4-person limit — which is why the seeded FY2026 ceilings
     * are exactly 70/80/90/100/108/116/124/132% of the 4-person figure.
     */
    public const HOME_FAMILY_SIZE_FACTORS = [
        1 => 0.70,
        2 => 0.80,
        3 => 0.90,
        4 => 1.00,
        5 => 1.08,
        6 => 1.16,
        7 => 1.24,
        8 => 1.32,
    ];

    /**
     * The LOW INCOME ceiling as a share of area median income. Used to
     * derive the median when HUD's published figure has not been
     * entered — see homeMedianFamilyIncome().
     */
    public const HOME_CEILING_SHARE = 0.80;

    /**
     * HUD's HOME income bands, as a percent of AREA MEDIAN INCOME. These
     * replace the FPL bracket ladder for the HOME dataset: a housing
     * intake is reported in one of these bands, not at a percent of the
     * federal poverty level.
     */
    public const HOME_TIERS = [30, 50, 60, 80];

    public const HOME_TIER_LABELS = [
        30 => 'Extremely low income',
        50 => 'Very low income',
        60 => 'Low income',
        80 => 'Low income',
    ];

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

    /**
     * Write one HUD HOME ceiling into this table: the annual row and
     * its derived monthly companion, each with every bracket column
     * scaled off the ceiling (HUD publishes no tiers — only the 80% AMI
     * ceiling in poverty_100 is real; the rest exist so the admin
     * matrix renders a complete row).
     *
     * This is the single writer for the HOME dataset. Both
     * HomeIncomeLimitsSeeder and the portal's income-limits editor go
     * through here, so a hand-edit and a re-seed can never disagree
     * about how a row is shaped.
     */
    public static function upsertHomeCeiling(int $year, int $familySize, float $annualCeiling): void
    {
        $periods = [
            'annual' => $annualCeiling,
            'monthly' => round($annualCeiling / 12, 2),
        ];

        foreach ($periods as $period => $ceiling) {
            $values = [];
            foreach (self::BRACKETS as $bracket) {
                $values['poverty_'.$bracket] = round($ceiling * $bracket / 100, 2);
            }

            static::updateOrCreate([
                'year' => $year,
                'region' => self::REGION_HOME_ABE,
                'family_size' => $familySize,
                'period' => $period,
            ], $values);
        }
    }

    /**
     * The published HOME annual ceilings for a year, keyed by family
     * size. Empty when the year has not been loaded yet.
     *
     * @return array<int, float>
     */
    public static function homeCeilings(int $year): array
    {
        return static::where('region', self::REGION_HOME_ABE)
            ->where('year', $year)
            ->where('period', 'annual')
            ->orderBy('family_size')
            ->pluck('poverty_100', 'family_size')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Every year the HOME dataset carries, newest first.
     *
     * @return array<int, int>
     */
    public static function homeYears(): array
    {
        return static::where('region', self::REGION_HOME_ABE)
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Where a year's published 4-person median family income is stored.
     * HUD prints it on the income-limits summary next to the limits.
     */
    public static function homeMfiSettingKey(int $year): string
    {
        return 'home_ami_mfi.'.$year;
    }

    /**
     * The area's 4-person MEDIAN family income for a HOME year.
     *
     * Prefers the figure someone entered from HUD's table. Falls back to
     * deriving it from the 4-person LOW INCOME ceiling (ceiling ÷ 0.8),
     * which is exact whenever HUD has not capped the 80% limit — and is
     * the same arithmetic a caseworker does by hand. Null when the year
     * carries no 4-person ceiling to derive from.
     */
    public static function homeMedianFamilyIncome(int $year): ?float
    {
        $stored = SiteSetting::get(self::homeMfiSettingKey($year));

        if ($stored !== null && $stored !== '' && is_numeric($stored) && (float) $stored > 0) {
            return (float) $stored;
        }

        $ceilings = self::homeCeilings($year);

        if (! isset($ceilings[4])) {
            return null;
        }

        return self::deriveHomeMfi($ceilings[4]);
    }

    /**
     * True when no published MFI has been entered and the figure above
     * is derived — the screen says so, because HUD caps the 80% limit in
     * some areas and the derivation silently drifts when it does.
     */
    public static function homeMfiIsDerived(int $year): bool
    {
        $stored = SiteSetting::get(self::homeMfiSettingKey($year));

        return ! ($stored !== null && $stored !== '' && is_numeric($stored) && (float) $stored > 0);
    }

    /** Store HUD's published 4-person MFI, or null to go back to deriving it. */
    public static function setHomeMedianFamilyIncome(int $year, ?float $mfi): void
    {
        SiteSetting::set(self::homeMfiSettingKey($year), $mfi === null ? '' : (string) round($mfi, 2));
    }

    /**
     * Area median income for a household size — the number a HOME
     * percentage is a percentage OF.
     *
     * HUD adjusts the 4-person median by family size; above 8 it adds 8%
     * of the 4-person figure per extra person, the same rule
     * thresholdFor() applies to the ceilings.
     */
    public static function homeAmiFor($familySize, int $year): ?float
    {
        $familySize = (int) $familySize;

        if ($familySize < 1) {
            return null;
        }

        $mfi = self::homeMedianFamilyIncome($year);

        return $mfi === null ? null : self::homeAmiFromMfi($mfi, $familySize);
    }

    /**
     * Same family-size adjustment, from an MFI held in hand rather than
     * read from storage — so an editor can preview the AMI for figures
     * that have not been saved yet without re-implementing the rule.
     */
    public static function homeAmiFromMfi(float $mfi, $familySize): ?float
    {
        $familySize = (int) $familySize;

        if ($familySize < 1 || $mfi <= 0) {
            return null;
        }

        if (isset(self::HOME_FAMILY_SIZE_FACTORS[$familySize])) {
            return round($mfi * self::HOME_FAMILY_SIZE_FACTORS[$familySize], 2);
        }

        $largest = max(array_keys(self::HOME_FAMILY_SIZE_FACTORS));

        return round(
            $mfi * self::HOME_FAMILY_SIZE_FACTORS[$largest]
                + ($mfi * self::HOME_INCREMENT_OVER_8 * ($familySize - $largest)),
            2
        );
    }

    /** The median a 4-person LOW INCOME ceiling implies (ceiling ÷ 0.8). */
    public static function deriveHomeMfi(float $fourPersonCeiling): ?float
    {
        return $fourPersonCeiling > 0
            ? round($fourPersonCeiling / self::HOME_CEILING_SHARE, 2)
            : null;
    }

    /**
     * The HOME band a percent-of-AMI lands in (24% → 30, "extremely low
     * income"). Null above 80%, which is over the program's ceiling.
     */
    public static function homeTierFor($percentage): ?int
    {
        if ($percentage === null) {
            return null;
        }

        foreach (self::HOME_TIERS as $tier) {
            if ($percentage <= $tier) {
                return $tier;
            }
        }

        return null;
    }

    public static function homeTierLabel(?int $tier): ?string
    {
        return $tier === null ? null : (self::HOME_TIER_LABELS[$tier] ?? null);
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
