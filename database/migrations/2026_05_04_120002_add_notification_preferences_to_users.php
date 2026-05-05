<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3B Step 2 — per-user email opt-outs for the three notification
 * categories: assignment_email (gates RequirementAssigned +
 * RequirementUpstreamUploaded), completion_email (gates
 * RequirementCompleted), over_budget_email (gates BudgetModified;
 * meaningful only for users on the over_budget_email_recipients list).
 *
 * Storage shape: a single nullable JSON column. null is interpreted as
 * "all categories on" by User::shouldEmailFor(), so existing rows need
 * no backfill. Three keys, fixed names. The settings page writes the
 * full struct on save.
 *
 * Reversible: down() drops the column. No data loss for the active
 * feature surface — categories default back to "on" the moment the
 * column is gone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
