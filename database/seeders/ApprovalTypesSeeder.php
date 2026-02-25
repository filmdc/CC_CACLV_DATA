<?php

namespace Database\Seeders;

use App\Models\ApprovalType;
use Illuminate\Database\Seeder;

class ApprovalTypesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding approval types...');

        $types = [
            [
                'name' => 'housing_document',
                'display_name' => 'Housing Document',
                'description' => 'Property and housing-related document approvals',
                'category' => 'Housing',
                'module' => 'housing',
                'icon' => 'home',
                'requires_document' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'contractor_document',
                'display_name' => 'Contractor Document',
                'description' => 'Contractor compliance and certification documents',
                'category' => 'Housing',
                'module' => 'housing',
                'icon' => 'wrench-screwdriver',
                'requires_document' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'job_document',
                'display_name' => 'Job Document',
                'description' => 'Project job checklist document approvals',
                'category' => 'Housing',
                'module' => 'housing',
                'icon' => 'clipboard-document-check',
                'requires_document' => true,
                'sort_order' => 30,
            ],
            [
                'name' => 'work_plan_goal',
                'display_name' => 'Work Plan Goal',
                'description' => 'Work plan goal completion approvals',
                'category' => 'Programs',
                'module' => 'programs',
                'icon' => 'flag',
                'requires_document' => false,
                'sort_order' => 40,
            ],
            [
                'name' => 'performance_report',
                'display_name' => 'Performance Report',
                'description' => 'Performance report review and approval',
                'category' => 'Contracts',
                'module' => 'contracts',
                'icon' => 'chart-bar',
                'requires_document' => true,
                'sort_order' => 50,
            ],
            [
                'name' => 'general_document',
                'display_name' => 'General Document',
                'description' => 'General document approval requests',
                'category' => 'General',
                'module' => 'general',
                'icon' => 'document-text',
                'requires_document' => true,
                'sort_order' => 60,
            ],
            [
                'name' => 'policy_acknowledgment',
                'display_name' => 'Policy Acknowledgment',
                'description' => 'Policy and procedure acknowledgment confirmations',
                'category' => 'General',
                'module' => 'general',
                'icon' => 'shield-check',
                'requires_document' => false,
                'sort_order' => 70,
            ],
            [
                'name' => 'budget_request',
                'display_name' => 'Budget Request',
                'description' => 'Budget allocation and expense approval requests',
                'category' => 'General',
                'module' => 'general',
                'icon' => 'currency-dollar',
                'requires_document' => true,
                'sort_order' => 80,
            ],
            [
                'name' => 'digital_confirmation',
                'display_name' => 'Digital Confirmation',
                'description' => 'Digital confirmation and sign-off requests',
                'category' => 'General',
                'module' => 'general',
                'icon' => 'check-badge',
                'requires_document' => false,
                'sort_order' => 90,
            ],
        ];

        foreach ($types as $type) {
            ApprovalType::updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }

        $this->command->info('Created/updated '.count($types).' approval types.');
    }
}
