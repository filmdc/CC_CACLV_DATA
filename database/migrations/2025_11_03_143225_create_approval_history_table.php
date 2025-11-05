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
        Schema::create('approval_history', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable'); // approvable_type, approvable_id
            $table->foreignId('housing_job_id')->nullable()->constrained('housing_jobs')->onDelete('cascade');
            $table->string('document_type'); // PropertyDocument, ContractorDocument, JobDocumentChecklist, WorkPlanGoal
            $table->unsignedBigInteger('document_id');
            $table->integer('version');
            $table->string('action'); // approved, rejected, resubmitted
            $table->foreignId('user_id')->constrained('users');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Store additional context
            $table->timestamps();

            $table->index(['document_type', 'document_id']);
            $table->index(['housing_job_id', 'created_at']);
            $table->index(['user_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_history');
    }
};
