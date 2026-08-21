<?php

namespace App\Livewire\Homes;

use App\Models\HousingJob;
use App\Traits\WithPermissionChecks;
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
    use WithPermissionChecks;

    #[Url]
    public string $search = '';

    /** open | attention | all */
    #[Url]
    public string $pill = 'open';

    public const FINISHED_STATUSES = ['completed', 'cancelled', 'rejected'];

    public function mount(): void
    {
        $this->authorizeAction('projects.view');
    }

    public function setPill(string $pill): void
    {
        $this->pill = in_array($pill, ['open', 'attention', 'all'], true) ? $pill : 'open';
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

    /** The single "next action" line: first in-progress milestone, else first not-started. */
    public static function nextAction(HousingJob $job): array
    {
        if (in_array($job->status, self::FINISHED_STATUSES, true)) {
            return ['text' => 'Nothing to do', 'due' => ucfirst($job->status)];
        }

        $next = $job->milestones->firstWhere('status', 'in_progress')
            ?? $job->milestones->firstWhere('status', 'not_started');

        if ($next === null) {
            return ['text' => 'All milestones done', 'due' => 'Wrap up'];
        }

        return [
            'text' => $next->name,
            'due' => $next->target_date ? 'Target '.$next->target_date->isoFormat('MMM D') : '—',
        ];
    }

    public function render()
    {
        $all = HousingJob::active()
            ->with(['participant', 'property', 'program', 'milestones', 'budgets'])
            ->orderByDesc('created_at')
            ->get();

        $rows = $all->map(function (HousingJob $job) {
            $next = self::nextAction($job);

            return [
                'id' => $job->id,
                'who' => $job->participant?->full_name ?: $job->job_title,
                'where' => $job->property
                    ? trim($job->property->address_line_1.', '.$job->property->city, ', ')
                    : '—',
                'status' => self::plainStatus($job),
                'program' => $job->program?->name,
                'title' => $job->job_title,
                'ms_done' => $job->milestones->where('status', 'completed')->count(),
                'ms_total' => $job->milestones->count(),
                'budget' => (float) $job->getTotalBudget(),
                'spent' => (float) $job->getTotalSpent(),
                'next' => $next['text'],
                'next_due' => $next['due'],
            ];
        });

        $counts = [
            'open' => $rows->filter(fn ($r) => $r['status'] !== 'finished')->count(),
            'attention' => $rows->where('status', 'attention')->count(),
        ];

        $filtered = $rows
            ->when($this->pill === 'open', fn ($c) => $c->filter(fn ($r) => $r['status'] !== 'finished'))
            ->when($this->pill === 'attention', fn ($c) => $c->where('status', 'attention'))
            ->when($this->search !== '', function ($c) {
                $needle = mb_strtolower($this->search);

                return $c->filter(fn ($r) => str_contains(mb_strtolower($r['who'].' '.$r['where'].' '.$r['title']), $needle));
            })
            ->values();

        return view('livewire.homes.projects', [
            'rows' => $filtered,
            'counts' => $counts,
        ]);
    }
}
