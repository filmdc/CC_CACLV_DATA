<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasLegacySyncScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Contract extends Model implements HasMedia
{
    use Auditable, HasFactory, HasLegacySyncScopes, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'title',
        'contract_number',
        'cfda_number',
        'purpose',
        'program_id',
        'funder_id',
        'date_of_award',
        'amount',
        'intacct_budget_amount',
        'intacct_budget_synced_at',
        'intacct_budget_source',
        'intacct_budget_lines_count',
        'preferred_intacct_budget_id',
        'start_date',
        'end_date',
        'grant_code',
        'service_code',
        'notes',
        'archived',
        'date_archived',
        'created_by',
        'updated_by',
        'legacy_id',
        'last_synced_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'intacct_budget_amount' => 'decimal:2',
        'intacct_budget_synced_at' => 'datetime',
        'intacct_budget_lines_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'date_of_award' => 'date',
        'date_archived' => 'datetime',
        'archived' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected $appends = [
        'documents',
        'status',
    ];

    public static $searchable = [
        'title',
        'contract_number',
        'cfda_number',
        'purpose',
        'grant_code',
        'service_code',
        'notes',
    ];

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
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
     * Get the program this contract belongs to
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the funder for this contract
     */
    public function funder(): BelongsTo
    {
        return $this->belongsTo(Funder::class, 'funder_id', 'id');
    }

    /**
     * Get all programs associated with this contract (many-to-many for legacy compatibility)
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'contract_program')
            ->withTimestamps();
    }

    /**
     * Get the services funded by this contract
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'contract_service')
            ->withPivot(['allocation_percentage', 'allocated_amount', 'notes', 'created_by', 'updated_by'])
            ->withTimestamps();
    }

    /**
     * Get project budgets funded by this contract
     */
    public function projectBudgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class);
    }

    /**
     * Get project expenses charged to this contract
     */
    public function projectExpenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    /**
     * Get Intacct-sourced posted expense lines for this contract
     */
    public function intacctFundExpenses(): HasMany
    {
        return $this->hasMany(IntacctFundExpense::class);
    }

    /**
     * Get total budgeted amount across all projects for this contract
     */
    public function getTotalBudgetedAttribute(): float
    {
        return (float) $this->projectBudgets()->sum('budgeted_amount');
    }

    /**
     * Get total spent amount for this contract.
     *
     * When Intacct-sourced expense lines exist for the contract, those become
     * the source of truth (posted GL activity). Otherwise, fall back to
     * manually-entered project_expenses.
     */
    public function getTotalSpentAttribute(): float
    {
        if ($this->intacctFundExpenses()->exists()) {
            return (float) $this->intacctFundExpenses()->sum('amount');
        }

        return (float) $this->projectExpenses()->sum('amount');
    }

    /**
     * Most recent Intacct sync timestamp across this contract's expense lines,
     * or null if never synced.
     */
    public function getLastIntacctSyncAttribute(): ?\Carbon\Carbon
    {
        $timestamp = $this->intacctFundExpenses()->max('last_synced_at');

        return $timestamp ? \Carbon\Carbon::parse($timestamp) : null;
    }

    /**
     * Whether this contract is currently sourcing utilization from Intacct
     * (has at least one synced GL line).
     */
    public function getIsIntacctSyncedAttribute(): bool
    {
        return $this->intacctFundExpenses()->exists();
    }

    /**
     * Effective budget used as the utilization denominator. Falls back to
     * the contract's awarded amount when no Intacct budget is synced.
     */
    public function getEffectiveBudgetAttribute(): float
    {
        if ($this->intacct_budget_amount !== null && (float) $this->intacct_budget_amount > 0) {
            return (float) $this->intacct_budget_amount;
        }

        return (float) $this->amount;
    }

    /**
     * Get remaining budget for this contract. Uses effective budget (Intacct
     * when synced, else awarded amount) as the starting point.
     */
    public function getRemainingBudgetAttribute(): float
    {
        return $this->effective_budget - $this->total_spent;
    }

    /**
     * Get the report due dates for this contract
     */
    public function reportDueDates(): HasMany
    {
        return $this->hasMany(ContractReportDueDate::class);
    }

    /**
     * Get orphan reports (reports without due dates) for this contract
     */
    public function orphanReports(): HasMany
    {
        return $this->hasMany(ContractReport::class)
            ->whereNull('contract_report_due_date_id')
            ->orderBy('submitted_date', 'desc');
    }

    /**
     * Get active report due dates
     */
    public function activeReportDueDates(): HasMany
    {
        return $this->hasMany(ContractReportDueDate::class)->where('active', true);
    }

    /**
     * Get the contract reports
     */
    public function contractReports(): HasMany
    {
        return $this->hasMany(ContractReport::class);
    }

    /**
     * Get documents attached to this contract
     */
    public function getDocumentsAttribute()
    {
        return $this->getMedia('documents');
    }

    /**
     * Get contract status based on dates and archived state
     */
    public function getStatusAttribute(): string
    {
        if ($this->archived) {
            return 'Archived';
        }

        if (! $this->start_date || ! $this->end_date) {
            return 'Draft';
        }

        $today = now()->toDateString();

        if ($this->start_date > $today) {
            return 'Upcoming';
        }

        if ($this->end_date < $today) {
            return 'Expired';
        }

        return 'Active';
    }

    /**
     * Scope to get active contracts
     */
    public function scopeActive($query)
    {
        return $query->where('archived', false);
    }

    /**
     * Scope to get archived contracts
     */
    public function scopeArchived($query)
    {
        return $query->where('archived', true);
    }

    /**
     * Archive the contract
     */
    public function archive(): bool
    {
        return $this->update([
            'archived' => true,
            'date_archived' => now(),
        ]);
    }

    /**
     * Unarchive the contract
     */
    public function unarchive(): bool
    {
        return $this->update([
            'archived' => false,
            'date_archived' => null,
        ]);
    }

    /**
     * Check if contract is expired
     */
    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    /**
     * Check if contract is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->start_date && $this->start_date->isFuture();
    }

    /**
     * Get overdue report due dates
     */
    public function getOverdueReportDueDates()
    {
        return $this->reportDueDates()->overdue()->get();
    }

    /**
     * Get upcoming report due dates
     */
    public function getUpcomingReportDueDates(int $days = 30)
    {
        return $this->reportDueDates()->upcoming($days)->get();
    }

    /**
     * Get all programs for this contract (includes both single program and many-to-many programs)
     */
    public function getAllPrograms()
    {
        $programs = collect();

        // Add the single program if it exists (current system)
        if ($this->program_id && $this->program) {
            $programs->push($this->program);
        }

        // Add many-to-many programs (legacy compatibility)
        $programs = $programs->merge($this->programs);

        return $programs->unique('id');
    }

    /**
     * Sync legacy programs (from many-to-many relationship data)
     */
    public function syncLegacyPrograms(array $programIds): void
    {
        $this->programs()->sync($programIds);
    }

    /**
     * Check if contract has multiple programs (legacy style)
     */
    public function hasMultiplePrograms(): bool
    {
        return $this->programs()->count() > 0;
    }

    /**
     * Get primary program (either the single program or first from many-to-many)
     */
    public function getPrimaryProgram()
    {
        if ($this->program_id && $this->program) {
            return $this->program;
        }

        return $this->programs()->first();
    }

    /**
     * Get formatted program names for display
     */
    public function getDisplayProgramNames(): string
    {
        $allPrograms = $this->getAllPrograms();

        if ($allPrograms->isEmpty()) {
            return 'N/A';
        }

        if ($allPrograms->count() === 1) {
            return $allPrograms->first()->name;
        }

        return $allPrograms->pluck('name')->join(', ');
    }

    /**
     * Get formatted program names with count for display
     */
    public function getDisplayProgramNamesWithCount(): string
    {
        $allPrograms = $this->getAllPrograms();

        if ($allPrograms->isEmpty()) {
            return 'N/A';
        }

        if ($allPrograms->count() === 1) {
            return $allPrograms->first()->name;
        }

        $count = $allPrograms->count();
        $firstProgram = $allPrograms->first()->name;

        return "{$firstProgram} (+{$count} programs)";
    }

    // ===== Contract Utilization Management =====

    /**
     * Get managers assigned to this contract
     */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'contract_managers')
            ->withPivot(['receive_notifications', 'created_by'])
            ->withTimestamps();
    }

    /**
     * Get utilization notifications for this contract
     */
    public function utilizationNotifications(): HasMany
    {
        return $this->hasMany(ContractUtilizationNotification::class);
    }

    /**
     * Get utilization activity logs for this contract
     */
    public function utilizationLogs(): HasMany
    {
        return $this->hasMany(ContractUtilizationLog::class);
    }

    /**
     * Get utilization percentage (amount spent as percentage of effective
     * budget — Intacct when synced, else awarded amount).
     */
    public function getUtilizationPercentageAttribute(): float
    {
        $budget = $this->effective_budget;

        if ($budget <= 0) {
            return 0;
        }

        return round(($this->total_spent / $budget) * 100, 2);
    }

    /**
     * Get remaining percentage (amount remaining as percentage of effective
     * budget).
     */
    public function getRemainingPercentageAttribute(): float
    {
        if ($this->effective_budget <= 0) {
            return 0;
        }

        return round(100 - $this->utilization_percentage, 2);
    }

    /**
     * Check if contract is fully utilized (spent >= amount)
     */
    public function isFullyUtilized(): bool
    {
        return $this->total_spent >= $this->amount;
    }

    /**
     * Check if contract can accept new expenses
     */
    public function canAcceptNewExpenses(): bool
    {
        return ! $this->isFullyUtilized() && ! $this->archived;
    }

    /**
     * Get remaining funds (capped at 0)
     */
    public function getRemainingFunds(): float
    {
        return max(0, $this->amount - $this->total_spent);
    }

    /**
     * Check if a specific threshold has been notified
     */
    public function hasNotifiedThreshold(int $threshold): bool
    {
        return ContractUtilizationNotification::hasBeenNotified($this->id, $threshold);
    }

    /**
     * Mark a threshold as notified
     */
    public function markThresholdNotified(int $threshold, array $recentExpenseIds = [], string $type = 'scheduled'): ContractUtilizationNotification
    {
        return ContractUtilizationNotification::create([
            'contract_id' => $this->id,
            'threshold_percentage' => $threshold,
            'utilization_at_notification' => $this->utilization_percentage,
            'remaining_amount' => $this->remaining_budget,
            'recent_expenses' => $recentExpenseIds,
            'notified_at' => now(),
            'notification_type' => $type,
        ]);
    }

    /**
     * Clear threshold notifications (when utilization drops)
     */
    public function clearThresholdNotifications(): int
    {
        return $this->utilizationNotifications()->delete();
    }

    /**
     * Reset thresholds that are no longer crossed (utilization dropped)
     */
    public function resetThresholdsIfNeeded(): int
    {
        return ContractUtilizationNotification::clearThresholdsAbove(
            $this->id,
            $this->remaining_percentage
        );
    }

    /**
     * Get recent expenses for this contract
     */
    public function getRecentExpenses(int $limit = 5): Collection
    {
        return $this->projectExpenses()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all notification recipients (managers + due date recipients)
     */
    public function getUtilizationNotificationRecipients(): Collection
    {
        $recipients = collect();

        // Get contract managers who have notifications enabled
        if (SiteSetting::get('utilization_notify_contract_managers', '1') === '1') {
            $managers = $this->managers()
                ->wherePivot('receive_notifications', true)
                ->get();
            $recipients = $recipients->merge($managers);
        }

        // Get due date email recipients
        if (SiteSetting::get('utilization_notify_due_date_emails', '1') === '1') {
            $dueDateRecipients = $this->reportDueDates()
                ->with('emails.user')
                ->get()
                ->flatMap(function ($dueDate) {
                    return $dueDate->emails
                        ->where('active', true)
                        ->pluck('user')
                        ->filter();
                });
            $recipients = $recipients->merge($dueDateRecipients);
        }

        // Return unique users only (filter out nulls and duplicates)
        return $recipients->filter()->unique('id');
    }

    /**
     * Get utilization status label
     */
    public function getUtilizationStatusAttribute(): string
    {
        $remaining = $this->remaining_percentage;

        if ($this->amount <= 0) {
            return 'No Budget';
        }

        if ($remaining <= 0) {
            return 'Depleted';
        }

        if ($remaining <= 5) {
            return 'Critical';
        }

        if ($remaining <= 10) {
            return 'High Risk';
        }

        if ($remaining <= 25) {
            return 'Warning';
        }

        if ($remaining <= 50) {
            return 'Caution';
        }

        return 'Healthy';
    }

    /**
     * Get utilization status color class
     */
    public function getUtilizationColorClassAttribute(): string
    {
        $remaining = $this->remaining_percentage;

        if ($this->amount <= 0) {
            return 'bg-gray-500';
        }

        if ($remaining <= 0) {
            return 'bg-red-600';
        }

        if ($remaining <= 5) {
            return 'bg-red-500';
        }

        if ($remaining <= 10) {
            return 'bg-orange-500';
        }

        if ($remaining <= 25) {
            return 'bg-yellow-500';
        }

        if ($remaining <= 50) {
            return 'bg-yellow-400';
        }

        if ($remaining <= 75) {
            return 'bg-green-400';
        }

        return 'bg-green-500';
    }
}
