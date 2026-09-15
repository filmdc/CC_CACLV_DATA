<?php

namespace App\Livewire\Homes;

use App\Livewire\Homes\Concerns\HasPins;
use App\Livewire\Homes\Concerns\HasSavedViews;
use App\Livewire\Homes\Concerns\HasSortableColumns;
use App\Models\CommunicationLog;
use App\Models\HomesPin;
use App\Models\IntakeRequirement;
use App\Models\RequirementDefinition;
use App\Services\Homes\HousingPortalData;
use App\Traits\WithPermissionChecks;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.homes')]
class CallsIntakes extends Component
{
    use HasPins;
    use HasSavedViews;
    use HasSortableColumns;
    use WithPagination;
    use WithPermissionChecks;

    #[Url]
    public string $search = '';

    /** Person-language filter: needs | collecting | all. */
    #[Url]
    public string $pill = 'all';

    /** City filter — exact match on contact_city ('' = any city). */
    #[Url]
    public string $city = '';

    /** Expanded view restores the full power table. */
    #[Url]
    public bool $expanded = true;

    public function mount()
    {
        $this->authorizeAction('communications_log.view');

        $this->initSavedViews();

        // Back-compat: the Phase-1 Today page emitted /calls?open={id} links.
        // The detail now lives at its own route — send stale links there.
        $openId = request()->query('open');
        if (is_numeric($openId)) {
            return redirect()->route('homes.call', (int) $openId);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCity(): void
    {
        $this->resetPage();
    }

    public function setPill(string $pill): void
    {
        $this->pill = in_array($pill, ['needs', 'collecting', 'all'], true) ? $pill : 'all';
        $this->resetPage();
    }

    public function toggleExpanded(): void
    {
        $this->expanded = ! $this->expanded;
    }

    /** Pins are held back while searching so results stay complete. */
    public function showsPinnedSection(): bool
    {
        return trim($this->search) === '';
    }

    /** Convenience for the Blade pin button — this screen only pins calls. */
    public function isCallPinned(int $id): bool
    {
        return $this->isPinned(HomesPin::TYPE_CALL, $id);
    }

    // ── Saved-view contract (HasSavedViews) ─────────────────────────

    protected function screenKey(): string
    {
        return 'calls';
    }

    /** Column registry for the expanded table, in display order. */
    public function columnOptions(): array
    {
        return [
            'who' => 'Contact',
            'where' => 'Address',
            'note' => 'Summary',
            'stage' => 'Stage',
            'docs' => 'Docs',
            'last' => 'Last touched',
            'next' => 'Next action',
            'type' => 'Type',
            'direction' => 'Direction',
        ];
    }

    public function lockedColumns(): array
    {
        return ['who'];
    }

    public function pillOptions(): array
    {
        return [
            'needs' => 'Needs something',
            'collecting' => 'Collecting docs',
            'all' => 'Everything',
        ];
    }

    protected function builtInDefaults(): array
    {
        return [
            'columns' => ['who', 'where', 'stage', 'docs', 'last', 'next', 'type', 'direction'],
            'filters' => ['pill' => 'all', 'expanded' => true, 'city' => '', 'sort' => '', 'sortDir' => 'asc'],
        ];
    }

    protected function applyViewState(array $columns, array $filters): void
    {
        $this->pill = $filters['pill'];
        $this->expanded = $filters['expanded'];
        $this->city = (string) $filters['city'];
        $this->sort = (string) $filters['sort'];
        $this->sortDir = (string) $filters['sortDir'];
    }

    protected function currentFilters(): array
    {
        return [
            'pill' => $this->pill,
            'expanded' => $this->expanded,
            'city' => $this->city,
            'sort' => $this->sort,
            'sortDir' => $this->sortDir,
        ];
    }

    protected function afterViewApplied(): void
    {
        $this->resetPage();
    }

    // ── Sorting (HasSortableColumns) ────────────────────────────────

    /**
     * Every column in the expanded table can be sorted. Two of them read
     * against something other than their own text, because the text is not
     * what the user means by "in order":
     *   stage — pipeline position (just a call → collecting → ready → converted)
     *   next  — how soon the follow-up is due, open ones ahead of settled ones
     */
    public function sortOptions(): array
    {
        return $this->columnOptions();
    }

    public function defaultSortLabel(): string
    {
        return 'Needs attention first';
    }

    /** "Last touched" means the most recent call first. */
    protected function descFirstSortKeys(): array
    {
        return ['last'];
    }

    protected function afterSortChanged(): void
    {
        $this->resetPage();
    }

    #[On('homes-call-logged')]
    public function refresh(): void
    {
        $this->resetPage();
    }

    /** Apply the active person-language pill to a base query. */
    protected function applyPill($query)
    {
        if ($this->pill === 'needs') {
            // Actionable set: cases with an open follow-up (overdue floats up via ordering).
            $query->pendingFollowUp();
        } elseif ($this->pill === 'collecting') {
            $query->whereNull('converted_to_job_id')
                ->whereHas('intakeRequirements', fn ($q) => $q->where('is_orphaned', false)
                    ->whereNotIn('status', IntakeRequirement::MET_STATUSES));
        }

        return $query;
    }

    /**
     * Apply the active sort to the list query.
     *
     * This list is paginated, so the ordering has to happen in SQL — sorting
     * the twenty rows of the current page would only shuffle a window.
     */
    protected function applySort($query)
    {
        [$key, $dir] = $this->activeSort();

        if ($key === '') {
            // The screen's own default: overdue follow-ups float to the top,
            // then the most recently touched first.
            $query->orderByRaw('CASE WHEN follow_up_completed = 0 AND follow_up_date IS NOT NULL AND follow_up_date < ? THEN 0 ELSE 1 END', [now()->startOfDay()])
                ->orderBy('communication_date', 'desc');
        } else {
            foreach ($this->sortExpressions($key) as $expression) {
                $query->orderByRaw("{$expression} {$dir}");
            }
        }

        // Deterministic tiebreak: without one, paginating a list with ties
        // (same date, same stage) can repeat or drop rows between pages.
        return $query->orderBy('communication_logs.id', 'desc');
    }

    /**
     * ORDER BY expressions for a sort column; the direction is appended to
     * each, so a column that needs "blanks last" or "open follow-ups first"
     * returns that ordinal ahead of its value and reverses as a whole.
     *
     * Every value here is a class constant or a column name — nothing user
     * supplied reaches the raw SQL.
     */
    protected function sortExpressions(string $key): array
    {
        return match ($key) {
            'who' => [$this->nameOrderSql()],
            'where' => [
                "CASE WHEN {$this->addressOrderSql()} = '' THEN 1 ELSE 0 END",
                $this->addressOrderSql(),
            ],
            'note' => [
                "CASE WHEN COALESCE(communication_logs.summary, '') = '' THEN 1 ELSE 0 END",
                'communication_logs.summary',
            ],
            // Pipeline position, mirroring HousingPortalData::stage():
            // just a call → collecting docs → ready for project → converted.
            'stage' => ['CASE'
                .' WHEN communication_logs.converted_to_job_id IS NOT NULL THEN 3'
                .' WHEN '.$this->phase1CountSql(false).' = 0 THEN 0'
                .' WHEN '.$this->phase1CountSql(true).' >= '.$this->phase1CountSql(false).' THEN 2'
                .' ELSE 1 END'],
            // Share of the Phase-1 docs collected, so 3-of-4 outranks 3-of-20.
            // Cases with no docs asked for have no share to compare, so they
            // sit at the bottom (and flip to the top when reversed).
            'docs' => [
                'CASE WHEN '.$this->phase1CountSql(false).' = 0 THEN 1 ELSE 0 END',
                $this->phase1CountSql(true).' / NULLIF('.$this->phase1CountSql(false).', 0)',
            ],
            'last' => ['COALESCE(communication_logs.communication_date, communication_logs.updated_at)'],
            // "Next action" orders by how soon it is due, not by its wording:
            // open follow-ups first (soonest to latest), then settled ones.
            'next' => [
                'CASE WHEN communication_logs.follow_up_completed = 0 AND communication_logs.follow_up_date IS NOT NULL THEN 0 ELSE 1 END',
                'communication_logs.follow_up_date',
            ],
            'type' => ['communication_logs.communication_type'],
            'direction' => ['communication_logs.direction'],
            default => [],
        };
    }

    /** The contact name the "who" column shows — participant first, then the log's own fields. */
    private function nameOrderSql(): string
    {
        return 'COALESCE('
            ."NULLIF((SELECT TRIM(CONCAT_WS(' ', p.first_name, NULLIF(p.middle_name, ''), p.last_name))"
            ." FROM participants p WHERE p.id = communication_logs.participant_id), ''), "
            ."NULLIF(TRIM(CONCAT_WS(' ', NULLIF(communication_logs.contact_first_name, ''),"
            ." NULLIF(communication_logs.contact_last_name, ''))), ''), "
            .'communication_logs.subject)';
    }

    /** The address line the "where" column shows, falling back to the linked property. */
    private function addressOrderSql(): string
    {
        return 'COALESCE('
            ."NULLIF(CONCAT_WS(', ', NULLIF(communication_logs.contact_address, ''),"
            ." NULLIF(communication_logs.contact_city, ''), NULLIF(communication_logs.contact_state, ''),"
            ." NULLIF(communication_logs.contact_zip, '')), ''), "
            .'(SELECT pr.address_line_1 FROM properties pr WHERE pr.id = communication_logs.property_id), '
            ."'')";
    }

    /**
     * Counts the same Phase-1 requirements HousingPortalData::phase1Progress()
     * counts in PHP — non-orphaned rows whose definition sits in a Phase-1
     * category (a missing or retired definition reads as eligibility, as it
     * does through the relation). Correlated so no join can duplicate a log.
     */
    private function phase1CountSql(bool $metOnly): string
    {
        $categories = collect(RequirementDefinition::PHASE_1_CATEGORIES)
            ->map(fn (string $category) => "'".$category."'")
            ->implode(', ');
        $metStatuses = collect(IntakeRequirement::MET_STATUSES)
            ->map(fn (string $status) => "'".$status."'")
            ->implode(', ');

        return '(SELECT COUNT(*) FROM intake_requirements ir'
            .' LEFT JOIN requirement_definitions rd ON rd.id = ir.source_definition_id AND rd.deleted_at IS NULL'
            .' WHERE ir.communication_log_id = communication_logs.id'
            .' AND ir.is_orphaned = 0'
            ." AND COALESCE(rd.category, '".RequirementDefinition::CATEGORY_ELIGIBILITY."') IN ({$categories})"
            .($metOnly ? " AND ir.status IN ({$metStatuses})" : '')
            .')';
    }

    public function render(HousingPortalData $data)
    {
        // Pins ride above the list and ignore the pills, so the same record
        // is dropped from the list below rather than shown twice. While a
        // search is running the pinned section steps aside (see the view)
        // and the list stays complete.
        $pinned = $this->showsPinnedSection() ? $data->pinnedCalls((int) auth()->id()) : collect();
        $pinnedIds = $pinned->pluck('id')->all();

        $base = fn () => CommunicationLog::query()
            ->when($pinnedIds !== [], fn ($q) => $q->whereNotIn('id', $pinnedIds))
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when(trim($this->city) !== '', fn ($q) => $q->whereRaw('TRIM(contact_city) = ?', [trim($this->city)]));

        $query = $this->applySort(
            $this->applyPill($base())->with(['participant', 'property', 'intakeRequirements.definition'])
        );

        $logs = $query->paginate(20)->through(fn (CommunicationLog $log) => $data->callRow($log));

        // Pill counts.
        $needsCount = CommunicationLog::pendingFollowUp()->count();
        $collectingCount = CommunicationLog::query()
            ->whereNull('converted_to_job_id')
            ->whereHas('intakeRequirements', fn ($q) => $q->where('is_orphaned', false)
                ->whereNotIn('status', IntakeRequirement::MET_STATUSES))
            ->count();

        // Distinct cities for the filter dropdown (TRIM guards legacy entries
        // saved with stray whitespace before the address-cleanup migration).
        $cities = CommunicationLog::query()
            ->whereNotNull('contact_city')
            ->where('contact_city', '!=', '')
            ->selectRaw('DISTINCT TRIM(contact_city) AS city')
            ->orderBy('city')
            ->pluck('city');

        return view('livewire.homes.calls-intakes', [
            'logs' => $logs,
            'pinnedRows' => $pinned,
            'needsCount' => $needsCount,
            'collectingCount' => $collectingCount,
            'cities' => $cities,
        ]);
    }
}
