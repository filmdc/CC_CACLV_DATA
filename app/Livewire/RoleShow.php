<?php

namespace App\Livewire;

use App\Models\Role;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class RoleShow extends Component
{
    use WithPermissionChecks;

    public $role;
    public $roleId;

    public function mount($id)
    {
        // Check if user has permission to view roles
        $this->authorizeAction('roles.view', 'You do not have permission to view roles.');
        
        $this->roleId = $id;
        $this->role = Role::with([
            'permissions' => function($query) {
                $query->orderBy('category')->orderBy('display_name');
            },
            'users' => function($query) {
                $query->orderBy('name');
            }
        ])->findOrFail($id);
    }

    public function editRole()
    {
        return redirect()->route('role-permission.management')->with('selectedRole', $this->roleId);
    }

    public function render()
    {
        return view('livewire.role-show')->layout('components.layouts.app');
    }
}