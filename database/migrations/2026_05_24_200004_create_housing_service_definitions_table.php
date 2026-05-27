<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 / Phase 9: housing_services ↔ requirement_definitions
 * (atoms) M:M.
 *
 * For one-off atom attachments that don't belong in a named bundle —
 * "this housing service needs an HMIS consent on top of whatever its
 * bundles include." Selecting the service on a Comm Log stamps the
 * atom on the log with via:'service:N'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_service_definitions', function (Blueprint $table) {
            $table->unsignedBigInteger('housing_service_id');
            $table->unsignedBigInteger('requirement_definition_id');
            $table->foreignId('created_by_user_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->primary(['housing_service_id', 'requirement_definition_id'], 'hs_defs_pk');
            $table->index('requirement_definition_id', 'hs_defs_reverse_idx');

            $table->foreign('housing_service_id', 'hs_defs_hs_fk')
                ->references('id')->on('housing_services')
                ->cascadeOnDelete();
            $table->foreign('requirement_definition_id', 'hs_defs_def_fk')
                ->references('id')->on('requirement_definitions')
                ->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'hs_defs_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_service_definitions');
    }
};
