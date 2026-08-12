<?php

namespace App\Livewire\Homes;

use App\Models\HousingJob;
use App\Models\ProjectExpense;
use App\Models\Requirement;
use App\Services\MediaValidationService;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Project detail (homes.caclv.org): milestones expanding to their
 * requirements (the milestone-source + fund-code-mapped union — NOT the
 * legacy completion_percentage), per-category budget bars with add-expense,
 * and the link back to the originating intake.
 */
#[Layout('components.layouts.homes')]
class ProjectDetail extends Component
{
    use WithFileUploads;
    use WithPermissionChecks;

    public HousingJob $job;

    /** Currently-expanded milestone id. */
    public ?int $openMilestone = null;

    // ── Document-requirement completion modal ────────────────────
    public ?int $completingRequirementId = null;

    public array $completionFiles = [];

    public string $completionNote = '';

    // ── Add-expense modal ─────────────────────────────────────────
    public bool $showExpense = false;

    public string $expenseCategory = '';

    public string $expenseAmount = '';

    public string $expenseDate = '';

    public string $expensePaidTo = '';

    public string $expenseDescription = '';

    public function mount(HousingJob $job): void
    {
        $this->authorizeAction('projects.view');

        abort_if($job->is_template, 404);

        $this->job = $job;
        $this->openMilestone = $job->milestones()
            ->where('status', 'in_progress')
            ->orderBy('sequence_order')
            ->first()?->id;
    }

    #[On('homes-call-logged')]
    public function refresh(): void
    {
        $this->job->refresh();
    }

    public function toggleMilestone(int $milestoneId): void
    {
        $this->openMilestone = $this->openMilestone === $milestoneId ? null : $milestoneId;
    }

    // ── Requirement completion ────────────────────────────────────

    public function completeTaskRequirement(int $requirementId): void
    {
        $req = $this->ownRequirement($requirementId);

        if ($req->type !== Requirement::TYPE_TASK) {
            abort(422, 'Use the upload flow for document requirements.');
        }

        $req->markComplete(Auth::user());
        $this->job->refresh();
        session()->flash('homes_message', 'Marked complete.');
    }

    public function openCompletionModal(int $requirementId): void
    {
        $req = $this->ownRequirement($requirementId);

        $this->completingRequirementId = $req->id;
        $this->completionFiles = [];
        $this->completionNote = '';
    }

    public function closeCompletionModal(): void
    {
        $this->completingRequirementId = null;
        $this->completionFiles = [];
        $this->completionNote = '';
    }

    public function saveRequirementCompletion(): void
    {
        if (! $this->completingRequirementId) {
            return;
        }

        $req = $this->ownRequirement($this->completingRequirementId);
        $actor = Auth::user();

        // Document type — must end up with media (the portal does not expose
        // the override path; that stays on the main app's project page).
        if (empty($this->completionFiles)) {
            $this->addError('completionFiles', 'Upload at least one file to mark this document received.');

            return;
        }

        foreach ($this->completionFiles as $file) {
            MediaValidationService::validate($file, MediaValidationService::DOCUMENT_MIME_TYPES, MediaValidationService::SIZE_100MB);

            $req->addMedia($file->getRealPath())
                ->usingName($file->getClientOriginalName())
                ->usingFileName(MediaValidationService::sanitizeFileName($file->getClientOriginalName()))
                ->withCustomProperties(['uploaded_by' => $actor->id, 'uploaded_at' => now()->toISOString()])
                ->toMediaCollection('attachments');
        }

        // Same downstream signal as the main app — single fire per save.
        $req->notifyDownstreamOfUpload($actor);

        $req->markComplete($actor, $this->completionNote !== '' ? $this->completionNote : null);

        $this->closeCompletionModal();
        $this->job->refresh();
        session()->flash('homes_message', 'Document received.');
    }

    protected function ownRequirement(int $requirementId): Requirement
    {
        $this->authorizeAction('projects.view');

        $req = Requirement::findOrFail($requirementId);

        if ($req->housing_job_id !== (int) $this->job->id) {
            abort(403);
        }

        return $req;
    }

    // ── Add expense ───────────────────────────────────────────────

    public function openExpense(): void
    {
        $this->authorizeAction('projects.view');

        $this->expenseCategory = $this->job->budgets()->orderBy('category')->first()?->category ?? '';
        $this->expenseAmount = '';
        $this->expenseDate = now()->toDateString();
        $this->expensePaidTo = '';
        $this->expenseDescription = '';
        $this->showExpense = true;
    }

