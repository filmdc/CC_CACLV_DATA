<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-requirement free-form worker note for intake (Phase 1) requirements.
 *
 * Distinct from the existing append-only `notes` column (which holds the
 * audit trail: [WAIVED]/[N/A]/[REOPENED] lines + transition notes). This
 * column is the editable standing annotation the intake worker maintains
 * to record partial-receipt details, e.g. "only need paystub covering
 * 4/1-4/13, all others in file". Surfaced under each item in the
 * requirements panel and editable from the per-row action modal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intake_requirements', function (Blueprint $table) {
            $table->text('worker_note')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('intake_requirements', function (Blueprint $table) {
            $table->dropColumn('worker_note');
        });
    }
};
