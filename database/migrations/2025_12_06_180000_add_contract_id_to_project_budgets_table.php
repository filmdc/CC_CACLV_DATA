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
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->after('housing_job_id')->constrained('contracts')->nullOnDelete();
            $table->index(['housing_job_id', 'contract_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
            $table->dropIndex(['housing_job_id', 'contract_id']);
            $table->dropColumn('contract_id');
        });
    }
};
