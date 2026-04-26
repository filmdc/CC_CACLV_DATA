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
        'primary_fund_code_id',
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

        // P-08 invariant: when primary_fund_code_id is set, the same fund
        // code must already be in the fund_code_housing_job pivot for this
        // project. Catches mass-assignment / tinker / console paths that
        // bypass the setPrimaryFundCode() helper.
        //
        // NOTE: detach-side enforcement is NOT in this model. If a code is
        // detached via $job->fundCodes()->detach() without going through
        // the Livewire ProjectManagement::detachFundCode() method,
        // primary_fund_code_id can dangle until the next save. The
        // Livewire layer is the only mutation path in Phase 2 so this is
        // acceptable; if a future console command or admin API exposes
        // pivot mutation, promote the detach enforcement here.
        static::saving(function ($job) {
            if (! $job->isDirty('primary_fund_code_id')) {
                return;
            }
            if ($job->primary_fund_code_id === null) {
                return;
            }

            $attached = DB::table('fund_code_housing_job')
                ->where('housing_job_id', $job->id)
                ->where('fund_code_id', $job->primary_fund_code_id)
                ->exists();

            if (! $attached) {
                $jobLabel = $job->id ?? 'new';
                throw new \InvalidArgumentException(
                    "Cannot set primary_fund_code_id={$job->primary_fund_code_id} on housing job {$jobLabel}: that fund code is not attached to the project. Use HousingJob::setPrimaryFundCode() to attach-and-set safely, or attach the fund code via fundCodes()->attach() first."
                );
            }
        });
    }

    public static function generateJobNumber(): string
    {
        $year = date('Y');
        $lastJob = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastJob && preg_match('/JOB-'.$year.'-(\d+)/', $lastJob->job_number, $matches)) {
            $sequence = str_pad((int) $matches[1] + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $sequence = '00001';
        }

        return "JOB-{$year}-{$sequence}";
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
     * The single fund code (if any) flagged as primary for this project.
     * The saving-event guard above ensures this can only be set to a code
     * that is also in the fundCodes() pivot.
     */
    public function primaryFundCode(): BelongsTo
    {
        return $this->belongsTo(FundCode::class, 'primary_fund_code_id');
    }

    /**
     * Attach (idempotent) and mark a fund code as primary in one shot.
     * Use this from any code path that mutates the primary FK; bypassing
     * it via direct property assignment will hit the saving-event guard.
     */
    public function setPrimaryFundCode(FundCode $code): void
    {
        $this->fundCodes()->syncWithoutDetaching([$code->id]);
        $this->primary_fund_code_id = $code->id;
        $this->save();
    }

    /**
     * Fund codes attached to any communication log that references this job
     * (either via housing_job_id or converted_to_job_id), deduped by id.
     * Phase 1 surfaces what already exists; Phase 2 (P-05) will introduce a
     * managed project↔fund_code link.
     */
    public function getDerivedFundCodesAttribute()
    {
        return $this->communicationLogs
            ->merge($this->originatingCommunicationLogs)
            ->flatMap->fundCodes
            ->unique('id')
            ->values();
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
            // Create new job from template
            $job = new self;
            $job->fill($jobData);
            $job->is_template = false;
            $job->created_by = Auth::id();
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

                // Clone tasks
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

                // Clone documents
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
