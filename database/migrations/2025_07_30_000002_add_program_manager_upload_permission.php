<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('name', 'program-manager-uploads.manage')->exists();

        if (!$exists) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'program-manager-uploads.manage',
                'display_name' => 'Manage Program Manager Uploads',
                'description' => 'View and process uploads submitted through the public program manager portal.',
                'category' => 'Program Management',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $adminRoleId = DB::table('roles')->where('name', 'Administrator')->value('id');
            if ($adminRoleId) {
                DB::table('role_permission')->insert([
                    'role_id' => $adminRoleId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('name', 'program-manager-uploads.manage')->first();

        if ($permission) {
            DB::table('role_permission')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
};
