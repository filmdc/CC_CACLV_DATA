<?php

namespace App\Livewire;

use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class UserEdit extends Component
{
    use WithPermissionChecks;

    public User $user;

    public string $name = '';
    public string $email = '';
    public bool $active = true;
    public bool $isHousingTeam = false;
    public ?int $approvalDelegateId = null;

    /** @var array<int> */
    public array $selectedRoles = [];

    /** @var array<int> */
    public array $selectedPrograms = [];

    public bool $showPasswordPanel = false;
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(int $id): void
    {
        $this->authorizeAction('users.view', 'You do not have permission to view users.');

        $this->user = User::with(['roles:id', 'programs:id'])->findOrFail($id);
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->active = (bool) $this->user->active;
        $this->isHousingTeam = (bool) $this->user->is_housing_team;
        $this->approvalDelegateId = $this->user->approval_delegate_id;
        $this->selectedRoles = $this->user->roles->pluck('id')->toArray();
        $this->selectedPrograms = $this->user->programs->pluck('id')->toArray();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user->id),
            ],
            'active' => ['boolean'],
            'isHousingTeam' => ['boolean'],
            'approvalDelegateId' => ['nullable', 'integer', 'exists:users,id'],
            'selectedRoles' => ['array'],
            'selectedRoles.*' => ['integer', 'exists:roles,id'],
            'selectedPrograms' => ['array'],
            'selectedPrograms.*' => ['integer', 'exists:programs,id'],
        ];
    }

    public function save()
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');

        $this->name = Str::of($this->name)->squish()->toString();
        $this->email = Str::lower(trim($this->email));

        $this->validate();

        try {
            $this->user->update([
                'name' => $this->name,
                'email' => $this->email,
                'active' => $this->active,
                'is_housing_team' => $this->isHousingTeam,
                'approval_delegate_id' => $this->approvalDelegateId ?: null,
            ]);

            $this->user->roles()->sync($this->sanitizeIds($this->selectedRoles));
            $this->user->programs()->sync($this->sanitizeIds($this->selectedPrograms));

            session()->flash('success', 'User updated successfully.');
            return $this->redirectRoute('users.index', navigate: true);
        } catch (Throwable $e) {
            report($e);
            session()->flash('error', 'Unable to update the user right now.');
        }
    }

    public function sendResetLink(): void
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');

        try {
            $status = Password::sendResetLink(['email' => $this->user->email]);
            if ($status === Password::RESET_LINK_SENT) {
                session()->flash('success', 'Password reset link sent to ' . $this->user->email);
            } else {
                session()->flash('error', 'Unable to send password reset link.');
            }
        } catch (Throwable $e) {
            report($e);
            session()->flash('error', 'Unable to send password reset link.');
        }
    }

    public function openPasswordPanel(): void
    {
        $this->showPasswordPanel = true;
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetValidation();
    }

    public function closePasswordPanel(): void
    {
        $this->showPasswordPanel = false;
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function setPassword(): void
    {
        $this->authorizeAction('users.edit', 'You do not have permission to edit users.');

        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ]);

        try {
            $this->user->update([
                'password' => Hash::make($this->password),
                'email_verified_at' => now(),
            ]);

            session()->flash('success', 'Password set for ' . $this->user->name . '.');
            $this->closePasswordPanel();
        } catch (Throwable $e) {
            report($e);
            session()->flash('error', 'Failed to update the password.');
        }
    }

    public function render()
    {
        $eligibleDelegates = User::active()
            ->where('id', '!=', $this->user->id)
            ->whereHas('roles.permissions', fn ($q) => $q->where('name', 'approvals.approve_reject'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.user-edit', [
            'roles' => Role::orderBy('display_name')->get(),
            'programs' => Program::orderBy('name')->get(),
            'eligibleDelegates' => $eligibleDelegates,
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
}
