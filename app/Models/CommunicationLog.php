<?php

namespace App\Models;

use App\Traits\HasTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationLog extends Model
{
    use HasTags;

    protected $fillable = [
        'participant_id',
        'household_id',
        'property_id',
        'contractor_id',
        'communication_date',
        'communication_type',
        'direction',
        'contact_name',
        'contact_phone',
        'contact_email',
        'contact_organization',
        'contact_role',
        'subject',
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
        'communication_date' => 'datetime',
        'follow_up_date' => 'date',
        'follow_up_completed' => 'boolean',
        'follow_up_completed_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    protected $appends = [
        'communication_type_display',
        'direction_display',
        'contact_role_display',
        'category_display',
        'priority_display',
        'is_follow_up_overdue',
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

    public function getFormattedCommunicationDateAttribute(): string
    {
        return $this->communication_date?->format('M d, Y g:i A') ?? '';
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
            $q->where('contact_name', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhere('contact_organization', 'like', "%{$search}%")
                ->orWhere('contact_email', 'like', "%{$search}%")
                ->orWhere('contact_phone', 'like', "%{$search}%");
        });
    }
}
