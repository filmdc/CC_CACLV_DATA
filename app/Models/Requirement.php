<?php

namespace App\Models;

use App\Notifications\RequirementAssignedNotification;
use App\Notifications\RequirementCompletedNotification;
use App\Traits\HasStandardMediaCollections;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Phase 3A unified requirement.
 *
 * type=document expects an attachment via Spatie media (collection
 * 'attachments'); marking complete without one requires an
 * override_reason.
 *
 * type=task is closed with a click + optional note.
 *
 * source_type points at one of: project_milestones (source_id), fund_codes
 * (source_id, with source_template_id = fund_code_requirements.id), or
 * 'ad_hoc' with both nulled. No FK on source_id because it is a typed
 * polymorphic pointer; lifecycle prompts handle cleanup explicitly.
 */
class Requirement extends Model implements HasMedia
{
    use HasFactory, HasStandardMediaCollections, InteractsWithMedia;

    public const TYPE_DOCUMENT = 'document';
    public const TYPE_TASK = 'task';

    public const STATUS_OUTSTANDING = 'outstanding';
    public const STATUS_COMPLETE = 'complete';
    public const STATUS_NOT_APPLICABLE = 'not_applicable';

    public const SOURCE_MILESTONE = 'milestone';
    public const SOURCE_FUND_CODE = 'fund_code';
    public const SOURCE_AD_HOC = 'ad_hoc';

    protected $fillable = [
        'housing_job_id',
        'type',
        'title',
        'description',
        'status',
        'owner_user_id',
        'source_type',
        'source_id',
        'source_template_id',
        'source_label_snapshot',
        'milestone_gate_text',
        'completed_at',
        'completed_by_user_id',
        'completion_note',
        'override_reason',
        'overridden_by_user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->registerStandardDocumentCollection('attachments');
    }

    // Relationships

