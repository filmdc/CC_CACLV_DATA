<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 / Convert-to-Project: housing_jobs ↔ housing_services M:M.
 *
 * When a Communication Log is promoted to a project, the counties (the
 * entity formerly known as "housing services") selected during intake
 * carry forward onto the new HousingJob as a historical/operational
 * record of which county/counties the project serves. The county
 * selection also drives the fund codes carried onto the job, which in
 * turn instantiate the Phase 2 (project-phase) requirements.
 *
 * Mirrors communication_log_housing_services in shape.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_job_housing_service', function (Blueprint $table) {
            $table->unsignedBigInteger('housing_job_id');
            $table->unsignedBigInteger('housing_service_id');
            $table->foreignId('created_by_user_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->primary(['housing_job_id', 'housing_service_id'], 'hjob_hs_pk');
            $table->index('housing_service_id', 'hjob_hs_reverse_idx');

            $table->foreign('housing_job_id', 'hjob_hs_job_fk')
                ->references('id')->on('housing_jobs')
                ->cascadeOnDelete();
            $table->foreign('housing_service_id', 'hjob_hs_hs_fk')
                ->references('id')->on('housing_services')
                ->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'hjob_hs_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_job_housing_service');
    }
};
