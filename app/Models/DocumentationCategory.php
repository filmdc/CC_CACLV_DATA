<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DocumentationCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
            if (auth()->check()) {
                $category->created_by = auth()->id();
            }
        });

        static::updating(function ($category) {
            if (auth()->check()) {
                $category->updated_by = auth()->id();
            }
        });
    }

    public function articles(): HasMany
    {
        return $this->hasMany(DocumentationArticle::class, 'category_id');
    }

    public function publishedArticles(): HasMany
    {
        return $this->hasMany(DocumentationArticle::class, 'category_id')
            ->where('status', 'published')
            ->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getArticleCountAttribute(): int
    {
        return $this->articles()->where('status', 'published')->count();
    }
}
