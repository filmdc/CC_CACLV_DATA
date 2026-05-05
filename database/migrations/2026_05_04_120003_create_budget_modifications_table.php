<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3B Step 7 (P-23 part B) — append-only audit ledger for any
 * change to ProjectBudget.budgeted_amount.
 *
 * Every modification (increase OR decrease) writes one row here. Rows
 * are never updated or deleted by application code; the table is an
 * audit trail. Cascade-on-delete from project_budgets is intentional:
 * if the project itself is deleted (which cascades budgets), the
 * audit rows go with it — we don't keep orphan ledger entries
 * referencing a project that no longer exists.
 *
 * old_amount / new_amount are decimal(15,2) to match
 * project_budgets.budgeted_amount. comment is text with a 10-char
 * minimum enforced server-side at the Livewire validation layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_budget_id')
                ->constrained('project_budgets')
                ->cascadeOnDelete();
            $table->foreignId('modified_by_user_id')
                ->constrained('users');
            $table->decimal('old_amount', 15, 2);
            $table->decimal('new_amount', 15, 2);
            $table->text('comment');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_budget_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_modifications');
    }
};
