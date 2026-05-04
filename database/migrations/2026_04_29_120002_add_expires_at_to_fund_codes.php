<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3A scope addition: a fund code may expire. Nullable —
     * existing rows default to null and behave as "never expires."
     * When set, the dashboard surfaces an indicator: urgent (≤30
     * days), warning (31–90 days), expired (past). Hardcoded
     * thresholds; documented in PHASE_3A_NOTES.md as a one-line
     * config-change target for a future phase.
     *
     * Expired funds remain attachable (backdated entry) and reportable;
     * the system flags expiration, it does not enforce it.
     */
    public function up(): void
    {
        Schema::table('fund_codes', function (Blueprint $table) {
            $table->date('expires_at')->nullable()->after('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('fund_codes', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
