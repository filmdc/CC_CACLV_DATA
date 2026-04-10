<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::updateOrCreate(
            ['name' => 'approvals.executive_view'],
            [
                'display_name' => 'Executive Approval Overview',
                'description' => 'Access executive-level approval overview with org-wide metrics',
                'category' => 'Approvals',
            ]
        );

        $adminRole = Role::whereIn('name', ['admin', 'Administrator'])->first();
        if ($adminRole && ! $adminRole->permissions->contains($permission->id)) {
            $adminRole->permissions()->attach($permission->id);
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'approvals.executive_view')->first();
        if ($permission) {
            $permission->roles()->detach();
            $permission->delete();
        }
    }
};
