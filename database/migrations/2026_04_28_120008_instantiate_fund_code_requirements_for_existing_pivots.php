<?php

use App\Support\Phase3ABackfillLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * P-18 (Phase 3A): for every existing fund_code_housing_job pivot
     * row, instantiate one requirements row per fund_code_requirement
     * template attached to that fund code. Source = fund_code, owner =
     * job.project_manager_id (now reliably populated by the previous
     * migration in this batch).
     *
     * Templates copy on instantiate — we capture source_label_snapshot
     * here so a future admin edit to fund_code_requirements.label does
     * NOT propagate to live project rows.
     */
    public function up(): void
    {
        $pivots = DB::table('fund_code_housing_job as p')
            ->join('housing_jobs as hj', 'hj.id', '=', 'p.housing_job_id')
            ->select('p.fund_code_id', 'p.housing_job_id', 'hj.project_manager_id')
            ->orderBy('p.fund_code_id')
            ->orderBy('p.housing_job_id')
            ->get();

        $insertedIds = [];
        $now = now();

        foreach ($pivots as $pivot) {
            $templates = DB::table('fund_code_requirements')
                ->where('fund_code_id', $pivot->fund_code_id)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            foreach ($templates as $tpl) {
                $id = DB::table('requirements')->insertGetId([
                    'housing_job_id' => $pivot->housing_job_id,
                    'type' => $tpl->type ?? 'task',
                    'title' => $tpl->label,
                    'description' => $tpl->description,
                    'status' => 'outstanding',
                    'owner_user_id' => $pivot->project_manager_id,
                    'source_type' => 'fund_code',
                    'source_id' => $pivot->fund_code_id,
                    'source_template_id' => $tpl->id,
                    'source_label_snapshot' => $tpl->label,
                    'milestone_gate_text' => null,
                    'completed_at' => null,
                    'completed_by_user_id' => null,
                    'completion_note' => null,
                    'override_reason' => null,
                    'overridden_by_user_id' => null,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $insertedIds[] = $id;
            }
        }

        (new Phase3ABackfillLog)->write('fund_code_requirements_instantiation', [
            'requirement_ids' => $insertedIds,
            'count' => count($insertedIds),
        ]);
    }

    public function down(): void
    {
        $section = (new Phase3ABackfillLog)->section('fund_code_requirements_instantiation');

        if ($section === null) {
            throw new \RuntimeException(
                'Phase 3A backfill log is missing fund_code_requirements_instantiation section. '.
                'Restore database/seeders/phase_3a_backfill_ids.json from git before rollback.'
            );
        }

        $ids = $section['requirement_ids'] ?? [];

        if (! empty($ids)) {
            DB::table('requirements')->whereIn('id', $ids)->delete();
        }
    }
};
