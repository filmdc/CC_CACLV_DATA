<?php

namespace App\Models;

use App\Services\MilestoneApprovalService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HousingJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_number',
        'participant_id',
        'household_id',
        'property_id',
        'contractor_id',
        'program_id',
        'service_id',
        'job_title',
        'job_description',
        'job_type_id',
        'status',
        'request_date',
        'approval_date',
        'start_date',
        'target_completion_date',
        'actual_completion_date',
        'estimated_cost',
        'approved_amount',
        'actual_cost',
        'funding_source',
        'work_order_number',
        'contract_number',
        'scope_of_work',
        'priority',
        'cancellation_reason',
        'internal_notes',
        'is_template',
        'template_name',
        'template_description',
        'times_used',
        'created_by',
        'updated_by',
        'approved_by',
        'project_manager_id',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approval_date' => 'date',
        'start_date' => 'date',
        'target_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'scope_of_work' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            if (empty($job->job_number)) {
                $job->job_number = self::generateJobNumber();
            }
        });

        // P-2.5 invariant: detach-side enforcement of the budget-row guard
        // (no detaching a fund code that a project_budgets row references)
        // lives in HousingJob::detachFundCode() below. The Livewire
        // ProjectManagement::detachFundCode() routes through it. A console /
        // tinker caller that bypasses it via $this->fundCodes()->detach()
        // retains the blind spot — project_budgets.fund_code_id can orphan.
        // If we ever expose pivot mutation outside Livewire (a console
        // command, an admin API, etc.), promote the budget check to a
        // model event on the pivot itself.
    }

    public static function generateJobNumber(): string
    {
        $year = date('Y');
        $prefix = "JOB-{$year}-";

        // Highest existing sequence for the year. Filter to the JOB-YYYY-
        // pattern (zero-padded, so it sorts lexically) so that templates
        // ("TEMPLATE-…") or any legacy-format numbers don't slip in as the
        // "last job" and reset the counter to 00001 — which collides with
        // the existing JOB-YYYY-00001 and throws a unique-key violation.
        $lastNumber = self::where('job_number', 'like', $prefix.'%')
            ->orderBy('job_number', 'desc')
            ->value('job_number');

        $next = ($lastNumber && preg_match('/^JOB-'.$year.'-(\d+)$/', $lastNumber, $matches))
            ? (int) $matches[1] + 1
            : 1;

        // Defensively advance past any already-taken number (gaps from
        // deletes, non-sequential historical data, or a concurrent insert)
        // so we never hand back a duplicate.
        do {
            $candidate = $prefix.str_pad($next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (self::where('job_number', $candidate)->exists());

        return $candidate;
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function jobType(): BelongsTo
    {
        return $this->belongsTo(ReferenceData::class, 'job_type_id');
    }

    public function contactLedgers(): HasMany
    {
        return $this->hasMany(JobContactLedger::class);
    }

    public function documentChecklists(): HasMany
    {
        return $this->hasMany(JobDocumentChecklist::class);
    }

    public function pendingApprovals(): MorphMany
    {
        return $this->morphMany(PendingApproval::class, 'approvable');
    }

    public function approvalHistory(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    // Project Management Relationships

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('sequence_order');
    }

    /**
     * Budget categories for this project.
     *
     * The Housing budget module is a standalone tracker — see the class
     * docblock on ProjectBudget. It is intentionally NOT integrated with
     * Sage Intacct, MIP, DonorPerfect, or any other fiscal system. Do
     * not add fiscal-system writes, reads, or syncs to budget code.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class);
    }

    /**
     * Expense entries against this project's budget categories.
     * See class docblock on ProjectExpense — standalone tracker only.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class);
    }

    public function projectContacts(): HasMany
    {
        return $this->hasMany(ProjectContact::class);
    }

    public function primaryContact()
    {
        return $this->hasOne(ProjectContact::class)->where('is_primary', true);
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class, 'housing_job_id');
    }

    public function originatingCommunicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class, 'converted_to_job_id');
    }

    /**
     * Fund codes directly assigned to this project. P-08: replaces the
     * Phase 1 derived-from-comm-log accessor.
     */
    public function fundCodes(): BelongsToMany
    {
        return $this->belongsToMany(FundCode::class, 'fund_code_housing_job')
            ->withTimestamps();
    }

    /**
     * Comm Log v2 / Convert-to-Project: the counties (HousingService
     * catalog entries, relabeled "County" in the UI) this project serves.
     * Populated at conversion from the originating Communication Log's
     * selected counties; the same county selection drives the fund codes
     * and, through them, the Phase 2 project requirements.
     */
    public function housingServices(): BelongsToMany
    {
        return $this->belongsToMany(
            HousingService::class,
            'housing_job_housing_service',
            'housing_job_id',
            'housing_service_id'
        )->withPivot('created_by_user_id', 'created_at');
    }

    /**
     * P-18 (Phase 3A): per-project requirements across all sources
     * (milestone, fund code, ad-hoc).
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class);
    }

    /**
     * Dashboard split: users who have pinned this project to their
     * Projects dashboard. Parallel to CommunicationLog::pinnedByUsers.
     */
    public function pinnedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'housing_job_user_pins')
            ->withPivot('pinned_at')
            ->withTimestamps();
    }

    public function isPinnedBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->pinnedByUsers()->where('users.id', $user->id)->exists();
    }

    /**
     * P-2.5: returns the budget category names on this project that
     * reference the given fund code. An empty array means a detach is
     * allowed; a non-empty array enumerates the rows blocking it. The
     * Phase 3A lifecycle prompt uses this to decide whether to surface
     * the keep/delete-requirements modal or a hard-stop error first.
     *
     * @return array<int, string>
     */
    public function budgetCategoriesReferencingFundCode(int $fundCodeId): array
    {
        return ProjectBudget::where('housing_job_id', $this->id)
            ->where('fund_code_id', $fundCodeId)
            ->pluck('category')
            ->all();
    }

    /**
     * Routes detach through this method to enforce the P-2.5 invariant:
     * a fund code cannot be detached if any project_budgets row references
     * it. Throws \InvalidArgumentException listing the affected category
     * names.
     *
     * @throws \InvalidArgumentException when one or more budget rows
     *                                   on this project reference the fund code being detached.
     */
    public function detachFundCode(int $id): void
    {
        $referencingCategories = $this->budgetCategoriesReferencingFundCode($id);

        if (! empty($referencingCategories)) {
            $list = '"'.implode('", "', $referencingCategories).'"';
            $noun = count($referencingCategories) === 1 ? 'category' : 'categories';
            throw new \InvalidArgumentException(
                "Cannot detach fund code {$id} from housing job {$this->id}: "
                ."referenced by budget {$noun} {$list}. Re-assign or delete "
                .'those rows first.'
            );
        }

        $this->fundCodes()->detach($id);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'approved' => 'blue',
            'in_progress' => 'indigo',
            'on_hold' => 'orange',
            'completed' => 'green',
            'cancelled' => 'red',
            'rejected' => 'gray',
            default => 'gray',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'gray',
            'medium' => 'blue',
            'high' => 'orange',
            'emergency' => 'red',
            default => 'gray',
        };
    }

    public function isOverdue(): bool
    {
        return $this->target_completion_date
            && $this->target_completion_date->isPast()
            && ! in_array($this->status, ['completed', 'cancelled', 'rejected']);
    }

    public function getDaysOverdueAttribute(): ?int
    {
        if (! $this->isOverdue()) {
            return null;
        }

        return $this->target_completion_date->diffInDays(now());
    }

    public function getRequiredDocumentsCount(): int
    {
        return $this->documentChecklists()->where('is_required', true)->count();
    }

    public function getUploadedDocumentsCount(): int
    {
        return $this->documentChecklists()
            ->where('is_required', true)
            ->whereIn('status', ['uploaded', 'verified'])
            ->count();
    }

    public function areAllRequiredDocumentsUploaded(): bool
    {
        return $this->getRequiredDocumentsCount() === $this->getUploadedDocumentsCount();
    }

    /**
     * Check if this job has pending approvals
     */
    public function hasPendingApprovals(): bool
    {
        $approvalService = app(MilestoneApprovalService::class);
        $progress = $approvalService->getApprovalProgress($this);

        return $progress['overall_pending'] > 0;
    }

    /**
     * Check if this job can be completed (all approvals satisfied)
     */
    public function canComplete(): bool
    {
        $approvalService = app(MilestoneApprovalService::class);

        return $approvalService->canCompleteJob($this);
    }

    /**
     * Get approval progress attribute
     */
    public function getApprovalProgressAttribute(): array
    {
        $approvalService = app(MilestoneApprovalService::class);

        return $approvalService->getApprovalProgress($this);
    }

    /**
     * Scope to eager load approval counts
     */
    public function scopeWithApprovalCounts($query)
    {
        return $query->with([
            'documentChecklists' => function ($q) {
                $q->where('requires_approval', true);
            },
            'property.documents' => function ($q) {
                $q->where('requires_approval', true);
            },
            'contractor.documents' => function ($q) {
                $q->where('requires_approval', true);
            },
        ]);
    }

    // Project Management Methods

    public function getCurrentMilestone(): ?ProjectMilestone
    {
        return $this->milestones()
            ->where('status', 'in_progress')
            ->orWhere(function ($query) {
                $query->where('status', 'not_started')
                    ->whereRaw('sequence_order = (SELECT MIN(sequence_order) FROM project_milestones WHERE housing_job_id = ? AND status = "not_started")', [$this->id]);
            })
            ->orderBy('sequence_order')
            ->first();
    }

    public function getLastCompletedMilestone(): ?ProjectMilestone
    {
        return $this->milestones()
            ->where('status', 'completed')
            ->orderBy('sequence_order', 'desc')
            ->first();
    }

    public function getNextDeadline(): ?string
    {
        $currentMilestone = $this->getCurrentMilestone();

        if (! $currentMilestone) {
            return null;
        }

        return $currentMilestone->getNextDeadline();
    }

    public function getTotalBudget(): float
    {
        return (float) $this->budgets()->sum('budgeted_amount');
    }

    public function getTotalSpent(): float
    {
        return (float) $this->budgets()->sum('spent_amount');
    }

    public function getBudgetStatus(): string
    {
        $budget = $this->getTotalBudget();
        $spent = $this->getTotalSpent();

        if ($budget == 0) {
            return 'no_budget';
        }

        $percentage = ($spent / $budget) * 100;

        if ($percentage > 100) {
            return 'over_budget';
        } elseif ($percentage > 90) {
            return 'at_risk';
        } else {
            return 'on_track';
        }
    }

    // Template Management Methods

    public function saveAsTemplate(string $name, ?string $description = null): self
    {
        DB::beginTransaction();

        try {
            // Clone job as template
            $template = $this->replicate([
                'job_number', 'participant_id', 'household_id', 'property_id',
                'contractor_id', 'status', 'request_date', 'approval_date',
                'start_date', 'target_completion_date', 'actual_completion_date',
                'approved_amount', 'actual_cost', 'created_by', 'updated_by',
                'approved_by',
            ]);

            $template->is_template = true;
            $template->template_name = $name;
            $template->template_description = $description;
            $template->job_number = 'TEMPLATE-'.\Illuminate\Support\Str::uuid();
            $template->status = 'pending';
            $template->created_by = Auth::id();

            // Explicitly set these to null for templates (templates are reusable patterns, not tied to specific entities)
            $template->participant_id = null;
            $template->household_id = null;
            $template->property_id = null;
            $template->contractor_id = null;
            $template->program_id = null;
            $template->request_date = null;

            $template->save();

            // Clone milestones
            foreach ($this->milestones as $milestone) {
                $newMilestone = $milestone->replicate([
                    'housing_job_id', 'status', 'target_date', 'completed_date',
                    'created_by', 'updated_by',
                ]);
                $newMilestone->housing_job_id = $template->id;
                $newMilestone->status = 'not_started';
                $newMilestone->created_by = Auth::id();
                $newMilestone->save();

                // Clone tasks (structure only, no assignments/dates/status)
                foreach ($milestone->tasks as $task) {
                    $newTask = $task->replicate([
                        'milestone_id', 'status', 'assigned_to', 'deadline',
                        'completed_date', 'approved_by', 'approved_at',
                        'created_by', 'updated_by',
                    ]);
                    $newTask->milestone_id = $newMilestone->id;
                    $newTask->status = 'pending';
                    $newTask->created_by = Auth::id();
                    $newTask->save();
                }

                // Clone documents (structure only, not files)
                foreach ($milestone->documents as $doc) {
                    $newDoc = $doc->replicate([
                        'milestone_id', 'status', 'assigned_to', 'uploaded_date',
                        'reviewed_date', 'approved_by', 'approved_at',
                        'rejection_reason', 'created_by', 'updated_by',
                    ]);
                    $newDoc->milestone_id = $newMilestone->id;
                    $newDoc->status = 'not_uploaded';
                    $newDoc->created_by = Auth::id();
                    $newDoc->save();
                    // Note: Media files are NOT copied
                }
            }

            // Clone budget categories (as starting point with $0 spent)
            foreach ($this->budgets as $budget) {
                $newBudget = $budget->replicate([
                    'housing_job_id', 'spent_amount', 'created_by', 'updated_by',
                ]);
                $newBudget->housing_job_id = $template->id;
                $newBudget->spent_amount = 0;
                $newBudget->created_by = Auth::id();
                $newBudget->save();
            }

            DB::commit();

            return $template;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function createFromTemplate(self $template, array $jobData): self
    {
        DB::beginTransaction();

        try {
            // Create new job from template. Override project_manager_id
            // unconditionally — template rows may carry a stale value
            // (the user who saved the template), and the conceptual
            // model treats the instantiating user as the new project's
            // owner. P-18.
            $job = new self;
            $job->fill($jobData);
            $job->is_template = false;
            $job->created_by = Auth::id();
            $job->project_manager_id = Auth::id();
            $job->save();

            // Clone template's milestone structure
            $milestones = ProjectMilestone::where('housing_job_id', $template->id)
                ->orderBy('sequence_order')
                ->get();

            foreach ($milestones as $milestone) {
                $newMilestone = $milestone->replicate([
                    'housing_job_id', 'status', 'target_date', 'completed_date',
                    'created_by', 'updated_by',
                ]);
                $newMilestone->housing_job_id = $job->id;
                $newMilestone->status = $newMilestone->sequence_order == 1 ? 'in_progress' : 'not_started';
                $newMilestone->created_by = Auth::id();
                $newMilestone->save();

                // Clone legacy tasks (write-vestigial — kept until the
                // legacy tables are retired in a later phase).
                $tasks = MilestoneTask::where('milestone_id', $milestone->id)->get();
                foreach ($tasks as $task) {
                    $newTask = $task->replicate([
                        'milestone_id', 'status', 'assigned_to', 'deadline',
                        'completed_date', 'approved_by', 'approved_at',
                        'created_by', 'updated_by',
                    ]);
                    $newTask->milestone_id = $newMilestone->id;
                    $newTask->status = 'pending';
                    $newTask->created_by = Auth::id();
                    $newTask->save();
                }

                // Clone legacy documents (vestigial; see above).
                $documents = MilestoneDocument::where('milestone_id', $milestone->id)->get();
                foreach ($documents as $doc) {
                    $newDoc = $doc->replicate([
                        'milestone_id', 'status', 'assigned_to', 'uploaded_date',
                        'reviewed_date', 'approved_by', 'approved_at',
                        'rejection_reason', 'created_by', 'updated_by',
                    ]);
                    $newDoc->milestone_id = $newMilestone->id;
                    $newDoc->status = 'not_uploaded';
                    $newDoc->created_by = Auth::id();
                    $newDoc->save();
                }

                // P-18: clone milestone-source requirements from the
                // template to the new project. Remap source_id to the
                // newly-created milestone, reset status/owner/completion
                // so the new project lands clean. Spatie media on the
                // template's requirement rows is intentionally NOT
                // copied (consistent with legacy clone behaviour: files
                // do not carry over).
                $templateRequirements = Requirement::query()
                    ->where('housing_job_id', $template->id)
                    ->where('source_type', Requirement::SOURCE_MILESTONE)
                    ->where('source_id', $milestone->id)
                    ->get();

                foreach ($templateRequirements as $tplReq) {
                    Requirement::create([
                        'housing_job_id' => $job->id,
                        'type' => $tplReq->type,
                        'title' => $tplReq->title,
                        'description' => $tplReq->description,
                        'status' => Requirement::STATUS_OUTSTANDING,
                        'owner_user_id' => Auth::id(),
                        'source_type' => Requirement::SOURCE_MILESTONE,
                        'source_id' => $newMilestone->id,
                        'source_template_id' => null,
                        'source_label_snapshot' => $tplReq->title,
                        'milestone_gate_text' => $tplReq->milestone_gate_text,
                        'completed_at' => null,
                        'completed_by_user_id' => null,
                        'completion_note' => null,
                        'override_reason' => null,
                        'overridden_by_user_id' => null,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }

            // Clone budget categories
            $budgets = ProjectBudget::where('housing_job_id', $template->id)->get();
            foreach ($budgets as $budget) {
                $newBudget = $budget->replicate([
                    'housing_job_id', 'spent_amount', 'created_by', 'updated_by',
                ]);
                $newBudget->housing_job_id = $job->id;
                $newBudget->spent_amount = 0;
                $newBudget->created_by = Auth::id();
                $newBudget->save();
            }

            // Increment template usage counter
            $template->increment('times_used');

            DB::commit();

            return $job;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if the assigned participant is head of household
     */
    public function participantIsHeadOfHousehold(): bool
    {
        return $this->participant && $this->participant->is_head_of_household;
    }

    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeActiveJobs($query)
    {
        return $query->where('is_template', false);
    }
}
