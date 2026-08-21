<?php

namespace App\Services\Homes;

use App\Livewire\Homes\Projects;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\ProjectBudget;
use App\Models\RequirementDefinition;
use Illuminate\Support\Collection;

/**
 * Read-only aggregations behind the housing portal's four canned reports.
 *
 * IMPORTANT: the housing budget module is a standalone tracker by design.
 * "Fund spending" is computed ONLY from housing-local data (ProjectBudget
 * grouped by fund_code_id, joined to FundCode for labels/expiry) — never
 * from Sage/Intacct/MIP/DonorPerfect or Contract drawdowns.
 */
class HousingPortalReports
{
    /** Rolling window used by the time-based reports. */
    public const MONTHS = 6;

    /**
     * Monthly activity: calls / new intakes / conversions per month,
     * oldest month first.
     */
    public function monthlyActivity(): array
    {
        $months = collect(range(self::MONTHS - 1, 0))
            ->map(fn ($back) => now()->startOfMonth()->subMonths($back));

        $start = $months->first();

        $calls = CommunicationLog::where('communication_date', '>=', $start)
            ->get(['communication_date'])
            ->groupBy(fn ($log) => $log->communication_date->format('Y-m'));

        $intakes = CommunicationLog::where('category', 'intake')
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn ($log) => $log->created_at->format('Y-m'));

        $conversions = CommunicationLog::whereNotNull('converted_at')
            ->where('converted_at', '>=', $start)
            ->get(['converted_at'])
            ->groupBy(fn ($log) => $log->converted_at->format('Y-m'));

        $rows = $months->map(fn ($month) => [
            'month' => $month->isoFormat('MMM'),
            'calls' => $calls->get($month->format('Y-m'), collect())->count(),
            'intakes' => $intakes->get($month->format('Y-m'), collect())->count(),
            'converted' => $conversions->get($month->format('Y-m'), collect())->count(),
        ])->values();

        $totalCalls = $rows->sum('calls');
        $totalIntakes = $rows->sum('intakes');
        $totalConverted = $rows->sum('converted');

