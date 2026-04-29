<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3A scope addition: a fund code may have a future start date.
     * Nullable — existing rows default to null and behave as
     * "always-started." When set in the future, the dashboard surfaces
     * a "Fund starts [date]" indicator on rows tied to the not-yet-
     * started fund. Assignment is NOT blocked while pre-start;
     * informational only.
     */
    public function up(): void
    {
        Schema::table('fund_codes', function (Blueprint $table) {
            $table->date('starts_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('fund_codes', function (Blueprint $table) {
            $table->dropColumn('starts_at');
        });
    }
};
