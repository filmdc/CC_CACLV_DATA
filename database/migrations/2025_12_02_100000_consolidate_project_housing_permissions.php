<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consolidate project and housing permissions.
     *
     * This migration:
     * 1. Renames .update permissions to .edit for consistency
     * 2. Migrates housing_jobs.* permissions to projects.*
     * 3. Merges duplicate roles (Project Manager → project_manager)
     * 4. Cleans up orphaned permissions
     */
    public function up(): void
    {
        echo "Starting permission consolidation...\n";

        // Step 1: Rename .update permissions to .edit
        $this->renameUpdatePermissions();

        // Step 2: Migrate housing_jobs.* to projects.*
        $this->migrateHousingJobsPermissions();

        // Step 3: Merge duplicate roles
        $this->mergeDuplicateRoles();

        // Step 4: Clean up orphaned housing_jobs.* permissions
        $this->cleanupOrphanedPermissions();

        echo "Permission consolidation complete!\n";
    }

    /**
     * Rename .update permissions to .edit for consistency.
     */
    protected function renameUpdatePermissions(): void
    {
        $renames = [
            'properties.update' => 'properties.edit',
            'contractors.update' => 'contractors.edit',
            'call_logs.update' => 'call_logs.edit',
            'communications_log.update' => 'communications_log.edit',
            'housing_jobs.update' => 'housing_jobs.edit', // Will be migrated to projects.edit next
        ];

        foreach ($renames as $old => $new) {
            // Check if old permission exists and new doesn't
            $oldPerm = DB::table('permissions')->where('name', $old)->first();
            $newPerm = DB::table('permissions')->where('name', $new)->first();

            if ($oldPerm && ! $newPerm) {
                // Simply rename the permission
                DB::table('permissions')
                    ->where('name', $old)
                    ->update([
                        'name' => $new,
                        'display_name' => ucwords(str_replace(['.', '_'], ' ', $new)),
                    ]);
                echo "  Renamed: {$old} -> {$new}\n";
            } elseif ($oldPerm && $newPerm) {
                // Both exist - migrate role assignments and delete old
                $this->mergePermissions($oldPerm->id, $newPerm->id);
                echo "  Merged: {$old} into {$new}\n";
            }
        }
    }

    /**
     * Migrate housing_jobs.* permissions to projects.* namespace.
     */
    protected function migrateHousingJobsPermissions(): void
    {
        $mappings = [
            'housing_jobs.view' => 'projects.view',
            'housing_jobs.create' => 'projects.create',
            'housing_jobs.edit' => 'projects.edit',
            'housing_jobs.delete' => 'projects.delete',
            'housing_jobs.approve' => 'projects.approve',
            'housing_jobs.contacts' => 'projects.contacts',
            'housing_jobs.documents' => 'projects.documents',
            'housing_jobs.verify_documents' => 'projects.verify_documents',
            'housing_jobs.approve_tasks' => 'projects.approve_tasks',
        ];

        foreach ($mappings as $old => $new) {
            $oldPerm = DB::table('permissions')->where('name', $old)->first();
            $newPerm = DB::table('permissions')->where('name', $new)->first();

            if ($oldPerm && $newPerm) {
                // Both exist - copy role assignments from old to new, then delete old
                $this->mergePermissions($oldPerm->id, $newPerm->id);
                echo "  Migrated: {$old} -> {$new}\n";
            } elseif ($oldPerm && ! $newPerm) {
                // Only old exists - rename it
                DB::table('permissions')
                    ->where('name', $old)
                    ->update([
                        'name' => $new,
                        'display_name' => ucwords(str_replace(['.', '_'], ' ', $new)),
                        'description' => str_replace('housing job', 'project', $oldPerm->description ?? ''),
                    ]);
                echo "  Renamed: {$old} -> {$new}\n";
            }
        }
    }

    /**
     * Merge duplicate roles.
     */
    protected function mergeDuplicateRoles(): void
    {
        // Merge "Project Manager" (with space) into "project_manager" (underscore)
        $this->mergeRoles('Project Manager', 'project_manager');

        // Merge "Project Team Member" (with space) into "project_team_member" (underscore)
        $this->mergeRoles('Project Team Member', 'project_team_member');
    }

    /**
     * Merge one role into another, transferring all user and permission assignments.
     */
    protected function mergeRoles(string $fromRoleName, string $toRoleName): void
    {
        $fromRole = DB::table('roles')->where('name', $fromRoleName)->first();
        $toRole = DB::table('roles')->where('name', $toRoleName)->first();

        if (! $fromRole) {
            echo "  Role '{$fromRoleName}' does not exist, skipping merge.\n";

            return;
        }

        if (! $toRole) {
            // Target role doesn't exist - just rename the source role
            DB::table('roles')
                ->where('id', $fromRole->id)
                ->update(['name' => $toRoleName]);
            echo "  Renamed role: {$fromRoleName} -> {$toRoleName}\n";

            return;
        }

        // Both roles exist - merge users and permissions from $fromRole to $toRole

        // Transfer user-role assignments (table is user_role, not role_user)
        $fromUserRoles = DB::table('user_role')->where('role_id', $fromRole->id)->get();
        foreach ($fromUserRoles as $userRole) {
            // Check if user already has the target role
            $exists = DB::table('user_role')
                ->where('user_id', $userRole->user_id)
                ->where('role_id', $toRole->id)
                ->exists();

            if (! $exists) {
                DB::table('user_role')->insert([
                    'user_id' => $userRole->user_id,
                    'role_id' => $toRole->id,
                ]);
            }
        }

        // Transfer role-permission assignments (table is role_permission, not permission_role)
        $fromRolePermissions = DB::table('role_permission')->where('role_id', $fromRole->id)->get();
        foreach ($fromRolePermissions as $rolePerm) {
            $exists = DB::table('role_permission')
                ->where('role_id', $toRole->id)
                ->where('permission_id', $rolePerm->permission_id)
                ->exists();

            if (! $exists) {
                DB::table('role_permission')->insert([
                    'role_id' => $toRole->id,
                    'permission_id' => $rolePerm->permission_id,
                ]);
            }
        }

        // Delete the source role and its pivot records
        DB::table('user_role')->where('role_id', $fromRole->id)->delete();
        DB::table('role_permission')->where('role_id', $fromRole->id)->delete();
        DB::table('roles')->where('id', $fromRole->id)->delete();

        $userCount = count($fromUserRoles);
        $permCount = count($fromRolePermissions);
        echo "  Merged role '{$fromRoleName}' into '{$toRoleName}' ({$userCount} users, {$permCount} permissions)\n";
    }

    /**
     * Merge permission role assignments and delete the source permission.
     */
    protected function mergePermissions(int $fromPermId, int $toPermId): void
    {
        // Copy role_permission assignments (table is role_permission, not permission_role)
        $fromRoles = DB::table('role_permission')->where('permission_id', $fromPermId)->get();
        foreach ($fromRoles as $rolePerm) {
            $exists = DB::table('role_permission')
                ->where('role_id', $rolePerm->role_id)
                ->where('permission_id', $toPermId)
                ->exists();

            if (! $exists) {
                DB::table('role_permission')->insert([
                    'role_id' => $rolePerm->role_id,
                    'permission_id' => $toPermId,
                ]);
            }
        }

        // Copy user_permission assignments (if direct user-permission pivot exists)
        if (Schema::hasTable('user_permission')) {
            $fromUsers = DB::table('user_permission')->where('permission_id', $fromPermId)->get();
            foreach ($fromUsers as $userPerm) {
                $exists = DB::table('user_permission')
                    ->where('user_id', $userPerm->user_id)
                    ->where('permission_id', $toPermId)
                    ->exists();

                if (! $exists) {
                    DB::table('user_permission')->insert([
                        'user_id' => $userPerm->user_id,
                        'permission_id' => $toPermId,
                    ]);
                }
            }
            DB::table('user_permission')->where('permission_id', $fromPermId)->delete();
        }

        // Delete the source permission
        DB::table('role_permission')->where('permission_id', $fromPermId)->delete();
        DB::table('permissions')->where('id', $fromPermId)->delete();
    }

    /**
     * Clean up orphaned housing_jobs.* permissions.
     */
    protected function cleanupOrphanedPermissions(): void
    {
        $orphanedPermissions = DB::table('permissions')
            ->where('name', 'like', 'housing_jobs.%')
            ->get();

        foreach ($orphanedPermissions as $perm) {
            // Remove from role_permission pivot
            DB::table('role_permission')->where('permission_id', $perm->id)->delete();

            // Remove from user_permission pivot if exists
            if (Schema::hasTable('user_permission')) {
                DB::table('user_permission')->where('permission_id', $perm->id)->delete();
            }

            // Delete the permission
            DB::table('permissions')->where('id', $perm->id)->delete();
            echo "  Deleted orphaned: {$perm->name}\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration consolidates permissions and cannot be fully reversed.
        // The seeder will recreate the correct permission state.
        echo "Warning: Permission consolidation cannot be fully reversed.\n";
        echo "Run the ProjectHousingPermissionsSeeder to restore the correct permission state.\n";
    }
};
