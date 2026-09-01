<?php

namespace App\Livewire\Homes;

use App\Livewire\Homes\Concerns\HasPins;
use App\Models\HomesPin;
use App\Models\HousingJob;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Services\Homes\HousingRecordDeleter;
use App\Services\MediaValidationService;
use App\Services\RequirementInstantiator;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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
    use HasPins;
    use WithFileUploads;
    use WithPermissionChecks;

    public HousingJob $job;

    /** Currently-expanded milestone id. */
    public ?int $openMilestone = null;

    // ── Document-requirement completion modal ────────────────────
    public ?int $completingRequirementId = null;

    public array $completionFiles = [];

    public string $completionNote = '';

    // ── Edit-job modal ────────────────────────────────────────────
    public bool $showEditJob = false;

    public array $editJob = [];

    // ── Add-milestone modal ───────────────────────────────────────
    public bool $showMilestoneForm = false;

    public string $milestoneName = '';

    public string $milestoneDescription = '';

    public string $milestoneTargetDate = '';

    // ── Per-milestone requirement add ─────────────────────────────
    public ?int $addingReqMilestoneId = null;

    public string $newReqTitle = '';

    public string $newReqType = Requirement::TYPE_DOCUMENT;

    // ── Add-budget-line modal ─────────────────────────────────────
    public bool $showBudgetForm = false;

    public string $budgetCategory = '';

    public string $budgetAmount = '';

    /** Untyped: the <select> round-trips '' for the empty option. */
    public $budgetFundCodeId = null;

    public $budgetMilestoneId = null;

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

    // ── Delete (recoverable — admin can restore from the recycle bin) ──

    public bool $showDelete = false;

    public function confirmDelete(): void
    {
        $this->authorizeAction('projects.delete');
        $this->showDelete = true;
    }

    /** Explicit Cancel is the only close path (portal modal convention). */
    public function cancelDelete(): void
    {
        $this->showDelete = false;
    }

    public function deleteProject(HousingRecordDeleter $deleter)
    {
        $this->authorizeAction('projects.delete');

        $number = $this->job->job_number;
        $deleter->deleteProject($this->job);

        session()->flash('homes_message', "Deleted project {$number}. If that was a mistake, an admin can restore it.");

        return redirect()->route('homes.projects');
    }

    // ── Pin (HasPins) ──────────────────────────────────────────────

    public function isProjectPinned(): bool
    {
        return $this->isPinned(HomesPin::TYPE_PROJECT, $this->job->id);
    }

    public function toggleProjectPin(): void
    {
        $this->togglePin(HomesPin::TYPE_PROJECT, $this->job->id);
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

    // ── Edit the project itself ───────────────────────────────────

    public function openEditJob(): void
    {
        $this->authorizeAction('projects.view');

        $this->editJob = [
            'job_title' => (string) $this->job->job_title,
            'job_description' => (string) ($this->job->job_description ?? ''),
            'status' => (string) $this->job->status,
            'priority' => (string) ($this->job->priority ?? 'medium'),
            'start_date' => $this->job->start_date?->toDateString() ?? '',
            'target_completion_date' => $this->job->target_completion_date?->toDateString() ?? '',
        ];
        $this->showEditJob = true;
    }

    public function closeEditJob(): void
    {
        $this->showEditJob = false;
        $this->resetErrorBag();
    }

    public function saveEditJob(): void
    {
        $this->authorizeAction('projects.view');

        $validated = $this->validate([
            'editJob.job_title' => 'required|string|max:255',
            'editJob.job_description' => 'nullable|string',
            'editJob.status' => 'required|in:pending,approved,in_progress,on_hold,completed,cancelled,rejected',
            'editJob.priority' => 'required|in:low,medium,high,emergency',
            'editJob.start_date' => 'nullable|date',
            'editJob.target_completion_date' => 'nullable|date',
        ])['editJob'];

        $this->job->update([
            'job_title' => $validated['job_title'],
            'job_description' => $validated['job_description'] ?: null,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'start_date' => $validated['start_date'] ?: null,
            'target_completion_date' => $validated['target_completion_date'] ?: null,
            'updated_by' => Auth::id(),
        ]);

        $this->showEditJob = false;
        $this->job->refresh();
        session()->flash('homes_message', 'Project updated.');
    }

    // ── Milestones: add + status ──────────────────────────────────

    public function openMilestoneForm(): void
    {
        $this->authorizeAction('projects.view');

        $this->milestoneName = '';
        $this->milestoneDescription = '';
        $this->milestoneTargetDate = '';
        $this->showMilestoneForm = true;
    }

    public function saveMilestone(): void
    {
        $this->authorizeAction('projects.view');

        $this->validate([
            'milestoneName' => 'required|string|max:255',
            'milestoneDescription' => 'nullable|string',
            'milestoneTargetDate' => 'nullable|date',
        ]);

        $sequenceOrder = ((int) $this->job->milestones()->max('sequence_order')) + 1;

        $milestone = ProjectMilestone::create([
            'housing_job_id' => $this->job->id,
            'name' => $this->milestoneName,
            'description' => $this->milestoneDescription !== '' ? $this->milestoneDescription : null,
            'target_date' => $this->milestoneTargetDate !== '' ? $this->milestoneTargetDate : null,
            'icon' => 'Circle',
            'color' => '#6366f1',
            'sequence_order' => $sequenceOrder,
            'status' => $sequenceOrder === 1 ? 'in_progress' : 'not_started',
            'created_by' => Auth::id(),
        ]);

        $this->showMilestoneForm = false;
        $this->openMilestone = $milestone->id;
        $this->job->refresh();
        session()->flash('homes_message', 'Milestone added.');
    }

    /** Start / complete a milestone — same guard as the main app. */
    public function updateMilestoneStatus(int $milestoneId, string $status): void
    {
        $this->authorizeAction('projects.view');

        $milestone = $this->job->milestones()->findOrFail($milestoneId);

        if (! in_array($status, ['in_progress', 'completed'], true)) {
            abort(422, 'Unknown milestone status.');
        }

        if ($status === 'completed') {
            if (! $milestone->canBeCompleted()) {
                session()->flash('homes_message', 'Cannot complete this milestone yet — its tasks and documents must be completed first.');

                return;
            }
            $milestone->markAsCompleted();
        } else {
            $milestone->markAsInProgress();
        }

        $this->job->refresh();
        session()->flash('homes_message', 'Milestone updated.');
    }

    // ── Per-milestone requirement add ─────────────────────────────

    public function openAddRequirement(int $milestoneId): void
    {
        $this->authorizeAction('projects.view');

        $this->job->milestones()->findOrFail($milestoneId);
        $this->addingReqMilestoneId = $milestoneId;
        $this->newReqTitle = '';
        $this->newReqType = Requirement::TYPE_DOCUMENT;
        $this->openMilestone = $milestoneId;
    }

    public function saveNewRequirement(RequirementInstantiator $instantiator): void
    {
        $this->authorizeAction('projects.view');

        if (! $this->addingReqMilestoneId) {
            return;
        }

        $this->validate([
            'newReqTitle' => 'required|string|max:255',
            'newReqType' => 'required|in:'.Requirement::TYPE_DOCUMENT.','.Requirement::TYPE_TASK,
        ]);

        $milestone = $this->job->milestones()->findOrFail($this->addingReqMilestoneId);

        $instantiator->forMilestone($milestone, $this->newReqType, trim($this->newReqTitle), Auth::user());

        $this->addingReqMilestoneId = null;
        $this->newReqTitle = '';
        $this->job->refresh();
        session()->flash('homes_message', 'Requirement added.');
    }

    // ── Add a budget line ─────────────────────────────────────────

    public function openBudgetForm(): void
    {
        $this->authorizeAction('projects.view');

        $this->budgetCategory = '';
        $this->budgetAmount = '';
        $this->budgetFundCodeId = $this->job->fundCodes()->first()?->id;
        $this->budgetMilestoneId = null;
        $this->showBudgetForm = true;
    }

    public function saveBudgetLine(): void
    {
        $this->authorizeAction('projects.view');

        // The empty <select> option round-trips ''.
        $this->budgetMilestoneId = $this->budgetMilestoneId === '' ? null : $this->budgetMilestoneId;

        $this->validate([
            'budgetCategory' => [
                'required', 'string', 'max:255',
                Rule::unique('project_budgets', 'category')
                    ->where(fn ($q) => $q->where('housing_job_id', $this->job->id)),
            ],
            'budgetAmount' => 'required|numeric|min:0',
            // Same rule as the main app: a fund is required, and the model's
            // saving-event guard verifies it is attached to this project.
            'budgetFundCodeId' => 'required|integer|exists:fund_codes,id',
            'budgetMilestoneId' => 'nullable|integer|exists:project_milestones,id',
        ], [
            'budgetCategory.unique' => 'A budget category with this name already exists for this project.',
        ]);

        // The optional milestone anchor must belong to this project.
        if ($this->budgetMilestoneId !== null) {
            $this->job->milestones()->findOrFail((int) $this->budgetMilestoneId);
        }

        try {
            ProjectBudget::create([
                'housing_job_id' => $this->job->id,
                'category' => trim($this->budgetCategory),
                'budgeted_amount' => $this->budgetAmount,
                'fund_code_id' => (int) $this->budgetFundCodeId,
                'milestone_id' => $this->budgetMilestoneId !== null ? (int) $this->budgetMilestoneId : null,
                'created_by' => Auth::id(),
            ]);
        } catch (\InvalidArgumentException $e) {
            // Model saving-guard: fund not attached to this project.
            $this->addError('budgetFundCodeId', $e->getMessage());

            return;
        }

        $this->showBudgetForm = false;
        $this->job->refresh();
        session()->flash('homes_message', 'Budget line added.');
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
            // withTrashed: a project converted under a since-retired county
            // should still show where it came from.
            'housingServices' => fn ($q) => $q->withTrashed(),
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
            'canDelete' => $this->can('projects.delete'),
        ]);
    }
}
