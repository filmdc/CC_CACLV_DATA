<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\Participant;
use App\Models\Program;
use App\Models\WorkPlan;
use App\Models\WorkPlanGoal;
use App\Models\Service;
use App\Models\ProgramEnrollment;
use App\Models\Contract;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    use WithPermissionChecks;
    
    public $activeTab = 'programs';
    public $expandedWorkPlans = false;
    
    // Protected listeners for real-time updates
    protected $listeners = [
        'refreshDashboard' => '$refresh',
        'participantUpdated' => 'refreshMetrics',
        'enrollmentUpdated' => 'refreshMetrics',
        'workPlanUpdated' => 'refreshMetrics',
        'programUpdated' => 'refreshMetrics'
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
        // No specific authorization needed for dashboard - handled per section
    }

    public function toggleExpandedWorkPlans()
    {
        $this->expandedWorkPlans = !$this->expandedWorkPlans;
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

    public function render()
    {
        $user = Auth::user();
        
        // Get user's assigned programs
        $userPrograms = $user->programs()->with(['services', 'programEnrollments.participant', 'workPlans'])->get();
        
        // Calculate metrics for user's programs - always fresh from database
        $userProgramIds = $userPrograms->pluck('id')->toArray();
        
        $metrics = $this->calculateMetrics($userProgramIds);

        // Recent activity - participants the user has worked with
        $recentParticipants = Participant::with(['household', 'programEnrollments.program'])
            ->whereHas('programEnrollments', function($query) use ($userProgramIds) {
                $query->whereIn('program_id', $userProgramIds);
            })
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // Recent households in user's programs
        $recentHouseholds = Household::with(['headOfHousehold', 'participants.programEnrollments.program'])
            ->whereHas('participants.programEnrollments', function($query) use ($userProgramIds) {
                $query->whereIn('program_id', $userProgramIds);
            })
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // Work plan goals for user's programs
        $workPlanGoals = WorkPlanGoal::with(['workPlan.program', 'service'])
            ->whereHas('workPlan', function($query) use ($userProgramIds) {
                $query->whereIn('program_id', $userProgramIds);
            })
            ->where('target_number', '>', 0)
            ->latest()
            ->limit(5)
            ->get();

        // Program enrollment stats for user's programs
        $programStats = $userPrograms->map(function($program) {
            return [
                'program' => $program,
                'active_enrollments' => $program->programEnrollments()->active()->count(),
                'total_enrollments' => $program->programEnrollments()->count(),
                'services_count' => $program->services()->where('active', true)->count(),
                'work_plans_count' => $program->workPlans()->where('active', true)->count(),
            ];
        });

        // Load tab-specific data
        $tabData = $this->getTabData($userProgramIds);

        return view('livewire.dashboard', [
            'userPrograms' => $userPrograms,
            'programStats' => $programStats,
            'metrics' => $metrics,
            'recentHouseholds' => $recentHouseholds,
            'recentParticipants' => $recentParticipants,
            'workPlanGoals' => $workPlanGoals,
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
            ->whereHas('programEnrollments', function($query) use ($userProgramIds) {
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
        $totalGoals = $workPlans->sum(function($plan) {
            return $plan->goals->count();
        });

        $completedGoals = $workPlans->sum(function($plan) {
            return $plan->goals->filter(function($goal) {
                return $goal->target_number > 0 && $goal->actual_number >= $goal->target_number;
            })->count();
        });

        $inProgressGoals = $workPlans->sum(function($plan) {
            return $plan->goals->filter(function($goal) {
                return $goal->target_number > 0 && $goal->actual_number > 0 && $goal->actual_number < $goal->target_number;
            })->count();
        });

        // Group work plans by year for calendar view
        $workPlansByYear = $workPlans->groupBy('year');

        // Services tied to work plan goals
        $servicesInWorkPlans = Service::with('program')
            ->whereHas('workPlanGoals', function($query) use ($userProgramIds) {
                $query->whereHas('workPlan', function($subQuery) use ($userProgramIds) {
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

        $upcomingDeadlines = $contracts->filter(function($contract) {
            return $contract->end_date <= now()->addDays(60);
        });

        // Placeholder for custom reminders
        $customReminders = collect([
            (object) [
                'id' => 1,
                'title' => 'Monthly Housing Report Due',
                'due_date' => now()->addDays(5),
                'priority' => 'high',
                'type' => 'report'
            ],
            (object) [
                'id' => 2,
                'title' => 'Quarterly CSBG Submission',
                'due_date' => now()->addDays(15),
                'priority' => 'medium',
                'type' => 'submission'
            ],
        ]);

        return [
            'contracts' => $contracts,
            'upcomingDeadlines' => $upcomingDeadlines,
            'customReminders' => $customReminders,
        ];
    }
}
