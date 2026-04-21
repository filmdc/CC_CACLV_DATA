<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('preferred_intacct_budget_id', 100)
                ->nullable()
                ->after('intacct_budget_lines_count')
                ->comment('Per-contract override — when set, the budget sync uses only this Intacct BUDGETID instead of iterating the site-wide priority list.');

            $table->index('preferred_intacct_budget_id');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['preferred_intacct_budget_id']);
            $table->dropColumn('preferred_intacct_budget_id');
        });
    }
};
