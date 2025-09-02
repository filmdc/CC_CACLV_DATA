<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Contract extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, Auditable;

    protected $fillable = [
        'title',
        'contract_number',
        'cfda_number',
        'purpose',
        'program_id',
        'funder_id',
        'date_of_award',
        'amount',
        'start_date',
        'end_date',
        'grant_code',
        'notes',
        'archived',
        'date_archived',
        'created_by',
        'updated_by',
        'legacy_id',
        'last_synced_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'date_of_award' => 'date',
        'date_archived' => 'datetime',
        'archived' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected $appends = [
        'documents',
        'status',
    ];

    public static $searchable = [
        'title',
        'contract_number',
        'cfda_number',
        'purpose',
        'grant_code',
        'notes',
    ];

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'application/vnd.oasis.opendocument.text',
                'application/vnd.oasis.opendocument.spreadsheet',
                'text/plain',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/jpg'
            ])
            ->useDisk('public');
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit('crop', 150, 150);
        $this->addMediaConversion('preview')->fit('crop', 300, 300);
    }

    /**
     * Get the program this contract belongs to
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the funder for this contract
     */
    public function funder(): BelongsTo
    {
        return $this->belongsTo(Funder::class, 'funder_id', 'id');
    }

    /**
     * Get all programs associated with this contract (many-to-many for legacy compatibility)
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'contract_program')
                    ->withTimestamps();
    }

    /**
     * Get the report due dates for this contract
     */
    public function reportDueDates(): HasMany
    {
        return $this->hasMany(ContractReportDueDate::class);
    }

    /**
     * Get active report due dates
     */
    public function activeReportDueDates(): HasMany
    {
        return $this->hasMany(ContractReportDueDate::class)->where('active', true);
    }

    /**
     * Get the contract reports
     */
    public function contractReports(): HasMany
    {
        return $this->hasMany(ContractReport::class);
    }

    /**
     * Get documents attached to this contract
     */
    public function getDocumentsAttribute()
    {
        return $this->getMedia('documents');
    }

    /**
     * Get contract status based on dates and archived state
     */
    public function getStatusAttribute(): string
    {
        if ($this->archived) {
            return 'Archived';
        }

        if (!$this->start_date || !$this->end_date) {
            return 'Draft';
        }

        $today = now()->toDateString();

        if ($this->start_date > $today) {
            return 'Upcoming';
        }

        if ($this->end_date < $today) {
            return 'Expired';
        }

        return 'Active';
    }

    /**
     * Scope to get active contracts
     */
    public function scopeActive($query)
    {
        return $query->where('archived', false);
    }

    /**
     * Scope to get archived contracts
     */
    public function scopeArchived($query)
    {
        return $query->where('archived', true);
    }

    /**
     * Archive the contract
     */
    public function archive(): bool
    {
        return $this->update([
            'archived' => true,
            'date_archived' => now(),
        ]);
    }

    /**
     * Unarchive the contract
     */
    public function unarchive(): bool
    {
        return $this->update([
            'archived' => false,
            'date_archived' => null,
        ]);
    }

    /**
     * Check if contract is expired
     */
    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    /**
     * Check if contract is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->start_date && $this->start_date->isFuture();
    }

    /**
     * Get overdue report due dates
     */
    public function getOverdueReportDueDates()
    {
        return $this->reportDueDates()->overdue()->get();
    }

    /**
     * Get upcoming report due dates
     */
    public function getUpcomingReportDueDates(int $days = 30)
    {
        return $this->reportDueDates()->upcoming($days)->get();
    }

    /**
     * Get all programs for this contract (includes both single program and many-to-many programs)
     */
    public function getAllPrograms()
    {
        $programs = collect();
        
        // Add the single program if it exists (current system)
        if ($this->program_id && $this->program) {
            $programs->push($this->program);
        }
        
        // Add many-to-many programs (legacy compatibility)
        $programs = $programs->merge($this->programs);
        
        return $programs->unique('id');
    }

    /**
     * Sync legacy programs (from many-to-many relationship data)
     */
    public function syncLegacyPrograms(array $programIds): void
    {
        $this->programs()->sync($programIds);
    }

    /**
     * Check if contract has multiple programs (legacy style)
     */
    public function hasMultiplePrograms(): bool
    {
        return $this->programs()->count() > 0;
    }

    /**
     * Get primary program (either the single program or first from many-to-many)
     */
    public function getPrimaryProgram()
    {
        if ($this->program_id && $this->program) {
            return $this->program;
        }
        
        return $this->programs()->first();
    }

    /**
     * Get formatted program names for display
     */
    public function getDisplayProgramNames(): string
    {
        $allPrograms = $this->getAllPrograms();
        
        if ($allPrograms->isEmpty()) {
            return 'N/A';
        }
        
        if ($allPrograms->count() === 1) {
            return $allPrograms->first()->name;
        }
        
        return $allPrograms->pluck('name')->join(', ');
    }
    
    /**
     * Get formatted program names with count for display
     */
    public function getDisplayProgramNamesWithCount(): string
    {
        $allPrograms = $this->getAllPrograms();
        
        if ($allPrograms->isEmpty()) {
            return 'N/A';
        }
        
        if ($allPrograms->count() === 1) {
            return $allPrograms->first()->name;
        }
        
        $count = $allPrograms->count();
        $firstProgram = $allPrograms->first()->name;
        return "{$firstProgram} (+{$count} programs)";
    }

    /**
     * Scope to find contracts by legacy ID
     */
    public function scopeByLegacyId($query, $legacyId)
    {
        return $query->where('legacy_id', $legacyId);
    }

    /**
     * Scope to get contracts that need sync (not synced recently)
     */
    public function scopeNeedsSync($query, $hours = 1)
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours($hours));
        });
    }
}
