<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 / Phase 9: housing_services ↔ requirement_template_sets
 * (bundles) M:M.
 *
 * Selecting a housing service on a Communication Log stamps every atom
 * in every bundle in this pivot onto the log (deduped by atom key).
 * Mirrors the program-service ↔ bundle pivot that already exists; the
 * two systems run in parallel during the housing-service transition.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_service_template_sets', function (Blueprint $table) {
            $table->unsignedBigInteger('housing_service_id');
            $table->unsignedBigInteger('template_set_id');
            $table->foreignId('created_by_user_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->primary(['housing_service_id', 'template_set_id'], 'hs_tpl_sets_pk');
            $table->index('template_set_id', 'hs_tpl_sets_reverse_idx');

            $table->foreign('housing_service_id', 'hs_tpl_sets_hs_fk')
                ->references('id')->on('housing_services')
                ->cascadeOnDelete();
            $table->foreign('template_set_id', 'hs_tpl_sets_tpl_fk')
                ->references('id')->on('requirement_template_sets')
                ->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'hs_tpl_sets_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_service_template_sets');
    }
};
