<?php

namespace App\Models;

use App\Traits\HasTags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationLog extends Model
{
    use HasFactory, HasTags;

    protected $fillable = [
        'housing_job_id',
        'participant_id',
        'household_id',
        'property_id',
        'contractor_id',
        'communication_date',
        'communication_type',
        'direction',
        'contact_first_name',
        'contact_last_name',
        'contact_phone',
        'contact_email',
        'contact_address',
        'contact_city',
        'contact_state',
        'contact_zip',
        'contact_organization',
        'contact_role',
        'subject',
        'subject_overridden_at',
        'summary',
        'notes',
        'outcome',
        'category',
        'priority',
        'follow_up_date',
        'follow_up_action',
        'follow_up_completed',
        'follow_up_completed_at',
        'follow_up_completed_by',
        'converted_to_job_id',
        'converted_at',
        'converted_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'communication_date' => 'date',
        'follow_up_date' => 'date',
        'follow_up_completed' => 'boolean',
        'follow_up_completed_at' => 'datetime',
        'converted_at' => 'datetime',
        'subject_overridden_at' => 'datetime',
    ];

    protected $appends = [
        'communication_type_display',
        'direction_display',
        'contact_role_display',
        'category_display',
        'priority_display',
        'is_follow_up_overdue',
        'contact_full_name',
    ];

    // Relationships

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function followUpCompletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follow_up_completed_by');
    }

    // New relationships for services association
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'communication_log_service')
            ->withTimestamps();
    }

    /**
     * P-39b (addendum D-2): authoritative fund-code association on a
     * CommunicationLog. Replaces the legacy `communication_log_fund_code`
     * tag pivot, which was dropped in the same PR. Drives
     * IntakeRequirementInstantiator::forFundCode().
     */
    public function anticipatedFundCodes(): BelongsToMany
    {
        // Pivot has only created_at (no updated_at) — see the migration's
        // rationale. withTimestamps() is intentionally omitted; callers
        // that care about audit set created_at + created_by_user_id
        // explicitly (see IntakeRequirementInstantiator::reconcileAnticipatedFundCodes).
        return $this->belongsToMany(FundCode::class, 'communication_log_anticipated_fund_codes')
            ->withPivot('created_by_user_id', 'created_at');
    }

    /**
     * Comm Log v2 / Phase 9: housing services attached to this log.
     * Selecting a housing service auto-attaches its fund codes to
     * anticipatedFundCodes() and auto-stamps its bundled + direct atoms
     * via IntakeRequirementInstantiator::forHousingService.
     */
    public function housingServices(): BelongsToMany
    {
        return $this->belongsToMany(
            HousingService::class,
            'communication_log_housing_services',
            'communication_log_id',
            'housing_service_id'
        )->withPivot('created_by_user_id', 'created_at');
    }

    /**
     * P-39b: intake-phase requirements on this CommunicationLog.
     * Independent lifecycle from project-execution Requirements; see
     * IntakeRequirementInstantiator and the "Intake vs. Project
     * requirements" section of CLAUDE.md.
     */
    public function intakeRequirements(): HasMany
    {
        return $this->hasMany(IntakeRequirement::class);
    }

    // New relationship for updates/follow-ups
    public function updates(): HasMany
    {
        return $this->hasMany(CommunicationLogUpdate::class)->orderBy('created_at', 'desc');
    }

    // New relationship for converted project
    public function convertedToJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class, 'converted_to_job_id');
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    // Display Attributes

    public function getCommunicationTypeDisplayAttribute(): string
    {
        return match ($this->communication_type) {
            'phone_call' => 'Phone Call',
            'email' => 'Email',
            'in_person' => 'In Person',
            'site_visit' => 'Site Visit',
            'video_call' => 'Video Call',
            'text' => 'Text Message',
            'mail' => 'Mail',
            'voicemail' => 'Voicemail',
            'fax' => 'Fax',
            'other' => 'Other',
            default => $this->communication_type ?? 'Unknown',
        };
    }

    public function getDirectionDisplayAttribute(): string
    {
        return match ($this->direction) {
            'incoming' => 'Incoming',
            'outgoing' => 'Outgoing',
            default => $this->direction ?? 'Unknown',
        };
    }

    public function getContactRoleDisplayAttribute(): string
    {
        return match ($this->contact_role) {
            'homeowner' => 'Homeowner',
            'contractor' => 'Contractor',
            'inspector' => 'Inspector',
            'vendor' => 'Vendor',
            'agency' => 'Agency',
            'applicant' => 'Applicant',
            'family_member' => 'Family Member',
            'other' => 'Other',
            default => $this->contact_role ?? 'Not Specified',
        };
    }

    public function getCategoryDisplayAttribute(): string
    {
        return match ($this->category) {
            'general' => 'General',
            'intake' => 'Intake',
            'follow_up' => 'Follow-up',
            'complaint' => 'Complaint',
            'inquiry' => 'Inquiry',
            'referral' => 'Referral',
            'outreach' => 'Outreach',
            'scheduling' => 'Scheduling',
            'documentation' => 'Documentation',
            'other' => 'Other',
            default => $this->category ?? 'General',
        };
    }

    public function getPriorityDisplayAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => $this->priority ?? 'Normal',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'gray',
            'normal' => 'blue',
            'high' => 'amber',
            'urgent' => 'red',
            default => 'gray',
        };
    }

    public function getIsFollowUpOverdueAttribute(): bool
    {
        if (! $this->follow_up_date || $this->follow_up_completed) {
            return false;
        }

        return $this->follow_up_date->isPast();
    }

    public function getIsConvertedAttribute(): bool
    {
        return ! is_null($this->converted_to_job_id);
    }

    public function getContactFullNameAttribute(): string
    {
        return trim(($this->contact_first_name ?? '').' '.($this->contact_last_name ?? ''));
    }

    public function getContactFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->contact_address,
            $this->contact_city,
            $this->contact_state ? $this->contact_state : null,
            $this->contact_zip,
        ]);

        return implode(', ', $parts);
    }

    // Helper Methods

    public function markFollowUpCompleted(?int $userId = null): void
    {
        $this->follow_up_completed = true;
        $this->follow_up_completed_at = now();
        $this->follow_up_completed_by = $userId ?? auth()->id();
        $this->save();
    }

    public function addUpdate(string $content, string $type = 'note', ?int $userId = null): CommunicationLogUpdate
    {
        return $this->updates()->create([
            'update_content' => $content,
            'update_type' => $type,
            'created_by' => $userId ?? auth()->id(),
        ]);
    }

    public function convertToProject(HousingJob $job, ?int $userId = null): void
    {
        $this->converted_to_job_id = $job->id;
        $this->converted_at = now();
        $this->converted_by = $userId ?? auth()->id();
        $this->save();

        // Add an update noting the conversion
        $this->addUpdate(
            "Converted to Project: {$job->job_number} - {$job->job_title}",
            'status_change',
            $userId
        );
    }

    /**
     * Reset the conversion status to allow re-conversion
     * Admin override to fix logs that were converted before validation was added
     */
    public function resetConversion(?int $userId = null): void
    {
        $oldJobNumber = $this->convertedToJob?->job_number ?? 'Unknown';

        $this->converted_to_job_id = null;
        $this->converted_at = null;
        $this->converted_by = null;
        $this->save();

        // Add an update noting the reset
        $this->addUpdate(
            "Conversion reset by admin (was: {$oldJobNumber}). Can now be re-converted with updated validation.",
            'status_change',
            $userId
        );
    }

    public function getFormattedCommunicationDateAttribute(): string
    {
        return $this->communication_date?->format('M d, Y') ?? '';
    }

    public function getFormattedFollowUpDateAttribute(): string
    {
        return $this->follow_up_date?->format('M d, Y') ?? '';
    }

    public function getRelatedEntityAttribute(): ?string
    {
        if ($this->participant) {
            return 'Participant: '.$this->participant->full_name;
        }
        if ($this->household) {
            return 'Household: '.$this->household->household_code;
        }
        if ($this->property) {
            return 'Property: '.$this->property->address;
        }
        if ($this->contractor) {
            return 'Contractor: '.$this->contractor->company_name;
        }

        return null;
    }

    // Scopes

    public function scopeForParticipant($query, int $participantId)
    {
        return $query->where('participant_id', $participantId);
    }

    public function scopeForHousehold($query, int $householdId)
    {
        return $query->where('household_id', $householdId);
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeForContractor($query, int $contractorId)
    {
        return $query->where('contractor_id', $contractorId);
    }

    public function scopeForHousingJob($query, int $housingJobId)
    {
        return $query->where('housing_job_id', $housingJobId);
    }

    public function scopeForService($query, int $serviceId)
    {
        return $query->whereHas('services', function ($q) use ($serviceId) {
            $q->where('services.id', $serviceId);
        });
    }

    public function scopePendingFollowUp($query)
    {
        return $query->whereNotNull('follow_up_date')
            ->where('follow_up_completed', false);
    }

    public function scopeOverdueFollowUp($query)
    {
        return $query->whereNotNull('follow_up_date')
            ->where('follow_up_completed', false)
            ->where('follow_up_date', '<', now()->startOfDay());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('communication_type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('communication_date', '>=', now()->subDays($days));
    }

    public function scopeConverted($query)
    {
        return $query->whereNotNull('converted_to_job_id');
    }

    public function scopeNotConverted($query)
    {
        return $query->whereNull('converted_to_job_id');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('contact_first_name', 'like', "%{$search}%")
                ->orWhere('contact_last_name', 'like', "%{$search}%")
                ->orWhereRaw("CONCAT(contact_first_name, ' ', contact_last_name) LIKE ?", ["%{$search}%"])
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhere('contact_organization', 'like', "%{$search}%")
                ->orWhere('contact_email', 'like', "%{$search}%")
                ->orWhere('contact_phone', 'like', "%{$search}%")
                ->orWhere('contact_address', 'like', "%{$search}%")
                ->orWhere('contact_city', 'like', "%{$search}%")
                // P-02: also match logs by their anticipated fund codes.
                // (Relation renamed in P-39b — see addendum D-2.)
                ->orWhereHas('anticipatedFundCodes', function ($fq) use ($search) {
                    $fq->where('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
        });
    }
}
