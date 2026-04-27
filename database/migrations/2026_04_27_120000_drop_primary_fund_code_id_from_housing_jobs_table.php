<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.7 / P-38: drop the primary_fund_code_id column from housing_jobs.
 *
 * The primary-fund-code concept is removed system-wide — all assigned codes
 * are equal. The model, Livewire, blade, seeder, and tests no longer
 * reference this column (those changes landed in commit 4 of this branch);
 * this migration just brings the schema into line.
 *
 * down() re-adds the column with the same shape as the original Phase 2
 * migration (2026_04_26_000003_add_primary_fund_code_id_to_housing_jobs_table.php).
 * It does NOT restore prior values — the 12 production rows that had
 * primary_fund_code_id set lose those values when up() runs, by design.
 * No sidecar table preserves them; if a primary concept is ever wanted
 * again, build it fresh against the new requirements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_fund_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->foreignId('primary_fund_code_id')
                ->nullable()
                ->after('funding_source')
                ->constrained('fund_codes')
                ->nullOnDelete();
        });
    }
};