        return [
            'rows' => $rows,
            'window' => $months->first()->isoFormat('MMM').' – '.now()->isoFormat('MMM YYYY'),
            'tiles' => [
                'calls' => $totalCalls,
                'intakes' => $totalIntakes,
                'intake_rate' => $totalCalls > 0 ? round($totalIntakes / $totalCalls * 100).'% of calls' : null,
                'converted' => $totalConverted,
            ],
        ];
    }

    /**
     * Fund spending from the housing PM's OWN budget data: per fund code,
     * allocated = SUM(budgeted_amount), spent = SUM(spent_amount).
     */
    public function fundSpending(): array
    {
        $sums = ProjectBudget::query()
            ->whereNotNull('fund_code_id')
            ->selectRaw('fund_code_id, SUM(budgeted_amount) as allocated, SUM(spent_amount) as spent')
            ->groupBy('fund_code_id')
            ->get()
            ->keyBy('fund_code_id');

        $rows = FundCode::whereIn('id', $sums->keys())->get()
            ->map(function (FundCode $fund) use ($sums) {
                $s = $sums[$fund->id];

                return [
                    'code' => $fund->code,
                    'name' => (string) ($fund->description ?? ''),
                    'allocated' => (float) $s->allocated,
                    'spent' => (float) $s->spent,
                    'balance' => (float) $s->allocated - (float) $s->spent,
                    'expiry_state' => $fund->expiry_state,
                    'days_to_expiry' => $fund->days_to_expiry,
                    'expiry_note' => match ($fund->expiry_state) {
                        'perpetual' => 'No expiry',
                        'expired' => 'Expired',
                        'soon', 'warning' => "Expires in {$fund->days_to_expiry}d",
                        default => $fund->expires_at ? 'Expires '.$fund->expires_at->isoFormat('MMM YYYY') : 'No expiry',
                    },
                ];
            })
            ->sortBy('code')
            ->values();

        $atRisk = $rows->firstWhere('expiry_state', 'soon');

        return [
            'rows' => $rows,
            'tiles' => [
                'spent' => $rows->sum('spent'),
                'remaining' => $rows->sum('balance'),
                'expiring_soon' => $rows->where('expiry_state', 'soon')->count(),
                'at_risk_note' => $atRisk ? "{$atRisk['code']} · \$".number_format($atRisk['balance']).' at risk' : null,
            ],
        ];
    }

    /**
     * Intake funnel over the same rolling window: every stage is a count of
     * logs created in the window (converted uses converted_at).
     */
    public function intakeFunnel(): array
    {
        $start = now()->startOfMonth()->subMonths(self::MONTHS - 1);

        $logs = CommunicationLog::where('created_at', '>=', $start)
            ->with(['intakeRequirements' => fn ($q) => $q->where('is_orphaned', false), 'intakeRequirements.definition'])
            ->get();

        $phase1 = function (CommunicationLog $log): array {
            $reqs = $log->intakeRequirements->filter(fn ($r) => in_array(
                $r->definition?->category ?? RequirementDefinition::CATEGORY_ELIGIBILITY,
                RequirementDefinition::PHASE_1_CATEGORIES,
                true
            ));
            $total = $reqs->count();
            $met = $reqs->filter(fn ($r) => in_array($r->status, IntakeRequirement::MET_STATUSES, true))->count();

            return [$met, $total];
        };

        $becameIntakes = $logs->filter(fn ($l) => in_array($l->category, ['intake', 'scheduling'], true)
            || $l->intakeRequirements->isNotEmpty()
            || $l->converted_to_job_id !== null);

        $collecting = $logs->filter(function (CommunicationLog $l) use ($phase1) {
            if ($l->converted_to_job_id !== null || $l->intakeRequirements->isEmpty()) {
                return false;
            }
            [$met, $total] = $phase1($l);

            return $total > 0 && $met < $total;
        });

        $allIn = $logs->filter(function (CommunicationLog $l) use ($phase1) {
            if ($l->converted_to_job_id !== null || $l->intakeRequirements->isEmpty()) {
                return false;
            }
            [$met, $total] = $phase1($l);

            return $total > 0 && $met === $total;
        });

        $converted = $logs->filter(fn ($l) => $l->converted_to_job_id !== null);

        return [
            'window' => $start->isoFormat('MMM').' – '.now()->isoFormat('MMM YYYY'),
            'rows' => collect([
                ['stage' => 'Calls logged', 'n' => $logs->count(), 'note' => 'everything, including wrong numbers'],
                ['stage' => 'Became intakes', 'n' => $becameIntakes->count(), 'note' => 'asked for help we provide'],
                ['stage' => 'Collecting documents', 'n' => $collecting->count(), 'note' => 'application out the door'],
                ['stage' => 'All documents in', 'n' => $allIn->count(), 'note' => 'ready for a project decision'],
                ['stage' => 'Converted to projects', 'n' => $converted->count(), 'note' => 'work scoped and funded'],
            ]),
        ];
    }

    /** One line per open (non-finished) active job. */
    public function projectStatus(): Collection
    {
        return HousingJob::active()
            ->whereNotIn('status', Projects::FINISHED_STATUSES)
            ->with(['participant', 'property', 'program', 'milestones', 'budgets'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (HousingJob $job) => [
                'who' => $job->participant?->full_name ?: $job->job_title,
                'where' => $job->property ? trim($job->property->address_line_1.', '.$job->property->city, ', ') : '—',
                'program' => trim(($job->program?->name ? $job->program->name.' · ' : '').$job->job_title, ' ·'),
                'milestones' => $job->milestones->where('status', 'completed')->count().' of '.$job->milestones->count(),
                'budget' => (float) $job->getTotalBudget(),
                'spent' => (float) $job->getTotalSpent(),
                'status' => Projects::plainStatus($job),
            ])
            ->values();
    }

    /** CSV rows (header + data) for a report id — consumed by the export stream. */
    public function csvRows(string $report): array
    {
        return match ($report) {
            'activity' => (function () {
                $data = $this->monthlyActivity();

                return array_merge(
                    [['Month', 'Calls logged', 'New intakes', 'Converted to projects']],
                    $data['rows']->map(fn ($r) => [$r['month'], $r['calls'], $r['intakes'], $r['converted']])->all(),
                );
            })(),
            'funds' => array_merge(
                [['Fund', 'Description', 'Allocated', 'Spent', 'Balance', 'Expiry']],
                $this->fundSpending()['rows']->map(fn ($r) => [
                    $r['code'], $r['name'], $r['allocated'], $r['spent'], $r['balance'], $r['expiry_note'],
                ])->all(),
            ),
            'funnel' => array_merge(
                [['Stage', 'Count', 'Note']],
                $this->intakeFunnel()['rows']->map(fn ($r) => [$r['stage'], $r['n'], $r['note']])->all(),
            ),
            'status' => array_merge(
                [['Homeowner', 'Address', 'Program', 'Milestones', 'Budget', 'Spent', 'Status']],
                $this->projectStatus()->map(fn ($r) => [
                    $r['who'], $r['where'], $r['program'], $r['milestones'], $r['budget'], $r['spent'], $r['status'],
                ])->all(),
            ),
            default => [],
        };
    }
}
