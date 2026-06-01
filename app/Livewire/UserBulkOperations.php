<?php

namespace App\Livewire;

use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class UserBulkOperations extends Component
{
    use WithPagination, WithPermissionChecks;

    public const ASSIGN_ADD = 'add';
    public const ASSIGN_REPLACE = 'replace';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ALL = 'all';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: self::STATUS_ALL)]
    public string $statusFilter = self::STATUS_ALL;

    /** @var array<int> */
    public array $selectedUsers = [];

    // Assignment fields
    /** @var array<int> */
    public array $bulkRoles = [];

    /** @var array<int> */
    public array $bulkPrograms = [];

    public string $assignAction = self::ASSIGN_ADD;

    // Confirmation modals
    public bool $showAssignModal = false;
    public bool $showDeactivateModal = false;
    public bool $showActivateModal = false;
    public bool $showDeleteModal = false;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizeAction('users.view', 'You do not have permission to view users.');

        // Allow the list page to seed a selection via session handoff.
        $seeded = session()->pull('users.bulk.selected');
        if (is_array($seeded)) {
            $this->selectedUsers = $this->sanitizeIds($seeded);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedUsers($value): void
    {
        if (is_array($value)) {
            $this->selectedUsers = $this->sanitizeIds($value);
        }
    }

    public function selectAllOnPage(array $idsOnPage): void
    {
        $merged = array_merge($this->selectedUsers, $idsOnPage);
        $this->selectedUsers = $this->sanitizeIds($merged);
    }

    public function clearSelection(): void
    {
        $this->selectedUsers = [];
    }

    // --- Assignment ---

    public function openAssignModal(): void
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        if (! $this->guardHasSelection()) {
            return;
        }
        $this->bulkRoles = [];
        $this->bulkPrograms = [];
        $this->assignAction = self::ASSIGN_ADD;
        $this->showAssignModal = true;
    }

    public function applyAssignment(): void
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');

        $userIds = $this->sanitizeIds($this->selectedUsers);
        $roleIds = $this->sanitizeIds($this->bulkRoles);
        $programIds = $this->sanitizeIds($this->bulkPrograms);

        if (empty($userIds)) {
            session()->flash('error', 'Select at least one user.');
            return;
        }
        if (! in_array($this->assignAction, [self::ASSIGN_ADD, self::ASSIGN_REPLACE], true)) {
            session()->flash('error', 'Invalid action.');
            return;
        }
        if (empty($roleIds) && empty($programIds)) {
            session()->flash('error', 'Choose at least one role or program to assign.');
            return;
        }

        try {
            User::whereIn('id', $userIds)
                ->with(['roles:id', 'programs:id'])
                ->get()
                ->each(function (User $user) use ($roleIds, $programIds) {
                    if ($this->assignAction === self::ASSIGN_REPLACE) {
                        if ($roleIds) {
                            $user->roles()->sync($roleIds);
                        }
                        if ($programIds) {
                            $user->programs()->sync($programIds);
                        }
                        return;
                    }
                    if ($roleIds) {
                        $current = $user->roles->pluck('id')->all();
                        $user->roles()->sync(array_values(array_unique(array_merge($current, $roleIds))));
                    }
                    if ($programIds) {
                        $current = $user->programs->pluck('id')->all();
                        $user->programs()->sync(array_values(array_unique(array_merge($current, $programIds))));
                    }
                });

            session()->flash('success', 'Applied to ' . count($userIds) . ' user(s).');
            $this->showAssignModal = false;
            $this->bulkRoles = [];
            $this->bulkPrograms = [];
        } catch (Throwable $e) {
            report($e);
            session()->flash('error', 'Failed to apply the bulk assignment.');
        }
    }

    // --- Activate / Deactivate ---

    public function openDeactivateModal(): void
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        if (! $this->guardHasSelection()) {
            return;
        }
        $this->showDeactivateModal = true;
    }

    public function applyDeactivate(): void
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');

        $ids = $this->sanitizeIds($this->selectedUsers);
        $ids = array_values(array_filter($ids, fn ($id) => $id !== auth()->id()));

        if (empty($ids)) {
            session()->flash('error', 'Nothing to deactivate (your own account is excluded).');
            $this->showDeactivateModal = false;
            return;
        }

        $count = User::whereIn('id', $ids)->update(['active' => false]);
        session()->flash('success', "Deactivated {$count} user(s).");
        $this->showDeactivateModal = false;
    }

    public function openActivateModal(): void
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');
        if (! $this->guardHasSelection()) {
            return;
        }
        $this->showActivateModal = true;
    }

    public function applyActivate(): void
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');

        $ids = $this->sanitizeIds($this->selectedUsers);
        if (empty($ids)) {
            session()->flash('error', 'Nothing to activate.');
            $this->showActivateModal = false;
            return;
        }

        $count = User::whereIn('id', $ids)->update(['active' => true]);
        session()->flash('success', "Activated {$count} user(s).");
        $this->showActivateModal = false;
    }

    // --- Delete ---

    public function openDeleteModal(): void
    {
        $this->authorizeAction('users.delete', 'You do not have permission to delete users.');
        if (! $this->guardHasSelection()) {
            return;
        }
        if (in_array(auth()->id(), $this->selectedUsers, true)) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }
        $this->showDeleteModal = true;
    }

    public function applyDelete(): void
    {
        $this->authorizeAction('users.delete', 'You do not have permission to delete users.');

        $ids = $this->sanitizeIds($this->selectedUsers);
        $ids = array_values(array_filter($ids, fn ($id) => $id !== auth()->id()));

        if (empty($ids)) {
            session()->flash('error', 'No valid users selected.');
            $this->showDeleteModal = false;
            return;
        }

        try {
            $users = User::whereIn('id', $ids)->get();
            $users->each(fn (User $u) => $u->delete());

            session()->flash('success', 'Deleted ' . $users->count() . ' user(s).');
            $this->selectedUsers = [];
            $this->showDeleteModal = false;
        } catch (Throwable $e) {
            report($e);
            session()->flash('error', 'An error occurred while deleting users.');
        }
    }

    public function render()
    {
        $search = trim($this->search);

        $users = User::with(['roles:id,display_name'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($s) use ($search) {
                    $s->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($this->statusFilter === self::STATUS_ACTIVE, fn ($q) => $q->where('active', true))
            ->when($this->statusFilter === self::STATUS_INACTIVE, fn ($q) => $q->where('active', false))
            ->orderBy('name')
            ->paginate(50);

        return view('livewire.user-bulk-operations', [
            'users' => $users,
            'roles' => Role::orderBy('display_name')->get(),
            'programs' => Program::orderBy('name')->get(),
            'selectionCount' => count($this->selectedUsers),
        ])->layout('components.layouts.app');
    }

    private function guardHasSelection(): bool
    {
        if (empty($this->selectedUsers)) {
            session()->flash('error', 'Select at least one user first.');
            return false;
        }
        return true;
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
}
