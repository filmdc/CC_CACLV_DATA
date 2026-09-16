<?php

namespace App\Livewire\Homes;

use App\Livewire\Homes\Concerns\HasPins;
use App\Livewire\Homes\Concerns\HasSavedViews;
use App\Livewire\Homes\Concerns\HasSortableColumns;
use App\Models\HomesPin;
use App\Models\HousingJob;
use App\Services\Homes\HousingPortalData;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Projects list (homes.caclv.org) — active rehab jobs with plain-language
 * status, milestone progress, budget bars, and one next action per row.
 *
 * "Needs attention" is computed (overdue milestone / over-budget line), so
 * the whole active set is loaded and filtered in PHP — the housing caseload
 * is a few dozen jobs, and this keeps status math in one place.
 */
#[Layout('components.layouts.homes')]
class Projects extends Component
{
    use HasPins;
    use HasSavedViews;
    use HasSortableColumns;
    use WithPermissionChecks;

    #[Url]
    public string $search = '';

    /** open | attention | all */
    #[Url]
    public string $pill = 'open';

    public const FINISHED_STATUSES = ['completed', 'cancelled', 'rejected'];

    /** Sort order for the plain-language status — the ones needing a hand first. */
    private const STATUS_ORDER = ['attention' => 0, 'active' => 1, 'finished' => 2];

    public function mount(): void
    {
        $this->authorizeAction('projects.view');

        $this->initSavedViews();
    }

    // ── Saved-view contract (HasSavedViews) ─────────────────────────

    protected function screenKey(): string
    {
        return 'projects';
    }

    /** Row segments that can be shown/hidden, in display order. */
    public function columnOptions(): array
    {
        return [
            'who' => 'Homeowner & address',
            'status' => 'Status & program',
            'progress' => 'Milestones & budget',
            'next' => 'Next action',
        ];
    }

    public function lockedColumns(): array
    {
        return ['who'];
    }

    public function pillOptions(): array
    {
        return [
            'open' => 'Open',
            'attention' => 'Needs attention',
            'all' => 'Everything',
        ];
    }

    protected function builtInDefaults(): array
    {
        return [
            'columns' => ['who', 'status', 'progress', 'next'],
            'filters' => ['pill' => 'open', 'sort' => '', 'sortDir' => 'asc'],
        ];
    }

    protected function applyViewState(array $columns, array $filters): void
    {
        $this->pill = $filters['pill'];
        $this->sort = (string) $filters['sort'];
        $this->sortDir = (string) $filters['sortDir'];
    }

    protected function currentFilters(): array
    {
        return ['pill' => $this->pill, 'sort' => $this->sort, 'sortDir' => $this->sortDir];
    }

    // ── Sorting (HasSortableColumns) ────────────────────────────────

    /**
     * Sortable columns. A row here packs several facts into one visual
     * segment (name + address; milestones + budget), so the sort registry
     * is finer-grained than the column registry and the header offers each
     * fact separately.
     */
    public function sortOptions(): array
    {
        return [
            'who' => 'Homeowner',
            'where' => 'Address',
            'status' => 'Status',
            'program' => 'Program',
            'progress' => 'Milestones done',
            'budget' => 'Budget spent',
            'next' => 'Next target date',
            'created' => 'Date added',
        ];
    }

    public function defaultSortLabel(): string
    {
        return 'Newest first';
    }

    /** Progress, money and dates read biggest-first; names read A→Z. */
    protected function descFirstSortKeys(): array
    {
        return ['progress', 'budget', 'created'];
    }

    /**
     * Sort the rows this screen already holds. The whole active set is in
     * memory (a few dozen jobs) and two of the sortable facts — status and
     * milestone progress — are computed in PHP, so sorting here keeps the
     * status math in one place rather than duplicating it in SQL.
     */
    protected function sortRows(Collection $rows): Collection
    {
        [$key, $dir] = $this->activeSort();

        if ($key === '') {
            return $rows;    // already newest-first from the query
        }

        return $rows
            ->sortBy(fn (array $row) => $this->sortValue($row, $key), SORT_REGULAR, $dir === 'desc')
            ->values();
    }

