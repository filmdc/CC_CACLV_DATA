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
        Schema::create('milestone_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained('project_milestones')->onDelete('cascade');
            $table->string('document_type');
            $table->text('description')->nullable();
            $table->enum('status', ['not_uploaded', 'pending_review', 'approved', 'rejected'])->default('not_uploaded');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('uploaded_date')->nullable();
            $table->timestamp('reviewed_date')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->enum('approval_role', ['project_manager', 'team_member'])->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for performance
            $table->index(['milestone_id', 'status']);
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestone_documents');
    }
};
