<?php

namespace App\Services;

use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\RequirementDefinition;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Milestone redesign (Phase 1): on new project creation, materialize one
 * ProjectMilestone per library atom flagged is_default_milestone=true.
 * Each seeded milestone is "promoted" — its requirement_definition_id
 * points back at the library atom, and a single canonical Requirement
 * row is created underneath so the UI can render the gating doc/task
 * the same way it renders PM-composed milestones' gates.
 *
 * Idempotent: re-running on the same job will not duplicate. The
 * uniqueness guard is the (housing_job_id, requirement_definition_id)
 * tuple. PM-composed milestones (requirement_definition_id=null) are
 * untouched.
 *
 * Hooked from CommunicationsLogManagement::convertToProject() after the
 * HousingJob row is created and inside the same transaction. Templates
 * intentionally skip this — they carry their own milestone shape.
 */
class DefaultMilestoneSeeder
{
    public function __construct(
        protected RequirementInstantiator $requirements,
    ) {}

    /**
     * Seed default milestones onto a freshly created HousingJob.
     *
     * @return Collection<int, ProjectMilestone> The milestones created
     *                                           (skips ones already present).
     */
    public function seed(HousingJob $job, ?User $actor = null): Collection
    {
        $alreadySeededDefinitionIds = ProjectMilestone::query()
            ->where('housing_job_id', $job->id)
            ->whereNotNull('requirement_definition_id')
            ->pluck('requirement_definition_id')
            ->all();

        $atoms = RequirementDefinition::query()
            ->defaultMilestones()
            ->orderBy('category')
            ->orderBy('label')
            ->get();

        $created = collect();
        $nextOrder = (int) ProjectMilestone::where('housing_job_id', $job->id)
            ->max('sequence_order');

        foreach ($atoms as $atom) {
            if (in_array($atom->id, $alreadySeededDefinitionIds, true)) {
                continue;
            }

            $nextOrder++;

            $milestone = ProjectMilestone::create([
                'housing_job_id' => $job->id,
                'requirement_definition_id' => $atom->id,
                'name' => $atom->label,
                'description' => $atom->hint,
                'sequence_order' => $nextOrder,
                'status' => 'not_started',
                'icon' => $this->iconForKind($atom->kind),
                'color' => $this->colorForCategory($atom->category),
                'assigned_to_user_id' => $job->project_manager_id,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            // The canonical gating row. We bypass RequirementInstantiator's
            // forMilestone() because we want to stamp source_template_id
            // (= atom.id) so the requirement is provably traceable back to
            // the library atom — forMilestone() omits that field by
            // design (it's reserved for fund-code-sourced templates today).
            // Reusing the instantiator's notification path would also
            // double-notify on the seeded set; the PM doesn't need a
            // mailbox full of "you were assigned" notes for their own
            // newly converted project.
            Requirement::create([
                'housing_job_id' => $job->id,
                'type' => $atom->kind === RequirementDefinition::KIND_DOC
                    ? Requirement::TYPE_DOCUMENT
                    : Requirement::TYPE_TASK,
                'title' => $atom->label,
                'description' => $atom->hint,
                'status' => Requirement::STATUS_OUTSTANDING,
                'source_type' => Requirement::SOURCE_MILESTONE,
                'source_id' => $milestone->id,
                'source_template_id' => $atom->id,
                'source_label_snapshot' => $atom->label,
                'owner_user_id' => $job->project_manager_id,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            $created->push($milestone);
        }

        return $created;
    }

    /**
     * Lucide icon mapping per atom kind. Visual cue only; the icon name
     * is consumed by the milestone row renderer.
     */
    protected function iconForKind(string $kind): string
    {
        return match ($kind) {
            RequirementDefinition::KIND_DOC => 'FileText',
            RequirementDefinition::KIND_FORM => 'ClipboardList',
            RequirementDefinition::KIND_DATA => 'Database',
            RequirementDefinition::KIND_VERIFY => 'ShieldCheck',
            RequirementDefinition::KIND_EXTERNAL => 'ExternalLink',
            default => 'Circle',
        };
    }

    /**
     * CALV-token-aligned default colors per workflow phase. The Edit
     * Milestone modal lets the PM override per milestone afterwards.
     */
    protected function colorForCategory(?string $category): string
    {
        return match ($category) {
            RequirementDefinition::CATEGORY_ELIGIBILITY => '#006269', // calv-teal
            RequirementDefinition::CATEGORY_COMPLIANCE => '#F1B434',  // calv-amber
            RequirementDefinition::CATEGORY_SERVICE_DELIVERY => '#6FA287', // calv-sage
            default => '#6366f1',
        };
    }
}
