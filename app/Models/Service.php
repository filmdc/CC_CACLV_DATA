<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'program_id',
        'start_date',
        'end_date',
        'active',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean'
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function workPlanGoals(): HasMany
    {
        return $this->hasMany(WorkPlanGoal::class);
    }
}
