<?php

namespace App\Models;

use App\Traits\HasStandardMediaCollections;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * P-39b: intake-phase requirement on a CommunicationLog.
 *
 * Parallel to App\Models\Requirement (Phase 3A) but rooted on the
 * intake-phase entity. Statuses are outstanding/received/waived; there
 * is no owner_user_id because Kaitlyn is the sole intake worker in v1
 * and OQ-6 defers per-row notifications.
 *
 * Templates are read from `fund_code_requirements` with `phase =
 * 'eligibility'` (the project-phase analogue, Requirement, reads
 * `phase = 'project'`). source_label_snapshot copies the template label
 * at instantiate time so historical fidelity survives template renames.
 *
 * is_orphaned implements OQ-1's orphan-and-keep reconciliation:
 * de-anticipating a fund code flips the boolean to true on sourced
 * rows; re-anticipating flips it back. Progress denominators filter
 * `is_orphaned = false`.
 *
 * Phase 12 (Comm Log v2 refinements): Spatie HasMedia is wired up with
 * a 'documents' collection (disk: private_media) so document-type intake
 * requirements can hold their actual scanned/uploaded supporting files.
 */
class IntakeRequirement extends Model implements HasMedia
{
    use HasFactory, HasStandardMediaCollections, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        // Phase 12: store uploaded documents for type=document requirements
        // on the 'documents' collection (disk: private_media).
        $this->registerStandardDocumentCollection('documents');
    }

    public const TYPE_DOCUMENT = 'document';

    public const TYPE_TASK = 'task';

    // 6-state lifecycle (Comm Log v2 / Option A). The legacy
    // STATUS_OUTSTANDING was backfilled to STATUS_PENDING in migration
    // 2026_05_24_100005 and is no longer accepted by the column enum.
    public const STATUS_PENDING = 'pending';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_WAIVED = 'waived';

    public const STATUS_NA = 'na';

    public const STATUS_BLOCKED = 'blocked';

    /** @deprecated kept for back-compat with P-39b code paths during migration. */
    public const STATUS_OUTSTANDING = self::STATUS_PENDING;

    /** Statuses that count toward the Convert-to-Project requirements gate. */
    public const MET_STATUSES = [
        self::STATUS_RECEIVED,
        self::STATUS_WAIVED,
        self::STATUS_NA,
    ];

    public const SOURCE_BASELINE = 'baseline';

    public const SOURCE_FUND_CODE = 'fund_code';

    public const SOURCE_AD_HOC = 'ad_hoc';

    public const SOURCE_TEMPLATE_SET = 'template_set';

    public const SOURCE_DEFINITION = 'definition';

    // Atom kinds (snapshot copy of RequirementDefinition::KIND_*).
    public const KIND_DOC = 'doc';

    public const KIND_FORM = 'form';

    public const KIND_DATA = 'data';

    public const KIND_VERIFY = 'verify';

    public const KIND_EXTERNAL = 'external';

    protected $fillable = [
        'communication_log_id',
        'type',
        'label',
        'key',
        'kind',
        'via',
        'status',
        'received_at',
        'received_by_user_id',
        'status_changed_at',
        'status_changed_by_user_id',
        'source_type',
        'source_template_id',
        'source_definition_id',
        'source_label_snapshot',
        'is_orphaned',
        'notes',
        'worker_note',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'is_orphaned' => 'boolean',
        'via' => 'array',
    ];

    public function isMet(): bool
    {
        return in_array($this->status, self::MET_STATUSES, true);
    }

    // Relationships

    public function communicationLog(): BelongsTo
    {
        return $this->belongsTo(CommunicationLog::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by_user_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(RequirementDefinition::class, 'source_definition_id');
    }

    // Scopes

    public function scopeOutstanding($query)
    {
        return $query->where('status', self::STATUS_OUTSTANDING);
    }

    public function scopeReceived($query)
    {
        return $query->where('status', self::STATUS_RECEIVED);
    }

    public function scopeWaived($query)
    {
        return $query->where('status', self::STATUS_WAIVED);
    }

    public function scopeNotOrphaned($query)
    {
        return $query->where('is_orphaned', false);
    }

    public function scopeForCommunicationLog($query, int $id)
    {
        return $query->where('communication_log_id', $id);
    }

    // State helpers

    public function isOutstanding(): bool
    {
        return $this->status === self::STATUS_OUTSTANDING;
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function isWaived(): bool
    {
        return $this->status === self::STATUS_WAIVED;
    }

    public function isOrphaned(): bool
    {
        return (bool) $this->is_orphaned;
    }

    // Mutating actions
    //
    // Each takes an explicit User $actor for audit / created-by-vs-actor
    // reasoning, matching the Phase 3A Requirement convention.

    /**
     * Mark this intake requirement as received. Stamps received_at and
     * received_by_user_id ONLY IF not already received — re-marking a
     * received row preserves the first-receipt time, which is what the
     * dashboard reads for "days since last activity."
     */
    public function markReceived(User $actor, ?string $notes = null): void
    {
        $previous = $this->status;

        if ($this->status !== self::STATUS_RECEIVED) {
            $this->received_at = now();
            $this->received_by_user_id = $actor->id;
        }

        if ($notes !== null && $notes !== '') {
            $this->notes = $this->appendNote($this->notes, $notes);
        }
        $this->applyTransition(self::STATUS_RECEIVED, $actor, $previous);
    }

    /**
     * Move to "requested" — we have asked the client, awaiting return.
     */
    public function markRequested(User $actor, ?string $note = null): void
    {
        $previous = $this->status;
        if ($note !== null && $note !== '') {
            $this->notes = $this->appendNote($this->notes, $note);
        }
        $this->applyTransition(self::STATUS_REQUESTED, $actor, $previous);
    }

    /**
     * Close out an intake requirement without a receipt. The reason is
     * required and appended to notes. received_at marks the closure
     * timestamp; received_by_user_id marks who closed it (waive is
     * conceptually a closure-with-rationale).
     */
    public function markWaived(User $actor, string $reason): void
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('A reason is required to waive an intake requirement.');
        }

        $previous = $this->status;
        $this->received_at = now();
        $this->received_by_user_id = $actor->id;
        $this->notes = $this->appendNote($this->notes, '[WAIVED] '.$reason);
        $this->applyTransition(self::STATUS_WAIVED, $actor, $previous, '[WAIVED] '.$reason);
    }

    /**
     * Mark not applicable — the requirement doesn't apply to this case.
     * Reason required and appended to notes; counts toward the gate as
     * "met". received_at left null (this isn't a closure-with-receipt).
     */
    public function markNotApplicable(User $actor, string $reason): void
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('A reason is required to mark a requirement N/A.');
        }

        $previous = $this->status;
        $this->notes = $this->appendNote($this->notes, '[N/A] '.$reason);
        $this->applyTransition(self::STATUS_NA, $actor, $previous, '[N/A] '.$reason);
    }

    /**
     * System-only: an external check (kind=external) is in flight. Used
     * by the PHFA / partner check workflow per spec §5.7. Not a user-
     * driven action — exposed publicly so the instantiator can call it.
     */
    public function markBlocked(User $actor, ?string $note = null): void
    {
        $previous = $this->status;
        if ($note !== null && $note !== '') {
            $this->notes = $this->appendNote($this->notes, $note);
        }
        $this->applyTransition(self::STATUS_BLOCKED, $actor, $previous);
    }

    /**
     * Revert to pending. Clears the receipt stamp and appends a
     * [REOPENED ...] audit line to notes (matches Requirement::reopen
     * audit pattern at Requirement.php:279-299).
     */
    public function reopen(User $actor): void
    {
        $previous = $this->status;
        $stamp = '[REOPENED by '.$actor->name.' on '.now()->toDateTimeString().']';

        $this->received_at = null;
        $this->received_by_user_id = null;
        $this->notes = $this->appendNote($this->notes, $stamp);
        $this->applyTransition(self::STATUS_PENDING, $actor, $previous, $stamp);
    }

    /**
     * Shared transition path. Stamps status + audit columns, writes a
     * `status_change` CommunicationLogUpdate on the parent log so the
     * Activity feed reads as the audit trail (per spec §5.7), and saves.
     *
     * Skips the activity-update side-effect when the status didn't
     * actually change (re-marking the same status is a no-op for audit
     * purposes — only `notes` etc. get updated).
     */
    protected function applyTransition(string $newStatus, User $actor, ?string $previous, ?string $extraNote = null): void
    {
        $this->status = $newStatus;
        $this->status_changed_at = now();
        $this->status_changed_by_user_id = $actor->id;
        $this->updated_by_user_id = $actor->id;
        $this->save();

        if ($previous === $newStatus) {
            return;
        }

        $log = $this->communicationLog()->first();
        if ($log === null) {
            return;
        }

        $label = $this->label ?: ('Requirement #'.$this->id);
        $msg = "Requirement \"{$label}\" → ".self::statusDisplay($newStatus);
        if ($previous) {
            $msg = "Requirement \"{$label}\": ".self::statusDisplay($previous).' → '.self::statusDisplay($newStatus);
        }
        if ($extraNote !== null && $extraNote !== '') {
            $msg .= ' ('.$extraNote.')';
        }

        $log->addUpdate($msg, 'status_change', $actor->id);
    }

    public static function statusDisplay(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REQUESTED => 'Requested',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_WAIVED => 'Waived',
            self::STATUS_NA => 'N/A',
            self::STATUS_BLOCKED => 'Blocked',
            default => ucfirst($status),
        };
    }

    /**
     * Used by IntakeRequirementInstantiator::reconcileAnticipatedFundCodes
     * when a fund code is de-anticipated. Not a user-facing action.
     */
    public function markOrphaned(User $actor): void
    {
        $this->is_orphaned = true;
        $this->updated_by_user_id = $actor->id;
        $this->save();
    }

    /**
     * Inverse of markOrphaned. Called when a previously-de-anticipated
     * fund code is re-anticipated — un-orphans existing sourced rows so
     * the instantiator's idempotency does not produce duplicates.
     */
    public function unmarkOrphaned(User $actor): void
    {
        $this->is_orphaned = false;
        $this->updated_by_user_id = $actor->id;
        $this->save();
    }

    protected function appendNote(?string $existing, string $line): string
    {
        $existing = trim((string) $existing);

        return $existing === '' ? $line : $existing."\n".$line;
    }
}
