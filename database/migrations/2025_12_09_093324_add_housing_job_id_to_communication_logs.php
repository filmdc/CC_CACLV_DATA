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
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->foreignId('housing_job_id')->nullable()->after('id')->constrained('housing_jobs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->dropForeign(['housing_job_id']);
            $table->dropColumn('housing_job_id');
        });
    }
};
