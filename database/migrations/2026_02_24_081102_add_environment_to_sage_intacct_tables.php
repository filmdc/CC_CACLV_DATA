<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Environment columns already added by 2026_02_11_200000_add_environment_to_sync_tables
        // This migration is a safe no-op to avoid duplicate column errors
        if (! Schema::hasColumn('sage_intacct_sync_history', 'environment')) {
            Schema::table('sage_intacct_sync_history', function (Blueprint $table) {
                $table->string('environment', 20)->default('production')->after('triggered_by');
                $table->index('environment');
            });
        }

        if (! Schema::hasColumn('sage_intacct_sync_items', 'environment')) {
            Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
                $table->string('environment', 20)->default('production')->after('sync_history_id');
                $table->index('environment');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sage_intacct_sync_history', function (Blueprint $table) {
            $table->dropIndex(['environment']);
            $table->dropColumn('environment');
        });

        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            $table->dropIndex(['environment']);
            $table->dropColumn('environment');
        });
    }
};
