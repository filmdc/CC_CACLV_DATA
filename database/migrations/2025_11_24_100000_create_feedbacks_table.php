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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();

            // Core fields
            $table->enum('category', ['bug_report', 'feature_request', 'usability_issue', 'question']);
            $table->string('title', 255);
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['new', 'acknowledged', 'in_progress', 'resolved', 'closed', 'wont_fix'])->default('new');

            // Context (auto-captured)
            $table->string('page_url', 500)->nullable();
            $table->string('browser_info', 255)->nullable();
            $table->json('additional_context')->nullable();

            // User who submitted
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Admin response
            $table->text('admin_notes')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            // Integration tracking
            $table->string('github_issue_url')->nullable();
            $table->integer('github_issue_number')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['status', 'created_at']);
            $table->index(['category', 'status']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
