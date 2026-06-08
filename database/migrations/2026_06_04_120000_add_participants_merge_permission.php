<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the `participants.merge` permission (gating the admin record-merge tool) and
 * grants it to the admin role. Data-only and idempotent — safe to run on production.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'participants.merge')->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'participants.merge',
                'display_name' => 'Merge Participants',
                'category' => 'Participants',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Grant to any admin/administrator role.
        $adminRoleIds = DB::table('roles')->whereIn('name', ['admin', 'administrator'])->pluck('id');

        foreach ($adminRoleIds as $roleId) {
            $exists = DB::table('role_permission')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_permission')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'participants.merge')->value('id');

        if ($permissionId) {
            DB::table('role_permission')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
