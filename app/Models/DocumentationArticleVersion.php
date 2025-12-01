<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentationArticleVersion extends Model
{
    protected $fillable = [
        'article_id',
        'version_number',
        'title',
        'content',
        'change_summary',
        'created_by',
    ];

    protected $casts = [
        'version_number' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(DocumentationArticle::class, 'article_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function restore(): void
    {
        $article = $this->article;

        $article->update([
            'title' => $this->title,
            'content' => $this->content,
        ]);
    }
}
