<?php

namespace App\Services\Homes;

use App\Livewire\Homes\Projects;
use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HomesPin;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\RequirementDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only data helpers for the CACLV Housing portal (homes.caclv.org).
 *
 * These are intentionally thin wrappers over the SAME models, scopes, and
 * constants the main app uses (CommunicationLog follow-up scopes,
 * IntakeRequirement::MET_STATUSES, RequirementDefinition::PHASE_1_CATEGORIES,
 * FundCode expiry_state, HousingJob::active). The redesigned Today and
 * Calls & Intakes screens both read through here so the "stage" and
 * "N of M docs" logic lives in one testable place, mirroring
 * HousingDashboard::intakePipeline() / intakeRequirements().
 */
class HousingPortalData
{
    /** Phase-1 (eligibility/compliance) requirement progress for a log. */
    public function phase1Progress(CommunicationLog $log): array
    {
        $reqs = $log->intakeRequirements
            ->where('is_orphaned', false)
            ->filter(fn (IntakeRequirement $r) => in_array(
                $r->definition?->category ?? RequirementDefinition::CATEGORY_ELIGIBILITY,
                RequirementDefinition::PHASE_1_CATEGORIES,
                true
            ));

        $total = $reqs->count();
        $met = $reqs->filter(fn (IntakeRequirement $r) => in_array($r->status, IntakeRequirement::MET_STATUSES, true))->count();

        return [
            'met' => $met,
            'total' => $total,
            'pct' => $total > 0 ? (int) round(($met / $total) * 100) : null,
        ];
    }

    /** Plain-language stage for a log: ['key' => ..., 'label' => ...]. */
    public function stage(CommunicationLog $log): array
    {
        if ($log->converted_to_job_id !== null) {
            return ['key' => 'converted', 'label' => 'Converted'];
        }

        $p = $this->phase1Progress($log);

        if ($p['total'] === 0) {
            return ['key' => 'call', 'label' => 'Just a call'];
        }
        if ($p['met'] === $p['total']) {
            return ['key' => 'ready', 'label' => 'Ready for project'];
        }

        return ['key' => 'collecting', 'label' => 'Collecting docs'];
    }

    /** Best available display name for a log's contact. */
    public function personName(CommunicationLog $log): string
    {
        $name = $log->participant?->full_name
            ?: ($log->contact_full_name ?: '');

        if ($name !== '') {
            return trim($name);
        }

        return $log->subject ?: 'Comm Log #'.$log->id;
    }

    /** Best available address/location line for a log. */
    public function personWhere(CommunicationLog $log): string
    {
        $addr = $log->contact_full_address ?: '';
        if ($addr !== '') {
            return $addr;
        }

        return (string) ($log->property?->address_line_1 ?? '');
    }

    /**
     * Follow-ups that need attention: pending (incl. overdue), soonest first.
     * Each row carries a tone (red = overdue, amber = due today) and a
     * human due label.
     */
    public function followUpsDue(int $limit = 12): Collection
    {
        return CommunicationLog::pendingFollowUp()
            ->with(['participant', 'property'])
            ->orderBy('follow_up_date')
            ->limit($limit)
            ->get()
            ->map(function (CommunicationLog $log) {
                $due = $log->follow_up_date;
                $tone = 'default';
                $dueLabel = $due?->isoFormat('MMM D') ?? '';

                if ($due) {
                    if ($due->lt(now()->startOfDay())) {
                        $tone = 'red';
                        $dueLabel = 'Overdue · '.((int) $due->diffInDays(now())).'d';
                    } elseif ($due->isToday()) {
                        $tone = 'amber';
                        $dueLabel = 'Due today';
                    } else {
                        $dueLabel = 'Due '.$due->isoFormat('ddd, MMM D');
                    }
                }

                return [
                    'id' => $log->id,
                    'who' => $this->personName($log),
                    'where' => $this->personWhere($log),
                    'what' => $log->follow_up_action ?: ($log->summary ?: ''),
                    'due_label' => $dueLabel,
                    'tone' => $tone,
                ];
            });
    }

    /** Count of pending follow-ups (matches the main app's header stat). */
    public function followUpsDueCount(): int
    {
        return CommunicationLog::pendingFollowUp()->count();
    }

    /**
     * Intake cases still waiting on documents: non-converted logs that have
     * at least one open (unmet) document-type intake requirement. Grouped
     * per case with the missing labels and a met/total summary.
     */
    public function waitingOnDocuments(int $limit = 12): Collection
    {
        $openDocs = IntakeRequirement::query()
            ->where('is_orphaned', false)
            ->where('type', IntakeRequirement::TYPE_DOCUMENT)
            ->whereNotIn('status', IntakeRequirement::MET_STATUSES)
            ->whereHas('communicationLog', fn ($q) => $q->whereNull('housing_job_id')->whereNull('converted_to_job_id'))
            ->with(['communicationLog.participant', 'communicationLog.property', 'communicationLog.intakeRequirements.definition', 'definition'])
            ->get();

        return $openDocs
            ->groupBy('communication_log_id')
            ->map(function (Collection $reqs) {
                $log = $reqs->first()->communicationLog;
                if ($log === null) {
                    return null;
                }

                $progress = $this->phase1Progress($log);
                $missing = $reqs
                    ->map(fn (IntakeRequirement $r) => (string) ($r->label ?? $r->source_label_snapshot ?? 'Document'))
                    ->take(4)
                    ->implode(' · ');

                return [
                    'id' => $log->id,
                    'who' => $this->personName($log),
                    'where' => $this->personWhere($log),
                    'missing' => $missing,
                    'met' => $progress['met'],
                    'total' => $progress['total'],
                ];
            })
            ->filter()
            ->take($limit)
            ->values();
    }

