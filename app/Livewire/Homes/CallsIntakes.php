<?php

namespace App\Livewire\Homes;

use App\Models\CommunicationLog;
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
    use WithPagination;
    use WithPermissionChecks;

    #[Url]
    public string $search = '';

    /** Person-language filter: needs | collecting | all. */
    #[Url]
    public string $pill = 'needs';

    /** Expanded view restores the full power table. */
    #[Url]
    public bool $expanded = false;

    public function mount()
    {
        $this->authorizeAction('communications_log.view');

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

    public function setPill(string $pill): void
    {
        $this->pill = in_array($pill, ['needs', 'collecting', 'all'], true) ? $pill : 'all';
        $this->resetPage();
    }

    public function toggleExpanded(): void
    {
        $this->expanded = ! $this->expanded;
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
        $base = fn () => CommunicationLog::query()
            ->when($this->search !== '', fn ($q) => $q->search($this->search));

        $query = $this->applyPill($base())
            ->with(['participant', 'property', 'intakeRequirements.definition'])
            ->orderByRaw('CASE WHEN follow_up_completed = 0 AND follow_up_date IS NOT NULL AND follow_up_date < ? THEN 0 ELSE 1 END', [now()->startOfDay()])
            ->orderBy('communication_date', 'desc');

        $logs = $query->paginate(20)->through(function (CommunicationLog $log) use ($data) {
            $stage = $data->stage($log);
            $progress = $data->phase1Progress($log);

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
                'who' => $data->personName($log),
                'where' => $data->personWhere($log),
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
        });

        // Pill counts.
        $needsCount = CommunicationLog::pendingFollowUp()->count();
        $collectingCount = CommunicationLog::query()
            ->whereNull('converted_to_job_id')
            ->whereHas('intakeRequirements', fn ($q) => $q->where('is_orphaned', false)
                ->whereNotIn('status', IntakeRequirement::MET_STATUSES))
            ->count();

        return view('livewire.homes.calls-intakes', [
            'logs' => $logs,
            'needsCount' => $needsCount,
            'collectingCount' => $collectingCount,
        ]);
    }
}
