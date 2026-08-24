<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user-defined view configuration for a Housing-portal list screen.
 *
 * screen:  'calls' | 'projects'
 * columns: ordered list of visible column keys (screen-specific)
 * filters: ['pill' => string, 'expanded' => bool] — expanded only on 'calls'
 */
class HomesSavedView extends Model
{
    protected $fillable = [
        'user_id',
        'screen',
        'name',
        'columns',
        'filters',
        'is_default',
    ];

    protected $casts = [
        'columns' => 'array',
        'filters' => 'array',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUserScreen($query, int $userId, string $screen)
    {
        return $query->where('user_id', $userId)->where('screen', $screen);
    }
}
