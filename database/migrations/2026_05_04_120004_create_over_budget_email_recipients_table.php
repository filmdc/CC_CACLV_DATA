<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3B Step 7 (P-23 part B) — admin-managed list of users who
 * receive emails when a ProjectBudget.budgeted_amount is modified.
 *
 * One row per user. The unique constraint on user_id dedupes at the
 * DB level so the admin UI doesn't need to guard against a
 * double-add race.
 *
 * Cascade-on-delete from users so that deactivating/deleting a user
 * cleans up their recipient registration automatically. The user_id
 * column is the only data — no per-recipient overrides on cadence,
 * threshold, or anything else, by phase scope.
 *
 * The over_budget_email category opt-out lives on
 * users.notification_preferences (Phase 3B Step 2) and is checked at
 * notification dispatch time — the recipient list is the eligibility
 * predicate; the per-user toggle is the delivery predicate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('over_budget_email_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('over_budget_email_recipients');
    }
};
