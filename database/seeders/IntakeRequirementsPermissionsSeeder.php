<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * P-39b (addendum D-4): seed the granular permission set for the P-39
 * epic ahead of the consumers landing in 39c/39d.
 *
 * Permissions seeded:
 *   - intake_dashboard.view         → housing_manager, admin
 *   - intake_requirements.create    → housing_manager, admin
 *   - intake_requirements.update    → housing_manager, admin
 *   - intake_requirements.delete    → admin only
 *   - intake_requirements.upload    → housing_manager, admin
 *   - comm_log_pins.manage          → every non-system role
 *
 * Idempotent: uses firstOrCreate / syncWithoutDetaching so re-running
 * the seeder is a no-op against an already-seeded database.
 *
 * Chained from both DatabaseSeeder and RegressionTestSeeder so the
 * dev DB and the test DB stay in lockstep.
 */
class IntakeRequirementsPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        [
            'name' => 'intake_dashboard.view',
            'display_name' => 'View Intake Dashboard',
            'description' => 'View the intake-phase applicant queue and progress gauges.',
            'category' => 'Intake',
        ],
        [
            'name' => 'intake_requirements.create',
            'display_name' => 'Create Intake Requirements',
            'description' => 'Create intake-phase requirement rows on a Communication Log entry.',
            'category' => 'Intake',
        ],
        [
            'name' => 'intake_requirements.update',
            'display_name' => 'Update Intake Requirements',
            'description' => 'Mark intake requirements received, waived, or reopen them.',
            'category' => 'Intake',
        ],
        [
            'name' => 'intake_requirements.delete',
            'display_name' => 'Delete Intake Requirements',
            'description' => 'Hard-delete an intake requirement (admin only).',
            'category' => 'Intake',
        ],
        [
            'name' => 'intake_requirements.upload',
            'display_name' => 'Upload Intake Documents',
            'description' => 'Attach documents to an intake requirement (consumed in 39c).',
            'category' => 'Intake',
        ],
        [
            'name' => 'comm_log_pins.manage',
            'display_name' => 'Manage Communication Log Pins',
            'description' => 'Pin and unpin Communication Log entries on the user\'s intake dashboard.',
            'category' => 'Intake',
        ],
    ];

    private const HOUSING_MANAGER_GRANTS = [
        'intake_dashboard.view',
        'intake_requirements.create',
        'intake_requirements.update',
        'intake_requirements.upload',
        'comm_log_pins.manage',
    ];

    private const ADMIN_GRANTS = [
        'intake_dashboard.view',
        'intake_requirements.create',
        'intake_requirements.update',
        'intake_requirements.delete',
        'intake_requirements.upload',
        'comm_log_pins.manage',
    ];

    /**
     * Roles considered "system" (do not auto-grant comm_log_pins.manage).
     * Currently empty — every defined role represents a human user who
     * may pin Comm Log entries. Listed here so the carve-out is easy
     * to extend if a service-account role appears.
     */
    private const SYSTEM_ROLE_NAMES = [];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permissionData) {
            Permission::updateOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        $this->grantToRoleByName('housing_manager', self::HOUSING_MANAGER_GRANTS);
        $this->grantToRoleByName('admin', self::ADMIN_GRANTS);
        $this->grantToRoleByName('Administrator', self::ADMIN_GRANTS);

        // comm_log_pins.manage → every non-system role.
        $pinPermId = Permission::where('name', 'comm_log_pins.manage')->value('id');
        if ($pinPermId !== null) {
            Role::whereNotIn('name', self::SYSTEM_ROLE_NAMES)
                ->get()
                ->each(function (Role $role) use ($pinPermId) {
                    $role->permissions()->syncWithoutDetaching([$pinPermId]);
                });
        }

        if (isset($this->command)) {
            $this->command->info('Seeded P-39 intake permissions ('.count(self::PERMISSIONS).' permissions).');
        }
    }

    private function grantToRoleByName(string $roleName, array $permissionNames): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role === null) {
            return;
        }

        $permIds = Permission::whereIn('name', $permissionNames)->pluck('id')->all();
        $role->permissions()->syncWithoutDetaching($permIds);
    }
}