    /** The comparable value behind one sortable column. */
    private function sortValue(array $row, string $key): int|float|string
    {
        return match ($key) {
            'who' => mb_strtolower((string) $row['who']),
            'where' => mb_strtolower((string) $row['where']),
            'status' => self::STATUS_ORDER[$row['status']] ?? count(self::STATUS_ORDER),
            'program' => mb_strtolower((string) $row['program']),
            // Share complete, so a 3-of-4 job outranks a 3-of-20 one. A job
            // with nothing to measure yet has no share to compare, so it
            // lands at the bottom of the natural most-complete-first order.
            'progress' => $row['ms_total'] > 0 ? $row['ms_done'] / $row['ms_total'] : -1,
            'budget' => $row['budget'] > 0 ? $row['spent'] / $row['budget'] : -1,
            'next' => $row['next_due_at']?->getTimestamp() ?? PHP_INT_MAX,
            'created' => $row['created_at']?->getTimestamp() ?? 0,
            default => 0,
        };
    }

    // ── Filters ─────────────────────────────────────────────────────

    public function setPill(string $pill): void
    {
        $this->pill = in_array($pill, ['open', 'attention', 'all'], true) ? $pill : 'open';
    }

    /** Pins are held back while searching so results stay complete. */
    public function showsPinnedSection(): bool
    {
        return trim($this->search) === '';
    }

    /** Convenience for the Blade pin button — this screen only pins projects. */
    public function isProjectPinned(int $id): bool
    {
        return $this->isPinned(HomesPin::TYPE_PROJECT, $id);
    }

    /**
     * Plain-language status for a job (relations already eager-loaded):
     * finished | attention (overdue milestone or over-budget line) | active.
     */
    public static function plainStatus(HousingJob $job): string
    {
        if (in_array($job->status, self::FINISHED_STATUSES, true)) {
            return 'finished';
        }

        $overdueMilestone = $job->milestones
            ->contains(fn ($m) => $m->target_date !== null
                && $m->target_date->isPast()
                && $m->status !== 'completed');

        $overBudget = $job->budgets->contains(fn ($b) => $b->is_over_budget);

        return ($overdueMilestone || $overBudget) ? 'attention' : 'active';
    }

    /**
     * The single "next action" line: first in-progress milestone, else first
     * not-started. 'due_at' carries the raw target date (null when there is
     * no date to show) so lists can sort on it.
     */
    public static function nextAction(HousingJob $job): array
    {
        if (in_array($job->status, self::FINISHED_STATUSES, true)) {
            return ['text' => 'Nothing to do', 'due' => ucfirst($job->status), 'due_at' => null];
        }

        $next = $job->milestones->firstWhere('status', 'in_progress')
            ?? $job->milestones->firstWhere('status', 'not_started');

        if ($next === null) {
            return ['text' => 'All milestones done', 'due' => 'Wrap up', 'due_at' => null];
        }

        return [
            'text' => $next->name,
            'due' => $next->target_date ? 'Target '.$next->target_date->isoFormat('MMM D') : '—',
            'due_at' => $next->target_date,
        ];
    }

    public function render(HousingPortalData $data)
    {
        $all = HousingJob::active()
            ->with(['participant', 'property', 'program', 'milestones', 'budgets'])
            ->orderByDesc('created_at')
            ->get();

        $rows = $all->map(fn (HousingJob $job) => $data->projectRow($job));

        $counts = [
            'open' => $rows->filter(fn ($r) => $r['status'] !== 'finished')->count(),
            'attention' => $rows->where('status', 'attention')->count(),
        ];

        // Pins ride above the list and ignore the pills, so the same project
        // is dropped from the list below rather than shown twice. Pinned
        // projects are read through the service (not $rows) so a pin still
        // resolves after the job is archived or finished.
        $pinned = $this->showsPinnedSection() ? $data->pinnedProjects((int) auth()->id()) : collect();
        $pinnedIds = $pinned->pluck('id')->all();

        $filtered = $rows
            ->when($pinnedIds !== [], fn ($c) => $c->reject(fn ($r) => in_array($r['id'], $pinnedIds, true)))
            ->when($this->pill === 'open', fn ($c) => $c->filter(fn ($r) => $r['status'] !== 'finished'))
            ->when($this->pill === 'attention', fn ($c) => $c->where('status', 'attention'))
            ->when($this->search !== '', function ($c) {
                $needle = mb_strtolower($this->search);

                return $c->filter(fn ($r) => str_contains(mb_strtolower($r['who'].' '.$r['where'].' '.$r['title']), $needle));
            })
            ->values();

        $filtered = $this->sortRows($filtered);

        return view('livewire.homes.projects', [
            'rows' => $filtered,
            'pinnedRows' => $pinned,
            'counts' => $counts,
        ]);
    }
}
