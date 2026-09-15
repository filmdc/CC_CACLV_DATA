<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * poverty_guidelines.region was born as enum('48_contiguous', 'alaska',
 * 'hawaii') — fine while the table only ever held the HHS federal
 * poverty guidelines. The housing projects module now keeps the HUD
 * HOME income limits in the same table under its own region key
 * (PovertyGuideline::REGION_HOME_ABE, 'home_abe_metro'), and MySQL
 * refuses any value outside an enum ("Data truncated for column
 * 'region'"), which is exactly how HomeIncomeLimitsSeeder died in CI.
 *
 * The column becomes a plain string(32) — the same width as
 * intake_income_assessments.guideline_region, which snapshots this
 * value. The admin UI already treats region as an open string
 * (validation is 'required|string' and the filter list is built from
 * distinct values), so nothing else has to change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('poverty_guidelines', function (Blueprint $table) {
            $table->string('region', 32)->change();
        });
    }

    public function down(): void
    {
        // Rows outside the original enum cannot survive the trip back.
        DB::table('poverty_guidelines')
            ->whereNotIn('region', ['48_contiguous', 'alaska', 'hawaii'])
            ->delete();

        DB::statement("ALTER TABLE `poverty_guidelines` MODIFY `region` ENUM('48_contiguous', 'alaska', 'hawaii') NOT NULL");
    }
};
