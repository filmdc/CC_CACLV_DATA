<?php

namespace App\Livewire;

use App\Models\Permission;
use App\Models\Role;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RolePermissionExportImport extends Component
{
    use WithFileUploads, WithPermissionChecks;

    // Export state
    public $selectedRoles = [];

    public $allRoles = [];

    // Import state
    public $importFile;

    public $importPreview = [];

    public $importActions = [];

    public $showImportPreview = false;

    public $importErrors = [];

    public $importSuccess = [];

    public function mount()
    {
        $this->authorizeAction('roles.view');
        $this->loadRoles();
    }

    public function loadRoles()
    {
        $this->allRoles = Role::with('permissions')->orderBy('name')->get()->toArray();
    }

    public function selectAllRoles()
    {
        $this->selectedRoles = collect($this->allRoles)->pluck('id')->toArray();
    }

    public function deselectAllRoles()
    {
        $this->selectedRoles = [];
    }

    public function exportRoles(): StreamedResponse
    {
        $this->authorizeAction('roles.view');

        if (empty($this->selectedRoles)) {
            session()->flash('error', 'Please select at least one role to export.');

            return response()->streamDownload(function () {
                echo '';
            }, 'empty.csv');
        }

        $roles = Role::with('permissions')
            ->whereIn('id', $this->selectedRoles)
            ->get();

        $filename = 'roles_permissions_export_'.date('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($roles) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'role_name',
                'role_display_name',
                'role_description',
                'permission_name',
                'permission_display_name',
                'permission_description',
                'permission_category',
            ]);

            foreach ($roles as $role) {
                if ($role->permissions->isEmpty()) {
                    // Export role even if it has no permissions
                    fputcsv($handle, [
                        $role->name,
                        $role->display_name ?? '',
                        $role->description ?? '',
                        '',
                        '',
                        '',
                        '',
                    ]);
                } else {
                    foreach ($role->permissions as $permission) {
                        fputcsv($handle, [
                            $role->name,
                            $role->display_name ?? '',
                            $role->description ?? '',
                            $permission->name,
                            $permission->display_name ?? '',
                            $permission->description ?? '',
                            $permission->category ?? '',
                        ]);
                    }
                }
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function updatedImportFile()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $this->parseImportFile();
    }

    protected function parseImportFile()
    {
        $this->importPreview = [];
        $this->importActions = [];
        $this->importErrors = [];
        $this->showImportPreview = false;

        if (! $this->importFile) {
            return;
        }

        $path = $this->importFile->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            $this->importErrors[] = 'Could not open file for reading.';

            return;
        }

        // Read header
        $header = fgetcsv($handle);
        $expectedHeader = ['role_name', 'role_display_name', 'role_description', 'permission_name', 'permission_display_name', 'permission_description', 'permission_category'];

        if (! $header || count($header) < 4) {
            $this->importErrors[] = 'Invalid CSV format. Expected columns: '.implode(', ', $expectedHeader);
            fclose($handle);

            return;
        }

        // Parse data and group by role
        $roleData = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if (count($row) < 4) {
                $this->importErrors[] = "Line {$lineNumber}: Invalid number of columns.";

                continue;
            }

            $roleName = trim($row[0]);
            if (empty($roleName)) {
                $this->importErrors[] = "Line {$lineNumber}: Role name is required.";

                continue;
            }

            if (! isset($roleData[$roleName])) {
                $roleData[$roleName] = [
                    'name' => $roleName,
                    'display_name' => trim($row[1] ?? ''),
                    'description' => trim($row[2] ?? ''),
                    'permissions' => [],
                ];
            }

            $permissionName = trim($row[3] ?? '');
            if (! empty($permissionName)) {
                $roleData[$roleName]['permissions'][$permissionName] = [
                    'name' => $permissionName,
                    'display_name' => trim($row[4] ?? ''),
                    'description' => trim($row[5] ?? ''),
                    'category' => trim($row[6] ?? ''),
                ];
            }
        }

        fclose($handle);

        if (empty($roleData)) {
            $this->importErrors[] = 'No valid role data found in the file.';

            return;
        }

        // Check for existing roles and permissions
        $existingRoles = Role::with('permissions')->whereIn('name', array_keys($roleData))->get()->keyBy('name');
        $existingPermissions = Permission::pluck('id', 'name')->toArray();

        foreach ($roleData as $roleName => $data) {
            $existingRole = $existingRoles->get($roleName);
            $missingPermissions = [];
            $newPermissions = [];
            $existingRolePermissions = [];

            foreach ($data['permissions'] as $permName => $permData) {
                if (! isset($existingPermissions[$permName])) {
                    $missingPermissions[] = $permName;
                } else {
                    if ($existingRole && $existingRole->permissions->contains('name', $permName)) {
                        $existingRolePermissions[] = $permName;
                    } else {
                        $newPermissions[] = $permName;
                    }
                }
            }

            $this->importPreview[$roleName] = [
                'data' => $data,
                'exists' => $existingRole !== null,
                'existing_role' => $existingRole,
                'existing_permissions' => $existingRolePermissions,
                'new_permissions' => $newPermissions,
                'missing_permissions' => $missingPermissions,
                'total_permissions' => count($data['permissions']),
            ];

            // Set default action
            if ($existingRole) {
                $this->importActions[$roleName] = 'skip'; // Default to skip for existing
            } else {
                $this->importActions[$roleName] = 'create'; // Default to create for new
            }
        }

        $this->showImportPreview = true;
    }

    public function executeImport()
    {
        $this->authorizeAction('roles.edit');

        $this->importSuccess = [];
        $this->importErrors = [];

        $existingPermissions = Permission::pluck('id', 'name')->toArray();

        DB::beginTransaction();

        try {
            foreach ($this->importPreview as $roleName => $preview) {
                $action = $this->importActions[$roleName] ?? 'skip';

                if ($action === 'skip') {
                    continue;
                }

                $data = $preview['data'];

                // Get permission IDs for this role (only existing permissions)
                $permissionIds = [];
                foreach ($data['permissions'] as $permName => $permData) {
                    if (isset($existingPermissions[$permName])) {
                        $permissionIds[] = $existingPermissions[$permName];
                    }
                }

                if ($action === 'create') {
                    // Create new role
                    $role = Role::create([
                        'name' => $data['name'],
                        'display_name' => $data['display_name'] ?: $data['name'],
                        'description' => $data['description'],
                    ]);

                    if (! empty($permissionIds)) {
                        $role->permissions()->sync($permissionIds);
                    }

                    $this->importSuccess[] = "Created role '{$roleName}' with ".count($permissionIds).' permissions.';

                } elseif ($action === 'merge') {
                    // Merge permissions into existing role
                    $role = Role::where('name', $roleName)->first();
                    if ($role) {
                        $existingIds = $role->permissions()->pluck('permissions.id')->toArray();
                        $mergedIds = array_unique(array_merge($existingIds, $permissionIds));
                        $role->permissions()->sync($mergedIds);

                        $addedCount = count($mergedIds) - count($existingIds);
                        $this->importSuccess[] = "Merged {$addedCount} new permissions into role '{$roleName}'.";
                    }

                } elseif ($action === 'replace') {
                    // Replace all permissions
                    $role = Role::where('name', $roleName)->first();
                    if ($role) {
                        $role->permissions()->sync($permissionIds);
                        $this->importSuccess[] = "Replaced permissions for role '{$roleName}' with ".count($permissionIds).' permissions.';
                    }
                }
            }

            DB::commit();

            // Reset import state
            $this->importFile = null;
            $this->importPreview = [];
            $this->importActions = [];
            $this->showImportPreview = false;

            // Reload roles
            $this->loadRoles();

            session()->flash('success', 'Import completed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->importErrors[] = 'Import failed: '.$e->getMessage();
        }
    }

    public function cancelImport()
    {
        $this->importFile = null;
        $this->importPreview = [];
        $this->importActions = [];
        $this->showImportPreview = false;
        $this->importErrors = [];
        $this->importSuccess = [];
    }

    public function render()
    {
        return view('livewire.role-permission-export-import')
            ->layout('components.layouts.app');
    }
}
