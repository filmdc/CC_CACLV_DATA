<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Adds helper scopes for models that track legacy identifiers and sync timestamps.
 */
trait HasLegacySyncScopes
{
    public function scopeByLegacyId(Builder $query, mixed $legacyId): Builder
    {
        return $query->where('legacy_id', $legacyId);
    }

    public function scopeNeedsSync(Builder $query, int $hours = 1): Builder
    {
        return $query->where(function (Builder $q) use ($hours) {
            $q->whereNull('last_synced_at')
              ->orWhere('last_synced_at', '<', now()->subHours($hours));
        });
    }
}
