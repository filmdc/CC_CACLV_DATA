<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            [
                'id'    => 1,
                'title' => 'user_management_access',
            ],
            [
                'id'    => 2,
                'title' => 'permission_create',
            ],
            [
                'id'    => 3,
                'title' => 'permission_edit',
            ],
            [
                'id'    => 4,
                'title' => 'permission_show',
            ],
            [
                'id'    => 5,
                'title' => 'permission_delete',
            ],
            [
                'id'    => 6,
                'title' => 'permission_access',
            ],
            [
                'id'    => 7,
                'title' => 'role_create',
            ],
            [
                'id'    => 8,
                'title' => 'role_edit',
            ],
            [
                'id'    => 9,
                'title' => 'role_show',
            ],
            [
                'id'    => 10,
                'title' => 'role_delete',
            ],
            [
                'id'    => 11,
                'title' => 'role_access',
            ],
            [
                'id'    => 12,
                'title' => 'user_create',
            ],
            [
                'id'    => 13,
                'title' => 'user_edit',
            ],
            [
                'id'    => 14,
                'title' => 'user_show',
            ],
            [
                'id'    => 15,
                'title' => 'user_delete',
            ],
            [
                'id'    => 16,
                'title' => 'user_access',
            ],
            [
                'id'    => 17,
                'title' => 'contract_management_access',
            ],
            [
                'id'    => 18,
                'title' => 'contract_create',
            ],
            [
                'id'    => 19,
                'title' => 'contract_edit',
            ],
            [
                'id'    => 20,
                'title' => 'contract_show',
            ],
            [
                'id'    => 21,
                'title' => 'contract_delete',
            ],
            [
                'id'    => 22,
                'title' => 'contract_access',
            ],
            [
                'id'    => 23,
                'title' => 'program_create',
            ],
            [
                'id'    => 24,
                'title' => 'program_edit',
            ],
            [
                'id'    => 25,
                'title' => 'program_show',
            ],
            [
                'id'    => 26,
                'title' => 'program_delete',
            ],
            [
                'id'    => 27,
                'title' => 'program_access',
            ],
            [
                'id'    => 28,
                'title' => 'funder_create',
            ],
            [
                'id'    => 29,
                'title' => 'funder_edit',
            ],
            [
                'id'    => 30,
                'title' => 'funder_show',
            ],
            [
                'id'    => 31,
                'title' => 'funder_delete',
            ],
            [
                'id'    => 32,
                'title' => 'funder_access',
            ],
            [
                'id'    => 33,
                'title' => 'report_due_date_create',
            ],
            [
                'id'    => 34,
                'title' => 'report_due_date_edit',
            ],
            [
                'id'    => 35,
                'title' => 'report_due_date_show',
            ],
            [
                'id'    => 36,
                'title' => 'report_due_date_delete',
            ],
            [
                'id'    => 37,
                'title' => 'report_due_date_access',
            ],
            [
                'id'    => 38,
                'title' => 'due_date_email_create',
            ],
            [
                'id'    => 39,
                'title' => 'due_date_email_edit',
            ],
            [
                'id'    => 40,
                'title' => 'due_date_email_show',
            ],
            [
                'id'    => 41,
                'title' => 'due_date_email_delete',
            ],
            [
                'id'    => 42,
                'title' => 'due_date_email_access',
            ],
            [
                'id'    => 43,
                'title' => 'audit_log_show',
            ],
            [
                'id'    => 44,
                'title' => 'audit_log_access',
            ],
            [
                'id'    => 45,
                'title' => 'profile_password_edit',
            ],
        ];

        Permission::insert($permissions);
    }
}
