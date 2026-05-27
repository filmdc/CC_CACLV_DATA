<?php

namespace App\Models;

use App\Contracts\HasMediaAuthorization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Feedback extends Model implements HasMedia, HasMediaAuthorization
{
    use InteractsWithMedia;

    /**
     * P-39a: submitter sees own; managers gated by feedback.view_all
     * or feedback.manage (see FeedbackManagement:61).
     */
    public function canViewMedia(User $user, Media $media): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        return $user->hasPermission('feedback.view_all')
            || $user->hasPermission('feedback.manage');
    }

    protected $table = 'feedbacks';

    protected $fillable = [
        'category',
        'title',
        'description',
        'priority',
        'status',
        'page_url',
        'browser_info',
        'additional_context',
        'user_id',
        'admin_notes',
        'resolution_notes',
        'assigned_to',
        'resolved_by',
        'resolved_at',
        'github_issue_url',
        'github_issue_number',
        'synced_at',
    ];

    protected $casts = [
        'additional_context' => 'array',
        'resolved_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    protected $appends = [
        'category_label',
        'status_label',
        'priority_label',
        'status_color',
        'priority_color',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FeedbackComment::class)->orderBy('created_at', 'asc');
    }

    // Media Collections

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('screenshots')
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/gif', 'image/webp']);
    }

    // Display Attributes

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'bug_report' => 'Bug Report',
            'feature_request' => 'Feature Request',
            'usability_issue' => 'Usability Issue',
            'question' => 'Question',
            default => ucwords(str_replace('_', ' ', $this->category ?? 'Unknown')),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'new' => 'New',
            'acknowledged' => 'Acknowledged',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            'wont_fix' => "Won't Fix",
            default => ucwords(str_replace('_', ' ', $this->status ?? 'Unknown')),
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
            default => ucfirst($this->priority ?? 'Medium'),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'blue',
            'acknowledged' => 'yellow',
            'in_progress' => 'purple',
            'resolved' => 'green',
            'closed' => 'gray',
            'wont_fix' => 'red',
            default => 'gray',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'amber',
            'critical' => 'red',
            default => 'gray',
        };
    }

    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            'bug_report' => 'bug-ant',
            'feature_request' => 'light-bulb',
            'usability_issue' => 'cursor-arrow-ripple',
            'question' => 'question-mark-circle',
            default => 'chat-bubble-left',
        };
    }

    // Scopes

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['new', 'acknowledged', 'in_progress']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed', 'wont_fix']);
    }

    public function scopeBugs($query)
    {
        return $query->where('category', 'bug_report');
    }

    public function scopeFeatures($query)
    {
        return $query->where('category', 'feature_request');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'critical']);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                });
        });
    }

    // Helper Methods

    public function isOpen(): bool
    {
        return in_array($this->status, ['new', 'acknowledged', 'in_progress']);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['resolved', 'closed', 'wont_fix']);
    }

    public function resolve(User $resolver, ?string $resolutionNotes = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by' => $resolver->id,
            'resolved_at' => now(),
            'resolution_notes' => $resolutionNotes,
        ]);
    }

    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    public function assign(User $assignee): void
    {
        $this->update(['assigned_to' => $assignee->id]);
    }

    public function updateStatus(string $status): void
    {
        $this->update(['status' => $status]);
    }

    public function hasGitHubIssue(): bool
    {
        return ! empty($this->github_issue_url);
    }
}
