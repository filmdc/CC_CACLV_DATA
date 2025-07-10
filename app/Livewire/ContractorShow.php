<?php

namespace App\Livewire;

use App\Models\Contractor;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class ContractorShow extends Component
{
    use WithPermissionChecks;

    public $contractor;
    public $contractorId;

    public function mount($id)
    {
        // Check if user has permission to view contractors
        $this->authorizeAction('contractors.view', 'You do not have permission to view contractors.');
        
        $this->contractorId = $id;
        $this->contractor = Contractor::with([
            'housingJobs.property',
            'contractorDocuments'
        ])->findOrFail($id);
    }

    public function editContractor()
    {
        return redirect()->route('contractor.management')->with('selectedContractor', $this->contractorId);
    }

    public function render()
    {
        return view('livewire.contractor-show')->layout('components.layouts.app');
    }
}