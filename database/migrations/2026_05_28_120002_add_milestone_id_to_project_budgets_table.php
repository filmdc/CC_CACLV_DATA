<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Milestone revamp: a budget category on a project can be assigned to
 * at most one milestone. The milestone re-parents the budget item in
 * the requirements/budget view so spend rolls up under the service unit
 * the PM is tracking. nullOnDelete keeps the budget row alive if the
 * milestone is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->foreignId('milestone_id')
                ->nullable()
                ->after('fund_code_id')
                ->constrained('project_milestones', 'id', 'fk_pb_milestone_id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_budgets', function (Blueprint $table) {
            $table->dropForeign('fk_pb_milestone_id');
            $table->dropColumn('milestone_id');
        });
    }
};