    public function housingJob(): BelongsTo
    {
        return $this->belongsTo(HousingJob::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Resolve the polymorphic source object. Returns null for ad_hoc or
     * if the source row has been deleted.
     */
    public function source()
    {
        return match ($this->source_type) {
            self::SOURCE_MILESTONE => ProjectMilestone::find($this->source_id),
            self::SOURCE_FUND_CODE => FundCode::find($this->source_id),
            default => null,
        };
    }

    public function sourceTemplate(): ?FundCodeRequirement
    {
        if ($this->source_type !== self::SOURCE_FUND_CODE || $this->source_template_id === null) {
            return null;
        }

        return FundCodeRequirement::find($this->source_template_id);
    }

    // State helpers

    public function isDocument(): bool
    {
        return $this->type === self::TYPE_DOCUMENT;
    }

    public function isTask(): bool
    {
        return $this->type === self::TYPE_TASK;
    }

    public function isOutstanding(): bool
    {
        return $this->status === self::STATUS_OUTSTANDING;
    }

    public function isComplete(): bool
    {
        return $this->status === self::STATUS_COMPLETE;
    }

    public function isNotApplicable(): bool
    {
        return $this->status === self::STATUS_NOT_APPLICABLE;
    }

    public function wasOverridden(): bool
    {
        return $this->override_reason !== null;
    }

    public function hasAttachment(): bool
    {
        return $this->getMedia('attachments')->isNotEmpty();
    }

    // Scopes

    public function scopeOutstanding($query)
    {
        return $query->where('status', self::STATUS_OUTSTANDING);
    }

    public function scopeForOwner($query, int $userId)
    {
        return $query->where('owner_user_id', $userId);
    }

    // Mutating actions — explicit actor parameter so notifications can
    // reason about "did this user do it?" without inferring from Auth.

    /**
     * Reassign the requirement to a new owner. Notifies the new owner
     * unless the actor is also the new owner (no self-pings).
     */
    public function assign(?User $newOwner, User $actor): void
    {
        $previousOwnerId = $this->owner_user_id;
        $newOwnerId = $newOwner?->id;

        if ($previousOwnerId === $newOwnerId) {
            return;
        }

        $this->owner_user_id = $newOwnerId;
        $this->updated_by = $actor->id;
        $this->save();

        if ($newOwner !== null && $newOwner->id !== $actor->id) {
            $newOwner->notify(new RequirementAssignedNotification($this));
        }
    }

    /**
     * Mark a task-type or document-type requirement complete. For
     * document-type without media, callers must use
     * markCompleteWithOverride() instead. Notifies the project manager
     * unless the actor IS the project manager.
     */
    public function markComplete(User $actor, ?string $note = null): void
    {
        if ($this->isDocument() && ! $this->hasAttachment()) {
            throw new \DomainException(
                'Document-type requirement cannot be marked complete without an attachment. '.
                'Use markCompleteWithOverride() with an override reason.'
            );
        }

        $this->status = self::STATUS_COMPLETE;
        $this->completed_at = now();
        $this->completed_by_user_id = $actor->id;
        $this->completion_note = $note;
        $this->updated_by = $actor->id;
        $this->save();

        $this->notifyProjectManagerOfCompletion($actor);
    }

    /**
     * Close out a document-type requirement without an upload. The
     * override reason is mandatory and persisted alongside the completer.
     */
    public function markCompleteWithOverride(User $actor, string $overrideReason, ?string $note = null): void
    {
        if (trim($overrideReason) === '') {
            throw new \InvalidArgumentException('Override reason is required when completing a document requirement without an upload.');
        }

        $this->status = self::STATUS_COMPLETE;
        $this->completed_at = now();
        $this->completed_by_user_id = $actor->id;
        $this->completion_note = $note;
        $this->override_reason = $overrideReason;
        $this->overridden_by_user_id = $actor->id;
        $this->updated_by = $actor->id;
        $this->save();

        $this->notifyProjectManagerOfCompletion($actor);
    }

    public function markNotApplicable(User $actor, ?string $note = null): void
    {
        $this->status = self::STATUS_NOT_APPLICABLE;
        $this->completed_at = now();
        $this->completed_by_user_id = $actor->id;
        $this->completion_note = $note;
        $this->updated_by = $actor->id;
        $this->save();

        $this->notifyProjectManagerOfCompletion($actor);
    }

    /**
     * Revert a complete or not_applicable requirement back to
     * outstanding. Preserves the prior completion_note as history and
     * appends a "[REOPENED by X on Y]" line so the full timeline is
     * legible. Override fields are cleared because a re-opened
     * requirement no longer has an active override; the original
     * override_reason text is rolled into completion_note for audit
     * before it disappears from its dedicated column.
     *
     * No notification fires — leaving any "your requirement was
     * reopened" ping to Phase 3B if Kaitlyn asks for it.
     */
    public function reopen(User $actor): void
    {
        $history = trim((string) $this->completion_note);

        if (! empty($this->override_reason) && ! str_contains($history, $this->override_reason)) {
            $history = ($history === '' ? '' : $history."\n").
                '[OVERRIDE was: '.$this->override_reason.']';
        }

        $stamp = '[REOPENED by '.$actor->name.' on '.now()->toDateTimeString().']';
        $history = $history === '' ? $stamp : $history."\n".$stamp;

        $this->status = self::STATUS_OUTSTANDING;
        $this->completed_at = null;
        $this->completed_by_user_id = null;
        $this->completion_note = $history;
        $this->override_reason = null;
        $this->overridden_by_user_id = null;
        $this->updated_by = $actor->id;
        $this->save();
    }

    protected function notifyProjectManagerOfCompletion(User $actor): void
    {
        $manager = $this->housingJob?->projectManager;

        if ($manager === null) {
            return;
        }

        if ($manager->id === $actor->id) {
            return;
        }

        $manager->notify(new RequirementCompletedNotification($this, $actor));
    }
}
