<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalHistory extends Model
{
    protected $table = 'approval_history';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'housing_job_id',
        'document_type',
        'document_id',
        'version',
        'action',
        'user_id',
        'reason',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'version' => 'integer',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForDocument($query, string $documentType, int $documentId)
    {
        return $query->where('document_type', $documentType)
                     ->where('document_id', $documentId);
    }

    public function scopeForJob($query, int $jobId)
    {
        return $query->where('housing_job_id', $jobId);
    }

    public function scopeApprovals($query)
    {
        return $query->where('action', 'approved');
    }

    public function scopeRejections($query)
    {
        return $query->where('action', 'rejected');
    }

    public function scopeResubmissions($query)
    {
        return $query->where('action', 'resubmitted');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'approved' => 'green',
            'rejected' => 'red',
            'resubmitted' => 'yellow',
            default => 'gray',
        };
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'resubmitted' => 'Resubmitted',
            default => ucfirst($this->action ?? 'Unknown'),
        };
    }

    /**
     * Log an approval action
     */
    public static function logApproval(
        Model $document,
        User $user,
        ?HousingJob $job = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'approvable_type' => get_class($document),
            'approvable_id' => $document->id,
            'housing_job_id' => $job?->id,
            'document_type' => get_class($document),
            'document_id' => $document->id,
            'version' => $document->document_version ?? 1,
            'action' => 'approved',
            'user_id' => $user->id,
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a rejection action
     */
    public static function logRejection(
        Model $document,
        User $user,
        string $reason,
        ?HousingJob $job = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'approvable_type' => get_class($document),
            'approvable_id' => $document->id,
            'housing_job_id' => $job?->id,
            'document_type' => get_class($document),
            'document_id' => $document->id,
            'version' => $document->document_version ?? 1,
            'action' => 'rejected',
            'user_id' => $user->id,
            'reason' => $reason,
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a resubmission action
     */
    public static function logResubmission(
        Model $document,
        User $user,
        ?HousingJob $job = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'approvable_type' => get_class($document),
            'approvable_id' => $document->id,
            'housing_job_id' => $job?->id,
            'document_type' => get_class($document),
            'document_id' => $document->id,
            'version' => $document->document_version ?? 1,
            'action' => 'resubmitted',
            'user_id' => $user->id,
            'notes' => $notes,
            'metadata' => $metadata,
        ]);
    }
}
