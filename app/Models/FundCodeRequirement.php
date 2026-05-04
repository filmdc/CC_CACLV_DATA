<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundCodeRequirement extends Model
{
    use HasFactory;

    public const TYPE_DOCUMENT = 'document';

    public const TYPE_TASK = 'task';

    protected $fillable = [
        'fund_code_id',
        'label',
        'description',
        'type',
        'is_hard_requirement',
        'sort_order',
    ];

    protected $casts = [
        'is_hard_requirement' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function fundCode(): BelongsTo
    {
        return $this->belongsTo(FundCode::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
