<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Comm Log v2 (Option A): permissions for the Requirements Library — the
 * UI where Kaitlyn curates her master atom list, bundles, and the
 * program-service → bundle auto-apply links.
 *
 * Idempotent. Granted to housing_manager + admin.
 */
class RequirementsLibraryPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        [
            'name' => 'requirements_library.view',
            'display_name' => 'View Requirements Library',
            'description' => 'View the master list of requirement atoms and bundles.',
            'category' => 'Intake',
        ],
        [
            'name' => 'requirements_library.manage',
            'display_name' => 'Manage Requirements Library',
            'description' => 'Create, edit, soft-delete and restore requirement atoms / bundles; manage service ↔ bundle links.',
            'category' => 'Intake',
        ],
    ];

    private const GRANTS = [
        'requirements_library.view',
        'requirements_library.manage',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $data) {
            Permission::updateOrCreate(['name' => $data['name']], $data);
        }

        $this->grantTo('housing_manager');
        $this->grantTo('admin');
        $this->grantTo('Administrator');

        if (isset($this->command)) {
            $this->command->info('Seeded Requirements Library permissions ('.count(self::PERMISSIONS).' permissions).');
        }
    }

    private function grantTo(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role === null) {
            return;
        }
        $ids = Permission::whereIn('name', self::GRANTS)->pluck('id')->all();
        $role->permissions()->syncWithoutDetaching($ids);
    }
}
