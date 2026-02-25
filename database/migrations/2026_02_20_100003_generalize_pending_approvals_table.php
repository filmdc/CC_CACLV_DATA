<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_approvals', function (Blueprint $table) {
            $table->nullableMorphs('context');
            $table->foreignId('approval_type_id')->nullable()->constrained('approval_types')->nullOnDelete();
            $table->tinyInteger('current_chain_step')->default(1);
            $table->text('submitter_notes')->nullable();
        });

        // Migrate existing housing_job_id data to context morph columns
        DB::statement("UPDATE pending_approvals SET context_type = 'App\\\\Models\\\\HousingJob', context_id = housing_job_id WHERE housing_job_id IS NOT NULL");

        Schema::table('pending_approvals', function (Blueprint $table) {
            $table->dropForeign(['housing_job_id']);
            $table->dropColumn('housing_job_id');
        });

        // Note: nullableMorphs('context') above already creates an index on (context_type, context_id)
        Schema::table('pending_approvals', function (Blueprint $table) {
            $table->index(['approval_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('pending_approvals', function (Blueprint $table) {
            $table->dropIndex(['approval_type_id', 'status']);
        });

        Schema::table('pending_approvals', function (Blueprint $table) {
            $table->foreignId('housing_job_id')->nullable()->constrained('housing_jobs')->cascadeOnDelete();
        });

        // Migrate context morph data back to housing_job_id
        DB::statement("UPDATE pending_approvals SET housing_job_id = context_id WHERE context_type = 'App\\\\Models\\\\HousingJob'");

        Schema::table('pending_approvals', function (Blueprint $table) {
            $table->dropForeign(['approval_type_id']);
            $table->dropColumn(['context_type', 'context_id', 'approval_type_id', 'current_chain_step', 'submitter_notes']);
        });
    }
};
