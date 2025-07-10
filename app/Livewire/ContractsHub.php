<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\Funder;
use App\Models\ContractReportDueDate;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class ContractsHub extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $activeTab = 'contracts'; // contracts, funders, duedates

    public function mount()
    {
        // Check if user has any relevant permissions
        $this->authorizeAny([
            'contracts.view',
            'funders.view',
            'due_dates.view'
        ]);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.contracts-hub', [
            'contracts' => $this->getContracts(),
            'funders' => $this->getFunders(),
            'dueDates' => $this->getDueDates(),
        ]);
    }

    protected function getContracts()
    {
        if ($this->activeTab !== 'contracts' || !$this->can('contracts.view')) {
            return collect();
        }

        return Contract::with(['funder', 'programs'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('contract_number', 'like', '%' . $this->search . '%')
                    ->orWhereHas('funder', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    protected function getFunders()
    {
        if ($this->activeTab !== 'funders' || !$this->can('funders.view')) {
            return collect();
        }

        return Funder::withCount('contracts')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('contact_name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(10);
    }

    protected function getDueDates()
    {
        if ($this->activeTab !== 'duedates' || !$this->can('due_dates.view')) {
            return collect();
        }

        return ContractReportDueDate::with(['contract', 'contract.funder'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('contract', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('contract_number', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy('due_date', 'asc')
            ->paginate(10);
    }
}