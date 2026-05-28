<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard split: per-user default sub-view for the Housing Dashboard.
 *
 * '' / null  = no preference (land on the "overview" sub-view: per-intake
 *              cards on the Intakes tab, per-project rollups on Projects)
 * 'overview' = explicitly default to the overview sub-view
 * 'requirements' = default to the flat per-requirement list on whichever
 *              top-level tab is active (the "requirements-focused" view).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('housing_dashboard_default_view', 16)
                ->nullable()
                ->after('comm_log_default_view');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('housing_dashboard_default_view');
        });
    }
};
