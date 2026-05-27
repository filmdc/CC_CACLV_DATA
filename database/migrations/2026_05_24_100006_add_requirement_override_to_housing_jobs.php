<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 (Option A): Convert-to-Project requirements-gate audit.
 *
 * Per spec §4.6 + §5.7: if Kaitlyn invokes Convert-to-Project while one
 * or more required requirements on the intake are unmet, she may tick
 * an "Override and create anyway" checkbox accompanied by a written
 * reason. The override is recorded permanently on the resulting
 * housing_jobs row and MUST be surfaced visibly on the project page
 * (location TBD, see Q15).
 *
 * Both fields are nullable — projects created from a fully-gated intake
 * (all requirements met) have nothing to record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->text('requirement_override_reason')->nullable()->after('updated_at');
            $table->foreignId('requirement_override_by_user_id')
                ->nullable()
                ->after('requirement_override_reason')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('requirement_overridden_at')->nullable()
                ->after('requirement_override_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            $table->dropForeign(['requirement_override_by_user_id']);
            $table->dropColumn([
                'requirement_override_reason',
                'requirement_override_by_user_id',
                'requirement_overridden_at',
            ]);
        });
    }
};
