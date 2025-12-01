<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentationArticleFeedback extends Model
{
    protected $table = 'documentation_article_feedback';

    protected $fillable = [
        'article_id',
        'user_id',
        'is_helpful',
        'comment',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(DocumentationArticle::class, 'article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
