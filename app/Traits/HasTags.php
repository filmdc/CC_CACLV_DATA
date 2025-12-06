<?php

namespace App\Traits;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTags
{
    /**
     * Get all tags for this model
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withTimestamps()
            ->withPivot('created_by');
    }

    /**
     * Attach a tag by name (creates if doesn't exist)
     */
    public function attachTagByName(string $name): void
    {
        $tag = Tag::findOrCreateByName($name);

        if (! $this->tags()->where('tag_id', $tag->id)->exists()) {
            $this->tags()->attach($tag->id, ['created_by' => auth()->id()]);
        }
    }

    /**
     * Attach multiple tags by name
     */
    public function attachTagsByNames(array $names): void
    {
        foreach ($names as $name) {
            $this->attachTagByName($name);
        }
    }

    /**
     * Sync tags from array of tag IDs
     */
    public function syncTags(array $tagIds): void
    {
        $syncData = [];
        foreach ($tagIds as $tagId) {
            $syncData[$tagId] = ['created_by' => auth()->id()];
        }
        $this->tags()->sync($syncData);
    }

    /**
     * Sync tags from array of tag names (creates new tags as needed)
     */
    public function syncTagsByNames(array $names): void
    {
        $tagIds = [];
        foreach ($names as $name) {
            if (empty(trim($name))) {
                continue;
            }
            $tag = Tag::findOrCreateByName(trim($name));
            $tagIds[] = $tag->id;
        }
        $this->syncTags($tagIds);
    }

    /**
     * Detach a tag
     */
    public function detachTag(Tag $tag): void
    {
        $this->tags()->detach($tag->id);
    }

    /**
     * Check if model has a specific tag
     */
    public function hasTag(string $name): bool
    {
        return $this->tags()->where('name', $name)->exists();
    }

    /**
     * Scope to filter by tag
     */
    public function scopeWithTag($query, string $tagName)
    {
        return $query->whereHas('tags', function ($q) use ($tagName) {
            $q->where('name', $tagName);
        });
    }

    /**
     * Scope to filter by any of the given tags
     */
    public function scopeWithAnyTags($query, array $tagNames)
    {
        return $query->whereHas('tags', function ($q) use ($tagNames) {
            $q->whereIn('name', $tagNames);
        });
    }

    /**
     * Scope to filter by all of the given tags
     */
    public function scopeWithAllTags($query, array $tagNames)
    {
        foreach ($tagNames as $tagName) {
            $query->whereHas('tags', function ($q) use ($tagName) {
                $q->where('name', $tagName);
            });
        }

        return $query;
    }
}
