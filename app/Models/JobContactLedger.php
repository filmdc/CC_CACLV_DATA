<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobContactLedger extends Model
{
    protected $fillable = [
        'housing_job_id',
        'contact_date',
        'contact_type',
        'direction',
        'contact_person',
        'contact_method_details',
        'contact_purpose',
        'notes',
        'outcome',
        'follow_up_date',
        'follow_up_action',
        'created_by',
    ];

    protected $casts = [
        'contact_date' => 'datetime',
        'follow_up_date' => 'date',
    ];

    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getContactTypeDisplayAttribute(): string
    {
        return match ($this->contact_type) {
            'phone_call' => 'Phone Call',
            'email' => 'Email',
            'in_person' => 'In Person',
            'site_visit' => 'Site Visit',
            'mail' => 'Mail',
            'text' => 'Text Message',
            'other' => 'Other',
            default => $this->contact_type,
        };
    }

    public function getContactPurposeDisplayAttribute(): string
    {
        return match ($this->contact_purpose) {
            'initial_inquiry' => 'Initial Inquiry',
            'status_update' => 'Status Update',
            'scheduling' => 'Scheduling',
            'inspection' => 'Inspection',
            'approval' => 'Approval',
            'issue_resolution' => 'Issue Resolution',
            'documentation' => 'Documentation',
            'payment' => 'Payment',
            'other' => 'Other',
            default => $this->contact_purpose ?? 'Not Specified',
        };
    }

    public function isFollowUpOverdue(): bool
    {
        return $this->follow_up_date && $this->follow_up_date->isPast();
    }
}
