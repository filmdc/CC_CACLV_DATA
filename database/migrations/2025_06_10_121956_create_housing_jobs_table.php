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
        if (!Schema::hasTable('housing_jobs')) {
            Schema::create('housing_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->unique();
            $table->foreignId('participant_id')->constrained()->onDelete('cascade');
            $table->foreignId('household_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('contractor_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
            $table->string('job_title');
            $table->text('job_description')->nullable();
            $table->foreignId('job_type_id')->nullable()->comment('Reference to reference_data for job types');
            $table->enum('status', [
                'pending',
                'approved',
                'in_progress',
                'on_hold',
                'completed',
                'cancelled',
                'rejected'
            ])->default('pending');
            $table->date('request_date');
            $table->date('approval_date')->nullable();
            $table->date('start_date')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->string('funding_source')->nullable();
            $table->string('work_order_number')->nullable();
            $table->string('contract_number')->nullable();
            $table->json('scope_of_work')->nullable()->comment('Detailed work items');
            $table->enum('priority', ['low', 'medium', 'high', 'emergency'])->default('medium');
            $table->text('cancellation_reason')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
            
            $table->index(['participant_id', 'household_id']);
            $table->index('property_id');
            $table->index('contractor_id');
            $table->index('program_id');
            $table->index('service_id');
            $table->index('status');
            $table->index('job_number');
            $table->index('request_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('housing_jobs');
    }
};
