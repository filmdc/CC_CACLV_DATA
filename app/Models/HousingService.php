<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Comm Log v2 / Phase 9: Kaitlyn-curated catalog of housing services,
 * decoupled from the program-tied Service model (which is CSBG
 * Module 4 reporting concern).
 *
 * A housing service is the answer to "what are we doing for this
 * client?" — picking one on a Communication Log auto-attaches its
 * linked fund codes (for budgeting) and stamps its linked atoms +
 * bundles onto intake_requirements (via IntakeRequirementInstantiator
 * ::forHousingService).
 *
 * Soft-deletable so historical comm-log → service references survive
 * removal of an obsolete catalog entry.
 */
class HousingService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function fundCodes(): BelongsToMany
    {
        return $this->belongsToMany(
            FundCode::class,
            'housing_service_fund_codes',
            'housing_service_id',
            'fund_code_id'
        )->withPivot('created_by_user_id', 'created_at');
    }

    public function templateSets(): BelongsToMany
    {
        return $this->belongsToMany(
            RequirementTemplateSet::class,
            'housing_service_template_sets',
            'housing_service_id',
            'template_set_id'
        )->withPivot('created_by_user_id', 'created_at');
    }

    public function requirementDefinitions(): BelongsToMany
    {
        return $this->belongsToMany(
            RequirementDefinition::class,
            'housing_service_definitions',
            'housing_service_id',
            'requirement_definition_id'
        )->withPivot('created_by_user_id', 'created_at');
    }

    public function communicationLogs(): BelongsToMany
    {
        return $this->belongsToMany(
            CommunicationLog::class,
            'communication_log_housing_services',
            'housing_service_id',
            'communication_log_id'
        )->withPivot('created_by_user_id', 'created_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
