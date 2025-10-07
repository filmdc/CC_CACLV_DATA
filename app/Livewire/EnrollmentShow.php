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
    public $canAccess = false;

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

            // Check if user has access to this program enrollment
            $this->canAccess = auth()->user()->canAccessEnrollment($id, 'program');
        } else {
            $this->enrollment = ServiceEnrollment::with([
                'participant.household',
                'service.program',
                'programEnrollment.program'
            ])->findOrFail($id);

            // Check if user has access to this service enrollment
            $this->canAccess = auth()->user()->canAccessEnrollment($id, 'service');
        }

        // If user doesn't have access, abort with 403
        if (!$this->canAccess) {
            abort(403, 'You do not have access to view this enrollment. You must be assigned to the program.');
        }
    }

    public function editEnrollment()
    {
        // Verify access before allowing edit
        if (!$this->canAccess) {
            session()->flash('error', 'You do not have permission to edit this enrollment.');
            return;
        }

        return redirect()->route('enrollment.management')
            ->with('selectedEnrollment', $this->enrollmentId)
            ->with('enrollmentType', $this->enrollmentType);
    }

    public function render()
    {
        return view('livewire.enrollment-show')->layout('components.layouts.app');
    }
}