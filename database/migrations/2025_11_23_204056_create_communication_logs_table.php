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
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();

            // Optional relationships - can be linked to participant/household/property but not required
            $table->foreignId('participant_id')->nullable()->constrained('participants')->onDelete('set null');
            $table->foreignId('household_id')->nullable()->constrained('households')->onDelete('set null');
            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('set null');
            $table->foreignId('contractor_id')->nullable()->constrained('contractors')->onDelete('set null');

            // Communication details
            $table->datetime('communication_date');
            $table->enum('communication_type', ['phone_call', 'email', 'in_person', 'site_visit', 'video_call', 'text', 'mail', 'voicemail', 'fax', 'other'])->default('phone_call');
            $table->enum('direction', ['incoming', 'outgoing'])->default('outgoing');

            // Contact information
            $table->string('contact_name');
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_organization')->nullable();
            $table->enum('contact_role', ['homeowner', 'contractor', 'inspector', 'vendor', 'agency', 'applicant', 'family_member', 'other'])->nullable();

            // Communication content
            $table->string('subject');
            $table->text('summary')->nullable();
            $table->text('notes')->nullable();
            $table->text('outcome')->nullable();

            // Categorization
            $table->enum('category', ['general', 'intake', 'follow_up', 'complaint', 'inquiry', 'referral', 'outreach', 'scheduling', 'documentation', 'other'])->default('general');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

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
            $table->index('participant_id');
            $table->index('household_id');
            $table->index('property_id');
            $table->index('contractor_id');
            $table->index('communication_date');
            $table->index('communication_type');
            $table->index('category');
            $table->index('priority');
            $table->index('follow_up_date');
            $table->index(['follow_up_completed', 'follow_up_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
