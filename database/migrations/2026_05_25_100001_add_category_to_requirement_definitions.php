<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 / Phase 10: each requirement atom now declares which
 * workflow phase it belongs to via a category column:
 *
 *   - eligibility               (Phase 1 — intake screening)
 *   - compliance                (Phase 1 — general compliance / paperwork)
 *   - service_delivery_compliance  (Phase 2 — post-promotion, on the project)
 *
 * Stamping logic remains category-agnostic — when a fund code or
 * housing service is attached to a Comm Log, every linked atom is
 * instantiated regardless of category. The UI then filters DISPLAY:
 * the Comm Log viewer shows only Phase-1 categories; the project page
 * shows Phase-2.
 *
 * Existing rows backfilled to 'eligibility' (the safest default — they
 * were created before the category concept existed and live on intake
 * Comm Logs which are Phase 1 by definition).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirement_definitions', function (Blueprint $table) {
            $table->string('category', 32)
                ->default('eligibility')
                ->after('kind');
        });

        // Backfill is implicit (the default takes care of new+existing
        // rows that didn't have one). Index for filtered queries.
        Schema::table('requirement_definitions', function (Blueprint $table) {
            $table->index('category', 'req_def_category_idx');
        });

        // Widen via raw SQL to a proper ENUM with the canonical 3 values.
        DB::statement(
            "ALTER TABLE requirement_definitions MODIFY COLUMN category "
            ."ENUM('eligibility','compliance','service_delivery_compliance') "
            ."NOT NULL DEFAULT 'eligibility'"
        );
    }

    public function down(): void
    {
        Schema::table('requirement_definitions', function (Blueprint $table) {
            $table->dropIndex('req_def_category_idx');
            $table->dropColumn('category');
        });
    }
};
