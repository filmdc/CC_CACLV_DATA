<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `is_housing_team` (a.k.a. "Community Action Homes designation")
 * flag to the users table.
 *
 * Drives the owner / assigned-to picker on the Project Detail page so
 * project managers only see teammates whose user profile explicitly
 * marks them as a Housing team member. The legacy filter that lived
 * inline in ProjectManagement::render() did a brittle
 *   Program::where('name', 'LIKE', '%CAH%')
 * lookup — fragile against any program rename, and unable to express a
 * dedicated team membership that's distinct from program access.
 *
 * Backfill: preserves the current visible-set on deploy. Anyone who
 * would have appeared under the old query — admins, contract managers,
 * or users explicitly assigned to a housing-enabled program — gets
 * is_housing_team=true so the picker keeps the same population. Admins
 * can later toggle individuals off in user-management.
 *
 * Indexed (is_housing_team, active) because the picker query is
 *   WHERE is_housing_team = 1 AND active = 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_housing_team')
                ->default(false)
                ->after('active');

            $table->index(['is_housing_team', 'active'], 'users_housing_team_active_idx');
        });

        // Backfill — preserve who is currently visible in the picker.
        // The legacy filter (ProjectManagement::render, pre-2026-06-01)
        // surfaced: users with role Admin / Contract Manager, OR users
        // assigned to any program flagged housing_module_enabled.
        $housingProgramIds = DB::table('programs')
            ->where('housing_module_enabled', true)
            ->pluck('id')
            ->all();

        // (1) Admins + Contract Managers.
        $privilegedRoleIds = DB::table('roles')
            ->whereIn('name', ['Admin', 'admin', 'Contract Manager', 'contract_manager'])
            ->pluck('id')
            ->all();

        if (! empty($privilegedRoleIds)) {
            $privilegedUserIds = DB::table('role_user')
                ->whereIn('role_id', $privilegedRoleIds)
                ->pluck('user_id')
                ->unique()
                ->all();

            if (! empty($privilegedUserIds)) {
                DB::table('users')
                    ->whereIn('id', $privilegedUserIds)
                    ->update(['is_housing_team' => true]);
            }
        }

        // (2) Users on a housing-enabled program.
        if (! empty($housingProgramIds)) {
            $housingUserIds = DB::table('user_program')
                ->whereIn('program_id', $housingProgramIds)
                ->pluck('user_id')
                ->unique()
                ->all();

            if (! empty($housingUserIds)) {
                DB::table('users')
                    ->whereIn('id', $housingUserIds)
                    ->update(['is_housing_team' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_housing_team_active_idx');
            $table->dropColumn('is_housing_team');
        });
    }
};
