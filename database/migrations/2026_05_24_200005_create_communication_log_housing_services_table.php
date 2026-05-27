<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 / Phase 9: communication_logs ↔ housing_services M:M.
 *
 * The user picks zero-to-many housing services on an entry; each pick
 * auto-attaches the service's fund codes to anticipatedFundCodes and
 * auto-stamps the service's bundled + direct atoms onto intake_requirements
 * (via IntakeRequirementInstantiator::forHousingService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_log_housing_services', function (Blueprint $table) {
            $table->unsignedBigInteger('communication_log_id');
            $table->unsignedBigInteger('housing_service_id');
            $table->foreignId('created_by_user_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->primary(['communication_log_id', 'housing_service_id'], 'clog_hs_pk');
            $table->index('housing_service_id', 'clog_hs_reverse_idx');

            $table->foreign('communication_log_id', 'clog_hs_log_fk')
                ->references('id')->on('communication_logs')
                ->cascadeOnDelete();
            $table->foreign('housing_service_id', 'clog_hs_hs_fk')
                ->references('id')->on('housing_services')
                ->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'clog_hs_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_log_housing_services');
    }
};
