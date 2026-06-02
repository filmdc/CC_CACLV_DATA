<?php

namespace App\Livewire;

use App\Models\User;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * Admin-wide activity audit log viewer (audit.view).
 *
 * Reads the spatie/laravel-activitylog `activity_log` table and presents
 * a filterable, paginated "who changed what, when, before → after" trail
 * across the housing module (projects, milestones, requirements,
 * budgets, expenses) and communication logs.
 *
 * Read-only. The log is append-only at the model layer; there is no edit
 * or delete affordance here by design.
 */
#[Layout('components.layouts.app')]
class ActivityAuditLog extends Component
{
    use WithPagination, WithPermissionChecks;

    public string $search = '';

    /** Filter by causer (acting user) id. */
    public string $userFilter = '';

    /** Filter by subject type — short class label e.g. 'ProjectMilestone'. */
    public string $entityFilter = '';

    /** Filter by event — created | updated | deleted. */
    public string $eventFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 25;

    /** Row ids whose before/after diff is expanded. */
    public array $expanded = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'userFilter' => ['except' => ''],
        'entityFilter' => ['except' => ''],
        'eventFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    /**
     * The subject types we surface. Maps the short label shown in the
     * filter to the fully-qualified model class stored in
     * activity_log.subject_type.
     */
    public const ENTITY_MAP = [
        'Project' => \App\Models\HousingJob::class,
        'Milestone' => \App\Models\ProjectMilestone::class,
        'Requirement' => \App\Models\Requirement::class,
        'Budget category' => \App\Models\ProjectBudget::class,
        'Expense' => \App\Models\ProjectExpense::class,
        'Communication log' => \App\Models\CommunicationLog::class,
    ];

    public function mount(): void
    {
        $this->authorizeAction('audit.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedUserFilter(): void
    {
        $this->resetPage();
    }

    public function updatedEntityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function toggleExpanded(int $id): void
    {
        if (in_array($id, $this->expanded, true)) {
            $this->expanded = array_values(array_diff($this->expanded, [$id]));
        } else {
            $this->expanded[] = $id;
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'userFilter', 'entityFilter', 'eventFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Activity::query()
            ->with('causer')
            // Scope to the surfaces this page documents — exclude any
            // unrelated activity logged elsewhere in the app now or later.
            ->whereIn('subject_type', array_values(self::ENTITY_MAP))
            ->latest();

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', $term)
                    ->orWhere('properties', 'like', $term);
            });
        }

        if ($this->userFilter !== '') {
            $query->where('causer_type', User::class)
                ->where('causer_id', $this->userFilter);
        }

        if ($this->entityFilter !== '' && isset(self::ENTITY_MAP[$this->entityFilter])) {
            $query->where('subject_type', self::ENTITY_MAP[$this->entityFilter]);
        }

        if ($this->eventFilter !== '') {
            $query->where('event', $this->eventFilter);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $activities = $query->paginate($this->perPage);

        // Reverse lookup for rendering the short entity label per row.
        $classToLabel = array_flip(self::ENTITY_MAP);

        // Causer dropdown — only users who actually appear as causers,
        // so the filter doesn't list the whole directory.
        $causerIds = Activity::query()
            ->whereIn('subject_type', array_values(self::ENTITY_MAP))
            ->where('causer_type', User::class)
            ->whereNotNull('causer_id')
            ->distinct()
            ->pluck('causer_id');

        $causers = User::whereIn('id', $causerIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.activity-audit-log', [
            'activities' => $activities,
            'classToLabel' => $classToLabel,
            'entityOptions' => array_keys(self::ENTITY_MAP),
            'causers' => $causers,
        ]);
    }

    /**
     * Helper used by the view to render a compact "field: old → new"
     * change set for an activity row. Returns an array of
     * [field, old, new] tuples.
     */
    public static function diffRows(Activity $activity): array
    {
        $props = $activity->properties ?? collect();
        $new = $props['attributes'] ?? [];
        $old = $props['old'] ?? [];

        $fields = array_unique(array_merge(array_keys($new), array_keys($old)));
        sort($fields);

        $rows = [];
        foreach ($fields as $field) {
            $rows[] = [
                'field' => Str::headline($field),
                'old' => array_key_exists($field, $old) ? self::stringify($old[$field]) : null,
                'new' => array_key_exists($field, $new) ? self::stringify($new[$field]) : null,
            ];
        }

        return $rows;
    }

    protected static function stringify($value): string
    {
        if (is_null($value)) {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}
