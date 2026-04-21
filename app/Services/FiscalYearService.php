<?php

namespace App\Services;

use App\Models\SiteSetting;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Fiscal year window calculations.
 *
 * Fiscal year boundary is configurable via SiteSetting
 * (fiscal_year_start_month, fiscal_year_start_day). Defaults to July 1.
 *
 * Fiscal years are labeled by the year they END in — e.g. a FY that
 * runs 7/1/2025 through 6/30/2026 is called "FY 2026".
 */
class FiscalYearService
{
    /**
     * Get the configured FY start month (1–12). Defaults to 7 (July).
     */
    public static function startMonth(): int
    {
        return (int) SiteSetting::get('fiscal_year_start_month', 7);
    }

    /**
     * Get the configured FY start day of month (1–31). Defaults to 1.
     */
    public static function startDay(): int
    {
        return (int) SiteSetting::get('fiscal_year_start_day', 1);
    }

    /**
     * Return the label year for the FY containing the given date.
     * A date in July 2025 returns 2026 (FY ends 6/30/2026).
     */
    public static function yearFor(Carbon|CarbonImmutable $date): int
    {
        $startMonth = self::startMonth();
        $startDay = self::startDay();

        $thisYearStart = $date->copy()->setDate($date->year, $startMonth, $startDay)->startOfDay();

        if ($date->lt($thisYearStart)) {
            // Before this calendar year's FY start → we're in the FY that ends this year
            return $date->year;
        }

        // On/after this calendar year's FY start → we're in the FY that ends next year
        return $date->year + 1;
    }

    /**
     * Get the [start, end] Carbon window for a given FY label year.
     * FY 2026 = 7/1/2025 through 6/30/2026 (default boundaries).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function window(int $fiscalYear): array
    {
        $startMonth = self::startMonth();
        $startDay = self::startDay();

        $start = Carbon::create($fiscalYear - 1, $startMonth, $startDay)->startOfDay();
        $end = $start->copy()->addYear()->subDay()->endOfDay();

        return [$start, $end];
    }

    /**
     * Window for the FY that contains today.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function currentWindow(): array
    {
        return self::window(self::yearFor(Carbon::today()));
    }

    /**
     * Human-readable label for an FY (e.g. "FY 2026 (7/1/2025 – 6/30/2026)").
     */
    public static function label(int $fiscalYear): string
    {
        [$start, $end] = self::window($fiscalYear);

        return sprintf('FY %d (%s – %s)', $fiscalYear, $start->format('n/j/Y'), $end->format('n/j/Y'));
    }
}
