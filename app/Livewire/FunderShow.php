<?php

namespace App\Livewire;

use App\Models\Funder;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class FunderShow extends Component
{
    use WithPermissionChecks;

    public $funder;
    public $funderId;

    public function mount($id)
    {
        // Check if user has permission to view funders
        $this->authorizeAction('funders.view', 'You do not have permission to view funders.');
        
        $this->funderId = $id;
        $this->funder = Funder::with([
            'contracts.programs',
            'createdBy',
            'updatedBy'
        ])->findOrFail($id);
    }

    public function editFunder()
    {
        return redirect()->route('funder.management')->with('selectedFunder', $this->funderId);
    }

    public function render()
    {
        return view('livewire.funder-show')->layout('components.layouts.app');
    }
}