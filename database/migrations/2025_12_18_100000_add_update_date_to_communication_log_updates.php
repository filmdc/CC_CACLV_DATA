<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('communication_log_updates', function (Blueprint $table) {
            $table->date('update_date')->nullable()->after('update_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communication_log_updates', function (Blueprint $table) {
            $table->dropColumn('update_date');
        });
    }
};
