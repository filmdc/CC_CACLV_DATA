<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12.4: sticky-override timestamp for CommunicationLog.category.
 *
 * The category now auto-flows through three stages based on the log's
 * lifecycle (no service → 'general'; service attached → 'intake';
 * Phase 1 requirements complete → 'scheduling'). If the user manually
 * picks a category from the dropdown, we record category_overridden_at
 * and the auto-bump stops touching this row. Mirrors the existing
 * subject_overridden_at pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->timestamp('category_overridden_at')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->dropColumn('category_overridden_at');
        });
    }
};
