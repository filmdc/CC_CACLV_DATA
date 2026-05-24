<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'contract_id',
        'is_active',
        'starts_at',
        'expires_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'date',
        'expires_at' => 'date',
    ];

    // Relationships

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * P-39b (addendum D-2): inverse of CommunicationLog::anticipatedFundCodes.
     * The legacy `communication_log_fund_code` tag pivot was migrated
     * into `communication_log_anticipated_fund_codes` and then dropped
     * in the same PR.
     */
    public function communicationLogs(): BelongsToMany
    {
        return $this->belongsToMany(CommunicationLog::class, 'communication_log_anticipated_fund_codes')
            ->withPivot('created_by_user_id', 'created_at');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(FundCodeRequirement::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors

    public function getFullDisplayAttribute(): string
    {
        return "{$this->code} – {$this->description}";
    }

    /**
     * Phase 3A / 7.5: bucket the fund's expiration distance from
     * today's start-of-day into one of four states. Hardcoded
     * thresholds (≤30 days = urgent, 31–90 days = warning, past =
     * expired). Documented in PHASE_3A_NOTES.md as a one-line config
     * change for a future phase if Kaitlyn's cadence differs.
     *
     * @return 'urgent'|'warning'|'expired'|null
     */
    public function expirationIndicator(): ?string
    {
        if ($this->expires_at === null) {
            return null;
        }

        $today = now()->startOfDay();
        $expiresAt = $this->expires_at->copy()->startOfDay();
        $days = (int) $today->diffInDays($expiresAt, false);

        if ($days < 0) {
            return 'expired';
        }

        if ($days <= 30) {
            return 'urgent';
        }

        if ($days <= 90) {
            return 'warning';
        }

        return null;
    }

    /**
     * True if the fund's start date is in the future. Informational
     * only — no impact on attachment, instantiation, or completion;
     * the dashboard surfaces a "Fund starts [date]" indicator on rows
     * tied to the not-yet-started fund.
     */
    public function isStartingInFuture(): bool
    {
        return $this->starts_at !== null
            && $this->starts_at->copy()->startOfDay()->isFuture();
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
