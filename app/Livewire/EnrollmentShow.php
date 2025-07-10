<?php

namespace App\Livewire;

use App\Models\ProgramEnrollment;
use App\Models\ServiceEnrollment;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class EnrollmentShow extends Component
{
    use WithPermissionChecks;

    public $enrollment;
    public $enrollmentId;
    public $enrollmentType;

    public function mount($type, $id)
    {
        // Check if user has permission to view enrollments
        $this->authorizeAction('enrollments.view', 'You do not have permission to view enrollments.');
        
        $this->enrollmentId = $id;
        $this->enrollmentType = $type;
        
        if ($type === 'program') {
            $this->enrollment = ProgramEnrollment::with([
                'participant.household',
                'program',
                'serviceEnrollments.service'
            ])->findOrFail($id);
        } else {
            $this->enrollment = ServiceEnrollment::with([
                'participant.household',
                'service.program',
                'programEnrollment.program'
            ])->findOrFail($id);
        }
    }

    public function editEnrollment()
    {
        return redirect()->route('enrollment.management')
            ->with('selectedEnrollment', $this->enrollmentId)
            ->with('enrollmentType', $this->enrollmentType);
    }

    public function render()
    {
        return view('livewire.enrollment-show')->layout('components.layouts.app');
    }
}