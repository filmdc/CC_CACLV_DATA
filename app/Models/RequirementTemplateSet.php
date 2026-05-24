<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A named bundle of requirement atoms ("HCP First-Time Homebuyer set").
 *
 * Bundles let Kaitlyn stamp a curated set of requirements onto a
 * CommunicationLog with a single action. They can be:
 *   - linked to one or more Services (auto-applies when she tags that
 *     service on a log), or
 *   - applied manually from a picker.
 *
 * Frozen-snapshot semantics: bundle edits do NOT propagate back to
 * previously-instantiated IntakeRequirement rows. Instances carry their
 * own copy of label / kind / required via source_label_snapshot.
 */
class RequirementTemplateSet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(
            RequirementDefinition::class,
            'requirement_template_set_items',
            'template_set_id',
            'requirement_definition_id'
        )->withPivot('required_override', 'position')
         ->withTimestamps()
         ->orderBy('requirement_template_set_items.position');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'program_service_requirement_template_sets',
            'template_set_id',
            'service_id'
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
