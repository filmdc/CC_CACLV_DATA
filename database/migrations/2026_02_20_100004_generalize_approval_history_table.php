<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_history', function (Blueprint $table) {
            $table->nullableMorphs('context');
            $table->foreignId('approval_type_id')->nullable()->constrained('approval_types')->nullOnDelete();
            $table->tinyInteger('chain_step')->default(1);
            $table->string('signature_ip', 45)->nullable();
            $table->string('signature_user_agent')->nullable();
        });

        // Migrate existing housing_job_id data to context morph columns
        DB::statement("UPDATE approval_history SET context_type = 'App\\\\Models\\\\HousingJob', context_id = housing_job_id WHERE housing_job_id IS NOT NULL");

        Schema::table('approval_history', function (Blueprint $table) {
            $table->dropForeign(['housing_job_id']);
            $table->dropColumn('housing_job_id');
            $table->dropColumn(['document_type', 'document_id']);
        });

        // Note: nullableMorphs('context') above already creates an index on (context_type, context_id)
        Schema::table('approval_history', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('approval_history', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('approval_history', function (Blueprint $table) {
            $table->foreignId('housing_job_id')->nullable()->constrained('housing_jobs')->cascadeOnDelete();
            $table->string('document_type')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
        });

        // Migrate context morph data back to housing_job_id
        DB::statement("UPDATE approval_history SET housing_job_id = context_id WHERE context_type = 'App\\\\Models\\\\HousingJob'");

        // Restore document_type/document_id from approvable morph
        DB::statement('UPDATE approval_history SET document_type = approvable_type, document_id = approvable_id');

        Schema::table('approval_history', function (Blueprint $table) {
            $table->dropForeign(['approval_type_id']);
            $table->dropColumn(['context_type', 'context_id', 'approval_type_id', 'chain_step', 'signature_ip', 'signature_user_agent']);
        });
    }
};
