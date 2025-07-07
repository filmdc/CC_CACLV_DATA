<?php

namespace App\Traits;

trait Auditable
{
    /**
     * Boot the trait.
     */
    protected static function bootAuditable()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    /**
     * Get the user who created this record.
     */
    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}