<?php

namespace App\Livewire;

use App\Models\HousingJob;
use App\Models\Requirement;
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
        $this->authorizeAction('projects.view');
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['projects', 'requirements'], true) ? $tab : 'projects';
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

    public function render()
    {
        $accessibleJobIds = $this->accessibleHousingJobIds();

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
        ])->layout('components.layouts.app');
    }
}
