<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->decimal('intacct_budget_amount', 14, 2)
                ->nullable()
                ->after('amount')
                ->comment('Total expense budget summed from Intacct GLBUDGETITEM. When set, overrides contract.amount as the utilization denominator.');

            $table->timestamp('intacct_budget_synced_at')
                ->nullable()
                ->after('intacct_budget_amount')
                ->comment('When the Intacct budget was last synced for this contract');

            $table->string('intacct_budget_source', 100)
                ->nullable()
                ->after('intacct_budget_synced_at')
                ->comment('BUDGETID the budget was pulled from (e.g. FY26_Budget)');

            $table->unsignedSmallInteger('intacct_budget_lines_count')
                ->nullable()
                ->after('intacct_budget_source')
                ->comment('Number of expense budget line items aggregated');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'intacct_budget_amount',
                'intacct_budget_synced_at',
                'intacct_budget_source',
                'intacct_budget_lines_count',
            ]);
        });
    }
};
