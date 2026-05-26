<?php

namespace App\Livewire;

use App\Models\CommunicationLog;
use App\Models\HousingJob;
use App\Models\IntakeRequirement;
use App\Models\Requirement;
use App\Models\RequirementDefinition;
use App\Models\User;
use App\Services\DashboardIndicatorSupport;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * P-17 (Phase 3A): personal housing dashboard.
 *
 * Two cuts on the same component, toggled by $tab:
 *   - 'projects' (Cut A) — list of housing projects with rolled-up
 *     outstanding-count + indicators per row.
 *   - 'requirements' (Cut B) — flat list of requirement rows with
 *     per-row source / owner / indicator visualization.
 *
 * Filters are owner / status / type / source / project. Default state
 * shows the logged-in user's outstanding work; switching to "anyone"
 * surfaces the chase view (see what Tony / Peter owe).
 *
 * Indicator state (blocked-milestone-IDs, attached-fund-codes-by-id)
 * is precomputed once per render via DashboardIndicatorSupport so
 * row-level checks are O(1) regardless of result size. The query-
 * bound test in tests/Feature/Regression/Dashboard/ asserts this
 * holds end-to-end.
 */
class HousingDashboard extends Component
{
    use WithPermissionChecks;

    public string $tab = 'projects';

    // Cut B filters. owner_user_id semantics:
    //   null = "me" (default, the logged-in user)
    //   0    = "anyone" (chase view)
    //   N    = explicit user id
    public ?int $filter_owner_id = null;

    public string $filter_status = 'outstanding'; // outstanding|complete|not_applicable|all

    public string $filter_type = 'all'; // task|document|all

    public string $filter_source = 'all'; // milestone|fund_code|ad_hoc|all

    public ?int $filter_project_id = null;

