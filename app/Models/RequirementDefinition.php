<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An atom in Kaitlyn's master requirement library.
 *
 * A RequirementDefinition is a single, deduplicable requirement item
 * (e.g. "Photo ID", "Proof of income"). It can be:
 *   - grouped into one or more RequirementTemplateSets (bundles), or
 *   - stamped directly onto a CommunicationLog as an IntakeRequirement
 *     instance.
 *
 * Soft-deletable: removing an atom from the library does NOT cascade
 * to historical IntakeRequirement instances. Historical instances keep
 * pointing at the soft-deleted definition via source_definition_id.
 */
class RequirementDefinition extends Model
{
    use HasFactory, SoftDeletes;

    public const KIND_DOC = 'doc';
    public const KIND_FORM = 'form';
    public const KIND_DATA = 'data';
    public const KIND_VERIFY = 'verify';
    public const KIND_EXTERNAL = 'external';

    public const KINDS = [
        self::KIND_DOC,
        self::KIND_FORM,
        self::KIND_DATA,
        self::KIND_VERIFY,
        self::KIND_EXTERNAL,
    ];

    /* Phase 10 — workflow-phase category. Phase 1 surfaces eligibility +
     * compliance on the Comm Log viewer; Phase 2 surfaces
     * service_delivery_compliance on the project page. */
    public const CATEGORY_ELIGIBILITY = 'eligibility';
    public const CATEGORY_COMPLIANCE = 'compliance';
    public const CATEGORY_SERVICE_DELIVERY = 'service_delivery_compliance';

    public const CATEGORIES = [
        self::CATEGORY_ELIGIBILITY,
        self::CATEGORY_COMPLIANCE,
        self::CATEGORY_SERVICE_DELIVERY,
    ];

    /* The workflow-phase buckets that map to category sets. */
    public const PHASE_1_CATEGORIES = [
        self::CATEGORY_ELIGIBILITY,
        self::CATEGORY_COMPLIANCE,
    ];

    public const PHASE_2_CATEGORIES = [
        self::CATEGORY_SERVICE_DELIVERY,
    ];

    protected $fillable = [
        'key',
        'label',
        'kind',
        'category',
        'default_required',
        'hint',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'default_required' => 'boolean',
    ];

    public function templateSets(): BelongsToMany
    {
        return $this->belongsToMany(
            RequirementTemplateSet::class,
            'requirement_template_set_items',
            'requirement_definition_id',
            'template_set_id'
        )->withPivot('required_override', 'position')->withTimestamps();
    }

    /**
     * Phase 10: fund codes that auto-stamp this atom on a Comm Log when
     * the fund code is anticipated. Replaces the legacy bundle path.
     */
    public function fundCodes(): BelongsToMany
    {
        return $this->belongsToMany(
            FundCode::class,
            'fund_code_requirement_definitions',
            'requirement_definition_id',
            'fund_code_id'
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
}
