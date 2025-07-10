<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class UserShow extends Component
{
    use WithPermissionChecks;

    public $user;
    public $userId;

    public function mount($id)
    {
        // Check if user has permission to view users
        $this->authorizeAction('users.view', 'You do not have permission to view users.');
        
        $this->userId = $id;
        $this->user = User::with([
            'roles.permissions',
            'programs',
            'createdBy',
            'updatedBy'
        ])->findOrFail($id);
    }

    public function editUser()
    {
        return redirect()->route('user.management')->with('selectedUser', $this->userId);
    }

    public function render()
    {
        return view('livewire.user-show')->layout('components.layouts.app');
    }
}