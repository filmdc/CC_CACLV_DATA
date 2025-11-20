<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            // DonorPerfect Sync Permissions
            [
                'name' => 'donorperfect.sync.view',
                'display_name' => 'View DonorPerfect Sync',
                'description' => 'View DonorPerfect sync configuration and history',
                'category' => 'DonorPerfect Sync',
            ],
            [
                'name' => 'donorperfect.sync.refresh',
                'display_name' => 'Refresh Gift Cache',
                'description' => 'Refresh gift cache from DonorPerfect',
                'category' => 'DonorPerfect Sync',
            ],
            [
                'name' => 'donorperfect.mappings.edit',
                'display_name' => 'Edit DonorPerfect Mappings',
                'description' => 'Edit DonorPerfect code mappings',
                'category' => 'DonorPerfect Sync',
            ],

            // Sage Intacct Sync Permissions
            [
                'name' => 'sage.sync.view',
                'display_name' => 'View Sage Intacct Sync',
                'description' => 'View Sage Intacct sync configuration and history',
                'category' => 'Sage Intacct Sync',
            ],
            [
                'name' => 'sage.sync.perform',
                'display_name' => 'Perform Sage Intacct Sync',
                'description' => 'Perform Sage Intacct sync operations',
                'category' => 'Sage Intacct Sync',
            ],
            [
                'name' => 'sage.mappings.edit',
                'display_name' => 'Edit Field Mappings',
                'description' => 'Edit Sage Intacct field mappings',
                'category' => 'Sage Intacct Sync',
            ],
            [
                'name' => 'sage.code_mappings.edit',
                'display_name' => 'Edit Code Mappings',
                'description' => 'Edit Sage Intacct code mappings (GL, Campaign, etc.)',
                'category' => 'Sage Intacct Sync',
            ],
            [
                'name' => 'sage.conditional_mappings.edit',
                'display_name' => 'Edit Conditional Mappings',
                'description' => 'Edit conditional code mappings',
                'category' => 'Sage Intacct Sync',
            ],
            [
                'name' => 'sage.filters.edit',
                'display_name' => 'Edit Sync Filters',
                'description' => 'Edit Sage Intacct sync filters',
                'category' => 'Sage Intacct Sync',
            ],
        ];

        $timestamp = now();

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
                'category' => $permission['category'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionNames = [
            'donorperfect.sync.view',
            'donorperfect.sync.refresh',
            'donorperfect.mappings.edit',
            'sage.sync.view',
            'sage.sync.perform',
            'sage.mappings.edit',
            'sage.code_mappings.edit',
            'sage.conditional_mappings.edit',
            'sage.filters.edit',
        ];

        DB::table('permissions')->whereIn('name', $permissionNames)->delete();
    }
};
