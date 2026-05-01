<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractReport;
use App\Models\ContractReportDueDate;
use App\Models\CustomImportTemplate;
use App\Models\Household;
use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use App\Models\Service;
use App\Models\ServiceEnrollment;
use App\Models\WorkPlan;
use App\Models\WorkPlanGoal;
use App\Services\CsbgReportService;
use App\Traits\WithPermissionChecks;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    use WithPermissionChecks;

    public $activeTab = 'programs';

    public $expandedWorkPlans = false;

    // Date filtering
    public $startDate;

    public $endDate;

    public $dateFilter = 'this_month'; // this_month, last_month, this_quarter, this_year, custom

    // CSBG Report Options
    public $selectedProgramForReport;

    public $reportFormat = 'pdf'; // pdf or excel

    // Protected listeners for real-time updates
    protected $listeners = [
        'refreshDashboard' => '$refresh',
        'participantUpdated' => 'refreshMetrics',
        'enrollmentUpdated' => 'refreshMetrics',
        'workPlanUpdated' => 'refreshMetrics',
        'programUpdated' => 'refreshMetrics',
    ];

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->expandedWorkPlans = false; // Reset expanded state when switching tabs
        $this->refreshMetrics(); // Refresh data when switching tabs
    }

    public function refreshMetrics()
    {
        // Force component refresh to recalculate metrics
        $this->dispatch('$refresh');
    }

    public function mount()
    {
        // Initialize date filters
        $this->setDateFilter('this_month');

        // Set default program for report if user has programs
        $user = Auth::user();
        $userPrograms = $user->programs()->first();
        if ($userPrograms) {
            $this->selectedProgramForReport = $userPrograms->id;
        }
    }

    public function toggleExpandedWorkPlans()
    {
        $this->expandedWorkPlans = ! $this->expandedWorkPlans;
    }

    public function viewProgram($programId)
    {
        // For now, we'll stay on dashboard but could redirect to program details
        session()->flash('message', 'Program details view - to be implemented');
    }

    public function viewParticipant($participantId)
    {
        return redirect()->route('participant.show', ['id' => $participantId]);
    }

    public function viewWorkPlan($workPlanId)
    {
        return redirect()->route('work-plan.management', ['work_plan_id' => $workPlanId]);
    }

    public function viewReportDueDate($dueDateId)
    {
        // For now, redirect to the contract report due dates management page
        return redirect()->route('contract-report-due-dates.management');
    }

    public function viewReport($reportId)
    {
        // For now, redirect to the contracts hub
        return redirect()->route('contracts.hub');
    }

    public function setDateFilter($filter)
    {
        $this->dateFilter = $filter;

        switch ($filter) {
            case 'this_month':
                $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->startDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'this_quarter':
                $this->startDate = Carbon::now()->firstOfQuarter()->format('Y-m-d');
                $this->endDate = Carbon::now()->lastOfQuarter()->format('Y-m-d');
                break;
            case 'this_year':
                $this->startDate = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->endDate = Carbon::now()->endOfYear()->format('Y-m-d');
                break;
            case 'custom':
                // Keep existing dates
                break;
        }

        $this->refreshMetrics();
    }

    public function generateCsbgReport()
    {
        if (! $this->selectedProgramForReport) {
            session()->flash('error', 'Please select a program for the report.');

            return;
        }

        try {
            $program = Program::findOrFail($this->selectedProgramForReport);

            // Initialize the CSBG Report Service
            $reportService = app(CsbgReportService::class);

            // Generate the report data
            $reportData = $reportService->generateReport([
                'program_id' => $this->selectedProgramForReport,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ]);

            // Generate filename
            $filename = 'CSBG_Report_'.$program->name.'_'.date('Y-m-d').'.'.$this->reportFormat;
            $filename = str_replace(' ', '_', $filename);

            if ($this->reportFormat === 'pdf') {
                // Generate PDF using the same format as CsbgReportService
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.csbg-pdf-template', [
                    'reportData' => $reportData,
                    'generatedAt' => Carbon::parse($reportData['report_metadata']['generated_at'])->format('F j, Y g:i A'),
                    'periodStart' => Carbon::parse($reportData['report_metadata']['period_start'])->format('F j, Y'),
                    'periodEnd' => Carbon::parse($reportData['report_metadata']['period_end'])->format('F j, Y'),
                ])
                    ->setPaper('a4', 'portrait')
                    ->setOptions([
                        'defaultFont' => 'sans-serif',
                        'isRemoteEnabled' => false,
                        'chroot' => public_path(),
                    ]);

                return response()->streamDownload(function () use ($pdf) {
                    echo $pdf->output();
                }, $filename);
            } else {
                // Generate CSV (Excel-compatible)
                $csvContent = $this->generateCsvContent($reportData, $program);

                return response()->streamDownload(function () use ($csvContent) {
                    echo $csvContent;
                }, str_replace('.excel', '.csv', $filename), [
                    'Content-Type' => 'text/csv',
                    'Content-Encoding' => 'UTF-8',
                ]);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error generating report: '.$e->getMessage());
        }
    }

    private function generateCsvContent($reportData, $program)
    {
        $csv = [];

        // Header
        $csv[] = ['CSBG Module 4, Section C: All Characteristics Report'];
        $csv[] = [$program->name];
        $csv[] = ['Report Period: '.date('m/d/Y', strtotime($reportData['report_metadata']['period_start'])).' - '.date('m/d/Y', strtotime($reportData['report_metadata']['period_end']))];
        $csv[] = [];

        // Summary
        $csv[] = ['A. Total unduplicated number of all INDIVIDUALS:', $reportData['participant_count'] ?? 0];
        $csv[] = ['B. Total unduplicated number of all HOUSEHOLDS:', count($reportData['households'] ?? [])];
        $csv[] = [];

        // Process each section
        $sections = [
            'demographics' => 'DEMOGRAPHICS',
            'characteristics' => 'CHARACTERISTICS',
            'employment_income' => 'EMPLOYMENT & INCOME',
            'health_housing' => 'HEALTH & HOUSING',
        ];

        foreach ($sections as $sectionKey => $sectionTitle) {
            if (isset($reportData[$sectionKey])) {
                $csv[] = [$sectionTitle];
                $csv[] = [];

                foreach ($reportData[$sectionKey] as $category => $categoryData) {
                    $csv[] = [ucfirst(str_replace('_', ' ', $category))];

                    if (isset($categoryData['items'])) {
                        foreach ($categoryData['items'] as $item) {
                            $csv[] = ['', $item['label'], $item['count'], $item['percentage'].'%'];
                        }
                        $csv[] = ['', 'TOTAL', $categoryData['total'] ?? ''];
                    } else {
                        // Handle flat arrays (like gender, ethnicity, etc.)
                        foreach ($categoryData as $label => $count) {
                            if ($label !== 'total') {
                                $csv[] = ['', $label, $count];
                            }
                        }
                        $csv[] = ['', 'TOTAL', array_sum($categoryData)];
                    }
                    $csv[] = [];
                }
            }
        }

        // Convert to CSV string
        $output = '';
        foreach ($csv as $row) {
            $output .= $this->arrayToCsv($row)."\n";
        }

        return $output;
    }

    private function arrayToCsv($array)
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $array);
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return rtrim($csv);
    }

    public function startImport($templateId)
    {
        // Redirect to import page with template
        return redirect()->route('participant.import-export', ['template_id' => $templateId]);
    }

    public function render()
    {
        $user = Auth::user();

        // Check if user has contract database dashboard role
        if ($user->hasRole('contract-database-dashboard')) {
            return $this->renderContractDashboard($user);
        }

        // P-17: housing users land on the housing dashboard at the same
        // /dashboard URL feel via redirect. Predicate: has projects.view
        // (the housing-module read permission) and is not a contract-
        // dashboard user (handled above). Admins fall through to the
        // existing programs default unless they also hold projects.view,
        // which most admins do.
        if ($user->hasPermission('projects.view')) {
            return redirect()->route('dashboard.housing');
        }

        // Get user's assigned programs
        $userPrograms = $user->programs()->with(['services', 'programEnrollments.participant', 'workPlans'])->get();

        // Calculate metrics for user's programs - always fresh from database
        $userProgramIds = $userPrograms->pluck('id')->toArray();

        $metrics = $this->calculateMetrics($userProgramIds);

        // Get recent activity with date filtering
        $recentActivity = $this->getRecentActivity($userProgramIds);

        // Recent activity - participants the user has worked with
        $recentParticipants = Participant::with(['household', 'programEnrollments.program'])
            ->whereHas('programEnrollments', function ($query) use ($userProgramIds) {
                $query->whereIn('program_id', $userProgramIds);
            })
            ->whereBetween('updated_at', [$this->startDate, $this->endDate.' 23:59:59'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // Recent households in user's programs
        $recentHouseholds = Household::with(['headOfHousehold', 'participants.programEnrollments.program'])
            ->whereHas('participants.programEnrollments', function ($query) use ($userProgramIds) {
                $query->whereIn('program_id', $userProgramIds);
            })
            ->whereBetween('updated_at', [$this->startDate, $this->endDate.' 23:59:59'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // Work plan goals for user's programs
        $workPlanGoals = WorkPlanGoal::with(['workPlan.program', 'service'])
            ->whereHas('workPlan', function ($query) use ($userProgramIds) {
                $query->whereIn('program_id', $userProgramIds);
            })
            ->where('target_number', '>', 0)
            ->latest()
            ->limit(5)
            ->get();

        // Program enrollment stats for user's programs
        $programStats = $userPrograms->map(function ($program) {
            return [
                'program' => $program,
                'active_enrollments' => $program->programEnrollments()->active()->count(),
                'total_enrollments' => $program->programEnrollments()->count(),
                'services_count' => $program->services()->where('active', true)->count(),
                'work_plans_count' => $program->workPlans()->where('active', true)->count(),
            ];
        });

        // Get statistics with date filtering
        $statistics = $this->getStatistics($userProgramIds);

        // Get available import templates
        $importTemplates = CustomImportTemplate::active()->get();

        // Get user configuration data
        $userConfig = [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
            'permissions' => $this->getUserPermissions($user),
            'last_login' => null, // No last_login_at field in database
            'programs_count' => $userPrograms->count(),
        ];

        // Load tab-specific data
        $tabData = $this->getTabData($userProgramIds);

        return view('livewire.dashboard', [
            'user' => $user,
            'userConfig' => $userConfig,
            'userPrograms' => $userPrograms,
            'programStats' => $programStats,
            'metrics' => $metrics,
            'statistics' => $statistics,
            'recentActivity' => $recentActivity,
            'recentHouseholds' => $recentHouseholds,
            'recentParticipants' => $recentParticipants,
            'workPlanGoals' => $workPlanGoals,
            'importTemplates' => $importTemplates,
            'tabData' => $tabData,
        ])->layout('components.layouts.app');
    }

    private function calculateMetrics($userProgramIds)
    {
        return [
            'my_programs' => count($userProgramIds),
            'my_participants' => ProgramEnrollment::whereIn('program_id', $userProgramIds)->active()->count(),
            'my_work_plans' => WorkPlan::whereIn('program_id', $userProgramIds)->where('active', true)->count(),
            'my_services' => Service::whereIn('program_id', $userProgramIds)->where('active', true)->count(),
        ];
    }

    private function getRecentActivity($userProgramIds)
    {
        // Get recent enrollments
        $recentEnrollments = ProgramEnrollment::with(['participant', 'program'])
            ->whereIn('program_id', $userProgramIds)
            ->whereBetween('created_at', [$this->startDate, $this->endDate.' 23:59:59'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'type' => 'enrollment',
                    'description' => $enrollment->participant->first_name.' '.$enrollment->participant->last_name.' enrolled in '.$enrollment->program->name,
                    'timestamp' => $enrollment->created_at,
                    'user' => $enrollment->created_by,
                ];
            });

        // Get recent service enrollments
        $recentServiceEnrollments = ServiceEnrollment::with(['participant', 'service.program'])
            ->whereHas('service', function ($query) use ($userProgramIds) {
                $query->whereIn('program_id', $userProgramIds);
            })
            ->whereBetween('created_at', [$this->startDate, $this->endDate.' 23:59:59'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'type' => 'service_enrollment',
                    'description' => $enrollment->participant->first_name.' '.$enrollment->participant->last_name.' enrolled in service: '.$enrollment->service->name,
                    'timestamp' => $enrollment->created_at,
                    'user' => $enrollment->created_by,
                ];
            });

        // Get recent work plan goal updates
        $recentGoalUpdates = WorkPlanGoal::with(['workPlan.program', 'service'])
            ->whereHas('workPlan', function ($query) use ($userProgramIds) {
                $query->whereIn('program_id', $userProgramIds);
            })
            ->whereBetween('updated_at', [$this->startDate, $this->endDate.' 23:59:59'])
            ->where('updated_at', '>', 'created_at')
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(function ($goal) {
                return [
                    'type' => 'goal_update',
                    'description' => 'Work plan goal "'.$goal->goal_name.'" updated: '.$goal->actual_number.'/'.$goal->target_number,
                    'timestamp' => $goal->updated_at,
                    'user' => $goal->updated_by,
                ];
            });

        // Combine and sort all activities
        return collect()
            ->merge($recentEnrollments)
            ->merge($recentServiceEnrollments)
            ->merge($recentGoalUpdates)
            ->sortByDesc('timestamp')
            ->take(15);
    }

    private function getStatistics($userProgramIds)
    {
        // Calculate statistics based on date range
        $stats = [];

        // Total participants in date range
        $stats['total_participants'] = Participant::whereHas('programEnrollments', function ($query) use ($userProgramIds) {
            $query->whereIn('program_id', $userProgramIds)
                ->whereBetween('created_at', [$this->startDate, $this->endDate.' 23:59:59']);
        })->count();

        // Active enrollments
        $stats['active_enrollments'] = ProgramEnrollment::whereIn('program_id', $userProgramIds)
            ->where('status', 'active')
            ->count();

        // New enrollments in period
        $stats['new_enrollments'] = ProgramEnrollment::whereIn('program_id', $userProgramIds)
            ->whereBetween('created_at', [$this->startDate, $this->endDate.' 23:59:59'])
            ->count();

        // Services delivered
        $stats['services_delivered'] = ServiceEnrollment::whereHas('service', function ($query) use ($userProgramIds) {
            $query->whereIn('program_id', $userProgramIds);
        })
            ->whereBetween('enrollment_date', [$this->startDate, $this->endDate])
            ->count();

        // Work plan goal completion rate
        $totalGoals = WorkPlanGoal::whereHas('workPlan', function ($query) use ($userProgramIds) {
            $query->whereIn('program_id', $userProgramIds);
        })
            ->where('target_number', '>', 0)
            ->count();

        $completedGoals = WorkPlanGoal::whereHas('workPlan', function ($query) use ($userProgramIds) {
            $query->whereIn('program_id', $userProgramIds);
        })
            ->where('target_number', '>', 0)
            ->whereColumn('actual_number', '>=', 'target_number')
            ->count();

        $stats['goal_completion_rate'] = $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100, 1) : 0;

        // Households served
        $stats['households_served'] = Household::whereHas('participants.programEnrollments', function ($query) use ($userProgramIds) {
            $query->whereIn('program_id', $userProgramIds)
                ->whereBetween('created_at', [$this->startDate, $this->endDate.' 23:59:59']);
        })->count();

        // Calculate trends (compare to previous period)
        $previousPeriod = $this->getPreviousPeriodDates();
        $stats['participant_trend'] = $this->calculateTrend(
            Participant::whereHas('programEnrollments', function ($query) use ($userProgramIds, $previousPeriod) {
                $query->whereIn('program_id', $userProgramIds)
                    ->whereBetween('created_at', $previousPeriod);
            })->count(),
            $stats['total_participants']
        );

        return $stats;
    }

    private function getPreviousPeriodDates()
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        $diff = $start->diffInDays($end);

        return [
            $start->subDays($diff + 1)->format('Y-m-d'),
            $end->subDays($diff + 1)->format('Y-m-d').' 23:59:59',
        ];
    }

    private function calculateTrend($previousValue, $currentValue)
    {
        if ($previousValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }

        return round((($currentValue - $previousValue) / $previousValue) * 100, 1);
    }

    private function getUserPermissions($user)
    {
        $permissions = [];

        // Get all permissions through user's roles
        foreach ($user->roles as $role) {
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            $permissions = array_merge($permissions, $rolePermissions);
        }

        // Return unique permissions
        return array_unique($permissions);
    }

    private function getTabData($userProgramIds)
    {
        switch ($this->activeTab) {
            case 'participants':
                return $this->getParticipantsTabData($userProgramIds);
            case 'work-plans':
                return $this->getWorkPlansTabData($userProgramIds);
            case 'schedule':
                return $this->getScheduleTabData($userProgramIds);
            default:
                return $this->getProgramsTabData($userProgramIds);
        }
    }

    private function getProgramsTabData($userProgramIds)
    {
        // Extended work plans data for expanded view
        $allWorkPlans = WorkPlan::with(['program', 'goals'])
            ->whereIn('program_id', $userProgramIds)
            ->where('active', true)
            ->get();

        return [
            'allWorkPlans' => $allWorkPlans,
        ];
    }

    private function getParticipantsTabData($userProgramIds)
    {
        $participants = Participant::with(['household', 'programEnrollments.program'])
            ->whereHas('programEnrollments', function ($query) use ($userProgramIds) {
                $query->whereIn('program_id', $userProgramIds);
            })
            ->latest('updated_at')
            ->paginate(10);

        $enrollmentStats = ProgramEnrollment::whereIn('program_id', $userProgramIds)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return [
            'participants' => $participants,
            'enrollmentStats' => $enrollmentStats,
        ];
    }

    private function getWorkPlansTabData($userProgramIds)
    {
        $workPlans = WorkPlan::with(['program', 'goals.service'])
            ->whereIn('program_id', $userProgramIds)
            ->where('active', true)
            ->get();

        // Calculate work plan statistics
        $totalGoals = $workPlans->sum(function ($plan) {
            return $plan->goals->count();
        });

        $completedGoals = $workPlans->sum(function ($plan) {
            return $plan->goals->filter(function ($goal) {
                return $goal->target_number > 0 && $goal->actual_number >= $goal->target_number;
            })->count();
        });

        $inProgressGoals = $workPlans->sum(function ($plan) {
            return $plan->goals->filter(function ($goal) {
                return $goal->target_number > 0 && $goal->actual_number > 0 && $goal->actual_number < $goal->target_number;
            })->count();
        });

        // Group work plans by year for calendar view
        $workPlansByYear = $workPlans->groupBy('year');

        // Services tied to work plan goals
        $servicesInWorkPlans = Service::with('program')
            ->whereHas('workPlanGoals', function ($query) use ($userProgramIds) {
                $query->whereHas('workPlan', function ($subQuery) use ($userProgramIds) {
                    $subQuery->whereIn('program_id', $userProgramIds);
                });
            })
            ->get();

        $workPlanStats = [
            'total_work_plans' => $workPlans->count(),
            'total_goals' => $totalGoals,
            'completed_goals' => $completedGoals,
            'in_progress_goals' => $inProgressGoals,
            'services_count' => $servicesInWorkPlans->count(),
        ];

        return [
            'workPlans' => $workPlans,
            'workPlanStats' => $workPlanStats,
            'workPlansByYear' => $workPlansByYear,
            'servicesInWorkPlans' => $servicesInWorkPlans,
        ];
    }

    private function getScheduleTabData($userProgramIds)
    {
        // Get contracts with upcoming deadlines (placeholder implementation)
        $contracts = Contract::with('program')
            ->whereIn('program_id', $userProgramIds)
            ->where('end_date', '>', now())
            ->orderBy('end_date')
            ->get();

        $upcomingDeadlines = $contracts->filter(function ($contract) {
            return $contract->end_date <= now()->addDays(60);
        });

        // Placeholder for custom reminders
        $customReminders = collect([
            (object) [
                'id' => 1,
                'title' => 'Monthly Housing Report Due',
                'due_date' => now()->addDays(5),
                'priority' => 'high',
                'type' => 'report',
            ],
            (object) [
                'id' => 2,
                'title' => 'Quarterly CSBG Submission',
                'due_date' => now()->addDays(15),
                'priority' => 'medium',
                'type' => 'submission',
            ],
        ]);

        return [
            'contracts' => $contracts,
            'upcomingDeadlines' => $upcomingDeadlines,
            'customReminders' => $customReminders,
        ];
    }

    /**
     * Render the contract database dashboard for users with contract dashboard role
     */
    private function renderContractDashboard($user)
    {
        // Get user's assigned contracts (via email assignments to report due dates)
        $userEmail = $user->email;

        // Get user's accessible program IDs
        $accessibleProgramIds = $user->getAccessibleProgramIds();

        // Check if user is a Contract Manager (sees all report due dates)
        $isContractManager = $user->hasRole('Contract Manager');

        // Build upcoming report due dates query
        $upcomingQuery = ContractReportDueDate::with(['contract.program', 'contract.funder', 'contractReports'])
            ->whereHas('contract', function ($query) use ($accessibleProgramIds) {
                $query->whereIn('program_id', $accessibleProgramIds);
            })
            ->where('active', true)
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', now());

        // For non-Contract Managers, filter by email assignment
        if (! $isContractManager) {
            $upcomingQuery->whereHas('emails', function ($query) use ($userEmail) {
                $query->where('email', $userEmail)
                    ->where('contract_report_due_date_emails.active', true);
            });
        }

        $upcomingReportDueDates = $upcomingQuery->orderBy('due_date')->get();

        // Build overdue report due dates query
        $overdueQuery = ContractReportDueDate::with(['contract.program', 'contract.funder', 'contractReports'])
            ->whereHas('contract', function ($query) use ($accessibleProgramIds) {
                $query->whereIn('program_id', $accessibleProgramIds);
            })
            ->where('active', true)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now());

        // For non-Contract Managers, filter by email assignment
        if (! $isContractManager) {
            $overdueQuery->whereHas('emails', function ($query) use ($userEmail) {
                $query->where('email', $userEmail)
                    ->where('contract_report_due_date_emails.active', true);
            });
        }

        $overdueReportDueDates = $overdueQuery->orderBy('due_date', 'desc')->get();

        // Get recently submitted reports
        $recentlySubmittedReports = ContractReport::with(['contract.program', 'contract.funder', 'contractReportDueDate'])
            ->where('created_by', $user->id)
            ->whereHas('contract', function ($query) use ($accessibleProgramIds) {
                $query->whereIn('program_id', $accessibleProgramIds);
            })
            ->whereIn('status', ['submitted', 'under_review', 'approved'])
            ->whereBetween('submitted_date', [$this->startDate, $this->endDate])
            ->orderBy('submitted_date', 'desc')
            ->limit(10)
            ->get();

        // Get recent contract activity
        $recentContractActivity = $this->getRecentContractActivity($user, $accessibleProgramIds);

        // Calculate contract metrics
        $contractMetrics = [
            'total_upcoming_reports' => $upcomingReportDueDates->count(),
            'overdue_reports' => $overdueReportDueDates->count(),
            'reports_due_this_week' => $upcomingReportDueDates->filter(function ($dueDate) {
                return $dueDate->due_date <= now()->addDays(7);
            })->count(),
            'reports_submitted_this_period' => $recentlySubmittedReports->count(),
        ];

        // Get contract statistics
        $contractStats = $this->getContractStatistics($user, $accessibleProgramIds);

        return view('livewire.contract-dashboard', [
            'user' => $user,
            'upcomingReportDueDates' => $upcomingReportDueDates,
            'overdueReportDueDates' => $overdueReportDueDates,
            'recentlySubmittedReports' => $recentlySubmittedReports,
            'recentContractActivity' => $recentContractActivity,
            'contractMetrics' => $contractMetrics,
            'contractStats' => $contractStats,
        ])->layout('components.layouts.app');
    }

    /**
     * Get recent contract activity for the user
     */
    private function getRecentContractActivity($user, $accessibleProgramIds)
    {
        $activities = collect();

        // Recent report submissions
        $recentSubmissions = ContractReport::with(['contract', 'contractReportDueDate'])
            ->where('created_by', $user->id)
            ->whereHas('contract', function ($query) use ($accessibleProgramIds) {
                $query->whereIn('program_id', $accessibleProgramIds);
            })
            ->where('status', 'submitted')
            ->whereBetween('submitted_date', [now()->subDays(30), now()])
            ->orderBy('submitted_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($report) {
                return [
                    'type' => 'report_submitted',
                    'description' => 'Submitted report: '.$report->title.' for '.$report->contract->title,
                    'timestamp' => $report->submitted_date,
                    'status' => $report->status,
                    'id' => $report->id,
                ];
            });

        // Recent status changes
        $recentStatusChanges = ContractReport::with(['contract'])
            ->where('created_by', $user->id)
            ->whereHas('contract', function ($query) use ($accessibleProgramIds) {
                $query->whereIn('program_id', $accessibleProgramIds);
            })
            ->whereIn('status', ['approved', 'rejected', 'revision_required'])
            ->whereBetween('updated_at', [now()->subDays(30), now()])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($report) {
                return [
                    'type' => 'status_change',
                    'description' => 'Report "'.$report->title.'" status changed to '.ContractReport::STATUS_OPTIONS[$report->status],
                    'timestamp' => $report->updated_at,
                    'status' => $report->status,
                    'id' => $report->id,
                ];
            });

        // Combine and sort activities
        return $activities
            ->merge($recentSubmissions)
            ->merge($recentStatusChanges)
            ->sortByDesc('timestamp')
            ->take(10);
    }

    /**
     * Get contract statistics for the user
     */
    private function getContractStatistics($user, $accessibleProgramIds)
    {
        $userEmail = $user->email;
        $isContractManager = $user->hasRole('Contract Manager');

        // Total contracts assigned
        if ($isContractManager) {
            // Contract Managers see all contracts in their accessible programs
            $totalContracts = Contract::whereIn('program_id', $accessibleProgramIds)->count();
        } else {
            // Others only see contracts with email assignments
            $totalContracts = Contract::whereHas('reportDueDates.emails', function ($query) use ($userEmail) {
                $query->where('email', $userEmail)
                    ->where('contract_report_due_date_emails.active', true);
            })
                ->whereIn('program_id', $accessibleProgramIds)
                ->count();
        }

        // Reports by status (for this user only)
        $reportsByStatus = ContractReport::where('created_by', $user->id)
            ->whereHas('contract', function ($query) use ($accessibleProgramIds) {
                $query->whereIn('program_id', $accessibleProgramIds);
            })
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Completion rate for current period
        $completedInPeriod = ContractReport::where('created_by', $user->id)
            ->whereHas('contract', function ($query) use ($accessibleProgramIds) {
                $query->whereIn('program_id', $accessibleProgramIds);
            })
            ->where('status', 'approved')
            ->whereBetween('submitted_date', [$this->startDate, $this->endDate])
            ->count();

        // Total due in period
        $totalDueQuery = ContractReportDueDate::whereHas('contract', function ($query) use ($accessibleProgramIds) {
            $query->whereIn('program_id', $accessibleProgramIds);
        })
            ->whereBetween('due_date', [$this->startDate, $this->endDate]);

        // For non-Contract Managers, filter by email assignment
        if (! $isContractManager) {
            $totalDueQuery->whereHas('emails', function ($query) use ($userEmail) {
                $query->where('email', $userEmail)
                    ->where('contract_report_due_date_emails.active', true);
            });
        }

        $totalDueInPeriod = $totalDueQuery->count();

        $completionRate = $totalDueInPeriod > 0 ? round(($completedInPeriod / $totalDueInPeriod) * 100, 1) : 0;

        return [
            'total_contracts' => $totalContracts,
            'reports_by_status' => $reportsByStatus,
            'completion_rate' => $completionRate,
            'completed_in_period' => $completedInPeriod,
            'total_due_in_period' => $totalDueInPeriod,
        ];
    }
}
