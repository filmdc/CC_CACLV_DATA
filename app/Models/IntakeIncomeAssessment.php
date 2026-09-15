<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The income figures and poverty determination behind an intake's
 * `income-eligibility` requirement.
 *
 * One per CommunicationLog. The determination columns
 * (guideline_year / guideline_region / poverty_threshold /
 * area_median_income / poverty_percentage / poverty_bracket) are a
 * snapshot taken at save time — HHS republishes the guidelines every
 * January and HUD reissues the HOME limits every year, and a 2025
 * intake has to keep reading the way it read in 2025.
 *
 * For housing (HOME) determinations the two money columns mean
 * different things and both matter: poverty_threshold is the 80%-AMI
 * ceiling the household was judged ELIGIBLE against, while
 * area_median_income is what poverty_percentage is a percentage OF.
 * poverty_bracket then holds HUD's band (30/50/60/80) rather than an
 * FPL bracket.
 *
 * Supporting documents live on the parent IntakeRequirement's Spatie
 * 'documents' collection, tagged with an `income_doc_type` custom
 * property (see self::DOC_TYPES), so the existing media-authorization
 * and download plumbing carries them unchanged.
 */
class IntakeIncomeAssessment extends Model
{
    use HasFactory;

    /** Income lines the worker can itemize, in the order they're offered. */
    public const SOURCE_TYPES = [
        'wages' => 'Wages / salary',
        'self_employment' => 'Self-employment',
        'social_security' => 'Social Security',
        'ssi' => 'SSI / SSDI',
        'pension' => 'Pension / retirement',
        'unemployment' => 'Unemployment',
        'child_support' => 'Child support / alimony',
        'public_assistance' => 'TANF / cash assistance',
        'rental' => 'Rental income',
        'other' => 'Other',
    ];

    /**
     * The proof slots the income requirement collects. Uploads are
     * stamped with the slot key so the modal can group them.
     */
    public const DOC_TYPES = [
        'pay_stub' => 'Pay stubs',
        'tax_return' => 'Tax return',
        'benefit_letter' => 'Benefit letters',
        'other' => 'Other income proof',
    ];

    protected $fillable = [
        'communication_log_id',
        'intake_requirement_id',
        'household_size',
        'entry_period',
        'income_sources',
        'entered_amount',
        'monthly_income',
        'annual_income',
        'guideline_year',
        'guideline_region',
        'poverty_threshold',
        'area_median_income',
        'poverty_percentage',
        'poverty_bracket',
        'calculated_at',
        'notes',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'household_size' => 'integer',
        'income_sources' => 'array',
        'entered_amount' => 'decimal:2',
        'monthly_income' => 'decimal:2',
        'annual_income' => 'decimal:2',
        'guideline_year' => 'integer',
        'poverty_threshold' => 'decimal:2',
        'area_median_income' => 'decimal:2',
        'poverty_percentage' => 'decimal:2',
        'poverty_bracket' => 'integer',
        'calculated_at' => 'datetime',
    ];

    public function communicationLog(): BelongsTo
    {
        return $this->belongsTo(CommunicationLog::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(IntakeRequirement::class, 'intake_requirement_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /** An assessment counts as done once it has income and a household size. */
    public function isComplete(): bool
    {
        return $this->annual_income !== null && (int) $this->household_size > 0;
    }

    /** True for a determination measured against the HUD HOME limits. */
    public function isHomeDetermination(): bool
    {
        return $this->guideline_region === PovertyGuideline::REGION_HOME_ABE;
    }

    /**
     * HUD's income band for a HOME determination ("Extremely low
     * income"), read off the snapshotted bracket. Null for FPL rows,
     * whose bracket is a poverty-level multiple and not a HUD band.
     */
    public function getTierLabelAttribute(): ?string
    {
        if (! $this->isHomeDetermination() || $this->poverty_bracket === null) {
            return null;
        }

        return PovertyGuideline::homeTierLabel((int) $this->poverty_bracket);
    }

    /**
     * Whether the household cleared the ceiling it was judged against.
     * Null when there is nothing to judge, or for FPL determinations,
     * which carry no program ceiling.
     */
    public function getIsIncomeEligibleAttribute(): ?bool
    {
        if (! $this->isHomeDetermination()
            || $this->annual_income === null
            || $this->poverty_threshold === null) {
            return null;
        }

        return (float) $this->annual_income <= (float) $this->poverty_threshold;
    }

    public static function sourceTypeLabel(?string $type): string
    {
        return self::SOURCE_TYPES[$type] ?? 'Other';
    }

    public static function docTypeLabel(?string $type): string
    {
        return self::DOC_TYPES[$type] ?? self::DOC_TYPES['other'];
    }

    /**
     * e.g. "143% of the 2025 federal poverty level", or — for housing
     * intakes snapshotted against the HUD HOME limits — "24.1% of the
     * FY2026 area median income · Extremely low income". Reads
     * guideline_region so a determination keeps describing the table it
     * was measured against.
     */
    public function getDeterminationLabelAttribute(): ?string
    {
        if ($this->poverty_percentage === null) {
            return null;
        }

        $percentage = rtrim(rtrim(number_format((float) $this->poverty_percentage, 1), '0'), '.');

        if ($this->guideline_region === PovertyGuideline::REGION_HOME_ABE) {
            $year = $this->guideline_year ? 'FY'.$this->guideline_year.' ' : '';
            $tier = $this->tier_label;

            return $percentage.'% of the '.$year.'area median income'
                .($tier === null ? '' : ' · '.$tier);
        }

        $year = $this->guideline_year ? $this->guideline_year.' ' : '';

        return $percentage.'% of the '.$year.'federal poverty level';
    }
}
