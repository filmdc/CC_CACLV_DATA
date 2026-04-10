<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove orphaned permissions that are defined in the database but never
     * checked anywhere in the application code. Also detach them from any
     * roles so role_permission pivot rows don't reference deleted permissions.
     */
    public function up(): void
    {
        $orphaned = [
            'intacct.view_history',      // Defined in DonorPerfectSeeder but never checked in code
            'donorperfect.mappings.edit', // Listed in role seeder but code checks sage.mappings.edit instead
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $orphaned)
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            // Remove from role_permission pivot first
            DB::table('role_permission')->whereIn('permission_id', $permissionIds)->delete();

            // Remove the permissions themselves
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }

    /**
     * Re-create the removed permissions if rolling back.
     */
    public function down(): void
    {
        $permissions = [
            [
                'name' => 'intacct.view_history',
                'display_name' => 'View Intacct Sync History',
                'description' => 'View historical sync records and detailed sync item results',
                'category' => 'Accounting & Fiscal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'donorperfect.mappings.edit',
                'display_name' => 'Edit DonorPerfect Mappings',
                'description' => 'Edit DonorPerfect field and code mappings',
                'category' => 'Accounting & Fiscal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore($permission);
        }
    }
};
