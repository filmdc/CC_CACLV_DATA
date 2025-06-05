<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\Participant;
use App\Models\Program;
use App\Models\WorkPlan;
use App\Models\WorkPlanGoal;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        // Calculate dashboard metrics
        $metrics = [
            'total_households' => Household::count(),
            'total_participants' => Participant::count(),
            'total_programs' => Program::where('active', true)->count(),
            'total_work_plans' => WorkPlan::where('active', true)->count(),
        ];

        // Recent households
        $recentHouseholds = Household::with('headOfHousehold')
            ->latest()
            ->limit(5)
            ->get();

        // Recent participants
        $recentParticipants = Participant::with('household')
            ->latest()
            ->limit(5)
            ->get();

        // Work plan goals progress
        $workPlanGoals = WorkPlanGoal::with(['workPlan.program', 'service'])
            ->where('target_number', '>', 0)
            ->latest()
            ->limit(5)
            ->get();

        // Poverty level distribution
        $povertyDistribution = Household::whereNotNull('hhs_poverty_percentage')
            ->selectRaw('
                CASE 
                    WHEN hhs_poverty_percentage <= 50 THEN "0-50%"
                    WHEN hhs_poverty_percentage <= 100 THEN "51-100%"
                    WHEN hhs_poverty_percentage <= 150 THEN "101-150%"
                    WHEN hhs_poverty_percentage <= 200 THEN "151-200%"
                    ELSE "200%+"
                END as poverty_range,
                COUNT(*) as count
            ')
            ->groupBy('poverty_range')
            ->get();

        return view('livewire.dashboard', [
            'metrics' => $metrics,
            'recentHouseholds' => $recentHouseholds,
            'recentParticipants' => $recentParticipants,
            'workPlanGoals' => $workPlanGoals,
            'povertyDistribution' => $povertyDistribution,
        ])->layout('components.layouts.app');
    }
}
