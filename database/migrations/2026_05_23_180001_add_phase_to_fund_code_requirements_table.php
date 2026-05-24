<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P-39b (addendum D-1): split fund-code requirement templates into two
 * phases so intake and project requirements draw from disjoint subsets
 * on the same fund. Default 'project' for backward compatibility — every
 * existing template was authored before the eligibility/project split
 * existed and represents project-execution work.
 *
 * IntakeRequirementInstantiator::forFundCode() filters phase='eligibility';
 * RequirementInstantiator::forFundCode() filters phase='project'. Both
 * filters are added in this PR; the backfill below guarantees the
 * project-side filter sees every existing row.
 *
 * The admin form for fund-code requirement templates does NOT yet have a
 * phase selector — that hand-off lands in 39d or a small dedicated
 * follow-up. Until then, the column default ensures new templates posted
 * by the existing form land as project-phase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fund_code_requirements', function (Blueprint $table) {
            $table->enum('phase', ['eligibility', 'project'])
                ->default('project')
                ->after('is_hard_requirement');
        });

        // Belt-and-braces backfill. The default-value clause already
        // covers new rows; this explicit update guarantees the legacy
        // fleet is project-phase regardless of how the column was
        // introduced (some MySQL versions don't apply enum defaults
        // retroactively in ALTER TABLE).
        DB::table('fund_code_requirements')->update(['phase' => 'project']);
    }

    public function down(): void
    {
        Schema::table('fund_code_requirements', function (Blueprint $table) {
            $table->dropColumn('phase');
        });
    }
};
