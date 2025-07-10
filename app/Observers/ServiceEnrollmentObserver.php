<?php

namespace App\Observers;

use App\Models\ServiceEnrollment;
use App\Models\WorkPlanGoal;
use Illuminate\Support\Facades\Log;

class ServiceEnrollmentObserver
{
    /**
     * Handle the ServiceEnrollment "created" event.
     */
    public function created(ServiceEnrollment $serviceEnrollment): void
    {
        $this->updateRelatedGoals($serviceEnrollment);
    }

    /**
     * Handle the ServiceEnrollment "updated" event.
     */
    public function updated(ServiceEnrollment $serviceEnrollment): void
    {
        // Only update if status changed
        if ($serviceEnrollment->isDirty('status')) {
            $this->updateRelatedGoals($serviceEnrollment);
        }
    }

    /**
     * Handle the ServiceEnrollment "deleted" event.
     */
    public function deleted(ServiceEnrollment $serviceEnrollment): void
    {
        $this->updateRelatedGoals($serviceEnrollment);
    }

    /**
     * Handle the ServiceEnrollment "restored" event.
     */
    public function restored(ServiceEnrollment $serviceEnrollment): void
    {
        $this->updateRelatedGoals($serviceEnrollment);
    }

    /**
     * Update all work plan goals related to this service enrollment
     */
    private function updateRelatedGoals(ServiceEnrollment $serviceEnrollment): void
    {
        try {
            // Find all work plan goals for this specific service
            $serviceGoals = WorkPlanGoal::where('service_id', $serviceEnrollment->service_id)
                ->get();

            foreach ($serviceGoals as $goal) {
                $goal->updateActualFromServiceData();
                Log::info('Updated work plan goal from service enrollment', [
                    'goal_id' => $goal->id,
                    'service_id' => $serviceEnrollment->service_id,
                    'new_actual' => $goal->actual_number
                ]);
            }

            // Also update program-wide goals if this enrollment has a program enrollment
            if ($serviceEnrollment->programEnrollment) {
                $programId = $serviceEnrollment->programEnrollment->program_id;
                
                // Find program-wide goals (service_id is null) for this program
                $programWideGoals = WorkPlanGoal::whereNull('service_id')
                    ->whereHas('workPlan', function ($query) use ($programId) {
                        $query->where('program_id', $programId);
                    })
                    ->get();

                foreach ($programWideGoals as $goal) {
                    $goal->updateActualFromServiceData();
                    Log::info('Updated program-wide work plan goal from service enrollment', [
                        'goal_id' => $goal->id,
                        'program_id' => $programId,
                        'new_actual' => $goal->actual_number
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't throw - we don't want to break enrollment operations
            Log::error('Failed to update work plan goals after service enrollment change', [
                'service_enrollment_id' => $serviceEnrollment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}