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
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->foreignId('project_manager_id')->nullable()->after('approved_by')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->dropForeign(['project_manager_id']);
            $table->dropColumn('project_manager_id');
        });
    }
};
