<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkPlanGoal extends Model
{
    protected $fillable = [
        'work_plan_id',
        'service_id',
        'goal_name',
        'description',
        'target_number',
        'actual_number'
    ];

    protected $casts = [
        'target_number' => 'integer',
        'actual_number' => 'integer'
    ];

    public function workPlan(): BelongsTo
    {
        return $this->belongsTo(WorkPlan::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function getProgressPercentageAttribute(): float
    {
        return $this->target_number > 0 ? 
            round(($this->actual_number / $this->target_number) * 100, 2) : 0;
    }
}
