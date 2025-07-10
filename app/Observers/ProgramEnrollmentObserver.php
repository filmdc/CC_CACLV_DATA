<?php

namespace App\Observers;

use App\Models\ProgramEnrollment;
use App\Models\WorkPlanGoal;
use Illuminate\Support\Facades\Log;

class ProgramEnrollmentObserver
{
    /**
     * Handle the ProgramEnrollment "created" event.
     */
    public function created(ProgramEnrollment $programEnrollment): void
    {
        // Only update if enrollment is active
        if (in_array($programEnrollment->status, ['active'])) {
            $this->updateProgramWideGoals($programEnrollment);
        }
    }

    /**
     * Handle the ProgramEnrollment "updated" event.
     */
    public function updated(ProgramEnrollment $programEnrollment): void
    {
        // Update if status changed or if it's an active enrollment
        if ($programEnrollment->isDirty('status') || $programEnrollment->status === 'active') {
            $this->updateProgramWideGoals($programEnrollment);
        }
    }

    /**
     * Handle the ProgramEnrollment "deleted" event.
     */
    public function deleted(ProgramEnrollment $programEnrollment): void
    {
        $this->updateProgramWideGoals($programEnrollment);
    }

    /**
     * Handle the ProgramEnrollment "restored" event.
     */
    public function restored(ProgramEnrollment $programEnrollment): void
    {
        if ($programEnrollment->status === 'active') {
            $this->updateProgramWideGoals($programEnrollment);
        }
    }

    /**
     * Update all program-wide work plan goals for this program
     */
    private function updateProgramWideGoals(ProgramEnrollment $programEnrollment): void
    {
        try {
            // Find program-wide goals (service_id is null) for this program
            $programWideGoals = WorkPlanGoal::whereNull('service_id')
                ->whereHas('workPlan', function ($query) use ($programEnrollment) {
                    $query->where('program_id', $programEnrollment->program_id)
                          ->where('active', true); // Only update active work plans
                })
                ->get();

            foreach ($programWideGoals as $goal) {
                $oldActual = $goal->actual_number;
                $goal->updateActualFromServiceData();
                
                Log::info('Updated program-wide work plan goal from program enrollment change', [
                    'goal_id' => $goal->id,
                    'program_id' => $programEnrollment->program_id,
                    'old_actual' => $oldActual,
                    'new_actual' => $goal->actual_number,
                    'enrollment_status' => $programEnrollment->status
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't throw - we don't want to break enrollment operations
            Log::error('Failed to update program-wide work plan goals after program enrollment change', [
                'program_enrollment_id' => $programEnrollment->id,
                'program_id' => $programEnrollment->program_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}