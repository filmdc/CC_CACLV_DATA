<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * P-17 hotfix: dedicated permission gate for the housing dashboard.
     *
     * The original Step 9 redirect predicate was hasPermission('projects.view')
     * which short-circuits for admins (admin role auto-grants every
     * permission), and a follow-up data-driven predicate based on
     * user_program assignment was too broad — many programs have
     * housing properties without being centrally housing-focused.
     *
     * This migration creates a single, narrow permission that admins
     * explicitly grant to whichever roles should see the housing
     * dashboard. NOT auto-attached to any role at migration time:
     * production admin assigns it via the existing role-permission UI.
     */
    public function up(): void
    {
        Permission::firstOrCreate(
            ['name' => 'housing_dashboard.view'],
            [
                'display_name' => 'Housing Dashboard View',
                'description' => 'Access the housing dashboard at /dashboard/housing and trigger the auto-redirect from /dashboard.',
            ]
        );
    }

    public function down(): void
    {
        Permission::where('name', 'housing_dashboard.view')->delete();
    }
};
