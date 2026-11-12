<?php

namespace App\Console\Commands;

use App\Models\IntakeIncomeAssessment;
use App\Models\PovertyGuideline;
use Illuminate\Console\Command;

/**
 * Rebase HOME income determinations onto area median income.
 *
 * Determinations taken before this ran measured the percentage against
 * the 80%-AMI ceiling, so a household at 24% of AMI was recorded as
 * "30.1%" and banded on the FPL ladder. The eligibility outcome never
 * depended on that figure — income vs. the ceiling — so recomputing
 * changes what is REPORTED, never who qualified.
 *
 * Each row is recomputed against the year it was originally snapshotted
 * to, never today's table, and poverty_threshold is left exactly as it
 * was: the ceiling the household was judged against.
 *
 *   php artisan intake:recompute-home-determinations --dry-run
 */
class RecomputeHomeDeterminations extends Command
{
    protected $signature = 'intake:recompute-home-determinations {--dry-run : Show the before/after without writing}';

    protected $description = 'Recompute HOME income determinations as a percent of area median income';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = IntakeIncomeAssessment::where('guideline_region', PovertyGuideline::REGION_HOME_ABE)
            ->whereNotNull('annual_income')
            ->where('household_size', '>', 0)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No HOME determinations to recompute.');

            return self::SUCCESS;
        }

        $this->line($dryRun
            ? "Dry run — {$rows->count()} HOME determination(s), nothing will be written."
            : "Recomputing {$rows->count()} HOME determination(s).");
        $this->newLine();

        $table = [];
        $changed = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $year = (int) $row->guideline_year;
            $size = (int) $row->household_size;
            $income = (float) $row->annual_income;

            // Pin to the year the determination was taken against, not
            // whatever is newest now — the snapshot has to stay put.
            $ami = $year > 0 ? PovertyGuideline::homeAmiFor($size, $year) : null;

            if ($ami === null || $ami <= 0) {
                $skipped++;
                $table[] = [
                    $row->id,
                    $size,
                    '$'.number_format($income),
                    $row->poverty_percentage.'%',
                    'SKIPPED — no FY'.$year.' limits loaded',
                ];

                continue;
            }

            $percentage = round($income / $ami * 100, 2);
            $tier = PovertyGuideline::homeTierFor($percentage);

            $table[] = [
                $row->id,
                $size,
                '$'.number_format($income),
                $row->poverty_percentage.'% (of ceiling)',
                $percentage.'% of AMI · '.(PovertyGuideline::homeTierLabel($tier) ?? 'over 80% AMI'),
            ];

            if (! $dryRun) {
                $row->forceFill([
                    'area_median_income' => $ami,
                    'poverty_percentage' => $percentage,
                    'poverty_bracket' => $tier,
                ])->save();
            }

            $changed++;
        }

        $this->table(['ID', 'Size', 'Income', 'Was', 'Now'], $table);

        $this->info($dryRun
            ? "Would update {$changed} row(s); {$skipped} skipped. Re-run without --dry-run to apply."
            : "Updated {$changed} row(s); {$skipped} skipped.");

        return self::SUCCESS;
    }
}
