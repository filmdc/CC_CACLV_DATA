<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ContractPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create contract permissions
        $contractPermissions = [
            'contracts.view' => 'View contracts',
            'contracts.create' => 'Create contracts',
            'contracts.edit' => 'Edit contracts',
            'contracts.delete' => 'Delete contracts',
            'contracts.archive' => 'Archive contracts',
            'contracts.manage_notifications' => 'Manage contract notification recipients and distribution lists',
            'funders.view' => 'View funders',
            'funders.create' => 'Create funders',
            'funders.edit' => 'Edit funders',
            'funders.delete' => 'Delete funders',
            'contract_reports.view' => 'View contract reports',
            'contract_reports.create' => 'Create contract reports',
            'contract_reports.create_orphan' => 'Create general reports without due dates',
            'contract_reports.associate' => 'Associate general reports with due dates',
            'contract_reports.edit' => 'Edit contract reports',
            'contract_reports.delete' => 'Delete contract reports',
            'contract_reports.approve' => 'Approve contract reports',
            'due_dates.view' => 'View due dates',
            'due_dates.create' => 'Create due dates',
            'due_dates.edit' => 'Edit due dates',
            'due_dates.delete' => 'Delete due dates',
            'due_date_emails.manage' => 'Manage due date email assignments',
        ];

        foreach ($contractPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => ucwords(str_replace(['.', '_'], [' ', ' '], $name)),
                    'description' => $description,
                    'category' => 'contracts',
                ]
            );
        }

        // Assign all contract permissions to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $permissions = Permission::whereIn('name', array_keys($contractPermissions))->get();
            $adminRole->permissions()->syncWithoutDetaching($permissions);
        }

        $this->command->info('Contract permissions created and assigned to admin role.');
    }
}