    public function mount(): void
    {
        $this->authorizeAction('housing_dashboard.view');
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['projects', 'requirements'], true) ? $tab : 'projects';
    }

    /**
     * Phase 12.3: toggle the current user's pin on a comm log without
     * leaving the dashboard. Mirrors CommunicationsLogManagement::togglePin.
     */
    public function togglePin(int $logId): void
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        $log = CommunicationLog::findOrFail($logId);
        $existing = $log->pinnedByUsers()->where('users.id', $user->id)->exists();

        if ($existing) {
            $log->pinnedByUsers()->detach($user->id);
            session()->flash('success', 'Unpinned.');
        } else {
            $log->pinnedByUsers()->attach($user->id, ['pinned_at' => now()]);
            session()->flash('success', 'Pinned to dashboard.');
        }
    }

    public function resetFilters(): void
    {
        $this->filter_owner_id = null;
        $this->filter_status = 'outstanding';
        $this->filter_type = 'all';
        $this->filter_source = 'all';
        $this->filter_project_id = null;
    }

    /**
     * Housing jobs visible on the dashboard. Phase 3A: admins see
     * every job in housing-enabled programs; non-admins see jobs they
     * created, manage, or have any requirement assigned on.
     */
    protected function accessibleHousingJobIds(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        $base = HousingJob::query()
            ->where('is_template', false)
            ->whereHas('program', fn ($q) => $q->where('housing_module_enabled', true));

        if ($user->isAdmin()) {
            return $base->pluck('id')->all();
        }

        return $base->where(function ($q) use ($user) {
            $q->where('project_manager_id', $user->id)
                ->orWhere('created_by', $user->id)
                ->orWhereHas('requirements', fn ($r) => $r->where('owner_user_id', $user->id));
        })->pluck('id')->all();
    }

    /**
     * Resolve the effective owner predicate from $filter_owner_id.
     * Returns either a user id, the special 'anyone' marker, or null
     * (= no filter applied; equivalent to all owners).
     */
    protected function ownerFilterValue(): null|int|string
    {
        if ($this->filter_owner_id === null) {
            return Auth::id();
        }

        if ($this->filter_owner_id === 0) {
            return 'anyone';
        }

        return (int) $this->filter_owner_id;
    }

    /**
     * Phase 12.2: intake-pipeline card data. Returns up to $limit
     * communication logs ranked by Phase 1 (eligibility + compliance)
     * requirement completion so Kaitlyn can prioritize follow-ups on the
     * ones closest to convertible.
     *
     * Filters:
     *   - not yet converted (housing_job_id is null AND converted_to_job_id is null)
     *   - has at least one non-orphaned intake requirement
     *
     * Sort: highest met% first (tiebreaker: oldest last-activity, so
     * stalled-but-far-along cases bubble up over fresh ones).
     */
    public function intakePipeline(int $limit = 8): \Illuminate\Support\Collection
    {
        if (! Auth::user()?->hasPermission('communications_log.view') && ! Auth::user()?->isAdmin()) {
            return collect();
        }

        $phase1 = RequirementDefinition::PHASE_1_CATEGORIES;
        $defaultCategory = RequirementDefinition::CATEGORY_ELIGIBILITY;

        $logs = CommunicationLog::query()
            ->whereNull('housing_job_id')
            ->whereNull('converted_to_job_id')
            ->whereHas('intakeRequirements', fn ($q) => $q->where('is_orphaned', false))
            ->with(['participant', 'intakeRequirements' => fn ($q) => $q->where('is_orphaned', false)->with('definition'), 'updates'])
            ->limit(120)
            ->get();

        $rows = $logs->map(function (CommunicationLog $log) use ($phase1, $defaultCategory) {
            $phase1Reqs = $log->intakeRequirements
                ->filter(fn ($r) => in_array($r->definition?->category ?? $defaultCategory, $phase1, true));

            $total = $phase1Reqs->count();
            if ($total === 0) {
                return null;
            }

            $met = $phase1Reqs->filter(fn ($r) => in_array($r->status, IntakeRequirement::MET_STATUSES, true))->count();
            $pct = (int) round(($met / $total) * 100);

            $lastActivity = $log->updates->isNotEmpty()
                ? $log->updates->max('created_at')
                : $log->updated_at;

            $displayName = $log->participant?->full_name
                ?? trim(($log->contact_first_name ?? '').' '.($log->contact_last_name ?? ''))
                ?: ($log->subject ?: 'Comm Log #'.$log->id);

            return [
                'id' => $log->id,
                'name' => trim((string) $displayName),
                'met' => $met,
                'total' => $total,
                'pct' => $pct,
                'last_activity' => $lastActivity,
                'days_idle' => $lastActivity ? (int) $lastActivity->diffInDays(now()) : null,
                'priority' => $log->priority,
                'has_participant' => $log->participant !== null,
            ];
        })->filter()->values();

        // Highest % first; tie-break by oldest last-activity (most stale = most need follow-up).
        return $rows
            ->sortBy([['pct', 'desc'], ['days_idle', 'desc']])
            ->take($limit)
            ->values();
    }

    /**
     * Phase 12.3: comm logs this user has pinned. Compact-card row at
     * the top of the dashboard. Newest-pinned first; capped at 10.
     */
    public function pinnedLogs(int $limit = 10): \Illuminate\Support\Collection
    {
        $user = Auth::user();
        if ($user === null) {
            return collect();
        }

        return CommunicationLog::query()
            ->whereHas('pinnedByUsers', fn ($q) => $q->where('users.id', $user->id))
            ->with([
                'participant',
                'pinnedByUsers' => fn ($q) => $q->where('users.id', $user->id),
            ])
            ->get()
            ->map(function (CommunicationLog $log) use ($user) {
                $pinnedAt = $log->pinnedByUsers->firstWhere('id', $user->id)?->pivot?->pinned_at;

                $name = $log->participant?->full_name
                    ?? trim(($log->contact_first_name ?? '').' '.($log->contact_last_name ?? ''))
                    ?: ($log->subject ?: 'Comm Log #'.$log->id);

                return [
                    'id' => $log->id,
                    'name' => trim((string) $name),
                    'subject' => (string) ($log->subject ?? ''),
                    'priority' => $log->priority,
                    'converted' => $log->converted_to_job_id !== null,
                    'pinned_at' => $pinnedAt,
                ];
            })
            ->sortByDesc('pinned_at')
            ->take($limit)
            ->values();
    }

    /**
     * Phase 12.3: oldest non-converted intake comm logs (older first).
     * Surfaces cases that have been languishing so they don't get
     * forgotten under newer activity. Capped at 8 — same count as
     * the intake-pipeline list for visual parity.
     *
     * Phase 12.4 refinement: filtered strictly to category='intake'.
     * 'general' entries are just contact notes with no service attached
     * yet — not a follow-up obligation. 'scheduling' entries are Phase 1
     * done and waiting for service delivery — they're tracked on the
     * Closest-to-convert card.
     */
    public function oldestLogs(int $limit = 8): \Illuminate\Support\Collection
    {
        if (! Auth::user()?->hasPermission('communications_log.view') && ! Auth::user()?->isAdmin()) {
            return collect();
        }

        return CommunicationLog::query()
            ->whereNull('housing_job_id')
            ->whereNull('converted_to_job_id')
            ->where('category', 'intake')
            ->orderBy('created_at', 'asc')
            ->with('participant')
            ->limit($limit)
            ->get()
            ->map(function (CommunicationLog $log) {
                $name = $log->participant?->full_name
                    ?? trim(($log->contact_first_name ?? '').' '.($log->contact_last_name ?? ''))
                    ?: ($log->subject ?: 'Comm Log #'.$log->id);

                return [
                    'id' => $log->id,
                    'name' => trim((string) $name),
                    'subject' => (string) ($log->subject ?? ''),
                    'priority' => $log->priority,
                    'created_at' => $log->created_at,
                    'days_old' => $log->created_at ? (int) $log->created_at->diffInDays(now()) : null,
                ];
            });
    }

    public function render()
    {
        $accessibleJobIds = $this->accessibleHousingJobIds();
        $intakePipeline = $this->intakePipeline();
        $pinnedLogs = $this->pinnedLogs();
        $oldestLogs = $this->oldestLogs();

        $support = app(DashboardIndicatorSupport::class);
        $blockedMilestoneIds = $support->blockedMilestoneIds($accessibleJobIds);
        $fundCodesById = $support->fundCodesAttachedToProjects($accessibleJobIds);
        $hardTemplateIds = $support->hardRequirementTemplateIds($accessibleJobIds);

        // Scope the owner-filter dropdown to users actually present on
        // the visible projects: project managers + requirement owners.
        // This shrinks the option list to people Kaitlyn cares about
        // chasing, and avoids surfacing every system user including
        // unrelated dev accounts.
        $relevantUserIds = collect()
            ->merge(
                HousingJob::query()
                    ->whereIn('id', $accessibleJobIds)
                    ->whereNotNull('project_manager_id')
                    ->pluck('project_manager_id')
            )
            ->merge(
                Requirement::query()
                    ->whereIn('housing_job_id', $accessibleJobIds)
                    ->whereNotNull('owner_user_id')
                    ->pluck('owner_user_id')
            )
            ->unique()
            ->values()
            ->all();

        $userOptions = User::query()
            ->whereIn('id', $relevantUserIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($this->tab === 'projects') {
            $projects = HousingJob::query()
                ->whereIn('id', $accessibleJobIds)
                ->with([
                    'property',
                    'participant',
                    'fundCodes',
                    'milestones',
                    'requirements',
                ])
                ->get()
                ->sortBy(fn ($p) => strtolower((string) (optional($p->property)->address_line_1 ?? $p->job_title)));

            return view('livewire.housing-dashboard', [
                'tab' => $this->tab,
                'projects' => $projects,
                'blockedMilestoneIds' => $blockedMilestoneIds,
                'fundCodesById' => $fundCodesById,
                'hardTemplateIds' => $hardTemplateIds,
                'userOptions' => $userOptions,
                'projectOptions' => collect(),
                'intakePipeline' => $intakePipeline,
                'pinnedLogs' => $pinnedLogs,
                'oldestLogs' => $oldestLogs,
            ])->layout('components.layouts.app');
        }

        // Cut B — per-requirement view. Apply filters then load.
        $query = Requirement::query()
            ->whereIn('housing_job_id', $accessibleJobIds)
            ->with(['owner', 'completedBy', 'housingJob.property', 'housingJob.participant']);

        $owner = $this->ownerFilterValue();
        if ($owner === 'anyone') {
            // no constraint
        } elseif (is_int($owner)) {
            $query->where('owner_user_id', $owner);
        }

        if ($this->filter_status !== 'all') {
            $query->where('status', $this->filter_status);
        }

        if ($this->filter_type !== 'all') {
            $query->where('type', $this->filter_type);
        }

        if ($this->filter_source !== 'all') {
            $query->where('source_type', $this->filter_source);
        }

        if ($this->filter_project_id !== null && $this->filter_project_id > 0) {
            $query->where('housing_job_id', $this->filter_project_id);
        }

        $requirements = $query->get()->sortBy(function ($req) use ($fundCodesById) {
            // Default sort: alphabetical by fund code (when source=fund_code),
            // then by requirement title. Per the no-hierarchy rule, this is
            // deterministic across all rows: fund-code-source rows sort
            // by the code's alphabetical position; milestone-source and
            // ad-hoc rows sort to the end alphabetically by title.
            if ($req->source_type === Requirement::SOURCE_FUND_CODE) {
                $code = optional($fundCodesById->get($req->source_id))->code ?? '';

                return [0, strtolower($code), strtolower((string) $req->title)];
            }

            if ($req->source_type === Requirement::SOURCE_MILESTONE) {
                return [1, '', strtolower((string) $req->title)];
            }

            return [2, '', strtolower((string) $req->title)]; // ad_hoc
        })->values();

        $projectOptions = HousingJob::query()
            ->whereIn('id', $accessibleJobIds)
            ->with('property')
            ->get(['id', 'job_number', 'job_title', 'property_id'])
            ->sortBy(fn ($p) => strtolower((string) ($p->property->address_line_1 ?? $p->job_title)))
            ->values();

        return view('livewire.housing-dashboard', [
            'tab' => $this->tab,
            'requirements' => $requirements,
            'blockedMilestoneIds' => $blockedMilestoneIds,
            'fundCodesById' => $fundCodesById,
            'userOptions' => $userOptions,
            'projectOptions' => $projectOptions,
            'intakePipeline' => $intakePipeline,
            'pinnedLogs' => $pinnedLogs,
            'oldestLogs' => $oldestLogs,
        ])->layout('components.layouts.app');
    }
}
