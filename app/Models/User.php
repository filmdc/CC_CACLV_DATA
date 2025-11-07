<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /**
     * The roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    /**
     * The programs that belong to the user.
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'user_program');
    }

    /**
     * The user's Sage Intacct OAuth token.
     */
    public function sageIntacctToken()
    {
        return $this->hasOne(UserSageIntacctToken::class);
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if the user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Check if the user has all of the given roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('name', $permission);
        })->exists();
    }

    /**
     * Check if the user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permissions) {
            $query->whereIn('name', $permissions);
        })->exists();
    }

    /**
     * Check if the user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('administrator');
    }

    /**
     * Get all permissions through roles.
     */
    public function permissions()
    {
        return $this->roles()->with('permissions')->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    /**
     * Scope to get active users.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /**
     * Check if user has access to a specific program.
     */
    public function hasAccessToProgram($programId): bool
    {
        // Admins and Contract Managers have access to all programs
        if ($this->isAdmin() || $this->hasRole('Contract Manager')) {
            return true;
        }

        // Check if user is assigned to this program
        return $this->programs()->where('program_id', $programId)->exists();
    }

    /**
     * Get all program IDs the user has access to.
     */
    public function getAccessibleProgramIds(): array
    {
        // Admins and Contract Managers have access to all programs
        if ($this->isAdmin() || $this->hasRole('Contract Manager')) {
            return Program::pluck('id')->toArray();
        }

        return $this->programs()->pluck('program_id')->toArray();
    }

    /**
     * Check if user can access participant sensitive data.
     * User must be enrolled in at least one program that the participant is enrolled in.
     */
    public function canAccessParticipantData($participantId): bool
    {
        // Admins can access all participant data
        if ($this->isAdmin()) {
            return true;
        }

        $userProgramIds = $this->getAccessibleProgramIds();

        // If user has no program assignments, they cannot access sensitive data
        if (empty($userProgramIds)) {
            return false;
        }

        // Check if participant is enrolled in any of user's programs
        return ProgramEnrollment::where('participant_id', $participantId)
            ->whereIn('program_id', $userProgramIds)
            ->exists();
    }

    /**
     * Check if user can access a specific enrollment.
     */
    public function canAccessEnrollment($enrollmentId, $enrollmentType = 'program'): bool
    {
        // Admins can access all enrollments
        if ($this->isAdmin()) {
            return true;
        }

        $userProgramIds = $this->getAccessibleProgramIds();

        if (empty($userProgramIds)) {
            return false;
        }

        if ($enrollmentType === 'program') {
            return ProgramEnrollment::where('id', $enrollmentId)
                ->whereIn('program_id', $userProgramIds)
                ->exists();
        } else {
            // For service enrollments, check the related program
            return ServiceEnrollment::where('id', $enrollmentId)
                ->whereHas('service', function ($query) use ($userProgramIds) {
                    $query->whereIn('program_id', $userProgramIds);
                })
                ->exists();
        }
    }

    /**
     * Check if user can access household sensitive data.
     * User can access if any household member is enrolled in user's programs.
     */
    public function canAccessHouseholdData($householdId): bool
    {
        // Admins can access all household data
        if ($this->isAdmin()) {
            return true;
        }

        $userProgramIds = $this->getAccessibleProgramIds();

        if (empty($userProgramIds)) {
            return false;
        }

        // Check if any household member is enrolled in user's programs
        return ProgramEnrollment::whereHas('participant', function ($query) use ($householdId) {
            $query->where('household_id', $householdId);
        })->whereIn('program_id', $userProgramIds)->exists();
    }
}