    public function closeExpense(): void
    {
        $this->showExpense = false;
    }

    public function saveExpense(): void
    {
        $this->authorizeAction('projects.view');

        $this->validate([
            'expenseCategory' => 'required|string',
            'expenseAmount' => 'required|numeric',
            'expenseDate' => 'required|date',
            'expenseDescription' => 'nullable|string',
        ]);

        // Same shape as ProjectManagement::saveExpense — the funding source
        // is implied by the (housing_job_id, category) budget tuple, and the
        // ProjectExpense observer recomputes that budget's spent_amount.
        $description = trim($this->expensePaidTo) !== ''
            ? trim('Paid to '.$this->expensePaidTo.($this->expenseDescription !== '' ? ' — '.$this->expenseDescription : ''))
            : ($this->expenseDescription ?: null);

        ProjectExpense::create([
            'housing_job_id' => $this->job->id,
            'contract_id' => null,
            'category' => $this->expenseCategory,
            'amount' => $this->expenseAmount,
            'expense_date' => $this->expenseDate,
            'description' => $description,
            'created_by' => Auth::id(),
        ]);

        $this->showExpense = false;
        $this->job->refresh();
        session()->flash('homes_message', 'Expense saved.');
    }

    // ── Render ────────────────────────────────────────────────────

    public function render()
    {
        $job = $this->job->load([
            'participant', 'property', 'program',
            'milestones' => fn ($q) => $q->orderBy('sequence_order'),
            'milestones.dependencies',
            'requirements.owner',
            'budgets.fundCode',
            'fundCodes',
            'originatingCommunicationLogs',
        ]);

        // Requirements union per milestone: own (source_type='milestone')
        // plus fund-code-source requirements whose fund pivot maps here.
        // Pattern from project-management.blade.php — NOT completion_percentage.
        $allReqs = $job->requirements;
        $fundCodeMilestoneMap = collect();
        foreach ($job->fundCodes as $fc) {
            $fundCodeMilestoneMap[$fc->id] = $fc->pivot?->milestone_id;
        }

        $milestones = $job->milestones->map(function ($m) use ($allReqs, $fundCodeMilestoneMap) {
            $own = $allReqs->where('source_type', 'milestone')->where('source_id', $m->id);
            $viaFundCode = $allReqs->where('source_type', 'fund_code')->filter(
                fn ($r) => ($fundCodeMilestoneMap[$r->source_id] ?? null) === $m->id
            );
            $reqs = $own->concat($viaFundCode)->values();

            $blockedBy = $m->dependencies
                ->filter(fn ($d) => $d->status !== 'completed')
                ->pluck('name');

            return [
                'id' => $m->id,
                'name' => $m->name,
                'status' => $m->status,
                'effective_status' => ($blockedBy->isNotEmpty() && $m->status !== 'completed') ? 'blocked' : $m->status,
                'target' => $m->target_date?->isoFormat('MMM D'),
                'blocked_by' => $blockedBy->implode(', '),
                'reqs' => $reqs->map(fn (Requirement $r) => [
                    'id' => $r->id,
                    'title' => $r->title,
                    'type' => $r->type,
                    'complete' => $r->status === Requirement::STATUS_COMPLETE,
                    'na' => $r->status === Requirement::STATUS_NOT_APPLICABLE,
                    'owner' => $r->owner?->name ?? '—',
                ])->values(),
                'reqs_met' => $reqs->filter(fn ($r) => $r->status !== Requirement::STATUS_OUTSTANDING)->count(),
                'reqs_total' => $reqs->count(),
            ];
        })->values();

        $origin = $job->originatingCommunicationLogs->first();

        return view('livewire.homes.project-detail', [
            'who' => $job->participant?->full_name ?: $job->job_title,
            'where' => $job->property ? trim($job->property->address_line_1.', '.$job->property->city, ', ') : null,
            'plainStatus' => Projects::plainStatus($job),
            'next' => Projects::nextAction($job),
            'milestones' => $milestones,
            'msDone' => $job->milestones->where('status', 'completed')->count(),
            'msTotal' => $job->milestones->count(),
            'budgets' => $job->budgets,
            'totalBudget' => (float) $job->getTotalBudget(),
            'totalSpent' => (float) $job->getTotalSpent(),
            'origin' => $origin,
            'canEdit' => $this->can('projects.view'),
        ]);
    }
}
