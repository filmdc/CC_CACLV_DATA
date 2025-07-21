<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the contract database dashboard role
        $role = Role::create([
            'name' => 'contract-database-dashboard',
            'display_name' => 'Contract Database Dashboard',
            'description' => 'User with access to contract reporting dashboard only'
        ]);
        
        // Create specific permissions for contract dashboard
        $permissions = [
            [
                'name' => 'contracts.view_dashboard',
                'display_name' => 'View Contract Dashboard',
                'description' => 'View contract reporting dashboard'
            ],
            [
                'name' => 'contract_reports.view',
                'display_name' => 'View Contract Reports',
                'description' => 'View contract reports'
            ],
            [
                'name' => 'contract_reports.create',
                'display_name' => 'Create Contract Reports',
                'description' => 'Create and submit contract reports'
            ],
            [
                'name' => 'contract_reports.edit',
                'display_name' => 'Edit Contract Reports',
                'description' => 'Edit contract reports'
            ],
            [
                'name' => 'contract_report_due_dates.view',
                'display_name' => 'View Report Due Dates',
                'description' => 'View assigned report due dates'
            ]
        ];
        
        $permissionIds = [];
        foreach ($permissions as $permData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permData['name']],
                $permData
            );
            $permissionIds[] = $permission->id;
        }
        
        // Assign permissions to the role
        $role->permissions()->sync($permissionIds);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the role
        $role = Role::where('name', 'contract-database-dashboard')->first();
        if ($role) {
            $role->permissions()->detach();
            $role->delete();
        }
        
        // Remove permissions
        Permission::whereIn('name', [
            'contracts.view_dashboard',
            'contract_reports.view',
            'contract_reports.create',
            'contract_reports.edit',
            'contract_report_due_dates.view'
        ])->delete();
    }
};
