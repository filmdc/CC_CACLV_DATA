<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Shift fund-utilization storage from contract-keyed to fund-keyed so that
 * a single Intacct fund (e.g. F8000) which is shared across dozens of
 * contracts is recorded exactly once, and funds that exist in Intacct but
 * have no local contract (e.g. F0135 General Unrestricted) can still be
 * synced and displayed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the foreign key first (it depends on the unique index below).
        Schema::table('intacct_fund_expenses', function (Blueprint $table) {
            $table->dropForeign(['contract_id']);
        });

        // Make contract_id nullable so orphan funds (Sage-only, no matching
        // local contract) can be stored.
        Schema::table('intacct_fund_expenses', function (Blueprint $table) {
            $table->foreignId('contract_id')->nullable()->change();
        });

        // Drop the old contract-keyed unique constraint and replace with a
        // fund-keyed one. First normalize NULL department_id -> '' so the
        // constraint behaves correctly in MySQL (NULLs break uniqueness).
        Schema::table('intacct_fund_expenses', function (Blueprint $table) {
            $table->dropUnique('intacct_fund_expenses_contract_record_unique');
        });

        DB::table('intacct_fund_expenses')
            ->whereNull('intacct_department_id')
            ->update(['intacct_department_id' => '']);

        Schema::table('intacct_fund_expenses', function (Blueprint $table) {
            $table->string('intacct_department_id', 50)->default('')->nullable(false)->change();
            $table->string('intacct_project_name', 255)->nullable()->after('intacct_department_id');

            $table->unique(
                ['intacct_project_id', 'intacct_department_id', 'intacct_record_no'],
                'intacct_fund_expenses_fund_record_unique'
            );

            // Re-add the foreign key, now on a nullable column.
            $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();
        });

        // New fund-keyed budgets table. One row per (project_id, dept_id)
        // within a given fiscal-year window.
        Schema::create('intacct_fund_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('intacct_project_id', 50);
            $table->string('intacct_department_id', 50)->default('');
            $table->string('intacct_project_name', 255)->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('budget_source', 100)->nullable()
                ->comment('Intacct BUDGETID this was pulled from');
            $table->unsignedSmallInteger('lines_count')->default(0);
            $table->unsignedSmallInteger('fiscal_year')->nullable();
            $table->date('window_start')->nullable();
            $table->date('window_end')->nullable();
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(
                ['intacct_project_id', 'intacct_department_id'],
                'intacct_fund_budgets_fund_unique'
            );
            $table->index('intacct_project_id');
            $table->index('fiscal_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intacct_fund_budgets');

        Schema::table('intacct_fund_expenses', function (Blueprint $table) {
            $table->dropUnique('intacct_fund_expenses_fund_record_unique');
            $table->dropColumn('intacct_project_name');
            $table->string('intacct_department_id', 50)->nullable()->change();
            $table->unique(
                ['contract_id', 'intacct_record_no'],
                'intacct_fund_expenses_contract_record_unique'
            );
        });
    }
};
