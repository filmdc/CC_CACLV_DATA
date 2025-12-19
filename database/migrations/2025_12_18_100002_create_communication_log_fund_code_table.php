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
        Schema::create('communication_log_fund_code', function (Blueprint $table) {
            $table->foreignId('communication_log_id')->constrained('communication_logs')->cascadeOnDelete();
            $table->foreignId('fund_code_id')->constrained('fund_codes')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['communication_log_id', 'fund_code_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_log_fund_code');
    }
};
