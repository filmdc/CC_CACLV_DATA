<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $fillable = [
        'name',
        'description',
        'program_manager_id',
        'active',
        'start_date',
        'end_date',
        'notes'
    ];

    protected $casts = [
        'active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function programManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'program_manager_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function workPlans(): HasMany
    {
        return $this->hasMany(WorkPlan::class);
    }
}
