<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Role;
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
    
    // User form fields
    public $name = '';
    public $email = '';
    public $active = true;
    public $selectedRoles = [];
    
    // Password form fields
    public $password = '';
    public $password_confirmation = '';
    public $send_password_reset = false;

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
        $this->showEditForm = false;
        $this->showViewModal = false;
        $this->selectedUser = null;
        $this->resetPasswordForm();
    }
    
    public function viewUser($userId)
    {
        // Redirect to the user show page instead of opening a modal
        return redirect()->route('user.show', $userId);
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