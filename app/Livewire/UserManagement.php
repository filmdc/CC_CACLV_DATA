<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Role;
use App\Models\Program;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $selectedUser = null;
    public $showEditForm = false;
    public $showPasswordForm = false;
    public $showViewModal = false;
    public $showBulkAssignModal = false;
    
    // User form fields
    public $name = '';
    public $email = '';
    public $active = true;
    public $selectedRoles = [];
    public $selectedPrograms = [];
    
    // Password form fields
    public $password = '';
    public $password_confirmation = '';
    public $send_password_reset = false;
    
    // Bulk assignment fields
    public $selectedUsers = [];
    public $bulkRoles = [];
    public $bulkPrograms = [];
    public $bulkAction = 'add'; // add or replace

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'active' => 'boolean',
        'selectedRoles' => 'array',
    ];
    
    protected function getPasswordRules()
    {
        return [
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ];
    }

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
            $this->active = $this->selectedUser->active;
            $this->selectedRoles = $this->selectedUser->roles->pluck('id')->toArray();
            $this->selectedPrograms = $this->selectedUser->programs->pluck('id')->toArray();
        }
    }
    
    public function showPasswordModal($userId)
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        $this->selectedUser = User::findOrFail($userId);
        $this->clearPasswordForm();
        $this->showPasswordForm = true;
    }

    public function updateUser()
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        $this->validate();

        try {
            $this->selectedUser->update([
                'name' => $this->name,
                'email' => $this->email,
                'active' => $this->active,
            ]);

            // Update roles
            $this->selectedUser->roles()->sync($this->selectedRoles);
            
            // Update programs
            $this->selectedUser->programs()->sync($this->selectedPrograms);

            session()->flash('success', 'User updated successfully.');
            $this->resetForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    
    public function setUserPassword()
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        $this->validate($this->getPasswordRules());

        try {
            if (!$this->selectedUser) {
                session()->flash('error', 'No user selected.');
                return;
            }

            $this->selectedUser->update([
                'password' => Hash::make($this->password),
                'email_verified_at' => now(), // Mark email as verified when admin sets password
            ]);

            session()->flash('success', 'Password updated successfully for ' . $this->selectedUser->name);
            $this->resetPasswordForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    
    public function sendPasswordReset($userId)
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        try {
            $user = User::findOrFail($userId);
            
            // Send password reset notification
            $status = Password::sendResetLink(['email' => $user->email]);
            
            if ($status === Password::RESET_LINK_SENT) {
                session()->flash('success', 'Password reset link sent to ' . $user->email);
            } else {
                session()->flash('error', 'Unable to send password reset link.');
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    
    public function toggleUserStatus($userId)
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        try {
            $user = User::findOrFail($userId);
            
            // Prevent deactivating yourself
            if ($user->id === auth()->id()) {
                session()->flash('error', 'You cannot deactivate your own account.');
                return;
            }
            
            $user->update(['active' => !$user->active]);
            
            $status = $user->active ? 'activated' : 'deactivated';
            session()->flash('success', 'User ' . $status . ' successfully.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->active = true;
        $this->selectedRoles = [];
        $this->selectedPrograms = [];
        $this->showEditForm = false;
        $this->showViewModal = false;
        $this->selectedUser = null;
        $this->resetPasswordForm();
    }
    
    public function showBulkAssign()
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        if (empty($this->selectedUsers)) {
            session()->flash('error', 'Please select at least one user.');
            return;
        }
        
        $this->bulkRoles = [];
        $this->bulkPrograms = [];
        $this->bulkAction = 'add';
        $this->showBulkAssignModal = true;
    }
    
    public function processBulkAssignment()
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        try {
            $users = User::whereIn('id', $this->selectedUsers)->get();
            
            foreach ($users as $user) {
                if ($this->bulkAction === 'replace') {
                    // Replace existing roles and programs
                    if (!empty($this->bulkRoles)) {
                        $user->roles()->sync($this->bulkRoles);
                    }
                    if (!empty($this->bulkPrograms)) {
                        $user->programs()->sync($this->bulkPrograms);
                    }
                } else {
                    // Add to existing roles and programs
                    if (!empty($this->bulkRoles)) {
                        $currentRoles = $user->roles->pluck('id')->toArray();
                        $newRoles = array_unique(array_merge($currentRoles, $this->bulkRoles));
                        $user->roles()->sync($newRoles);
                    }
                    if (!empty($this->bulkPrograms)) {
                        $currentPrograms = $user->programs->pluck('id')->toArray();
                        $newPrograms = array_unique(array_merge($currentPrograms, $this->bulkPrograms));
                        $user->programs()->sync($newPrograms);
                    }
                }
            }
            
            session()->flash('success', 'Bulk assignment completed for ' . count($users) . ' users.');
            $this->resetBulkAssignment();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    
    public function resetBulkAssignment()
    {
        $this->selectedUsers = [];
        $this->bulkRoles = [];
        $this->bulkPrograms = [];
        $this->bulkAction = 'add';
        $this->showBulkAssignModal = false;
    }
    
    public function viewUser($userId)
    {
        $this->selectedUser = User::with(['roles', 'programs'])->findOrFail($userId);
        $this->showViewModal = true;
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->selectedUser = null;
    }
    
    public function clearPasswordForm()
    {
        $this->password = '';
        $this->password_confirmation = '';
        $this->send_password_reset = false;
    }
    
    public function resetPasswordForm()
    {
        $this->clearPasswordForm();
        $this->showPasswordForm = false;
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
        $users = User::with(['roles', 'programs'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(10);
            
        $roles = Role::orderBy('display_name')->get();
        $programs = Program::orderBy('name')->get();

        return view('livewire.user-management', [
            'users' => $users,
            'roles' => $roles,
            'programs' => $programs,
        ])->layout('components.layouts.app');
    }
}