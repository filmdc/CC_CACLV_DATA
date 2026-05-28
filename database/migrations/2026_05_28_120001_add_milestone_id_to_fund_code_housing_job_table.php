<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Milestone revamp: a fund code attached to a housing job can be assigned
 * to at most one milestone on that project (its "service unit"). Many
 * fund codes may share a milestone; a fund code with no assigned
 * milestone surfaces under "Unassigned" in the per-project requirements
 * view. Functional re-parenting: a fund code's Phase 2 requirements
 * render under the milestone the fund code is assigned to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_code_housing_job', function (Blueprint $table) {
            $table->foreignId('milestone_id')
                ->nullable()
                ->after('fund_code_id')
                ->constrained('project_milestones', 'id', 'fk_fchj_milestone_id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fund_code_housing_job', function (Blueprint $table) {
            $table->dropForeign('fk_fchj_milestone_id');
            $table->dropColumn('milestone_id');
        });
    }
};
