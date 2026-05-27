<?php

use App\Models\Requirement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Comm Log v2 follow-up to migration 2026_05_25_140001.
 *
 * That migration copied every legacy fund_code_requirements row into a
 * requirement_definitions row (deduped by slugified label) and linked it
 * to its fund code via the fund_code_requirement_definitions pivot, but
 * it left RequirementInstantiator still reading the legacy table. This
 * PR repoints the project-phase instantiator at requirement_definitions,
 * where source_template_id now means requirement_definitions.id.
 *
 * Existing project Requirement rows (source_type=fund_code) still store
 * the legacy fund_code_requirements.id in source_template_id. Remap each
 * to the corresponding requirement_definitions.id by matching on the
 * same slugified label the prior migration used. We must NOT assume the
 * two id spaces coincide — they happen to on some dev databases, but on
 * production they will not.
 *
 * Forward-only. down() is a no-op: the legacy fund_code_requirements rows
 * are still present, but rebuilding the exact old pointers after the fact
 * is not worth the risk. Re-running up() is safe in practice because rows
 * whose source_template_id no longer matches a fund_code_requirements.id
 * are skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        // slug(label) → requirement_definitions.id
        $defBySlug = [];
        foreach (DB::table('requirement_definitions')->get(['id', 'key']) as $def) {
            $defBySlug[$def->key] = $def->id;
        }

        // legacy fund_code_requirements.id → slug(label)
        $legacy = DB::table('fund_code_requirements')->get(['id', 'label']);
        $slugByLegacyId = [];
        foreach ($legacy as $row) {
            $slugByLegacyId[$row->id] = $this->slugify($row->label, $row->id);
        }

        $rows = Requirement::query()
            ->where('source_type', Requirement::SOURCE_FUND_CODE)
            ->whereNotNull('source_template_id')
            ->get(['id', 'source_template_id']);

        foreach ($rows as $req) {
            // Only touch rows that still point at a legacy template id.
            $slug = $slugByLegacyId[$req->source_template_id] ?? null;
            if ($slug === null) {
                continue;
            }

            $defId = $defBySlug[$slug] ?? null;
            if ($defId === null || $defId === $req->source_template_id) {
                continue;
            }

            DB::table('requirements')
                ->where('id', $req->id)
                ->update(['source_template_id' => $defId]);
        }
    }

    public function down(): void
    {
        // Intentional no-op — forward-only data remap.
    }

    private function slugify(string $label, int $fallbackId): string
    {
        $slug = Str::slug($label, '-');

        return $slug !== '' ? $slug : 'legacy-req-'.$fallbackId;
    }
};
