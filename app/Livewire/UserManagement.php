<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Role;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $selectedUser = null;
    public $showEditForm = false;
    
    // User form fields
    public $name = '';
    public $email = '';
    public $selectedRoles = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'selectedRoles' => 'array',
    ];

    public function mount()
    {
        $this->authorizeAction('users.view', 'You do not have permission to view users.');
    }

    public function showEditUser($userId)
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        $this->selectedUser = User::findOrFail($userId);
        $this->fillUserForm();
        $this->showEditForm = true;
    }

    public function fillUserForm()
    {
        if ($this->selectedUser) {
            $this->name = $this->selectedUser->name;
            $this->email = $this->selectedUser->email;
            $this->selectedRoles = $this->selectedUser->roles->pluck('id')->toArray();
        }
    }

    public function updateUser()
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        $this->validate();

        try {
            $this->selectedUser->update([
                'name' => $this->name,
                'email' => $this->email,
            ]);

            // Update roles
            $this->selectedUser->roles()->sync($this->selectedRoles);

            session()->flash('success', 'User updated successfully.');
            $this->resetForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->selectedRoles = [];
        $this->showEditForm = false;
        $this->selectedUser = null;
    }

    public function deleteUser($userId)
    {
        $this->authorizeAction('users.delete', 'You do not have permission to delete users.');
        
        try {
            $user = User::findOrFail($userId);
            
            // Prevent deleting yourself
            if ($user->id === auth()->id()) {
                session()->flash('error', 'You cannot delete your own account.');
                return;
            }
            
            $user->delete();
            session()->flash('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete user: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $users = User::with('roles')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(10);
            
        $roles = Role::orderBy('display_name')->get();

        return view('livewire.user-management', [
            'users' => $users,
            'roles' => $roles,
        ])->layout('components.layouts.app');
    }
}