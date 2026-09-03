<?php

use App\Models\IntakeRequirement;
use App\Models\RequirementDefinition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Stamp the standing income-eligibility requirement onto intakes that
 * already graduated before the feature existed.
 *
 * Scope is deliberately narrow:
 *   - only logs that have a service or housing service attached (that
 *     is what CommunicationLog::computeAutoCategory() treats as
 *     graduating from "just a call" to an intake);
 *   - only logs that have NOT been converted to a project — a converted
 *     intake is a closed historical record and gets no new open item;
 *   - only logs that don't already have the row.
 *
 * Rows are created pending with no actor (this is a system backfill, so
 * created_by_user_id stays null rather than crediting whoever runs the
 * migration).
 *
 * `category` on the affected logs is intentionally NOT recomputed here.
 * Adding an unmet requirement can move a log from 'scheduling' back to
 * 'intake', and that transition belongs to the app's own
 * applyAutoCategory() call on the next interaction — the homes portal
 * already derives its stage live, so nothing displays stale there.
 */
return new class extends Migration
{
    public function up(): void
    {
        $atom = RequirementDefinition::withTrashed()->firstOrCreate(
            ['key' => IntakeRequirement::KEY_INCOME_ELIGIBILITY],
            [
                'label' => 'Income Eligibility',
                'kind' => RequirementDefinition::KIND_DOC,
                'category' => RequirementDefinition::CATEGORY_ELIGIBILITY,
                'default_required' => true,
                'hint' => 'Annual household income, household size, and proof: pay stubs, tax return, benefit letters.',
            ]
        );

        $now = now();

        DB::table('communication_logs')
            ->whereNull('converted_to_job_id')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereExists(fn ($sub) => $sub->select(DB::raw(1))
                    ->from('communication_log_service')
                    ->whereColumn('communication_log_service.communication_log_id', 'communication_logs.id')
                )->orWhereExists(fn ($sub) => $sub->select(DB::raw(1))
                    ->from('communication_log_housing_services')
                    ->whereColumn('communication_log_housing_services.communication_log_id', 'communication_logs.id')
                );
            })
            ->whereNotExists(fn ($sub) => $sub->select(DB::raw(1))
                ->from('intake_requirements')
                ->whereColumn('intake_requirements.communication_log_id', 'communication_logs.id')
                ->where('intake_requirements.key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)
            )
            ->orderBy('id')
            ->select('id')
            ->chunk(200, function ($logs) use ($atom, $now) {
                $rows = [];

                foreach ($logs as $log) {
                    $rows[] = [
                        'communication_log_id' => $log->id,
                        'type' => IntakeRequirement::TYPE_DOCUMENT,
                        'label' => $atom->label,
                        'key' => $atom->key,
                        'kind' => $atom->kind,
                        'via' => json_encode(['standard']),
                        'status' => IntakeRequirement::STATUS_PENDING,
                        'source_type' => IntakeRequirement::SOURCE_DEFINITION,
                        'source_definition_id' => $atom->id,
                        'source_label_snapshot' => $atom->label,
                        'is_orphaned' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (! empty($rows)) {
                    DB::table('intake_requirements')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        // Only the untouched backfilled rows go away; anything a worker
        // has since acted on keeps its history.
        DB::table('intake_requirements')
            ->where('key', IntakeRequirement::KEY_INCOME_ELIGIBILITY)
            ->whereNull('created_by_user_id')
            ->where('status', IntakeRequirement::STATUS_PENDING)
            ->delete();
    }
};
