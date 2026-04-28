<?php

use App\Support\Phase3ABackfillLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * P-18 (Phase 3A): treat created_by as the canonical project owner
     * for any housing_jobs row that hasn't had project_manager_id set
     * explicitly yet. Going forward, CommunicationsLogManagement::
     * convertToProject() defaults project_manager_id to Auth::id() at
     * conversion time so the column reliably reflects ownership.
     *
     * Reverse: re-NULL the project_manager_id for the exact set of jobs
     * we updated, using the IDs persisted to the backfill log.
     */
    public function up(): void
    {
        $rows = DB::table('housing_jobs')
            ->whereNull('project_manager_id')
            ->whereNotNull('created_by')
            ->select('id', 'created_by')
            ->get();

        $touched = [];

        foreach ($rows as $row) {
            DB::table('housing_jobs')
                ->where('id', $row->id)
                ->update(['project_manager_id' => $row->created_by]);

            $touched[] = $row->id;
        }

        (new Phase3ABackfillLog)->write('housing_jobs_project_manager', [
            'housing_job_ids' => $touched,
            'count' => count($touched),
        ]);
    }

    public function down(): void
    {
        $section = (new Phase3ABackfillLog)->section('housing_jobs_project_manager');

        if ($section === null) {
            throw new \RuntimeException(
                'Phase 3A backfill log is missing housing_jobs_project_manager section. '.
                'Restore database/seeders/phase_3a_backfill_ids.json from git before rollback.'
            );
        }

        $ids = $section['housing_job_ids'] ?? [];

        if (! empty($ids)) {
            DB::table('housing_jobs')
                ->whereIn('id', $ids)
                ->update(['project_manager_id' => null]);
        }
    }
};
