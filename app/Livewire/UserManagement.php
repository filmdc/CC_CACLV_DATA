<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class UserManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ALL = 'all';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: self::STATUS_ACTIVE)]
    public string $statusFilter = self::STATUS_ACTIVE;

    /** @var array<int> */
    public array $selectedUsers = [];

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizeAction('users.view', 'You do not have permission to view users.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        if (! in_array($status, [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_ALL], true)) {
            return;
        }
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function updatedSelectedUsers($value): void
    {
        if (is_array($value)) {
            $this->selectedUsers = $this->sanitizeIds($value);
        }
    }

    public function toggleUserStatus($userId): void
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');

        try {
            $user = $this->findUserForManagement($userId);

            if ($user->id === auth()->id()) {
                session()->flash('error', 'You cannot deactivate your own account.');
                return;
            }

            $user->update(['active' => ! $user->active]);
            $status = $user->fresh()->active ? 'activated' : 'deactivated';
            session()->flash('success', "User {$status} successfully.");
        } catch (Throwable $exception) {
            report($exception);
            session()->flash('error', 'Unable to toggle user status.');
        }
    }

    public function sendPasswordReset($userId): void
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');

        try {
            $user = $this->findUserForManagement($userId);
            $status = Password::sendResetLink(['email' => $user->email]);

            if ($status === Password::RESET_LINK_SENT) {
                session()->flash('success', 'Password reset link sent to ' . $user->email);
            } else {
                session()->flash('error', 'Unable to send password reset link.');
            }
        } catch (Throwable $exception) {
            report($exception);
            session()->flash('error', 'Unable to send password reset link.');
        }
    }

    public function deleteUser($userId): void
    {
        $this->authorizeAction('users.delete', 'You do not have permission to delete users.');

        try {
            $user = $this->findUserForManagement($userId);

            if ($user->id === auth()->id()) {
                session()->flash('error', 'You cannot delete your own account.');
                return;
            }

            $user->delete();
            session()->flash('success', 'User deleted successfully.');
        } catch (Throwable $exception) {
            report($exception);
            session()->flash('error', 'Unable to delete the user.');
        }
    }

    public function bulkActOnSelected(): void
    {
        $ids = $this->sanitizeIds($this->selectedUsers);

        if (empty($ids)) {
            session()->flash('error', 'Select at least one user first.');
            return;
        }

        session(['users.bulk.selected' => $ids]);
        $this->redirectRoute('users.bulk', navigate: true);
    }

    public function clearSelection(): void
    {
        $this->selectedUsers = [];
    }

    public function render()
    {
        $search = trim($this->search);

        $users = User::with(['roles:id,display_name', 'programs:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($this->statusFilter === self::STATUS_ACTIVE, fn ($q) => $q->where('active', true))
            ->when($this->statusFilter === self::STATUS_INACTIVE, fn ($q) => $q->where('active', false))
            ->orderBy('name')
            ->paginate(25);

        $counts = [
            'active' => User::where('active', true)->count(),
            'inactive' => User::where('active', false)->count(),
            'all' => User::count(),
        ];

        return view('livewire.user-management', [
            'users' => $users,
            'counts' => $counts,
        ])->layout('components.layouts.app');
    }

    /**
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
        }, $ids), static fn ($value) => ! is_null($value))));
    }

    private function findUserForManagement($userId): User
    {
        $id = is_numeric($userId) ? (int) $userId : 0;
        if ($id <= 0) {
            abort(404);
        }
        return User::findOrFail($id);
    }
}