    /**
     * Non-converted intake cases whose Phase-1 requirements are all met —
     * ready to be promoted to a project. Read-only in Phase 1 (the convert
     * action still lives in the main app's CommunicationsLogManagement).
     */
    public function readyToConvert(int $limit = 8): Collection
    {
        return CommunicationLog::query()
            ->whereNull('housing_job_id')
            ->whereNull('converted_to_job_id')
            ->whereHas('intakeRequirements', fn ($q) => $q->where('is_orphaned', false))
            ->with(['participant', 'property', 'intakeRequirements.definition'])
            ->limit(120)
            ->get()
            ->map(function (CommunicationLog $log) {
                $p = $this->phase1Progress($log);

                return [
                    'id' => $log->id,
                    'who' => $this->personName($log),
                    'where' => $this->personWhere($log),
                    'summary' => (string) ($log->summary ?? ''),
                    'pct' => $p['pct'],
                    'total' => $p['total'],
                ];
            })
            ->filter(fn ($row) => $row['total'] > 0 && $row['pct'] === 100)
            ->take($limit)
            ->values();
    }

    /**
     * One call/intake shaped for a portal list row. Shared by the paginated
     * Calls & Intakes list and by the pinned sections on Calls and Today, so
     * a pinned row reads exactly like the row it was lifted out of.
     *
     * Expects participant, property and intakeRequirements.definition loaded.
     */
    public function callRow(CommunicationLog $log): array
    {
        $stage = $this->stage($log);
        $progress = $this->phase1Progress($log);

        $next = $log->follow_up_completed
            ? null
            : ($log->follow_up_action ?: null);
        if ($next === null) {
            $next = match ($stage['key']) {
                'ready' => 'Ready to convert',
                'collecting' => 'Collecting documents',
                'converted' => 'Converted to project',
                default => 'No action needed',
            };
        }

        return [
            'id' => $log->id,
            'who' => $this->personName($log),
            'where' => $this->personWhere($log),
            'note' => (string) ($log->summary ?? ''),
            'stage_key' => $stage['key'],
            'stage_label' => $stage['label'],
            'reqs' => $progress['total'] > 0 ? "{$progress['met']} of {$progress['total']} docs" : null,
            'next' => $next,
            'overdue' => $log->is_follow_up_overdue,
            'last' => $log->communication_date?->isoFormat('MMM D') ?? $log->updated_at?->isoFormat('MMM D'),
            'type' => ucfirst(str_replace('_', ' ', (string) $log->communication_type)),
            'direction' => ucfirst((string) $log->direction),
        ];
    }

    /**
     * One project shaped for a portal list row. Shared by the Projects list
     * and by the pinned sections on Projects and Today.
     *
     * Expects participant, property, program, milestones and budgets loaded.
     * Carries both the display strings and the raw dates behind them, so
     * Projects::sortRows() can order on the same values the row shows.
     */
    public function projectRow(HousingJob $job): array
    {
        $next = Projects::nextAction($job);

        return [
            'id' => $job->id,
            'who' => $job->participant?->full_name ?: $job->job_title,
            'where' => $job->property
                ? trim($job->property->address_line_1.', '.$job->property->city, ', ')
                : '—',
            'status' => Projects::plainStatus($job),
            'program' => $job->program?->name,
            'title' => $job->job_title,
            'ms_done' => $job->milestones->where('status', 'completed')->count(),
            'ms_total' => $job->milestones->count(),
            'budget' => (float) $job->getTotalBudget(),
            'spent' => (float) $job->getTotalSpent(),
            'next' => $next['text'],
            'next_due' => $next['due'],
            // Raw values behind the formatted ones, for list sorting.
            'next_due_at' => $next['due_at'],
            'created_at' => $job->created_at,
        ];
    }

