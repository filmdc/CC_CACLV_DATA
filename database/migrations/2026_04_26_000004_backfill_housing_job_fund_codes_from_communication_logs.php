<?php

use App\Models\HousingJob;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * P-08 backfill: copy each non-template HousingJob's Phase-1-derived
 * fund codes (the union of fund_codes attached to its communicationLogs
 * and originatingCommunicationLogs) into the new fund_code_housing_job
 * pivot, and pick a primary fund code for each job by the
 * oldest-comm-log rule.
 *
 * Oldest-comm-log rule: of the comm logs that reference this job
 * (housing_job_id OR converted_to_job_id), sort by communication_date
 * ascending, then created_at ascending, then id ascending. The primary
 * is the first attached fund code on the earliest comm log that has
 * any. Empirically degenerate at this snapshot (every job that has any
 * code has exactly one), but the rule is documented for future cases.
 *
 * down() is destructive of Phase 2 data only. At the time it runs in a
 * clean rollback, this migration was the only writer of the new pivot
 * AND the only setter of housing_jobs.primary_fund_code_id. Operator
 * activity between up() and down() — UI-driven attaches, primary
 * toggles — will be lost. Documented as the trade for a deterministic,
 * non-brittle reverse path.
 *
 * Bypasses the saving-event guard on HousingJob via direct DB::update,
 * because the migration is establishing the invariant rather than
 * operating under it. Pivot rows are inserted before the primary FK is
 * set, so the guard would also pass on a model-event path.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        HousingJob::where('is_template', false)
            ->with([
                'communicationLogs.fundCodes',
                'originatingCommunicationLogs.fundCodes',
            ])
            ->each(function (HousingJob $job) use ($now) {
                $allLogs = $job->communicationLogs
                    ->concat($job->originatingCommunicationLogs)
                    ->unique('id')
                    ->values();

                $allCodes = $allLogs
                    ->flatMap->fundCodes
                    ->unique('id')
                    ->values();

                if ($allCodes->isEmpty()) {
                    return;
                }

                $pivotRows = $allCodes->map(fn ($code) => [
                    'fund_code_id' => $code->id,
                    'housing_job_id' => $job->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('fund_code_housing_job')->insertOrIgnore($pivotRows);

                $primary = $allLogs
                    ->sortBy([
                        ['communication_date', 'asc'],
                        ['created_at', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->first(fn ($log) => $log->fundCodes->isNotEmpty())
                    ?->fundCodes
                    ?->sortBy('id')
                    ?->first();

                if ($primary) {
                    DB::table('housing_jobs')
                        ->where('id', $job->id)
                        ->update(['primary_fund_code_id' => $primary->id]);
                }
            });
    }

    public function down(): void
    {
        DB::table('housing_jobs')
            ->whereNotNull('primary_fund_code_id')
            ->update(['primary_fund_code_id' => null]);

        DB::table('fund_code_housing_job')->truncate();
    }
};
