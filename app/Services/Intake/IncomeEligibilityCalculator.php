<?php

namespace App\Services\Intake;

use App\Models\IntakeIncomeAssessment;
use App\Models\PovertyGuideline;

/**
 * Turns what an intake worker types — a lump total or an itemized list
 * of income sources, in monthly or annual dollars — into an annual
 * household income and a percent-of-poverty placement against the
 * HHS guidelines already loaded in `poverty_guidelines`.
 *
 * Deliberately stateless and model-free on the way in so both front
 * doors (the main app's Communications Log viewer and the homes
 * portal's Call detail) can call it for a live preview before anything
 * is persisted, and both get identical numbers.
 *
 * Percent of poverty is always computed against the ANNUAL guideline
 * table. Monthly entry is annualized first (×12) rather than compared
 * to the monthly table, because the monthly rows are rounded to whole
 * dollars and comparing against them drifts by a percent or two.
 */
class IncomeEligibilityCalculator
{
    public const REGION_DEFAULT = '48_contiguous';

    /**
     * Annual household income from either entry style.
     *
     * Itemized lines win when any are filled in — the lump `entered`
     * field is the fallback for "she just knows the total". Amounts are
     * denominated in $period, so monthly entry is multiplied out.
     */
    public function annualTotal(array $sources, $entered, string $period = 'annual'): ?float
    {
        $multiplier = $period === 'monthly' ? 12 : 1;

        $itemized = null;
        foreach ($sources as $line) {
            $amount = $line['amount'] ?? null;
            if ($amount === null || $amount === '' || ! is_numeric($amount)) {
                continue;
            }
            $itemized = ($itemized ?? 0) + (float) $amount;
        }

        if ($itemized !== null) {
            return round($itemized * $multiplier, 2);
        }

        if ($entered === null || $entered === '' || ! is_numeric($entered)) {
            return null;
        }

        return round((float) $entered * $multiplier, 2);
    }

    /**
     * Place an annual income at a percent of poverty.
     *
     * Returns a fully-populated shape in every case; `available` is
     * false when no guideline covers the requested year/region/size, so
     * callers can say "no guidelines loaded" instead of showing 0%.
     *
     * @return array{annual_income: float|null, monthly_income: float|null, household_size: int|null, guideline_year: int|null, guideline_region: string, threshold: float|null, percentage: float|null, bracket: int|null, label: string|null, available: bool}
     */
    public function assess($annualIncome, $householdSize, ?int $year = null, string $region = self::REGION_DEFAULT): array
    {
        $annualIncome = ($annualIncome === null || $annualIncome === '' || ! is_numeric($annualIncome))
            ? null
            : round((float) $annualIncome, 2);
        $householdSize = (int) $householdSize;
        $resolvedYear = $this->resolveYear($year, $region);

        $base = [
            'annual_income' => $annualIncome,
            'monthly_income' => $annualIncome === null ? null : round($annualIncome / 12, 2),
            'household_size' => $householdSize > 0 ? $householdSize : null,
            'guideline_year' => $resolvedYear,
            'guideline_region' => $region,
            'threshold' => null,
            'percentage' => null,
            'bracket' => null,
            'label' => null,
            'available' => false,
        ];

        if ($annualIncome === null || $householdSize < 1 || $resolvedYear === null) {
            return $base;
        }

        $threshold = PovertyGuideline::thresholdFor($householdSize, 'annual', $resolvedYear, $region);

        if ($threshold === null || $threshold <= 0) {
            return $base;
        }

        $percentage = round(($annualIncome / $threshold) * 100, 2);
        $bracket = PovertyGuideline::bracketFor($percentage);

        return array_merge($base, [
            'threshold' => $threshold,
            'percentage' => $percentage,
            'bracket' => $bracket,
            'label' => $this->format($percentage).'% of the '.$resolvedYear.' federal poverty level',
            'available' => true,
        ]);
    }

    /**
     * Which guideline year to measure against: the requested one when
     * it has data, otherwise the newest year loaded. Never silently
     * falls back to the calendar year — a January intake run before the
     * new guidelines are entered must read against last year's table
     * and say so, not report a blank.
     */
    public function resolveYear(?int $year, string $region = self::REGION_DEFAULT): ?int
    {
        if ($year !== null) {
            $exists = PovertyGuideline::where('year', $year)
                ->where('region', $region)
                ->where('period', 'annual')
                ->exists();

            if ($exists) {
                return $year;
            }
        }

        $current = (int) date('Y');
        $currentExists = PovertyGuideline::where('year', $current)
            ->where('region', $region)
            ->where('period', 'annual')
            ->exists();

        if ($currentExists) {
            return $current;
        }

        $latest = PovertyGuideline::latestYear($region);

        return $latest === null ? null : (int) $latest;
    }

    /** Blank income lines the form starts with / adds. */
    public function blankSource(string $type = 'wages'): array
    {
        return ['type' => $type, 'label' => '', 'amount' => ''];
    }

    /** Strip empty rows and normalize amounts before persisting. */
    public function normalizeSources(array $sources): array
    {
        $clean = [];

        foreach ($sources as $line) {
            $amount = $line['amount'] ?? null;
            if ($amount === null || $amount === '' || ! is_numeric($amount)) {
                continue;
            }

            $type = $line['type'] ?? 'other';

            $clean[] = [
                'type' => array_key_exists($type, IntakeIncomeAssessment::SOURCE_TYPES) ? $type : 'other',
                'label' => trim((string) ($line['label'] ?? '')) ?: null,
                'amount' => round((float) $amount, 2),
            ];
        }

        return $clean;
    }

    /** 143.00 → "143", 143.50 → "143.5". */
    private function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
}
