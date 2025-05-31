<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class RolePermissionManagement extends Component
{
    use WithPagination;

    public $activeTab = 'roles';
    public $search = '';
    
    // Role management
    public $selectedRole = null;
    public $showCreateRoleForm = false;
    public $showEditRoleForm = false;
    public $roleName = '';
    public $roleDisplayName = '';
    public $roleDescription = '';
    public $selectedPermissions = [];

    // Permission management
    public $selectedPermission = null;
    public $showCreatePermissionForm = false;
    public $showEditPermissionForm = false;
    public $permissionName = '';
    public $permissionDisplayName = '';
    public $permissionDescription = '';
    public $permissionCategory = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
        $this->search = '';
    }

    // Role Management Methods
    public function showCreateRole()
    {
        $this->showCreateRoleForm = true;
        $this->showEditRoleForm = false;
        $this->selectedRole = null;
        $this->resetRoleForm();
    }

    public function showEditRole($roleId)
    {
        $this->selectedRole = Role::with('permissions')->find($roleId);
        if ($this->selectedRole) {
            $this->showEditRoleForm = true;
            $this->showCreateRoleForm = false;
            $this->fillRoleForm();
        }
    }

    public function resetRoleForm()
    {
        $this->roleName = '';
        $this->roleDisplayName = '';
        $this->roleDescription = '';
        $this->selectedPermissions = [];
    }

    public function fillRoleForm()
    {
        if ($this->selectedRole) {
            $this->roleName = $this->selectedRole->name;
            $this->roleDisplayName = $this->selectedRole->display_name;
            $this->roleDescription = $this->selectedRole->description;
            $this->selectedPermissions = $this->selectedRole->permissions->pluck('id')->toArray();
        }
    }

    public function createRole()
    {
        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name',
            'roleDisplayName' => 'required|string|max:255',
            'roleDescription' => 'nullable|string',
            'selectedPermissions' => 'array',
        ]);

        try {
            $role = Role::create([
                'name' => $this->roleName,
                'display_name' => $this->roleDisplayName,
                'description' => $this->roleDescription,
            ]);

            if (!empty($this->selectedPermissions)) {
                $role->permissions()->sync($this->selectedPermissions);
            }

            session()->flash('success', 'Role created successfully.');
            $this->showCreateRoleForm = false;
            $this->resetRoleForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateRole()
    {
        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name,' . $this->selectedRole->id,
            'roleDisplayName' => 'required|string|max:255',
            'roleDescription' => 'nullable|string',
            'selectedPermissions' => 'array',
        ]);

        try {
            $this->selectedRole->update([
                'name' => $this->roleName,
                'display_name' => $this->roleDisplayName,
                'description' => $this->roleDescription,
            ]);

            $this->selectedRole->permissions()->sync($this->selectedPermissions);

            session()->flash('success', 'Role updated successfully.');
            $this->showEditRoleForm = false;
            $this->selectedRole = null;
            $this->resetRoleForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteRole($roleId)
    {
        try {
            $role = Role::find($roleId);
            if ($role) {
                if ($role->users()->count() > 0) {
                    session()->flash('error', 'Cannot delete role with assigned users.');
                    return;
                }
                
                $role->delete();
                session()->flash('success', 'Role deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete role: ' . $e->getMessage());
        }
    }

    // Permission Management Methods
    public function showCreatePermission()
    {
        $this->showCreatePermissionForm = true;
        $this->showEditPermissionForm = false;
        $this->selectedPermission = null;
        $this->resetPermissionForm();
    }

    public function showEditPermission($permissionId)
    {
        $this->selectedPermission = Permission::find($permissionId);
        if ($this->selectedPermission) {
            $this->showEditPermissionForm = true;
            $this->showCreatePermissionForm = false;
            $this->fillPermissionForm();
        }
    }

    public function resetPermissionForm()
    {
        $this->permissionName = '';
        $this->permissionDisplayName = '';
        $this->permissionDescription = '';
        $this->permissionCategory = '';
    }

    public function fillPermissionForm()
    {
        if ($this->selectedPermission) {
            $this->permissionName = $this->selectedPermission->name;
            $this->permissionDisplayName = $this->selectedPermission->display_name;
            $this->permissionDescription = $this->selectedPermission->description;
            $this->permissionCategory = $this->selectedPermission->category;
        }
    }

    public function createPermission()
    {
        $this->validate([
            'permissionName' => 'required|string|max:255|unique:permissions,name',
            'permissionDisplayName' => 'required|string|max:255',
            'permissionDescription' => 'nullable|string',
            'permissionCategory' => 'nullable|string|max:255',
        ]);

        try {
            Permission::create([
                'name' => $this->permissionName,
                'display_name' => $this->permissionDisplayName,
                'description' => $this->permissionDescription,
                'category' => $this->permissionCategory,
            ]);

            session()->flash('success', 'Permission created successfully.');
            $this->showCreatePermissionForm = false;
            $this->resetPermissionForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updatePermission()
    {
        $this->validate([
            'permissionName' => 'required|string|max:255|unique:permissions,name,' . $this->selectedPermission->id,
            'permissionDisplayName' => 'required|string|max:255',
            'permissionDescription' => 'nullable|string',
            'permissionCategory' => 'nullable|string|max:255',
        ]);

        try {
            $this->selectedPermission->update([
                'name' => $this->permissionName,
                'display_name' => $this->permissionDisplayName,
                'description' => $this->permissionDescription,
                'category' => $this->permissionCategory,
            ]);

            session()->flash('success', 'Permission updated successfully.');
            $this->showEditPermissionForm = false;
            $this->selectedPermission = null;
            $this->resetPermissionForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deletePermission($permissionId)
    {
        try {
            $permission = Permission::find($permissionId);
            if ($permission) {
                if ($permission->roles()->count() > 0) {
                    session()->flash('error', 'Cannot delete permission assigned to roles.');
                    return;
                }
                
                $permission->delete();
                session()->flash('success', 'Permission deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete permission: ' . $e->getMessage());
        }
    }

    public function render()
    {
        if ($this->activeTab === 'roles') {
            $roles = Role::with(['permissions', 'users'])
                ->when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('display_name', 'like', '%' . $this->search . '%');
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);
                
            $permissions = Permission::orderBy('category')->orderBy('display_name')->get();
            
            return view('livewire.role-permission-management', [
                'roles' => $roles,
                'permissions' => $permissions,
            ])->layout('components.layouts.app');
        } else {
            $permissions = Permission::when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('display_name', 'like', '%' . $this->search . '%')
                          ->orWhere('category', 'like', '%' . $this->search . '%');
                })
                ->orderBy('category')
                ->orderBy('created_at', 'desc')
                ->paginate(15);
                
            return view('livewire.role-permission-management', [
                'permissions' => $permissions,
            ])->layout('components.layouts.app');
        }
    }
}