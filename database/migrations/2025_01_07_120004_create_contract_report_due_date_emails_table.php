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
        Schema::create('contract_report_due_date_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_report_due_date_id');
            $table->unsignedBigInteger('due_date_email_id');
            $table->json('reminder_intervals')->nullable(); // Custom intervals for this assignment
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->foreign('contract_report_due_date_id', 'crdd_id_fk')->references('id')->on('contract_report_due_dates')->onDelete('cascade');
            $table->foreign('due_date_email_id', 'dde_id_fk')->references('id')->on('due_date_emails')->onDelete('cascade');
            
            $table->unique(['contract_report_due_date_id', 'due_date_email_id'], 'crdd_dde_unique');
            $table->index(['contract_report_due_date_id', 'active'], 'crdd_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_report_due_date_emails');
    }
};