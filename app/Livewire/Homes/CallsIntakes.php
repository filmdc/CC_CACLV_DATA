<?php

namespace App\Livewire\Homes;

use App\Livewire\Homes\Concerns\HasPins;
use App\Livewire\Homes\Concerns\HasSavedViews;
use App\Models\CommunicationLog;
use App\Models\HomesPin;
use App\Models\IntakeRequirement;
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
            // Optional — not in the built-in defaults.
            'log_id' => 'Log ID',
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
            'filters' => ['pill' => 'all', 'expanded' => true, 'city' => ''],
        ];
    }

    protected function applyViewState(array $columns, array $filters): void
    {
        $this->pill = $filters['pill'];
        $this->expanded = $filters['expanded'];
        $this->city = (string) $filters['city'];
    }

    protected function currentFilters(): array
    {
        return ['pill' => $this->pill, 'expanded' => $this->expanded, 'city' => $this->city];
    }

    protected function afterViewApplied(): void
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

        $query = $this->applyPill($base())
            ->with(['participant', 'property', 'intakeRequirements.definition'])
            ->orderByRaw('CASE WHEN follow_up_completed = 0 AND follow_up_date IS NOT NULL AND follow_up_date < ? THEN 0 ELSE 1 END', [now()->startOfDay()])
            ->orderBy('communication_date', 'desc');

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
