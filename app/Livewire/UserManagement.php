<?php

namespace App\Livewire;

use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class UserManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    /** @var string */
    public $search = '';

    /** @var array<int> */
    public $selectedUsers = [];

    /**
     * The user that is currently being viewed or edited.
     */
    public $selectedUser = null;

    public $showEditForm = false;
    public $showPasswordForm = false;
    public $showViewModal = false;
    public $showBulkAssignModal = false;
    public $showBulkDeleteModal = false;

    // User form fields
    public $name = '';
    public $email = '';
    public $active = true;

    /** @var array<int> */
    public $selectedRoles = [];

    /** @var array<int> */
    public $selectedPrograms = [];

    // Password form fields
    public $password = '';
    public $password_confirmation = '';

    // Bulk assignment fields
    /** @var array<int> */
    public $bulkRoles = [];

    /** @var array<int> */
    public $bulkPrograms = [];

    /** @var string */
    public $bulkAction = self::BULK_ACTION_ADD; // add or replace

    private const BULK_ACTION_ADD = 'add';
    private const BULK_ACTION_REPLACE = 'replace';

    protected string $paginationTheme = 'tailwind';

    protected function rules(): array
    {
        $userId = $this->selectedUser->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'active' => ['boolean'],
            'selectedRoles' => ['array'],
            'selectedRoles.*' => ['integer', 'exists:roles,id'],
            'selectedPrograms' => ['array'],
            'selectedPrograms.*' => ['integer', 'exists:programs,id'],
        ];
    }

    protected function passwordRules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ];
    }

    public function mount()
    {
        $this->authorizeAction('users.view', 'You do not have permission to view users.');
    }

    public function showEditUser($userId)
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        $this->selectedUser = $this->findUserForManagement($userId)->loadMissing(['roles', 'programs']);
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
        
        $this->selectedUser = $this->findUserForManagement($userId);
        $this->clearPasswordForm();
        $this->showPasswordForm = true;
    }

    public function updateUser()
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');

        if (!$this->selectedUser) {
            $this->flashError('Please select a user to update.');
            return;
        }

        $this->name = Str::of($this->name)->squish()->toString();
        $this->email = Str::lower(trim($this->email));

        $this->validate();

        try {
            $this->selectedUser->update([
                'name' => $this->name,
                'email' => $this->email,
                'active' => (bool) $this->active,
            ]);

            $this->selectedUser->roles()->sync($this->sanitizeIds($this->selectedRoles));
            $this->selectedUser->programs()->sync($this->sanitizeIds($this->selectedPrograms));

            $this->flashSuccess('User updated successfully.');
            $this->resetForm();
        } catch (Throwable $exception) {
            $this->handleException($exception, 'Unable to update the user right now.');
        }
    }
    
    public function setUserPassword()
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');

        if (!$this->selectedUser) {
            $this->flashError('No user selected.');
            return;
        }

        $this->validate($this->passwordRules());

        try {
            $this->selectedUser->update([
                'password' => Hash::make($this->password),
                'email_verified_at' => now(), // Mark email as verified when admin sets password
            ]);

            $this->flashSuccess('Password updated successfully for ' . $this->selectedUser->name);
            $this->resetPasswordForm();
        } catch (Throwable $exception) {
            $this->handleException($exception, 'Failed to update the password.');
        }
    }
    
    public function sendPasswordReset($userId)
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        try {
            $user = $this->findUserForManagement($userId);
            
            // Send password reset notification
            $status = Password::sendResetLink(['email' => $user->email]);
            
            if ($status === Password::RESET_LINK_SENT) {
                $this->flashSuccess('Password reset link sent to ' . $user->email);
            } else {
                $this->flashError('Unable to send password reset link.');
            }
            
        } catch (Throwable $exception) {
            $this->handleException($exception, 'Unable to send password reset link.');
        }
    }
    
    public function toggleUserStatus($userId)
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        try {
            $user = $this->findUserForManagement($userId);
            
            // Prevent deactivating yourself
            if ($user->id === auth()->id()) {
                $this->flashError('You cannot deactivate your own account.');
                return;
            }
            
            $user->update(['active' => !$user->active]);

            $status = $user->fresh()->active ? 'activated' : 'deactivated';
            $this->flashSuccess('User ' . $status . ' successfully.');

        } catch (Throwable $exception) {
            $this->handleException($exception, 'Unable to toggle user status.');
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
        $this->resetValidation();
    }
    
    public function showBulkAssign()
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        
        $this->selectedUsers = $this->sanitizeIds($this->selectedUsers);

        if (empty($this->selectedUsers)) {
            $this->flashError('Please select at least one user.');
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

        $targetUserIds = $this->sanitizeIds($this->selectedUsers);
        $roleIds = $this->sanitizeIds($this->bulkRoles);
        $programIds = $this->sanitizeIds($this->bulkPrograms);

        if (empty($targetUserIds)) {
            $this->flashError('Please select at least one user.');
            return;
        }

        if ($this->bulkAction !== self::BULK_ACTION_ADD && $this->bulkAction !== self::BULK_ACTION_REPLACE) {
            $this->flashError('Invalid bulk action selected.');
            return;
        }

        if (empty($roleIds) && empty($programIds)) {
            $this->flashError('Choose at least one role or program to assign.');
            return;
        }

        try {
            User::whereIn('id', $targetUserIds)
                ->with(['roles:id', 'programs:id'])
                ->get()
                ->each(function (User $user) use ($roleIds, $programIds) {
                    if ($this->bulkAction === self::BULK_ACTION_REPLACE) {
                        // Replace existing roles and programs; sync only when payload present
                        if ($roleIds) {
                            $user->roles()->sync($roleIds);
                        }
                        if ($programIds) {
                            $user->programs()->sync($programIds);
                        }
                        return;
                    }

                    // Merge new assignments with current ones
                    if ($roleIds) {
                        $currentRoles = $user->roles->pluck('id')->all();
                        $user->roles()->sync(array_values(array_unique(array_merge($currentRoles, $roleIds))));
                    }

                    if ($programIds) {
                        $currentPrograms = $user->programs->pluck('id')->all();
                        $user->programs()->sync(array_values(array_unique(array_merge($currentPrograms, $programIds))));
                    }
                });

            $this->flashSuccess('Bulk assignment completed for ' . count($targetUserIds) . ' users.');
            $this->resetBulkAssignment();
        } catch (Throwable $exception) {
            $this->handleException($exception, 'Failed to apply the bulk assignment.');
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
    
    public function showBulkDelete()
    {
        $this->authorizeAction('users.delete', 'You do not have permission to delete users.');

        $this->selectedUsers = $this->sanitizeIds($this->selectedUsers);

        if (empty($this->selectedUsers)) {
            $this->flashError('Please select at least one user.');
            return;
        }
        
        // Check if current user is selected
        if (in_array(auth()->id(), $this->selectedUsers, true)) {
            $this->flashError('You cannot delete your own account.');
            return;
        }
        
        $this->showBulkDeleteModal = true;
    }
    
    public function processBulkDelete()
    {
        $this->authorizeAction('users.delete', 'You do not have permission to delete users.');
        
        $targetUserIds = $this->sanitizeIds($this->selectedUsers);

        try {
            $currentUserId = auth()->id();

            $usersToDelete = User::whereIn('id', $targetUserIds)
                ->where('id', '!=', $currentUserId)
                ->get();

            if ($usersToDelete->isEmpty()) {
                $this->flashError('No valid users selected for deletion.');
                $this->resetBulkDelete();
                return;
            }

            $usersToDelete->each(static function (User $user) {
                $user->delete();
            });

            $this->flashSuccess('Successfully deleted ' . $usersToDelete->count() . ' users.');
            $this->resetBulkDelete();

        } catch (Throwable $exception) {
            $this->handleException($exception, 'An error occurred while deleting users.');
        }
    }
    
    public function resetBulkDelete()
    {
        $this->selectedUsers = [];
        $this->showBulkDeleteModal = false;
    }
    
    public function viewUser($userId)
    {
        $this->authorizeAction('users.view', 'You do not have permission to view users.');

        $userId = $this->resolveUserId($userId);

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
            $user = $this->findUserForManagement($userId);
            
            // Prevent deleting yourself
            if ($user->id === auth()->id()) {
                $this->flashError('You cannot delete your own account.');
                return;
            }
            
            $user->delete();
            $this->flashSuccess('User deleted successfully.');
        } catch (Throwable $exception) {
            $this->handleException($exception, 'Unable to delete the user.');
        }
    }

    public function render()
    {
        $search = trim($this->search);

        $users = User::with(['roles', 'programs'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
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

    /**
     * Normalize a list of ids to positive integers.
     *
     * @param  array<int, mixed>  $ids
     * @return array<int>
     */
    private function sanitizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map(static function ($value) {
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }

            return null;
        }, $ids), static fn ($value) => !is_null($value))));
    }

    private function flashSuccess(string $message): void
    {
        session()->flash('success', $message);
    }

    private function flashError(string $message): void
    {
        session()->flash('error', $message);
    }

    private function handleException(Throwable $exception, string $message): void
    {
        report($exception);
        $this->flashError($message);
    }

    private function findUserForManagement($userId): User
    {
        return User::findOrFail($this->resolveUserId($userId));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedUsers($value): void
    {
        if (is_array($value)) {
            $this->selectedUsers = $this->sanitizeIds($value);
        }
    }

    private function resolveUserId($userId): int
    {
        $id = is_numeric($userId) ? (int) $userId : 0;

        if ($id <= 0) {
            abort(404);
        }

        return $id;
    }
}
