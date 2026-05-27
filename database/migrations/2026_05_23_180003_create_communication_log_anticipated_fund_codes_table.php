<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P-39b (addendum D-2): anticipated fund codes on a CommunicationLog.
 *
 * Replaces the legacy `communication_log_fund_code` tag pivot, which
 * was a free-form association. "Anticipated" expresses the intake
 * worker's intent that this applicant will likely pursue a given fund
 * code — that signal drives IntakeRequirementInstantiator::forFundCode().
 *
 * Surrogate id rather than composite (communication_log_id, fund_code_id)
 * PK because per-row created_by_user_id is useful audit and the unique
 * constraint covers data integrity. No updated_at: reconciliation
 * deletes and recreates rather than mutating in place.
 *
 * The cutover migration in this PR (Step 4 of the migration sequence)
 * backfills from the legacy tag pivot and then drops it.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Table name is 41 chars; auto-generated FK names would exceed
        // MySQL's 64-char identifier limit. Constraint names are explicit
        // and prefixed with the short stem `cl_anticipated_fc`.
        Schema::create('communication_log_anticipated_fund_codes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('communication_log_id');
            $table->foreign('communication_log_id', 'cl_anticipated_fc_cl_id_fk')
                ->references('id')->on('communication_logs')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('fund_code_id');
            $table->foreign('fund_code_id', 'cl_anticipated_fc_fc_id_fk')
                ->references('id')->on('fund_codes')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->foreign('created_by_user_id', 'cl_anticipated_fc_user_id_fk')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->nullable();

            $table->unique(
                ['communication_log_id', 'fund_code_id'],
                'cl_anticipated_fc_uniq'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_log_anticipated_fund_codes');
    }
};
