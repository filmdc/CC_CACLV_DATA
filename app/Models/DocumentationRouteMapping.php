<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentationRouteMapping extends Model
{
    protected $fillable = [
        'route_name',
        'route_label',
        'article_id',
        'priority',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(DocumentationArticle::class, 'article_id');
    }

    public function scopeForRoute($query, string $routeName)
    {
        return $query->where('route_name', $routeName);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
