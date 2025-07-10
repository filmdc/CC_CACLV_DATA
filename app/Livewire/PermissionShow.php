<?php

namespace App\Livewire;

use App\Models\Permission;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class PermissionShow extends Component
{
    use WithPermissionChecks;

    public $permission;
    public $permissionId;

    public function mount($id)
    {
        // Check if user has permission to view permissions
        $this->authorizeAction('permissions.view', 'You do not have permission to view permissions.');
        
        $this->permissionId = $id;
        $this->permission = Permission::with([
            'roles' => function($query) {
                $query->orderBy('display_name');
            }
        ])->findOrFail($id);
    }

    public function editPermission()
    {
        return redirect()->route('role-permission.management')->with('selectedPermission', $this->permissionId);
    }

    public function render()
    {
        return view('livewire.permission-show')->layout('components.layouts.app');
    }
}