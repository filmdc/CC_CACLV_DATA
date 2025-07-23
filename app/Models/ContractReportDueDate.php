<?php

namespace App\Models;

use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContractReportDueDate extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia, Auditable;

    public const STATUS_OPTIONS = [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'overdue' => 'Overdue',
        'cancelled' => 'Cancelled',
    ];

    public const REPORT_TYPE_OPTIONS = [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'semi_annual' => 'Semi-Annual',
        'annual' => 'Annual',
        'final' => 'Final',
        'special' => 'Special Report',
    ];

    protected $fillable = [
        'contract_id',
        'due_date',
        'status',
        'description',
        'report_name',
        'report_type',
        'reminder_sent',
        'reminder_sent_at',
        'reminder_intervals',
        'active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'reminder_sent' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'reminder_intervals' => 'array',
        'active' => 'boolean',
    ];

    protected $appends = [
        'documents',
        'days_until_due',
        'is_overdue',
    ];

    public static $searchable = [
        'description',
        'report_type',
        'status',
    ];

    /**
     * Register media conversions
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit('crop', 150, 150);
        $this->addMediaConversion('preview')->fit('crop', 300, 300);
    }

    /**
     * Get the contract this due date belongs to
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the emails assigned to this due date
     */
    public function emails(): BelongsToMany
    {
        return $this->belongsToMany(DueDateEmail::class, 'contract_report_due_date_emails')
                    ->withPivot(['reminder_intervals', 'active'])
                    ->withTimestamps();
    }

    /**
     * Get the contract reports for this due date
     */
    public function contractReports(): HasMany
    {
        return $this->hasMany(ContractReport::class);
    }

    /**
     * Get the assigned users through emails relationship
     */
    public function assignedUsers()
    {
        return $this->emails()
            ->join('users', 'users.email', '=', 'due_date_emails.email')
            ->select('users.*')
            ->where('contract_report_due_date_emails.active', true);
    }

    /**
     * Get the pivot table records for this due date
     */
    public function dueDateEmails()
    {
        return $this->hasMany(ContractReportDueDateEmail::class);
    }

    /**
     * Get documents attached to this due date
     */
    public function getDocumentsAttribute()
    {
        return $this->getMedia('documents');
    }

    /**
     * Get days until due date
     */
    public function getDaysUntilDueAttribute(): int
    {
        return Carbon::today()->diffInDays($this->due_date, false);
    }

    /**
     * Check if the due date is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date->isPast() && $this->status !== 'completed';
    }

    /**
     * Scope to get active due dates
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get overdue items
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', Carbon::today())
                    ->where('status', '!=', 'completed');
    }

    /**
     * Scope to get upcoming due dates
     */
    public function scopeUpcoming($query, int $days = 30)
    {
        return $query->where('due_date', '>=', Carbon::today())
                    ->where('due_date', '<=', Carbon::today()->addDays($days))
                    ->where('status', '!=', 'completed');
    }

    /**
     * Check if reminder should be sent for a specific interval
     */
    public function shouldSendReminder(int $daysUntilDue): bool
    {
        $intervals = $this->reminder_intervals ?? [30, 15, 7, 3, 1];
        return in_array($daysUntilDue, $intervals) && $this->status !== 'completed' && $this->active;
    }

    /**
     * Mark reminder as sent
     */
    public function markReminderSent(): void
    {
        $this->update([
            'reminder_sent' => true,
            'reminder_sent_at' => now(),
        ]);
    }

    /**
     * Get default reminder intervals
     */
    public static function getDefaultReminderIntervals(): array
    {
        return [30, 15, 7, 3, 1];
    }
}