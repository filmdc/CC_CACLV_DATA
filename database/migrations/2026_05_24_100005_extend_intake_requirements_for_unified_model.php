<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 (Option A): extend intake_requirements to support the
 * unified model — atoms (definitions), bundles (template sets), and
 * the 6-state lifecycle from the design spec.
 *
 * Changes:
 *   - Add `key` (dedupe slug, snapshotted from definition.key).
 *   - Add `kind` (snapshotted from definition.kind; widens beyond the
 *     existing document/task taxonomy).
 *   - Add `via` (json array of source identifiers — service codes,
 *     bundle IDs, etc. — for attribution and dedupe).
 *   - Add `status_changed_at` + `status_changed_by_user_id` (audit
 *     trail; every transition writes a Status activity update on the
 *     parent CommLog, per spec §5.7).
 *   - Add `source_definition_id` FK back to requirement_definitions
 *     (nullable, nullOnDelete to preserve historical instances when an
 *     atom is soft-deleted).
 *   - Widen `status` enum: outstanding/received/waived → pending/
 *     requested/received/waived/na/blocked. Backfill outstanding →
 *     pending. Default changes from 'outstanding' to 'pending'.
 *   - Widen `source_type` enum: + template_set, + definition.
 *
 * MySQL enum widening requires raw SQL because Laravel's enum->change()
 * needs doctrine/dbal. Three-step pattern: widen, backfill, narrow.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add new columns ─────────────────────────────────────
        Schema::table('intake_requirements', function (Blueprint $table) {
            $table->string('key', 80)->nullable()->after('label');
            $table->string('kind', 16)->nullable()->after('key');
            $table->json('via')->nullable()->after('kind');

            $table->timestamp('status_changed_at')->nullable()->after('received_by_user_id');
            $table->foreignId('status_changed_by_user_id')
                ->nullable()
                ->after('status_changed_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('source_definition_id')
                ->nullable()
                ->after('source_template_id')
                ->constrained('requirement_definitions')
                ->nullOnDelete();
        });

        // ── 2. Widen status enum ───────────────────────────────────
        // Step 2a: add the 6 new values alongside the existing 3.
        DB::statement(
            'ALTER TABLE intake_requirements MODIFY COLUMN status '
            ."ENUM('outstanding','pending','requested','received','waived','na','blocked') "
            ."NOT NULL DEFAULT 'outstanding'"
        );

        // Step 2b: backfill — every existing 'outstanding' becomes 'pending'.
        DB::table('intake_requirements')
            ->where('status', 'outstanding')
            ->update(['status' => 'pending']);

        // Step 2c: drop the legacy 'outstanding' value; change default.
        DB::statement(
            'ALTER TABLE intake_requirements MODIFY COLUMN status '
            ."ENUM('pending','requested','received','waived','na','blocked') "
            ."NOT NULL DEFAULT 'pending'"
        );

        // ── 3. Widen source_type enum ──────────────────────────────
        DB::statement(
            'ALTER TABLE intake_requirements MODIFY COLUMN source_type '
            ."ENUM('baseline','fund_code','ad_hoc','template_set','definition') NOT NULL"
        );

        // ── 4. Add an index for the new dedupe-by-key lookup ───────
        Schema::table('intake_requirements', function (Blueprint $table) {
            $table->index(
                ['communication_log_id', 'key'],
                'intake_req_log_key_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('intake_requirements', function (Blueprint $table) {
            $table->dropIndex('intake_req_log_key_idx');
        });

        // Reverse source_type widening.
        DB::statement(
            'ALTER TABLE intake_requirements MODIFY COLUMN source_type '
            ."ENUM('baseline','fund_code','ad_hoc') NOT NULL"
        );

        // Reverse status widening: back to outstanding/received/waived.
        DB::statement(
            'ALTER TABLE intake_requirements MODIFY COLUMN status '
            ."ENUM('outstanding','pending','requested','received','waived','na','blocked') "
            ."NOT NULL DEFAULT 'outstanding'"
        );
        DB::table('intake_requirements')
            ->whereIn('status', ['pending', 'requested', 'na', 'blocked'])
            ->update(['status' => 'outstanding']);
        DB::statement(
            'ALTER TABLE intake_requirements MODIFY COLUMN status '
            ."ENUM('outstanding','received','waived') "
            ."NOT NULL DEFAULT 'outstanding'"
        );

        Schema::table('intake_requirements', function (Blueprint $table) {
            $table->dropForeign(['source_definition_id']);
            $table->dropForeign(['status_changed_by_user_id']);
            $table->dropColumn([
                'key',
                'kind',
                'via',
                'status_changed_at',
                'status_changed_by_user_id',
                'source_definition_id',
            ]);
        });
    }
};
