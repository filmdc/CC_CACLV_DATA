<?php

namespace App\Livewire;

use App\Models\Contractor;
use App\Models\HousingJob;
use App\Models\MilestoneDocument;
use App\Models\MilestoneTask;
use App\Models\Participant;
use App\Models\Program;
use App\Models\ProjectBudget;
use App\Models\ProjectMilestone;
use App\Models\Property;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectReporting extends Component
{
    use WithPagination, WithPermissionChecks;

    // Filter Properties
    public $filterProgram = '';

    public $filterStatus = '';

    public $filterPriority = '';

    public $filterContractor = '';

    public $filterProperty = '';

    public $filterParticipant = '';

    public $filterProjectManager = '';

    public $filterBudgetStatus = '';

    public $filterMilestoneStatus = '';

    public $filterDocumentStatus = '';

    public $filterTaskStatus = '';

    public $dateRangeType = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $search = '';

    // Report View Mode
    public $viewMode = 'summary'; // summary, detailed, chart

    public $groupBy = 'status'; // status, program, contractor, priority, project_manager

    // Metric Toggles
    public $showBudgetMetrics = true;

    public $showTimelineMetrics = true;

    public $showProgressMetrics = true;

    // Export
    public $exportFormat = 'csv';

    protected $queryString = [
        'filterProgram' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterPriority' => ['except' => ''],
        'filterContractor' => ['except' => ''],
        'filterProperty' => ['except' => ''],
        'filterParticipant' => ['except' => ''],
        'filterProjectManager' => ['except' => ''],
        'filterBudgetStatus' => ['except' => ''],
        'viewMode' => ['except' => 'summary'],
        'groupBy' => ['except' => 'status'],
    ];

    public function mount()
    {
        $this->authorizeAction('projects.view');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->filterProgram = '';
        $this->filterStatus = '';
        $this->filterPriority = '';
        $this->filterContractor = '';
        $this->filterProperty = '';
        $this->filterParticipant = '';
        $this->filterProjectManager = '';
        $this->filterBudgetStatus = '';
        $this->filterMilestoneStatus = '';
        $this->filterDocumentStatus = '';
        $this->filterTaskStatus = '';
        $this->dateRangeType = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->search = '';
        $this->resetPage();
    }

    protected function getFilteredQuery()
    {
        return HousingJob::query()
            ->activeJobs()
            ->with([
                'program',
                'participant',
                'property',
                'contractor',
                'projectManager',
                'milestones.tasks',
                'milestones.documents',
                'budgets',
                'expenses',
            ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('job_title', 'like', '%'.$this->search.'%')
                        ->orWhere('job_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('participant', function ($pq) {
                            $pq->where('first_name', 'like', '%'.$this->search.'%')
                                ->orWhere('last_name', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('property', function ($propq) {
                            $propq->where('address_line_1', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->filterProgram, fn ($q) => $q->where('program_id', $this->filterProgram))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterPriority, fn ($q) => $q->where('priority', $this->filterPriority))
            ->when($this->filterContractor, fn ($q) => $q->where('contractor_id', $this->filterContractor))
            ->when($this->filterProperty, fn ($q) => $q->where('property_id', $this->filterProperty))
            ->when($this->filterParticipant, fn ($q) => $q->where('participant_id', $this->filterParticipant))
            ->when($this->filterProjectManager, fn ($q) => $q->where('project_manager_id', $this->filterProjectManager))
            ->when($this->filterBudgetStatus, function ($q) {
                $q->whereHas('budgets', function ($bq) {
                    if ($this->filterBudgetStatus === 'over_budget') {
                        $bq->whereRaw('spent_amount > budgeted_amount');
                    } elseif ($this->filterBudgetStatus === 'at_risk') {
                        $bq->whereRaw('spent_amount > (budgeted_amount * 0.9) AND spent_amount <= budgeted_amount');
                    } elseif ($this->filterBudgetStatus === 'on_track') {
                        $bq->whereRaw('spent_amount <= (budgeted_amount * 0.9)');
                    } elseif ($this->filterBudgetStatus === 'no_budget') {
                        // Projects with no budget entries
                    }
                });

                if ($this->filterBudgetStatus === 'no_budget') {
                    $q->whereDoesntHave('budgets');
                }
            })
            ->when($this->filterMilestoneStatus, function ($q) {
                $q->whereHas('milestones', fn ($mq) => $mq->where('status', $this->filterMilestoneStatus));
            })
            ->when($this->filterTaskStatus, function ($q) {
                $q->whereHas('milestones.tasks', fn ($tq) => $tq->where('status', $this->filterTaskStatus));
            })
            ->when($this->filterDocumentStatus, function ($q) {
                $q->whereHas('milestones.documents', fn ($dq) => $dq->where('status', $this->filterDocumentStatus));
            })
            ->when($this->dateRangeType && $this->dateFrom, function ($q) {
                $dateField = match ($this->dateRangeType) {
                    'request' => 'request_date',
                    'start' => 'start_date',
                    'target' => 'target_completion_date',
                    'completed' => 'actual_completion_date',
                    'created' => 'created_at',
                    default => 'created_at',
                };
                $q->where($dateField, '>=', $this->dateFrom);
            })
            ->when($this->dateRangeType && $this->dateTo, function ($q) {
                $dateField = match ($this->dateRangeType) {
                    'request' => 'request_date',
                    'start' => 'start_date',
                    'target' => 'target_completion_date',
                    'completed' => 'actual_completion_date',
                    'created' => 'created_at',
                    default => 'created_at',
                };
                $q->where($dateField, '<=', $this->dateTo);
            });
    }

    public function getSummaryMetrics(): array
    {
        $baseQuery = $this->getFilteredQuery();

        $totalProjects = (clone $baseQuery)->count();

        // Status distribution
        $statusCounts = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Priority distribution
        $priorityCounts = (clone $baseQuery)
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        // Budget metrics
        $projectIds = (clone $baseQuery)->pluck('id');
        $budgetData = ProjectBudget::whereIn('housing_job_id', $projectIds)
            ->select(
                DB::raw('SUM(budgeted_amount) as total_budget'),
                DB::raw('SUM(spent_amount) as total_spent')
            )
            ->first();

        $totalBudget = (float) ($budgetData->total_budget ?? 0);
        $totalSpent = (float) ($budgetData->total_spent ?? 0);
        $budgetRemaining = $totalBudget - $totalSpent;
        $budgetUtilization = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 1) : 0;

        // Overdue projects
        $overdueCount = (clone $baseQuery)
            ->whereNotNull('target_completion_date')
            ->where('target_completion_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
            ->count();

        // At risk projects (due within 7 days)
        $atRiskCount = (clone $baseQuery)
            ->whereNotNull('target_completion_date')
            ->whereBetween('target_completion_date', [now(), now()->addDays(7)])
            ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
            ->count();

        // Completed this month
        $completedThisMonth = (clone $baseQuery)
            ->where('status', 'completed')
            ->whereMonth('actual_completion_date', now()->month)
            ->whereYear('actual_completion_date', now()->year)
            ->count();

        // Average completion time (for completed projects)
        $avgCompletionDays = (clone $baseQuery)
            ->where('status', 'completed')
            ->whereNotNull('start_date')
            ->whereNotNull('actual_completion_date')
            ->select(DB::raw('AVG(DATEDIFF(actual_completion_date, start_date)) as avg_days'))
            ->value('avg_days');

        return [
            'total_projects' => $totalProjects,
            'status_counts' => $statusCounts,
            'priority_counts' => $priorityCounts,
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'budget_remaining' => $budgetRemaining,
            'budget_utilization' => $budgetUtilization,
            'overdue_count' => $overdueCount,
            'at_risk_count' => $atRiskCount,
            'completed_this_month' => $completedThisMonth,
            'avg_completion_days' => round($avgCompletionDays ?? 0),
        ];
    }

    public function getMilestoneMetrics(): array
    {
        $projectIds = $this->getFilteredQuery()->pluck('id');

        $totalMilestones = ProjectMilestone::whereIn('housing_job_id', $projectIds)->count();
        $completedMilestones = ProjectMilestone::whereIn('housing_job_id', $projectIds)
            ->where('status', 'completed')
            ->count();
        $inProgressMilestones = ProjectMilestone::whereIn('housing_job_id', $projectIds)
            ->where('status', 'in_progress')
            ->count();
        $overdueMilestones = ProjectMilestone::whereIn('housing_job_id', $projectIds)
            ->whereNotNull('target_date')
            ->where('target_date', '<', now())
            ->where('status', '!=', 'completed')
            ->count();

        $completionRate = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100, 1) : 0;

        return [
            'total' => $totalMilestones,
            'completed' => $completedMilestones,
            'in_progress' => $inProgressMilestones,
            'overdue' => $overdueMilestones,
            'completion_rate' => $completionRate,
        ];
    }

    public function getTaskMetrics(): array
    {
        $projectIds = $this->getFilteredQuery()->pluck('id');
        $milestoneIds = ProjectMilestone::whereIn('housing_job_id', $projectIds)->pluck('id');

        $totalTasks = MilestoneTask::whereIn('milestone_id', $milestoneIds)->count();
        $completedTasks = MilestoneTask::whereIn('milestone_id', $milestoneIds)
            ->where('status', 'completed')
            ->count();
        $pendingTasks = MilestoneTask::whereIn('milestone_id', $milestoneIds)
            ->where('status', 'pending')
            ->count();
        $inProgressTasks = MilestoneTask::whereIn('milestone_id', $milestoneIds)
            ->where('status', 'in_progress')
            ->count();
        $overdueTasks = MilestoneTask::whereIn('milestone_id', $milestoneIds)
            ->whereNotNull('deadline')
            ->where('deadline', '<', now())
            ->where('status', '!=', 'completed')
            ->count();

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        return [
            'total' => $totalTasks,
            'completed' => $completedTasks,
            'pending' => $pendingTasks,
            'in_progress' => $inProgressTasks,
            'overdue' => $overdueTasks,
            'completion_rate' => $completionRate,
        ];
    }

    public function getDocumentMetrics(): array
    {
        $projectIds = $this->getFilteredQuery()->pluck('id');
        $milestoneIds = ProjectMilestone::whereIn('housing_job_id', $projectIds)->pluck('id');

        $totalDocs = MilestoneDocument::whereIn('milestone_id', $milestoneIds)->count();
        $approvedDocs = MilestoneDocument::whereIn('milestone_id', $milestoneIds)
            ->where('status', 'approved')
            ->count();
        $pendingReviewDocs = MilestoneDocument::whereIn('milestone_id', $milestoneIds)
            ->where('status', 'pending_review')
            ->count();
        $notUploadedDocs = MilestoneDocument::whereIn('milestone_id', $milestoneIds)
            ->where('status', 'not_uploaded')
            ->count();
        $rejectedDocs = MilestoneDocument::whereIn('milestone_id', $milestoneIds)
            ->where('status', 'rejected')
            ->count();

        $uploadRate = $totalDocs > 0 ? round((($totalDocs - $notUploadedDocs) / $totalDocs) * 100, 1) : 0;
        $approvalRate = ($totalDocs - $notUploadedDocs) > 0
            ? round(($approvedDocs / ($totalDocs - $notUploadedDocs)) * 100, 1)
            : 0;

        return [
            'total' => $totalDocs,
            'approved' => $approvedDocs,
            'pending_review' => $pendingReviewDocs,
            'not_uploaded' => $notUploadedDocs,
            'rejected' => $rejectedDocs,
            'upload_rate' => $uploadRate,
            'approval_rate' => $approvalRate,
        ];
    }

    public function getBudgetByCategory(): array
    {
        $projectIds = $this->getFilteredQuery()->pluck('id');

        return ProjectBudget::whereIn('housing_job_id', $projectIds)
            ->select(
                'category',
                DB::raw('SUM(budgeted_amount) as total_budgeted'),
                DB::raw('SUM(spent_amount) as total_spent')
            )
            ->groupBy('category')
            ->get()
            ->map(function ($item) {
                $item->remaining = $item->total_budgeted - $item->total_spent;
                $item->utilization = $item->total_budgeted > 0
                    ? round(($item->total_spent / $item->total_budgeted) * 100, 1)
                    : 0;

                return $item;
            })
            ->toArray();
    }

    public function getGroupedMetrics(): array
    {
        $baseQuery = $this->getFilteredQuery();

        $groupField = match ($this->groupBy) {
            'program' => 'program_id',
            'contractor' => 'contractor_id',
            'priority' => 'priority',
            'project_manager' => 'project_manager_id',
            default => 'status',
        };

        $grouped = (clone $baseQuery)
            ->select(
                $groupField,
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count'),
                DB::raw('SUM(estimated_cost) as total_estimated'),
                DB::raw('SUM(actual_cost) as total_actual')
            )
            ->groupBy($groupField)
            ->get();

        // Enhance with related data
        return $grouped->map(function ($item) use ($groupField) {
            $label = match ($groupField) {
                'program_id' => Program::find($item->program_id)?->name ?? 'No Program',
                'contractor_id' => Contractor::find($item->contractor_id)?->company_name ?? 'No Contractor',
                'project_manager_id' => User::find($item->project_manager_id)?->name ?? 'Unassigned',
                'priority' => ucfirst($item->priority ?? 'Unknown'),
                default => ucfirst(str_replace('_', ' ', $item->status ?? 'Unknown')),
            };

            $item->label = $label;
            $item->completion_rate = $item->total_count > 0
                ? round(($item->completed_count / $item->total_count) * 100, 1)
                : 0;

            return $item;
        })->toArray();
    }

    public function getContractorPerformance(): array
    {
        $projectIds = $this->getFilteredQuery()->pluck('id');

        return HousingJob::whereIn('id', $projectIds)
            ->whereNotNull('contractor_id')
            ->with('contractor')
            ->select(
                'contractor_id',
                DB::raw('COUNT(*) as total_projects'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_projects'),
                DB::raw('SUM(actual_cost) as total_actual_cost'),
                DB::raw('AVG(CASE WHEN status = "completed" AND start_date IS NOT NULL AND actual_completion_date IS NOT NULL
                         THEN DATEDIFF(actual_completion_date, start_date) ELSE NULL END) as avg_completion_days')
            )
            ->groupBy('contractor_id')
            ->orderByDesc('total_projects')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $contractor = Contractor::find($item->contractor_id);
                $item->contractor_name = $contractor?->company_name ?? 'Unknown';
                $item->completion_rate = $item->total_projects > 0
                    ? round(($item->completed_projects / $item->total_projects) * 100, 1)
                    : 0;
                $item->avg_completion_days = round($item->avg_completion_days ?? 0);

                return $item;
            })
            ->toArray();
    }

    public function getProjectManagerPerformance(): array
    {
        $projectIds = $this->getFilteredQuery()->pluck('id');

        return HousingJob::whereIn('id', $projectIds)
            ->whereNotNull('project_manager_id')
            ->select(
                'project_manager_id',
                DB::raw('COUNT(*) as total_projects'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_projects'),
                DB::raw('SUM(CASE WHEN target_completion_date < NOW() AND status NOT IN ("completed", "cancelled", "rejected") THEN 1 ELSE 0 END) as overdue_projects')
            )
            ->groupBy('project_manager_id')
            ->orderByDesc('total_projects')
            ->get()
            ->map(function ($item) {
                $user = User::find($item->project_manager_id);
                $item->manager_name = $user?->name ?? 'Unknown';
                $item->completion_rate = $item->total_projects > 0
                    ? round(($item->completed_projects / $item->total_projects) * 100, 1)
                    : 0;

                return $item;
            })
            ->toArray();
    }

    public function exportReport()
    {
        $projects = $this->getFilteredQuery()
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'project_report_'.now()->format('Y-m-d_His').'.'.$this->exportFormat;

        if ($this->exportFormat === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($projects) {
                $file = fopen('php://output', 'w');

                // Header row
                fputcsv($file, [
                    'Job Number',
                    'Title',
                    'Status',
                    'Priority',
                    'Program',
                    'Participant',
                    'Property Address',
                    'Contractor',
                    'Project Manager',
                    'Request Date',
                    'Start Date',
                    'Target Completion',
                    'Actual Completion',
                    'Estimated Cost',
                    'Actual Cost',
                    'Total Budget',
                    'Total Spent',
                    'Budget Status',
                    'Milestones',
                    'Completed Milestones',
                    'Tasks',
                    'Completed Tasks',
                    'Documents',
                    'Approved Documents',
                ]);

                foreach ($projects as $project) {
                    $totalMilestones = $project->milestones->count();
                    $completedMilestones = $project->milestones->where('status', 'completed')->count();
                    $totalTasks = $project->milestones->flatMap->tasks->count();
                    $completedTasks = $project->milestones->flatMap->tasks->where('status', 'completed')->count();
                    $totalDocs = $project->milestones->flatMap->documents->count();
                    $approvedDocs = $project->milestones->flatMap->documents->where('status', 'approved')->count();

                    fputcsv($file, [
                        $project->job_number,
                        $project->job_title,
                        ucfirst($project->status),
                        ucfirst($project->priority),
                        $project->program?->name ?? 'N/A',
                        $project->participant ? $project->participant->first_name.' '.$project->participant->last_name : 'N/A',
                        $project->property?->address_line_1 ?? 'N/A',
                        $project->contractor?->company_name ?? 'N/A',
                        $project->projectManager?->name ?? 'Unassigned',
                        $project->request_date?->format('Y-m-d') ?? 'N/A',
                        $project->start_date?->format('Y-m-d') ?? 'N/A',
                        $project->target_completion_date?->format('Y-m-d') ?? 'N/A',
                        $project->actual_completion_date?->format('Y-m-d') ?? 'N/A',
                        $project->estimated_cost ?? 0,
                        $project->actual_cost ?? 0,
                        $project->getTotalBudget(),
                        $project->getTotalSpent(),
                        ucfirst(str_replace('_', ' ', $project->getBudgetStatus())),
                        $totalMilestones,
                        $completedMilestones,
                        $totalTasks,
                        $completedTasks,
                        $totalDocs,
                        $approvedDocs,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        session()->flash('error', 'Export format not supported');
    }

    public function render()
    {
        $summaryMetrics = $this->getSummaryMetrics();
        $milestoneMetrics = $this->getMilestoneMetrics();
        $taskMetrics = $this->getTaskMetrics();
        $documentMetrics = $this->getDocumentMetrics();
        $budgetByCategory = $this->getBudgetByCategory();
        $groupedMetrics = $this->getGroupedMetrics();
        $contractorPerformance = $this->getContractorPerformance();
        $projectManagerPerformance = $this->getProjectManagerPerformance();

        $projects = $this->getFilteredQuery()
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('livewire.project-reporting', [
            'projects' => $projects,
            'summaryMetrics' => $summaryMetrics,
            'milestoneMetrics' => $milestoneMetrics,
            'taskMetrics' => $taskMetrics,
            'documentMetrics' => $documentMetrics,
            'budgetByCategory' => $budgetByCategory,
            'groupedMetrics' => $groupedMetrics,
            'contractorPerformance' => $contractorPerformance,
            'projectManagerPerformance' => $projectManagerPerformance,
            'programs' => Program::orderBy('name')->get(),
            'contractors' => Contractor::orderBy('company_name')->get(),
            'properties' => Property::orderBy('address_line_1')->get(),
            'participants' => Participant::where('is_head_of_household', true)
                ->orderBy('last_name')
                ->get(),
            'projectManagers' => User::orderBy('name')->get(),
            'statuses' => ['pending', 'approved', 'in_progress', 'on_hold', 'completed', 'cancelled', 'rejected'],
            'priorities' => ['low', 'medium', 'high', 'emergency'],
            'budgetStatuses' => [
                'on_track' => 'On Track',
                'at_risk' => 'At Risk (>90%)',
                'over_budget' => 'Over Budget',
                'no_budget' => 'No Budget Set',
            ],
            'milestoneStatuses' => ['not_started', 'in_progress', 'completed'],
            'taskStatuses' => ['pending', 'in_progress', 'completed', 'overdue'],
            'documentStatuses' => ['not_uploaded', 'uploaded', 'pending_review', 'approved', 'rejected'],
            'dateRangeTypes' => [
                'request' => 'Request Date',
                'start' => 'Start Date',
                'target' => 'Target Completion',
                'completed' => 'Actual Completion',
                'created' => 'Created Date',
            ],
            'groupByOptions' => [
                'status' => 'Status',
                'program' => 'Program',
                'contractor' => 'Contractor',
                'priority' => 'Priority',
                'project_manager' => 'Project Manager',
            ],
        ]);
    }
}
