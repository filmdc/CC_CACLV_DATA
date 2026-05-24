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

    protected $fillable = [
        'key',
        'label',
        'kind',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
