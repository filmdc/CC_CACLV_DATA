<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P-39b cutover (addendum D-2): backfill the legacy
 * `communication_log_fund_code` tag pivot into the new
 * `communication_log_anticipated_fund_codes` pivot, then drop the
 * legacy table.
 *
 * Sequence-critical: must run AFTER the migration that creates the
 * destination table. INSERT IGNORE silently suppresses duplicates if
 * the destination already has rows (e.g., from fixtures); the parity
 * assertion uses < (not !=) so destination-greater-than-source is
 * allowed, while destination-less-than-source aborts before drop.
 *
 * down() is for dev rollback only. It recreates the legacy table and
 * back-migrates the rows. It does NOT touch the new pivot, so a
 * round-trip up→down→up would re-backfill duplicates (suppressed by
 * the unique constraint via INSERT IGNORE).
 */
return new class extends Migration
{
    public function up(): void
    {
        $sourceCount = DB::table('communication_log_fund_code')->count();

        DB::statement('
            INSERT IGNORE INTO communication_log_anticipated_fund_codes
                (communication_log_id, fund_code_id, created_at)
            SELECT communication_log_id, fund_code_id, created_at
              FROM communication_log_fund_code
        ');

        $migratedCount = DB::table('communication_log_anticipated_fund_codes')->count();

        if ($migratedCount < $sourceCount) {
            throw new \RuntimeException(sprintf(
                'P-39b backfill row-count mismatch: source=%d, destination=%d. '.
                'Aborting before drop to preserve source data.',
                $sourceCount,
                $migratedCount
            ));
        }

        Schema::dropIfExists('communication_log_fund_code');
    }

    public function down(): void
    {
        Schema::create('communication_log_fund_code', function (Blueprint $table) {
            $table->foreignId('communication_log_id')
                ->constrained('communication_logs')
                ->cascadeOnDelete();
            $table->foreignId('fund_code_id')
                ->constrained('fund_codes')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['communication_log_id', 'fund_code_id']);
        });

        DB::statement('
            INSERT IGNORE INTO communication_log_fund_code
                (communication_log_id, fund_code_id, created_at, updated_at)
            SELECT communication_log_id, fund_code_id, created_at, COALESCE(created_at, NOW())
              FROM communication_log_anticipated_fund_codes
        ');
    }
};
