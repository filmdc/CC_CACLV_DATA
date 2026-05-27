<?php

use App\Models\FundCodeRequirement;
use App\Models\RequirementDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Phase 10+ consolidation: migrate every P-39b FundCodeRequirement row
 * into the unified atom-library shape:
 *
 *   - requirement_definitions row (deduped by slugified label),
 *   - fund_code_requirement_definitions pivot row linking the
 *     definition back to the original fund code.
 *
 * Duplicate labels across fund codes merge into a single shared
 * RequirementDefinition (one "Photo ID" library item, N pivot rows).
 *
 * Type / phase mapping:
 *   document → kind=doc        |  task → kind=verify
 *   eligibility → category=eligibility
 *   project     → category=service_delivery_compliance
 *
 * Idempotent: re-running detects already-migrated rows via the pivot
 * uniqueness and skips them. Legacy fund_code_requirements rows are
 * NOT deleted — they stay in place as historical reference. The
 * instantiator gets a code change in the same PR that stops reading
 * from the legacy table, so dupes don't get stamped.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $createdByCache = []; // slug → definition_id (in-memory dedupe within this run)

        $legacy = FundCodeRequirement::query()
            ->orderBy('fund_code_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($legacy as $row) {
            $key = $this->slugify($row->label, $row->id);

            // Find existing definition (live or trashed — re-use either).
            $definitionId = $createdByCache[$key]
                ?? RequirementDefinition::withTrashed()->where('key', $key)->value('id');

            if ($definitionId === null) {
                $def = RequirementDefinition::create([
                    'key' => $key,
                    'label' => $row->label,
                    'kind' => $this->mapType($row->type),
                    'category' => $this->mapPhase($row->phase),
                    'default_required' => (bool) ($row->is_hard_requirement ?? false),
                    'hint' => $row->description !== '' ? $row->description : null,
                    'created_by_user_id' => null,
                    'updated_by_user_id' => null,
                ]);
                $definitionId = $def->id;
                $createdByCache[$key] = $definitionId;
            }

            // Idempotent pivot insert. Unique PK on (fund_code_id, requirement_definition_id)
            // means a second run no-ops cleanly.
            DB::table('fund_code_requirement_definitions')
                ->updateOrInsert(
                    [
                        'fund_code_id' => $row->fund_code_id,
                        'requirement_definition_id' => $definitionId,
                    ],
                    [
                        'created_by_user_id' => null,
                        'created_at' => $now,
                    ]
                );
        }
    }

    public function down(): void
    {
        // Intentional no-op. This is a forward-only data migration —
        // legacy fund_code_requirements rows are still present, so
        // undoing would just remove the new definitions + pivots
        // without restoring any data. If rollback is genuinely needed,
        // craft it manually after auditing pivot rows.
    }

    private function slugify(string $label, int $fallbackId): string
    {
        $slug = Str::slug($label, '-');

        return $slug !== '' ? $slug : 'legacy-req-'.$fallbackId;
    }

    private function mapType(?string $type): string
    {
        return match ($type) {
            'task' => RequirementDefinition::KIND_VERIFY,
            default => RequirementDefinition::KIND_DOC,
        };
    }

    private function mapPhase(?string $phase): string
    {
        return match ($phase) {
            'project' => RequirementDefinition::CATEGORY_SERVICE_DELIVERY,
            default => RequirementDefinition::CATEGORY_ELIGIBILITY,
        };
    }
};
