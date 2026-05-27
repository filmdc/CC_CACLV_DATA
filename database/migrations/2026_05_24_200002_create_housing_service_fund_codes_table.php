<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 / Phase 9: housing_services ↔ fund_codes M:M.
 *
 * Picking a housing service on a Communication Log auto-checks every
 * fund code in this pivot under the form's anticipated_fund_codes
 * selection — the "auto-select the funds that can be used for
 * budgeting and requirements" affordance.
 *
 * Explicit short FK names to dodge MySQL's 64-char identifier limit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_service_fund_codes', function (Blueprint $table) {
            $table->unsignedBigInteger('housing_service_id');
            $table->unsignedBigInteger('fund_code_id');
            $table->foreignId('created_by_user_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->primary(['housing_service_id', 'fund_code_id'], 'hs_fund_codes_pk');
            $table->index('fund_code_id', 'hs_fund_codes_reverse_idx');

            $table->foreign('housing_service_id', 'hs_fund_codes_hs_fk')
                ->references('id')->on('housing_services')
                ->cascadeOnDelete();
            $table->foreign('fund_code_id', 'hs_fund_codes_fc_fk')
                ->references('id')->on('fund_codes')
                ->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'hs_fund_codes_user_fk')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_service_fund_codes');
    }
};
