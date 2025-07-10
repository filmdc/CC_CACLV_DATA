<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class ContractShow extends Component
{
    use WithPermissionChecks;

    public $contract;
    public $contractId;

    public function mount($id)
    {
        // Check if user has permission to view contracts
        $this->authorizeAction('contracts.view', 'You do not have permission to view contracts.');
        
        $this->contractId = $id;
        $this->contract = Contract::with([
            'funder',
            'program',
            'reportDueDates.emails',
            'createdBy',
            'updatedBy'
        ])->findOrFail($id);
    }

    public function editContract()
    {
        return redirect()->route('contract.management')->with('selectedContract', $this->contractId);
    }

    public function render()
    {
        return view('livewire.contract-show')->layout('components.layouts.app');
    }
}