<?php

namespace App\Models;

use App\Traits\HasLegacySyncScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory, HasLegacySyncScopes;

    protected $fillable = [
        'name',
        'description',
        'program_manager_id',
        'active',
        'housing_module_enabled',
        'contract_management_only',
        'start_date',
        'end_date',
        'notes',
        'legacy_id',
        'last_synced_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'housing_module_enabled' => 'boolean',
        'contract_management_only' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_synced_at' => 'datetime',
    ];

    public function programManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'program_manager_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function workPlans(): HasMany
    {
        return $this->hasMany(WorkPlan::class);
    }

    public function programEnrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    public function activeEnrollments()
    {
        return $this->programEnrollments()->active()->with('participant');
    }

    public function getEnrolledParticipantsCount(): int
    {
        return $this->activeEnrollments()->count();
    }

    public function getTotalEnrollmentsCount(): int
    {
        return $this->programEnrollments()->count();
    }

    public function housingJobs(): HasMany
    {
        return $this->hasMany(HousingJob::class);
    }

    public function hasHousingModule(): bool
    {
        return $this->housing_module_enabled;
    }

    /**
     * Scope to get only contract management only programs
     * (Programs that are ONLY for contract management and hidden from end users)
     */
    public function scopeContractManagementOnly($query)
    {
        return $query->where('contract_management_only', true);
    }

    /**
     * Scope to get regular programs (available to end users)
     * (Excludes contract management only programs, but regular programs can be used for contracts too)
     */
    public function scopeRegularPrograms($query)
    {
        return $query->where('contract_management_only', false);
    }

    /**
     * Get contracts associated with this program via many-to-many (legacy compatibility)
     */
    public function contractsViaMany(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class, 'contract_program')
                    ->withTimestamps();
    }

    /**
     * Get all contracts for this program (both direct FK and many-to-many)
     */
    public function getAllContracts()
    {
        $contracts = collect();
        
        // Add contracts with direct FK relationship (current system)
        $contracts = $contracts->merge($this->contracts);
        
        // Add contracts via many-to-many (legacy compatibility)
        $contracts = $contracts->merge($this->contractsViaMany);
        
        return $contracts->unique('id');
    }

    /**
     * Scope to get active programs (with optional contract management filter)
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

}
