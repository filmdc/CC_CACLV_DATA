<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\FundCode;
use App\Models\FundCodeRequirement;
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

    // P-06: requirements management state
    public $showRequirementsModal = false;

    public $requirementsForFundCodeId = null;

    public $showRequirementForm = false;

    public $editingRequirementId = null;

    public $requirement_label = '';

    public $requirement_description = '';

    public $requirement_is_hard = false;

    public $requirement_sort_order = 0;

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

    // P-06: Requirements management

    public function manageRequirements($id)
    {
        $this->authorizeAction('fund_codes.edit');
        $this->requirementsForFundCodeId = $id;
        $this->showRequirementsModal = true;
        $this->resetRequirementForm();
    }

    public function closeRequirementsModal()
    {
        $this->showRequirementsModal = false;
        $this->requirementsForFundCodeId = null;
        $this->resetRequirementForm();
    }

    public function openRequirementForm()
    {
        $this->authorizeAction('fund_codes.edit');
        $this->resetRequirementForm();
        $max = FundCodeRequirement::where('fund_code_id', $this->requirementsForFundCodeId)
            ->max('sort_order');
        $this->requirement_sort_order = (int) ($max ?? 0) + 1;
        $this->showRequirementForm = true;
    }

    public function editRequirement($id)
    {
        $this->authorizeAction('fund_codes.edit');
        $req = FundCodeRequirement::findOrFail($id);

        $this->editingRequirementId = $id;
        $this->requirement_label = $req->label;
        $this->requirement_description = $req->description ?? '';
        $this->requirement_is_hard = (bool) $req->is_hard_requirement;
        $this->requirement_sort_order = (int) $req->sort_order;
        $this->showRequirementForm = true;
    }

    public function saveRequirement()
    {
        $this->authorizeAction('fund_codes.edit');
        $this->validate([
            'requirement_label' => 'required|string|max:255',
            'requirement_description' => 'nullable|string',
            'requirement_is_hard' => 'boolean',
            'requirement_sort_order' => 'integer|min:0',
        ]);

        $data = [
            'fund_code_id' => $this->requirementsForFundCodeId,
            'label' => trim($this->requirement_label),
            'description' => $this->requirement_description !== '' ? $this->requirement_description : null,
            'is_hard_requirement' => $this->requirement_is_hard,
            'sort_order' => (int) $this->requirement_sort_order,
        ];

        if ($this->editingRequirementId) {
            FundCodeRequirement::findOrFail($this->editingRequirementId)->update($data);
        } else {
            FundCodeRequirement::create($data);
        }

        $this->showRequirementForm = false;
        $this->resetRequirementForm();
    }

    public function deleteRequirement($id)
    {
        $this->authorizeAction('fund_codes.edit');
        FundCodeRequirement::findOrFail($id)->delete();
    }

    public function moveRequirementUp($id)
    {
        $this->swapRequirementWithNeighbor($id, '<', 'desc');
    }

    public function moveRequirementDown($id)
    {
        $this->swapRequirementWithNeighbor($id, '>', 'asc');
    }

    /**
     * Reorder by swapping sort_order with the immediate neighbor in the
     * given direction. Tie-break on id so duplicates of sort_order are
     * still deterministic. Cheap pairwise swap; no full normalization.
     */
    protected function swapRequirementWithNeighbor(int $id, string $cmp, string $dir): void
    {
        $this->authorizeAction('fund_codes.edit');
        $req = FundCodeRequirement::findOrFail($id);

        $neighbor = FundCodeRequirement::where('fund_code_id', $req->fund_code_id)
            ->where(function ($q) use ($req, $cmp) {
                $q->where('sort_order', $cmp, $req->sort_order)
                    ->orWhere(function ($qq) use ($req, $cmp) {
                        $qq->where('sort_order', $req->sort_order)
                            ->where('id', $cmp, $req->id);
                    });
            })
            ->orderBy('sort_order', $dir)
            ->orderBy('id', $dir)
            ->first();

        if (! $neighbor) {
            return;
        }

        $reqOrder = $req->sort_order;
        $neighborOrder = $neighbor->sort_order;

        if ($reqOrder === $neighborOrder) {
            // Tie on sort_order — bump one side so they end up distinct
            // and in the expected order.
            if ($dir === 'desc') {
                $req->sort_order = $neighborOrder;
                $neighbor->sort_order = $neighborOrder + 1;
            } else {
                $req->sort_order = $neighborOrder;
                $neighbor->sort_order = $neighborOrder - 1;
            }
        } else {
            $req->sort_order = $neighborOrder;
            $neighbor->sort_order = $reqOrder;
        }

        $req->save();
        $neighbor->save();
    }

    protected function resetRequirementForm(): void
    {
        $this->editingRequirementId = null;
        $this->requirement_label = '';
        $this->requirement_description = '';
        $this->requirement_is_hard = false;
        $this->requirement_sort_order = 0;
        $this->showRequirementForm = false;
    }

    public function render()
    {
        $query = FundCode::with(['contract', 'createdBy', 'updatedBy'])
            ->withCount(['communicationLogs', 'requirements'])
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

        $activeRequirements = $this->requirementsForFundCodeId
            ? FundCodeRequirement::where('fund_code_id', $this->requirementsForFundCodeId)
                ->ordered()
                ->get()
            : collect();

        $activeFundCode = $this->requirementsForFundCodeId
            ? FundCode::find($this->requirementsForFundCodeId)
            : null;

        return view('livewire.fund-code-management', [
            'fundCodes' => $fundCodes,
            'contracts' => $contracts,
            'activeRequirements' => $activeRequirements,
            'activeFundCode' => $activeFundCode,
        ]);
    }
}
