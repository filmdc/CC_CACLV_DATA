<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sage_intacct_sync_history', function (Blueprint $table) {
            $table->string('environment', 20)->default('production')->after('triggered_by');
            $table->index('environment');
        });

        Schema::table('sage_intacct_sync_items', function (Blueprint $table) {
            $table->string('environment', 20)->default('production')->after('sync_history_id');
            $table->index('environment');
        });
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
