<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\Funder;
use App\Models\Program;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContractManagement extends Component
{
    use WithPagination, WithPermissionChecks, WithFileUploads;

    public $search = '';
    public $statusFilter = 'all';
    public $selectedContract = null;
    public $showCreateForm = false;
    public $showEditForm = false;
    public $showArchivedContracts = false;

    // Contract Form Fields
    public $title = '';
    public $contract_number = '';
    public $cfda_number = '';
    public $purpose = '';
    public $program_id = '';
    public $funder_id = '';
    public $date_of_award = '';
    public $amount = '';
    public $start_date = '';
    public $end_date = '';
    public $grant_code = '';
    public $notes = '';
    
    // File upload
    public $documents = [];

    public function mount()
    {
        $this->authorizeAction('contracts.view');
    }

    public function resetContractForm()
    {
        $this->title = '';
        $this->contract_number = '';
        $this->cfda_number = '';
        $this->purpose = '';
        $this->program_id = '';
        $this->funder_id = '';
        $this->date_of_award = '';
        $this->amount = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->grant_code = '';
        $this->notes = '';
        $this->documents = [];
    }

    public function showCreateContract()
    {
        $this->authorizeAction('contracts.create');
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedContract = null;
        $this->resetContractForm();
    }

    public function showEditContract($contractId)
    {
        $this->authorizeAction('contracts.edit');
        $this->selectedContract = Contract::findOrFail($contractId);
        $this->showEditForm = true;
        $this->showCreateForm = false;
        $this->fillContractForm();
    }

    public function fillContractForm()
    {
        if ($this->selectedContract) {
            $this->title = $this->selectedContract->title;
            $this->contract_number = $this->selectedContract->contract_number;
            $this->cfda_number = $this->selectedContract->cfda_number;
            $this->purpose = $this->selectedContract->purpose;
            $this->program_id = $this->selectedContract->program_id;
            $this->funder_id = $this->selectedContract->funder_id;
            $this->date_of_award = $this->selectedContract->date_of_award ? $this->selectedContract->date_of_award->format('Y-m-d') : '';
            $this->amount = $this->selectedContract->amount;
            $this->start_date = $this->selectedContract->start_date ? $this->selectedContract->start_date->format('Y-m-d') : '';
            $this->end_date = $this->selectedContract->end_date ? $this->selectedContract->end_date->format('Y-m-d') : '';
            $this->grant_code = $this->selectedContract->grant_code;
            $this->notes = $this->selectedContract->notes;
        }
    }

    public function createContract()
    {
        $this->authorizeAction('contracts.create');
        
        $this->validate([
            'title' => 'required|string|max:255',
            'contract_number' => 'nullable|string|max:255',
            'cfda_number' => 'nullable|string|max:255',
            'purpose' => 'nullable|string',
            'program_id' => 'required|exists:programs,id',
            'funder_id' => 'required|exists:funders,id',
            'date_of_award' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'grant_code' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'documents.*' => 'nullable|file|max:10240', // 10MB max
        ]);

        try {
            $contract = Contract::create([
                'title' => $this->title,
                'contract_number' => $this->contract_number,
                'cfda_number' => $this->cfda_number,
                'purpose' => $this->purpose,
                'program_id' => $this->program_id,
                'funder_id' => $this->funder_id,
                'date_of_award' => $this->date_of_award,
                'amount' => $this->amount,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'grant_code' => $this->grant_code,
                'notes' => $this->notes,
                'created_by' => auth()->id(),
            ]);

            // Handle document uploads
            if (!empty($this->documents)) {
                foreach ($this->documents as $document) {
                    $contract->addMediaFromStream($document->readStream())
                            ->usingName($document->getClientOriginalName())
                            ->toMediaCollection('documents');
                }
            }

            session()->flash('success', 'Contract created successfully.');
            $this->showCreateForm = false;
            $this->resetContractForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateContract()
    {
        $this->authorizeAction('contracts.edit');
        
        $this->validate([
            'title' => 'required|string|max:255',
            'contract_number' => 'nullable|string|max:255',
            'cfda_number' => 'nullable|string|max:255',
            'purpose' => 'nullable|string',
            'program_id' => 'required|exists:programs,id',
            'funder_id' => 'required|exists:funders,id',
            'date_of_award' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'grant_code' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'documents.*' => 'nullable|file|max:10240', // 10MB max
        ]);

        try {
            $this->selectedContract->update([
                'title' => $this->title,
                'contract_number' => $this->contract_number,
                'cfda_number' => $this->cfda_number,
                'purpose' => $this->purpose,
                'program_id' => $this->program_id,
                'funder_id' => $this->funder_id,
                'date_of_award' => $this->date_of_award,
                'amount' => $this->amount,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'grant_code' => $this->grant_code,
                'notes' => $this->notes,
                'updated_by' => auth()->id(),
            ]);

            // Handle new document uploads
            if (!empty($this->documents)) {
                foreach ($this->documents as $document) {
                    $this->selectedContract->addMediaFromStream($document->readStream())
                                          ->usingName($document->getClientOriginalName())
                                          ->toMediaCollection('documents');
                }
            }

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
        $this->authorizeAction('contracts.delete');
        
        try {
            $contract = Contract::findOrFail($contractId);
            $contract->update(['updated_by' => auth()->id()]);
            $contract->delete();
            session()->flash('success', 'Contract deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete contract: ' . $e->getMessage());
        }
    }

    public function archiveContract($contractId)
    {
        $this->authorizeAction('contracts.edit');
        
        try {
            $contract = Contract::findOrFail($contractId);
            $contract->archive();
            $contract->update(['updated_by' => auth()->id()]);
            session()->flash('success', 'Contract archived successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot archive contract: ' . $e->getMessage());
        }
    }

    public function unarchiveContract($contractId)
    {
        $this->authorizeAction('contracts.edit');
        
        try {
            $contract = Contract::findOrFail($contractId);
            $contract->unarchive();
            $contract->update(['updated_by' => auth()->id()]);
            session()->flash('success', 'Contract unarchived successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot unarchive contract: ' . $e->getMessage());
        }
    }

    public function removeDocument($documentId)
    {
        $this->authorizeAction('contracts.edit');
        
        try {
            $media = Media::findOrFail($documentId);
            $media->delete();
            session()->flash('success', 'Document removed successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot remove document: ' . $e->getMessage());
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function toggleArchivedView()
    {
        $this->showArchivedContracts = !$this->showArchivedContracts;
        $this->resetPage();
    }

    public function render()
    {
        $query = Contract::with(['program', 'funder', 'reportDueDates'])
            ->when($this->showArchivedContracts, function ($q) {
                $q->archived();
            }, function ($q) {
                $q->active();
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%')
                      ->orWhere('contract_number', 'like', '%' . $this->search . '%')
                      ->orWhere('cfda_number', 'like', '%' . $this->search . '%')
                      ->orWhere('grant_code', 'like', '%' . $this->search . '%')
                      ->orWhereHas('program', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('funder', function ($q) {
                          $q->where('name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                switch ($this->statusFilter) {
                    case 'active':
                        $query->where('start_date', '<=', now())
                              ->where('end_date', '>=', now());
                        break;
                    case 'upcoming':
                        $query->where('start_date', '>', now());
                        break;
                    case 'expired':
                        $query->where('end_date', '<', now());
                        break;
                }
            });

        $contracts = $query->orderBy('created_at', 'desc')->paginate(10);
        $programs = Program::active()->orderBy('name')->get();
        $funders = Funder::active()->orderBy('name')->get();

        return view('livewire.contract-management', [
            'contracts' => $contracts,
            'programs' => $programs,
            'funders' => $funders,
        ])->layout('components.layouts.app');
    }
}