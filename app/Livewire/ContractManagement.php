<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\Program;
use Livewire\Component;
use Livewire\WithPagination;

class ContractManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedContract = null;
    public $showCreateForm = false;
    public $showEditForm = false;

    // Contract Form Fields
    public $title = '';
    public $contract_number = '';
    public $cfda_number = '';
    public $purpose = '';
    public $program_id = '';
    public $funder = '';
    public $amount = '';
    public $start_date = '';
    public $end_date = '';
    public $fund_code = '';
    public $notes = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateContract()
    {
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedContract = null;
        $this->resetContractForm();
    }

    public function showEditContract($contractId)
    {
        $this->selectedContract = Contract::find($contractId);
        if ($this->selectedContract) {
            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->fillContractForm();
        }
    }

    public function resetContractForm()
    {
        $this->title = '';
        $this->contract_number = '';
        $this->cfda_number = '';
        $this->purpose = '';
        $this->program_id = '';
        $this->funder = '';
        $this->amount = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->fund_code = '';
        $this->notes = '';
    }

    public function fillContractForm()
    {
        if ($this->selectedContract) {
            $this->title = $this->selectedContract->title;
            $this->contract_number = $this->selectedContract->contract_number;
            $this->cfda_number = $this->selectedContract->cfda_number;
            $this->purpose = $this->selectedContract->purpose;
            $this->program_id = $this->selectedContract->program_id;
            $this->funder = $this->selectedContract->funder;
            $this->amount = $this->selectedContract->amount;
            $this->start_date = $this->selectedContract->start_date ? $this->selectedContract->start_date->format('Y-m-d') : '';
            $this->end_date = $this->selectedContract->end_date ? $this->selectedContract->end_date->format('Y-m-d') : '';
            $this->fund_code = $this->selectedContract->fund_code;
            $this->notes = $this->selectedContract->notes;
        }
    }

    public function createContract()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'contract_number' => 'nullable|string|max:255',
            'cfda_number' => 'nullable|string|max:255',
            'purpose' => 'nullable|string',
            'program_id' => 'required|exists:programs,id',
            'funder' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'fund_code' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            Contract::create([
                'title' => $this->title,
                'contract_number' => $this->contract_number,
                'cfda_number' => $this->cfda_number,
                'purpose' => $this->purpose,
                'program_id' => $this->program_id,
                'funder' => $this->funder,
                'amount' => $this->amount,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'fund_code' => $this->fund_code,
                'notes' => $this->notes,
            ]);

            session()->flash('success', 'Contract created successfully.');
            $this->showCreateForm = false;
            $this->resetContractForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateContract()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'contract_number' => 'nullable|string|max:255',
            'cfda_number' => 'nullable|string|max:255',
            'purpose' => 'nullable|string',
            'program_id' => 'required|exists:programs,id',
            'funder' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'fund_code' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $this->selectedContract->update([
                'title' => $this->title,
                'contract_number' => $this->contract_number,
                'cfda_number' => $this->cfda_number,
                'purpose' => $this->purpose,
                'program_id' => $this->program_id,
                'funder' => $this->funder,
                'amount' => $this->amount,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'fund_code' => $this->fund_code,
                'notes' => $this->notes,
            ]);

            session()->flash('success', 'Contract updated successfully.');
            $this->showEditForm = false;
            $this->selectedContract = null;
            $this->resetContractForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteContract($contractId)
    {
        try {
            $contract = Contract::find($contractId);
            if ($contract) {
                $contract->delete();
                session()->flash('success', 'Contract deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete contract: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $contracts = Contract::with('program')
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('contract_number', 'like', '%' . $this->search . '%')
                      ->orWhere('funder', 'like', '%' . $this->search . '%')
                      ->orWhereHas('program', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $programs = Program::where('active', true)->orderBy('name')->get();

        return view('livewire.contract-management', [
            'contracts' => $contracts,
            'programs' => $programs,
        ])->layout('components.layouts.app');
    }
}