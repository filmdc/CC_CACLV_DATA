<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    protected $fillable = [
        'housing_job_id',
        'project_milestone_id',
        'call_date',
        'call_type',
        'direction',
        'contact_name',
        'contact_phone',
        'contact_email',
        'contact_organization',
        'contact_role',
        'subject',
        'notes',
        'outcome',
        'follow_up_date',
        'follow_up_action',
        'follow_up_completed',
        'follow_up_completed_at',
        'follow_up_completed_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'call_date' => 'datetime',
        'follow_up_date' => 'date',
        'follow_up_completed' => 'boolean',
        'follow_up_completed_at' => 'datetime',
    ];

    protected $appends = [
        'call_type_display',
        'direction_display',
        'contact_role_display',
        'is_follow_up_overdue',
    ];

    // Relationships

    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'project_milestone_id');
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

    // Display Attributes

    public function getCallTypeDisplayAttribute(): string
    {
        return match ($this->call_type) {
            'phone_call' => 'Phone Call',
            'email' => 'Email',
            'in_person' => 'In Person',
            'site_visit' => 'Site Visit',
            'video_call' => 'Video Call',
            'text' => 'Text Message',
            'mail' => 'Mail',
            'voicemail' => 'Voicemail',
            'other' => 'Other',
            default => $this->call_type ?? 'Unknown',
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
            'other' => 'Other',
            default => $this->contact_role ?? 'Not Specified',
        };
    }

    public function getIsFollowUpOverdueAttribute(): bool
    {
        if (!$this->follow_up_date || $this->follow_up_completed) {
            return false;
        }

        return $this->follow_up_date->isPast();
    }

    // Helper Methods

    public function markFollowUpCompleted(?int $userId = null): void
    {
        $this->follow_up_completed = true;
        $this->follow_up_completed_at = now();
        $this->follow_up_completed_by = $userId ?? auth()->id();
        $this->save();
    }

    public function getFormattedCallDateAttribute(): string
    {
        return $this->call_date?->format('M d, Y g:i A') ?? '';
    }

    public function getFormattedFollowUpDateAttribute(): string
    {
        return $this->follow_up_date?->format('M d, Y') ?? '';
    }

    // Scopes

    public function scopeForJob($query, int $jobId)
    {
        return $query->where('housing_job_id', $jobId);
    }

    public function scopeForMilestone($query, int $milestoneId)
    {
        return $query->where('project_milestone_id', $milestoneId);
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
        return $query->where('call_type', $type);
    }

    public function scopeByDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('call_date', '>=', now()->subDays($days));
    }
}
