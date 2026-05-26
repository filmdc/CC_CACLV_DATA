<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12.8: per-user default view-mode preference for the
 * Communications Log list page (table vs card). Null = no preference;
 * fall back to the hardcoded 'table' default. URL ?viewMode= still
 * wins over the user pref so deep-links stay deterministic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('comm_log_default_view', 16)->nullable()->after('notification_preferences');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('comm_log_default_view');
        });
    }
};
