<?php

namespace App\Livewire;

use App\Models\HousingJob;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class HousingJobShow extends Component
{
    use WithPermissionChecks;

    public $housingJob;
    public $housingJobId;

    public function mount($id)
    {
        // Check if user has permission to view housing jobs
        $this->authorizeAction('housing_jobs.view', 'You do not have permission to view housing jobs.');
        
        $this->housingJobId = $id;
        $this->housingJob = HousingJob::with([
            'property',
            'contractor',
            'participant',
            'household',
            'jobContactLedgers',
            'jobDocumentChecklists',
            'serviceEnrollments.service'
        ])->findOrFail($id);
    }

    public function editHousingJob()
    {
        return redirect()->route('housing-job.management')->with('selectedHousingJob', $this->housingJobId);
    }

    public function render()
    {
        return view('livewire.housing-job-show')->layout('components.layouts.app');
    }
}