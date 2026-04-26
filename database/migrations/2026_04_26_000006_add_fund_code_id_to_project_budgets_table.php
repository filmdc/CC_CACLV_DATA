<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2.5 — give each budget category row its own fund code.
 *
 * Phase 2 wired fund codes to the project (fund_code_housing_job pivot
 * + primary_fund_code_id on housing_jobs) but the budget form was left
 * with the legacy Contract dropdown. This migration adds the column
 * the new Fund Code dropdown writes into.
 *
 * Existing rows stay NULL on purpose. The operator sets the fund code
 * per row on next edit. No automatic inference from contract or from
 * project primary — that decision is the operator's, by design.
 *
 * The schema-level invariant ("fund_code_id must be one of the project's
 * assigned codes") is enforced at the application layer via a saving
 * event on ProjectBudget — same shape as Phase 2's primary_fund_code_id
 * guard on HousingJob. MySQL FK alone can't express the cross-table
 * constraint without a trigger.
 *
 * Reversible. project_budgets.contract_id and the belongsTo(Contract)
 * relationship are preserved — Phase 2.5 hides the dropdown but keeps
 * the column for any future use.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->foreignId('fund_code_id')
                ->nullable()
                ->after('contract_id')
                ->constrained('fund_codes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fund_code_id');
        });
    }
};
