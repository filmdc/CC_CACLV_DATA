<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HousingJob extends Model
{
    protected $fillable = [
        'job_number',
        'participant_id',
        'household_id',
        'property_id',
        'contractor_id',
        'program_id',
        'service_id',
        'job_title',
        'job_description',
        'job_type_id',
        'status',
        'request_date',
        'approval_date',
        'start_date',
        'target_completion_date',
        'actual_completion_date',
        'estimated_cost',
        'approved_amount',
        'actual_cost',
        'funding_source',
        'work_order_number',
        'contract_number',
        'scope_of_work',
        'priority',
        'cancellation_reason',
        'internal_notes',
        'created_by',
        'updated_by',
        'approved_by',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approval_date' => 'date',
        'start_date' => 'date',
        'target_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'scope_of_work' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            if (empty($job->job_number)) {
                $job->job_number = self::generateJobNumber();
            }
        });
    }

    public static function generateJobNumber(): string
    {
        $year = date('Y');
        $lastJob = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastJob && preg_match('/JOB-' . $year . '-(\d+)/', $lastJob->job_number, $matches)) {
            $sequence = str_pad((int)$matches[1] + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $sequence = '00001';
        }

        return "JOB-{$year}-{$sequence}";
    }

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

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function jobType(): BelongsTo
    {
        return $this->belongsTo(ReferenceData::class, 'job_type_id');
    }

    public function contactLedgers(): HasMany
    {
        return $this->hasMany(JobContactLedger::class);
    }

    public function documentChecklists(): HasMany
    {
        return $this->hasMany(JobDocumentChecklist::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'blue',
            'in_progress' => 'indigo',
            'on_hold' => 'orange',
            'completed' => 'green',
            'cancelled' => 'red',
            'rejected' => 'gray',
            default => 'gray',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'emergency' => 'red',
            default => 'gray',
        };
    }

    public function isOverdue(): bool
    {
        return $this->target_completion_date 
            && $this->target_completion_date->isPast() 
            && !in_array($this->status, ['completed', 'cancelled', 'rejected']);
    }

    public function getDaysOverdueAttribute(): ?int
    {
        if (!$this->isOverdue()) {
            return null;
        }

        return $this->target_completion_date->diffInDays(now());
    }

    public function getRequiredDocumentsCount(): int
    {
        return $this->documentChecklists()->where('is_required', true)->count();
    }

    public function getUploadedDocumentsCount(): int
    {
        return $this->documentChecklists()
            ->where('is_required', true)
            ->whereIn('status', ['uploaded', 'verified'])
            ->count();
    }

    public function areAllRequiredDocumentsUploaded(): bool
    {
        return $this->getRequiredDocumentsCount() === $this->getUploadedDocumentsCount();
    }
}
