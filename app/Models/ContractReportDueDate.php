<?php

namespace App\Models;

use App\Contracts\HasMediaAuthorization;
use App\Traits\Auditable;
use App\Traits\HasLegacySyncScopes;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContractReportDueDate extends Model implements HasMedia, HasMediaAuthorization
{
    use Auditable, HasLegacySyncScopes, InteractsWithMedia, SoftDeletes;

    /**
     * P-39a: matches ContractManagement / ContractReportDueDateManagement
     * page-level gate (due_dates.view).
     */
    public function canViewMedia(User $user, Media $media): bool
    {
        return $user->hasPermission('due_dates.view');
    }

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
        'due_time',
        'status',
        'description',
        'report_name',
        'report_type',
        'reminder_sent',
        'reminder_sent_at',
        'reminder_intervals',
        'first_reminder',
        'second_reminder',
        'final_reminder',
        'invite_sent',
        'microsoft_event_id',
        'is_recurring',
        'recurrence_pattern',
        'reminder_emails',
        'active',
        'notes',
        'created_by',
        'updated_by',
        'legacy_id',
        'last_synced_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'due_time' => 'datetime:H:i:s',
        'reminder_sent' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'reminder_intervals' => 'array',
        'invite_sent' => 'boolean',
        'is_recurring' => 'boolean',
        'active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected $appends = [
        'templates',
        'documents', // Legacy support
        'days_until_due',
        'is_overdue',
    ];

    public static $searchable = [
        'description',
        'report_type',
        'status',
    ];

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('templates')
            ->acceptsMimeTypes([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'application/vnd.oasis.opendocument.text',
                'application/vnd.oasis.opendocument.spreadsheet',
                'text/plain',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/jpg',
            ])
            ->useDisk('public');
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?Media $media = null): void
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
            ->using(ContractReportDueDateEmail::class)
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
     * Get templates attached to this due date
     */
    public function getTemplatesAttribute()
    {
        return $this->getMedia('templates');
    }

    /**
     * Get documents attached to this due date (legacy accessor)
     *
     * @deprecated Use getTemplatesAttribute() instead
     */
    public function getDocumentsAttribute()
    {
        return $this->getMedia('templates');
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

    /**
     * Convert legacy individual reminder fields to current reminder_intervals array
     */
    public function setLegacyReminders(?int $first = null, ?int $second = null, ?int $final = null): void
    {
        $intervals = [];
        if ($first) {
            $intervals[] = $first;
        }
        if ($second) {
            $intervals[] = $second;
        }
        if ($final) {
            $intervals[] = $final;
        }

        $this->reminder_intervals = $intervals;
        $this->first_reminder = $first;
        $this->second_reminder = $second;
        $this->final_reminder = $final;
    }

    /**
     * Get legacy reminder fields from current reminder_intervals array
     */
    public function getLegacyReminderFields(): array
    {
        $intervals = $this->reminder_intervals ?? [];

        return [
            'first_reminder' => $intervals[0] ?? null,
            'second_reminder' => $intervals[1] ?? null,
            'final_reminder' => $intervals[2] ?? null,
        ];
    }

    /**
     * Create due date with time from legacy date and time fields
     */
    public function setLegacyDateTime(string $date, ?string $time = null): void
    {
        $this->due_date = $date;
        $this->due_time = $time;
    }

    /**
     * Get full datetime for legacy compatibility
     */
    public function getLegacyDateTimeAttribute(): string
    {
        $dateTime = $this->due_date->format('Y-m-d');
        if ($this->due_time) {
            $dateTime .= ' '.$this->due_time;
        }

        return $dateTime;
    }

    /**
     * Check if this due date has calendar integration
     */
    public function hasCalendarIntegration(): bool
    {
        return ! empty($this->microsoft_event_id);
    }
}
