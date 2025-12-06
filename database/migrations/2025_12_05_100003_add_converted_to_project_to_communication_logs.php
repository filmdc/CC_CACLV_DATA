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
            $table->foreignId('converted_to_job_id')->nullable()->after('follow_up_completed_by')->constrained('housing_jobs')->onDelete('set null');
            $table->timestamp('converted_at')->nullable()->after('converted_to_job_id');
            $table->foreignId('converted_by')->nullable()->after('converted_at')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communication_logs', function (Blueprint $table) {
            $table->dropForeign(['converted_to_job_id']);
            $table->dropForeign(['converted_by']);
            $table->dropColumn(['converted_to_job_id', 'converted_at', 'converted_by']);
        });
    }
};
