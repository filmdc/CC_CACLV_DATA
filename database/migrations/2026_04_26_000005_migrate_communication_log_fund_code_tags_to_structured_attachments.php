<?php

use App\Models\CommunicationLog;
use App\Models\FundCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * P-27: migrate ad-hoc fund-code tags on CommunicationLog into the
 * structured communication_log_fund_code pivot.
 *
 * For each Tag whose name matches an existing FundCode.code (case-
 * insensitive), attach the FundCode to every CommunicationLog that
 * carries that tag via the existing pivot — only if not already
 * attached. Leaves the original tag rows in place; coexistence holds
 * until the housing manager signs off (cleanup tracked as P-27b).
 *
 * Idempotent: re-running up() against an already-migrated dataset
 * writes zero new pivot rows because every potential attachment is
 * already present. The "skipped (already attached)" counter goes up;
 * "new attachments" stays at zero.
 *
 * Reversible: down() detaches only the (log, code) pairs whose tag
 * name still matches a fund code (case-insensitive). It does NOT
 * touch tags themselves and does NOT remove pivot rows that pre-dated
 * this migration.
 *
 * Discover-phase prevalence on the dev DB: 2 distinct tag names
 * (F0531, F4205) attached to 4 distinct comm logs total — both names
 * already match real fund codes; 0 unmatched, 0 suspicious. The
 * migration is therefore essentially defensive scaffolding that
 * sweeps a small tail into the structured form and stays in place
 * against future ad-hoc tag re-introduction.
 */
return new class extends Migration
{
    public function up(): void
    {
        $codeIdByLower = FundCode::pluck('id', 'code')
            ->mapWithKeys(fn ($id, $code) => [strtolower($code) => $id]);

        $matched = 0;
        $skipped = 0;
        $unmatched = [];
        $suspicious = [];

        $rows = DB::table('taggables')
            ->where('taggable_type', CommunicationLog::class)
            ->join('tags', 'tags.id', '=', 'taggables.tag_id')
            ->select('taggables.taggable_id as log_id', 'tags.name as tag_name')
            ->get();

        $now = now();

        foreach ($rows as $row) {
            $lower = strtolower(trim($row->tag_name));
            $codeId = $codeIdByLower[$lower] ?? null;

            if ($codeId === null) {
                // No matching FundCode. Flag as suspicious if the tag
                // looks like a fund code by pattern; otherwise just
                // unmatched.
                if (preg_match('/^[A-Za-z]{1,3}[0-9]{2,5}$/', $row->tag_name)) {
                    $suspicious[] = $row->tag_name;
                } else {
                    $unmatched[] = $row->tag_name;
                }

                continue;
            }

            $exists = DB::table('communication_log_fund_code')
                ->where('communication_log_id', $row->log_id)
                ->where('fund_code_id', $codeId)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            DB::table('communication_log_fund_code')->insert([
                'communication_log_id' => $row->log_id,
                'fund_code_id' => $codeId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $matched++;
        }

        fwrite(STDERR, "\n[P-27] tag rows scanned:               ".count($rows)."\n");
        fwrite(STDERR, "[P-27] new attachments written:         {$matched}\n");
        fwrite(STDERR, "[P-27] already-attached skips:          {$skipped}\n");
        fwrite(STDERR, '[P-27] unmatched tags:                  '.count(array_unique($unmatched))."\n");
        fwrite(STDERR, '[P-27] suspicious (fund-code-shaped):   '.count(array_unique($suspicious))."\n");

        if (! empty($suspicious)) {
            fwrite(STDERR, '[P-27]   suspicious tag names: '.json_encode(array_values(array_unique($suspicious)))."\n");
        }
    }

    public function down(): void
    {
        $codeIdByLower = FundCode::pluck('id', 'code')
            ->mapWithKeys(fn ($id, $code) => [strtolower($code) => $id]);

        $rows = DB::table('taggables')
            ->where('taggable_type', CommunicationLog::class)
            ->join('tags', 'tags.id', '=', 'taggables.tag_id')
            ->select('taggables.taggable_id as log_id', 'tags.name as tag_name')
            ->get();

        foreach ($rows as $row) {
            $lower = strtolower(trim($row->tag_name));
            $codeId = $codeIdByLower[$lower] ?? null;

            if ($codeId === null) {
                continue;
            }

            DB::table('communication_log_fund_code')
                ->where('communication_log_id', $row->log_id)
                ->where('fund_code_id', $codeId)
                ->delete();
        }
    }
};
