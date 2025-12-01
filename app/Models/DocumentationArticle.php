<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DocumentationArticle extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'category_id',
        'status',
        'is_internal',
        'show_in_help_widget',
        'sort_order',
        'view_count',
        'current_version',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'show_in_help_widget' => 'boolean',
        'sort_order' => 'integer',
        'view_count' => 'integer',
        'current_version' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'status_color',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
            if (auth()->check()) {
                $article->created_by = auth()->id();
            }
        });

        static::updating(function ($article) {
            if (auth()->check()) {
                $article->updated_by = auth()->id();
            }

            // Auto-create version before update if content or title changed
            if ($article->isDirty(['title', 'content']) && $article->exists) {
                $original = $article->getOriginal();
                DocumentationArticleVersion::create([
                    'article_id' => $article->id,
                    'version_number' => $article->current_version,
                    'title' => $original['title'],
                    'content' => $original['content'],
                    'change_summary' => 'Auto-saved before update',
                    'created_by' => auth()->id(),
                ]);
                $article->current_version = $article->current_version + 1;
            }
        });
    }

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentationCategory::class, 'category_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentationArticleVersion::class, 'article_id')
            ->orderBy('version_number', 'desc');
    }

    public function routeMappings(): HasMany
    {
        return $this->hasMany(DocumentationRouteMapping::class, 'article_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(DocumentationArticleFeedback::class, 'article_id');
    }

    public function bookmarkedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'documentation_user_bookmarks', 'article_id', 'user_id')
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
            default => ucfirst($this->status ?? 'Unknown'),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'yellow',
            'published' => 'green',
            'archived' => 'gray',
            default => 'gray',
        };
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeForRoute($query, string $routeName)
    {
        return $query->whereHas('routeMappings', function ($q) use ($routeName) {
            $q->where('route_name', $routeName);
        });
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%");
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    public function scopeVisibleToUser($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return $query->where('is_internal', false);
        }

        if ($user->hasPermission('documentation.view_internal')) {
            return $query;
        }

        return $query->where('is_internal', false);
    }

    public function scopeForHelpWidget($query)
    {
        return $query->where('show_in_help_widget', true);
    }

    // Methods
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isBookmarkedBy(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return false;
        }

        return $this->bookmarkedByUsers()->where('user_id', $user->id)->exists();
    }

    public function getFeedbackStats(): array
    {
        $total = $this->feedback()->count();
        $helpful = $this->feedback()->where('is_helpful', true)->count();
        $notHelpful = $total - $helpful;

        return [
            'total' => $total,
            'helpful' => $helpful,
            'not_helpful' => $notHelpful,
            'percentage' => $total > 0 ? round(($helpful / $total) * 100) : 0,
        ];
    }

    public function getUserFeedback(?User $user = null): ?DocumentationArticleFeedback
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return null;
        }

        return $this->feedback()->where('user_id', $user->id)->first();
    }
}
