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
        Schema::create('pending_approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable'); // approvable_type, approvable_id
            $table->foreignId('housing_job_id')->nullable()->constrained('housing_jobs')->onDelete('cascade');
            $table->foreignId('program_id')->nullable()->constrained('programs');
            $table->string('item_type'); // document, task, milestone
            $table->string('item_name');
            $table->text('item_description')->nullable();
            $table->foreignId('submitted_by')->constrained('users');
            $table->timestamp('submitted_at');
            $table->foreignId('approver_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->timestamp('actioned_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->integer('priority')->default(0); // 0=normal, 1=high, 2=urgent
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index(['approver_id', 'status']);
            $table->index(['housing_job_id', 'status']);
            $table->index('program_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_approvals');
    }
};
