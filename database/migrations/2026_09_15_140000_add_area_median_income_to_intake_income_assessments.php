<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Housing intakes report a percent of AREA MEDIAN INCOME — that is how
 * HUD bands HOME beneficiaries (ELI / VLI / low income) and how the
 * figure is reported in IDIS. Until now the percentage was computed
 * against the 80% AMI ceiling in poverty_100 instead, so a household at
 * 24% of AMI read as "30.1% of the HOME income limit".
 *
 * poverty_threshold keeps its meaning untouched — the eligibility
 * ceiling the household was judged against. This column records the
 * area median income the percentage is a percentage OF, so a HOME
 * determination stays a self-contained snapshot: income, ceiling, AMI,
 * percentage and tier all frozen at save time, per the module's rule
 * that a determination keeps reading the way it read when it was made.
 *
 * Nullable, so rows written before this (and every HHS/FPL row, which
 * has no AMI concept) simply carry null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intake_income_assessments', function (Blueprint $table) {
            $table->decimal('area_median_income', 12, 2)
                ->nullable()
                ->after('poverty_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('intake_income_assessments', function (Blueprint $table) {
            $table->dropColumn('area_median_income');
        });
    }
};
