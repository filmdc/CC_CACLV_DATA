<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'date_of_award' => 'date',
        'date_archived' => 'datetime',
        'archived' => 'boolean',
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
     * Register media conversions
     */
    public function registerMediaConversions(Media $media = null): void
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
        return $this->belongsTo(Funder::class);
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
}
