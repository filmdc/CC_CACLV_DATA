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
        // Skip if table already exists (created by later migration)
        if (Schema::hasTable('contract_reports')) {
            return;
        }

        // Skip if required tables don't exist yet
        if (!Schema::hasTable('contracts') || !Schema::hasTable('contract_report_due_dates')) {
            return;
        }

        Schema::create('contract_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('contract_report_due_date_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('submitted_date')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, approved, rejected
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Store additional report data
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('contract_report_due_date_id')->references('id')->on('contract_report_due_dates')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['contract_id', 'status']);
            $table->index(['submitted_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_reports');
    }
};