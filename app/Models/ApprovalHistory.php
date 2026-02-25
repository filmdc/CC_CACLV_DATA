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
        'context_type',
        'context_id',
        'approval_type_id',
        'version',
        'action',
        'user_id',
        'reason',
        'notes',
        'metadata',
        'chain_step',
        'signature_ip',
        'signature_user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'version' => 'integer',
        'chain_step' => 'integer',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    public function approvalType(): BelongsTo
    {
        return $this->belongsTo(ApprovalType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by context morph.
     */
    public function scopeForContext($query, string $contextType, int $contextId)
    {
        return $query->where('context_type', $contextType)
                     ->where('context_id', $contextId);
    }

    /**
     * Scope for filtering by housing job via context morph.
     */
    public function scopeForJob($query, int $jobId)
    {
        return $query->where('context_type', HousingJob::class)
                     ->where('context_id', $jobId);
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
     * Log an approval action.
     * Accepts either a HousingJob (legacy) or context type/id for generalized usage.
     */
    public static function logApproval(
        Model $document,
        User $user,
        ?object $contextOrJob = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        return self::create(array_merge(
            self::buildBaseRecord($document, $user, 'approved', $notes, $metadata),
            self::buildContextFields($contextOrJob),
        ));
    }

    /**
     * Log a rejection action.
     */
    public static function logRejection(
        Model $document,
        User $user,
        string $reason,
        ?object $contextOrJob = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        return self::create(array_merge(
            self::buildBaseRecord($document, $user, 'rejected', $notes, $metadata),
            self::buildContextFields($contextOrJob),
            ['reason' => $reason],
        ));
    }

    /**
     * Log a resubmission action.
     */
    public static function logResubmission(
        Model $document,
        User $user,
        ?object $contextOrJob = null,
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        return self::create(array_merge(
            self::buildBaseRecord($document, $user, 'resubmitted', $notes, $metadata),
            self::buildContextFields($contextOrJob),
        ));
    }

    /**
     * Build the base record fields shared by all log methods.
     */
    private static function buildBaseRecord(
        Model $document,
        User $user,
        string $action,
        ?string $notes,
        ?array $metadata
    ): array {
        return [
            'approvable_type' => get_class($document),
            'approvable_id' => $document->id,
            'version' => $document->document_version ?? 1,
            'action' => $action,
            'user_id' => $user->id,
            'notes' => $notes,
            'metadata' => $metadata,
        ];
    }

    /**
     * Build context fields from a HousingJob or generic context object.
     */
    private static function buildContextFields(?object $contextOrJob): array
    {
        if (! $contextOrJob) {
            return [];
        }

        // HousingJob context
        if ($contextOrJob instanceof HousingJob) {
            return [
                'context_type' => HousingJob::class,
                'context_id' => $contextOrJob->id,
            ];
        }

        // Generic context model
        if ($contextOrJob instanceof Model) {
            return [
                'context_type' => get_class($contextOrJob),
                'context_id' => $contextOrJob->id,
            ];
        }

        return [];
    }
}
