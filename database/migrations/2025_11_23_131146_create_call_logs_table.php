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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();

            // Foreign keys - job is required, milestone is optional
            $table->foreignId('housing_job_id')->constrained('housing_jobs')->onDelete('cascade');
            $table->foreignId('project_milestone_id')->nullable()->constrained('project_milestones')->onDelete('set null');

            // Call/Contact details
            $table->datetime('call_date');
            $table->enum('call_type', ['phone_call', 'email', 'in_person', 'site_visit', 'video_call', 'text', 'mail', 'voicemail', 'other'])->default('phone_call');
            $table->enum('direction', ['incoming', 'outgoing'])->default('outgoing');

            // Contact information
            $table->string('contact_name');
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_organization')->nullable();
            $table->enum('contact_role', ['homeowner', 'contractor', 'inspector', 'vendor', 'agency', 'other'])->nullable();

            // Call content
            $table->string('subject');
            $table->text('notes')->nullable();
            $table->text('outcome')->nullable();

            // Follow-up tracking
            $table->date('follow_up_date')->nullable();
            $table->text('follow_up_action')->nullable();
            $table->boolean('follow_up_completed')->default(false);
            $table->datetime('follow_up_completed_at')->nullable();
            $table->foreignId('follow_up_completed_by')->nullable()->constrained('users')->onDelete('set null');

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes for performance
            $table->index('housing_job_id');
            $table->index('project_milestone_id');
            $table->index('call_date');
            $table->index('call_type');
            $table->index('follow_up_date');
            $table->index(['housing_job_id', 'project_milestone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
