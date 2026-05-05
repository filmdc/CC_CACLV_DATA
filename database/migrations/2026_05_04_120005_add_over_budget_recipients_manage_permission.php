<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

/**
 * Phase 3B Step 7 (P-23 part B) — dedicated admin permission for the
 * over-budget recipient list management page.
 *
 * Mirrors the 3A pattern (housing_dashboard.view): admin-only by
 * convention, NOT auto-attached to any role at migration time.
 * Production admin grants it explicitly via the existing role-
 * permission UI to whoever should manage the budget-email list
 * (typically just the Director and the Housing Project Manager).
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(
            ['name' => 'over_budget_recipients.manage'],
            [
                'display_name' => 'Manage Over-Budget Email Recipients',
                'description' => 'Add or remove users from the over_budget_email_recipients list at /admin/over-budget-recipients. Recipients receive notifications when a project budget category is modified (increase or decrease).',
            ]
        );
    }

    public function down(): void
    {
        Permission::where('name', 'over_budget_recipients.manage')->delete();
    }
};
