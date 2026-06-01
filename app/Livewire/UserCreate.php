<?php

namespace App\Livewire;

use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

class UserCreate extends Component
{
    use WithPermissionChecks;

    public const PW_METHOD_RESET_LINK = 'reset_link';
    public const PW_METHOD_ADMIN_SET = 'admin_set';

    #[Url(as: 'duplicate_from')]
    public ?int $duplicateFromId = null;

    public ?User $sourceUser = null;

    public string $name = '';
    public string $email = '';
    public bool $active = true;
    public bool $isHousingTeam = false;

    /** @var array<int> */
    public array $selectedRoles = [];

    /** @var array<int> */
    public array $selectedPrograms = [];

    public string $passwordMethod = self::PW_METHOD_RESET_LINK;
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(?int $duplicate_from = null): void
    {
        $this->authorizeAction('users.edit', 'You do not have permission to create users.');

        // The #[Url] attribute on $duplicateFromId hydrates from the query string
        // on a real HTTP request; on a Livewire test that passes mount params, the
        // attribute does not fire, so accept it as a mount arg too.
        if ($duplicate_from !== null) {
            $this->duplicateFromId = (int) $duplicate_from;
        } elseif ($this->duplicateFromId === null && request()->filled('duplicate_from')) {
            $this->duplicateFromId = (int) request()->query('duplicate_from');
        }

        if ($this->duplicateFromId) {
            $source = User::with(['roles:id', 'programs:id'])->find($this->duplicateFromId);
            if ($source) {
                $this->sourceUser = $source;
                // Copy roles, programs, and the housing-team flag — NOT name, email, password, or delegate.
                $this->selectedRoles = $source->roles->pluck('id')->toArray();
                $this->selectedPrograms = $source->programs->pluck('id')->toArray();
                $this->isHousingTeam = (bool) $source->is_housing_team;
            }
        }
    }

    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'active' => ['boolean'],
            'isHousingTeam' => ['boolean'],
            'passwordMethod' => ['required', 'in:' . self::PW_METHOD_RESET_LINK . ',' . self::PW_METHOD_ADMIN_SET],
            'selectedRoles' => ['array'],
            'selectedRoles.*' => ['integer', 'exists:roles,id'],
            'selectedPrograms' => ['array'],
            'selectedPrograms.*' => ['integer', 'exists:programs,id'],
        ];

        if ($this->passwordMethod === self::PW_METHOD_ADMIN_SET) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            $rules['password_confirmation'] = ['required', 'string', 'min:8'];
        }

        return $rules;
    }

    public function create()
    {
        $this->authorizeAction('users.edit', 'You do not have permission to create users.');

        $this->name = Str::of($this->name)->squish()->toString();
        $this->email = Str::lower(trim($this->email));

        $this->validate();

        try {
            $attributes = [
                'name' => $this->name,
                'email' => $this->email,
                'active' => $this->active,
                'is_housing_team' => $this->isHousingTeam,
            ];

            if ($this->passwordMethod === self::PW_METHOD_ADMIN_SET) {
                $attributes['password'] = Hash::make($this->password);
                $attributes['email_verified_at'] = now();
            } else {
                // User will set their own via the reset link. Store an unusable hash so the column isn't null.
                $attributes['password'] = Hash::make(Str::random(40));
            }

            $user = User::create($attributes);

            $user->roles()->sync($this->sanitizeIds($this->selectedRoles));
            $user->programs()->sync($this->sanitizeIds($this->selectedPrograms));

            if ($this->passwordMethod === self::PW_METHOD_RESET_LINK) {
                $status = Password::sendResetLink(['email' => $user->email]);
                if ($status === Password::RESET_LINK_SENT) {
                    session()->flash('success', "User created. A password setup link was emailed to {$user->email}.");
                } else {
                    session()->flash('success', "User created, but the password reset email could not be sent. You can resend it from the edit page.");
                }
            } else {
                session()->flash('success', "User {$user->name} created.");
            }

            return $this->redirectRoute('users.edit', ['id' => $user->id], navigate: true);
        } catch (Throwable $e) {
            report($e);
            session()->flash('error', 'Unable to create the user right now.');
        }
    }

    public function render()
    {
        return view('livewire.user-create', [
            'roles' => Role::orderBy('display_name')->get(),
            'programs' => Program::orderBy('name')->get(),
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
