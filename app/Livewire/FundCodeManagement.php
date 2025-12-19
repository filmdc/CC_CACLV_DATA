<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\FundCode;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FundCodeManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    // Search and Filters
    public $search = '';

    public $activeFilter = '';

    // Form state
    public $showForm = false;

    public $editingId = null;

    // Form fields
    public $code = '';

    public $description = '';

    public $contract_id = '';

    public $is_active = true;

    protected $queryString = [
        'search' => ['except' => ''],
        'activeFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $this->authorizeAction('fund_codes.view');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedActiveFilter()
    {
        $this->resetPage();
    }

    public function showCreateForm()
    {
        $this->authorizeAction('fund_codes.create');
        $this->resetForm();
        $this->showForm = true;
    }

    public function showEditForm($id)
    {
        $this->authorizeAction('fund_codes.edit');
        $fundCode = FundCode::findOrFail($id);

        $this->editingId = $id;
        $this->code = $fundCode->code;
        $this->description = $fundCode->description;
        $this->contract_id = $fundCode->contract_id ?? '';
        $this->is_active = $fundCode->is_active;

        $this->showForm = true;
    }

    public function save()
    {
        $this->validate([
            'code' => 'required|string|max:50|unique:fund_codes,code'.($this->editingId ? ','.$this->editingId : ''),
            'description' => 'required|string|max:255',
            'contract_id' => 'nullable|exists:contracts,id',
            'is_active' => 'boolean',
        ]);

        $data = [
            'code' => strtoupper(trim($this->code)),
            'description' => trim($this->description),
            'contract_id' => $this->contract_id !== '' ? $this->contract_id : null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            $this->authorizeAction('fund_codes.edit');
            $fundCode = FundCode::findOrFail($this->editingId);
            $data['updated_by'] = Auth::id();
            $fundCode->update($data);
            session()->flash('success', 'Fund code updated successfully.');
        } else {
            $this->authorizeAction('fund_codes.create');
            $data['created_by'] = Auth::id();
            FundCode::create($data);
            session()->flash('success', 'Fund code created successfully.');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        $this->authorizeAction('fund_codes.delete');

        $fundCode = FundCode::findOrFail($id);

        // Check if fund code is being used
        if ($fundCode->communicationLogs()->count() > 0) {
            session()->flash('error', 'Cannot delete fund code that is in use. Consider deactivating it instead.');

            return;
        }

        $fundCode->delete();
        session()->flash('success', 'Fund code deleted successfully.');
    }

    public function toggleActive($id)
    {
        $this->authorizeAction('fund_codes.edit');

        $fundCode = FundCode::findOrFail($id);
        $fundCode->update([
            'is_active' => ! $fundCode->is_active,
            'updated_by' => Auth::id(),
        ]);

        $status = $fundCode->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Fund code {$status} successfully.");
    }

    protected function resetForm()
    {
        $this->editingId = null;
        $this->code = '';
        $this->description = '';
        $this->contract_id = '';
        $this->is_active = true;
    }

    public function render()
    {
        $query = FundCode::with(['contract', 'createdBy', 'updatedBy'])
            ->withCount('communicationLogs')
            ->orderBy('code');

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->activeFilter !== '') {
            $query->where('is_active', $this->activeFilter === 'active');
        }

        $fundCodes = $query->paginate(20);

        // Get contracts for dropdown
        $contracts = Contract::where('archived', false)
            ->orderBy('title')
            ->get(['id', 'title', 'contract_number']);

        return view('livewire.fund-code-management', [
            'fundCodes' => $fundCodes,
            'contracts' => $contracts,
        ]);
    }
}