    /**
     * A user's pinned calls, in the order they were pinned. Pins are never
     * filtered by the screen's pills — the point of a pin is that it stays
     * visible — and pins whose record has since been deleted just drop out.
     */
    public function pinnedCalls(int $userId): Collection
    {
        $ids = HomesPin::idsFor($userId, HomesPin::TYPE_CALL);
        if ($ids === []) {
            return collect();
        }

        $logs = CommunicationLog::query()
            ->whereIn('id', $ids)
            ->with(['participant', 'property', 'intakeRequirements.definition'])
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $logs->get($id))
            ->filter()
            ->map(fn (CommunicationLog $log) => $this->callRow($log))
            ->values();
    }

    /** A user's pinned projects, in the order they were pinned. */
    public function pinnedProjects(int $userId): Collection
    {
        $ids = HomesPin::idsFor($userId, HomesPin::TYPE_PROJECT);
        if ($ids === []) {
            return collect();
        }

        $jobs = HousingJob::query()
            ->whereIn('id', $ids)
            ->with(['participant', 'property', 'program', 'milestones', 'budgets'])
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $jobs->get($id))
            ->filter()
            ->map(fn (HousingJob $job) => $this->projectRow($job))
            ->values();
    }

    /** Glanceable weekly counts for the Today sidebar card. */
    /**
     * Today card: the active project list regrouped under the funds paying
     * for it — "what is riding on F7000 right now?".
     *
     * Grouped off the fund↔project pivot, NOT off budget dollars. The
     * standalone budget module only knows what she has typed into it, and
     * a project can be assigned to a fund long before a single budget line
     * exists — so grouping on money would show almost nothing while
     * grouping on the assignment shows the real picture.
     *
     * A project on two funds appears under both. That is not double
     * counting: the question the card answers is per fund, and until the
     * funding is narrowed to one code the project genuinely rides on both
     * (see App\Services\ProjectFundingService).
     *
     * @return Collection<int, array<string,mixed>>
     */
    public function projectsByFund(int $projectsPerFund = 6): Collection
    {
        $jobs = HousingJob::active()
            ->with(['participant', 'property', 'program', 'milestones', 'budgets', 'fundCodes'])
            ->get();

        $groups = collect();

        foreach ($jobs as $job) {
            foreach ($job->fundCodes as $fund) {
                if (! $groups->has($fund->id)) {
                    $groups[$fund->id] = [
                        'id' => $fund->id,
                        'code' => $fund->code,
                        'name' => (string) ($fund->description ?? ''),
                        'expiry_state' => $fund->expiry_state,
                        'days_to_expiry' => $fund->days_to_expiry,
                        'jobs' => collect(),
                    ];
                }

                $group = $groups[$fund->id];
                $group['jobs'] = $group['jobs']->push($this->projectRow($job));
                $groups[$fund->id] = $group;
            }
        }

        return $groups
            ->map(function (array $group) use ($projectsPerFund) {
                /** @var Collection<int, array<string,mixed>> $jobs */
                $jobs = $group['jobs'];

                // The card reports ONE number per fund — how many projects are
                // on it. The status split lives on the Projects list, where
                // she can filter it; repeating it here just made the card
                // harder to read than the thing it summarises.
                //
                // Ordering inside a fund still puts anything needing a hand
                // at the top, so expanding a fund leads with the problems.
                $ordered = $jobs->where('status', 'attention')
                    ->concat($jobs->where('status', 'active')->sortByDesc('created_at'))
                    ->concat($jobs->where('status', 'finished')->sortByDesc('created_at'))
                    ->values();

                return [
                    'id' => $group['id'],
                    'code' => $group['code'],
                    'name' => $group['name'],
                    'expiry_state' => $group['expiry_state'],
                    'days_to_expiry' => $group['days_to_expiry'],
                    'total' => $jobs->count(),
                    'rows' => $ordered->take($projectsPerFund),
                    'more' => max(0, $ordered->count() - $projectsPerFund),
                ];
            })
            // Busiest fund first, then alphabetically — the same number the
            // row shows, so the order never looks arbitrary.
            ->sortBy([
                fn (array $a, array $b) => $b['total'] <=> $a['total'],
                fn (array $a, array $b) => strcmp($a['code'], $b['code']),
            ])
            ->values();
    }

    /**
     * Active fund codes carrying no project at all — said out loud under
     * the card so an empty fund reads as "nothing on it yet" rather than
     * looking like the card forgot about it.
     */
    public function fundsWithoutProjects(): Collection
    {
        $inUse = DB::table('fund_code_housing_job')
            ->join('housing_jobs', 'housing_jobs.id', '=', 'fund_code_housing_job.housing_job_id')
            ->where('housing_jobs.is_template', false)
            ->where('housing_jobs.is_archived', false)
            ->whereNull('housing_jobs.deleted_at')
            ->distinct()
            ->pluck('fund_code_housing_job.fund_code_id');

        return FundCode::active()
            ->whereNotIn('id', $inUse)
            ->orderBy('code')
            ->pluck('code');
    }

    public function weekGlance(): array
    {
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();

        $callsLogged = CommunicationLog::whereBetween('communication_date', [$start, $end])->count();

        $newIntakes = CommunicationLog::where('category', 'intake')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $projectsActive = HousingJob::active()
            ->whereIn('status', ['pending', 'in_progress', 'on_hold'])
            ->count();

        $fundExpiring = FundCode::active()->get()
            ->filter(fn (FundCode $f) => $f->expiry_state === 'soon')
            ->sortBy('days_to_expiry')
            ->first();

        return [
            'calls_logged' => $callsLogged,
            'new_intakes' => $newIntakes,
            'projects_active' => $projectsActive,
            'fund_expiring' => $fundExpiring
                ? ['code' => $fundExpiring->code, 'days' => $fundExpiring->days_to_expiry]
                : null,
        ];
    }
}
