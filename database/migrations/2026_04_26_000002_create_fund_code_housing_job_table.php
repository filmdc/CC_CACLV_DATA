<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fund_code_housing_job', function (Blueprint $table) {
            // Alphabetical pivot name (f < h), matching the existing
            // communication_log_fund_code precedent in this repo.
            $table->foreignId('fund_code_id')
                ->constrained('fund_codes')
                ->cascadeOnDelete();
            $table->foreignId('housing_job_id')
                ->constrained('housing_jobs')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['fund_code_id', 'housing_job_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_code_housing_job');
    }
};
