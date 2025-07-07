<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DueDateEmail extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'email',
        'name',
        'active',
        'preferences',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'preferences' => 'array',
    ];

    public static $searchable = [
        'email',
        'name',
    ];

    /**
     * Get the contract report due dates this email is assigned to
     */
    public function contractReportDueDates(): BelongsToMany
    {
        return $this->belongsToMany(ContractReportDueDate::class, 'contract_report_due_date_emails')
                    ->withPivot(['reminder_intervals', 'active'])
                    ->withTimestamps();
    }

    /**
     * Scope to get only active emails
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get the display name for this email
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ? "{$this->name} ({$this->email})" : $this->email;
    }

    /**
     * Get default email preferences
     */
    public static function getDefaultPreferences(): array
    {
        return [
            'reminder_intervals' => [30, 15, 7, 3, 1],
            'receive_overdue_notifications' => true,
            'receive_completion_notifications' => false,
        ];
    }

    /**
     * Set default preferences if none exist
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->preferences)) {
                $model->preferences = self::getDefaultPreferences();
            }
        });
    }
}