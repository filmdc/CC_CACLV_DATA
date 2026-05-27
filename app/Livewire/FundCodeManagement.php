<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\FundCode;
use App\Models\HousingService;
use App\Models\Requirement;
use App\Models\RequirementDefinition;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FundCodeManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    // Phase 10: top-level tab on the page — 'fund_codes' | 'housing_services'.
    public string $activeTab = 'fund_codes';

    // Search and Filters
    public $search = '';

    public $activeFilter = '';

    // ── Phase 10: per-fund-code atom attachment modal ─────────────
    public bool $showAtomLinkModal = false;

    public ?int $atomLinkFundCodeId = null;

    public array $atomLinkSelectedIds = [];

    // ── Phase 10: housing-services tab state ──────────────────────
    public bool $showServiceForm = false;

    public ?int $serviceFormId = null;

    public string $serviceName = '';

    public string $serviceDescription = '';

    public bool $serviceActive = true;

    public ?int $editingServiceId = null;

    public array $serviceLinkedFundCodeIds = [];

    public array $serviceLinkedAtomIds = [];

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
        'activeTab' => ['as' => 'tab', 'except' => 'fund_codes'],
        'editingServiceId' => ['as' => 'service'],
    ];

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['fund_codes', 'housing_services'], true)) {
            return;
        }
        $this->activeTab = $tab;
        $this->resetPage();
        $this->search = '';
        $this->editingServiceId = null;
    }

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

    // P-18: global fund-code delete keep-or-cascade prompt state.
    public $showDeleteFundCodeModal = false;

    public $deletingFundCodeId = null;

    public $deleteFundCodeRequirementCount = 0;

    /**
     * Entry for global fund-code delete (admin). Three branches:
     *   1. Communication-log usage exists → block with flash error
     *      (existing pre-Phase 3A guard, unchanged).
     *   2. No fund-code-source requirements anywhere → delete now.
     *   3. Has requirements (across one or more projects) → open the
     *      keep/cascade modal; the actual delete happens in
     *      confirmDeleteFundCode().
     */
    public function delete($id)
    {
        $this->authorizeAction('fund_codes.delete');

        $fundCode = FundCode::findOrFail($id);

        if ($fundCode->communicationLogs()->count() > 0) {
            session()->flash('error', 'Cannot delete fund code that is in use. Consider deactivating it instead.');

            return;
        }

        $reqCount = Requirement::where('source_type', Requirement::SOURCE_FUND_CODE)
            ->where('source_id', $fundCode->id)
            ->count();

        if ($reqCount === 0) {
            $fundCode->delete();
            session()->flash('success', 'Fund code deleted successfully.');

            return;
        }

        $this->deletingFundCodeId = $fundCode->id;
        $this->deleteFundCodeRequirementCount = $reqCount;
        $this->showDeleteFundCodeModal = true;
    }

    public function confirmDeleteFundCode(bool $cascadeRequirements): void
    {
        $this->authorizeAction('fund_codes.delete');

        $id = $this->deletingFundCodeId;

        if ($id === null) {
            $this->closeDeleteFundCodeModal();

            return;
        }

        $fundCode = FundCode::find($id);

        if ($fundCode === null) {
            $this->closeDeleteFundCodeModal();

            return;
        }

        if ($cascadeRequirements) {
            Requirement::where('source_type', Requirement::SOURCE_FUND_CODE)
                ->where('source_id', $id)
                ->delete();
        } else {
            // Keep branch: leave the requirement rows in place. Their
            // source_id continues to reference an id whose fund_codes
            // row is gone — by design, the audit text in
            // source_label_snapshot survives so the project history
            // remains legible.
        }

        $fundCode->delete();
        session()->flash('success', 'Fund code deleted successfully.');

        $this->closeDeleteFundCodeModal();
    }

    public function closeDeleteFundCodeModal(): void
    {
        $this->showDeleteFundCodeModal = false;
        $this->deletingFundCodeId = null;
        $this->deleteFundCodeRequirementCount = 0;
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

    // ─────────────────────────────────────────────────────────────
    // Phase 10: per-fund-code atom attachment (replaces the bundle path).
    // ─────────────────────────────────────────────────────────────

    public function openAtomLinkModal(int $fundCodeId): void
    {
        $this->authorizeAction('fund_codes.edit');
        $fc = FundCode::with('requirementDefinitions')->findOrFail($fundCodeId);
        $this->atomLinkFundCodeId = $fc->id;
        $this->atomLinkSelectedIds = $fc->requirementDefinitions->pluck('id')->all();
        $this->showAtomLinkModal = true;
    }

    public function closeAtomLinkModal(): void
    {
        $this->showAtomLinkModal = false;
        $this->atomLinkFundCodeId = null;
        $this->atomLinkSelectedIds = [];
    }

    public function toggleAtomLink(int $atomId): void
    {
        $current = array_map('intval', $this->atomLinkSelectedIds);
        if (in_array($atomId, $current, true)) {
            $this->atomLinkSelectedIds = array_values(array_filter($current, fn ($v) => $v !== $atomId));
        } else {
            $this->atomLinkSelectedIds = [...$current, $atomId];
        }
    }

    public function saveAtomLinks(): void
    {
        $this->authorizeAction('fund_codes.edit');
        if (! $this->atomLinkFundCodeId) {
            return;
        }
        $fc = FundCode::findOrFail($this->atomLinkFundCodeId);
        $payload = collect($this->atomLinkSelectedIds)
            ->mapWithKeys(fn ($id) => [(int) $id => ['created_by_user_id' => Auth::id(), 'created_at' => now()]])
            ->all();
        $fc->requirementDefinitions()->sync($payload);
        session()->flash('success', 'Linked requirements updated.');
        $this->closeAtomLinkModal();
    }

    // ─────────────────────────────────────────────────────────────
    // Phase 10: housing services CRUD (merged from /requirements-library).
    // ─────────────────────────────────────────────────────────────

    public function showCreateService(): void
    {
        $this->authorizeAction('fund_codes.edit');
        $this->resetServiceForm();
        $this->serviceFormId = null;
        $this->showServiceForm = true;
    }

    public function showEditService(int $id): void
    {
        $this->authorizeAction('fund_codes.edit');
        $svc = HousingService::withTrashed()->findOrFail($id);
        $this->serviceFormId = $svc->id;
        $this->serviceName = $svc->name;
        $this->serviceDescription = $svc->description ?? '';
        $this->serviceActive = (bool) $svc->is_active;
        $this->showServiceForm = true;
    }

    public function saveService(): void
    {
        $this->authorizeAction('fund_codes.edit');

        $validated = $this->validate([
            'serviceName' => 'required|string|max:255',
            'serviceDescription' => 'nullable|string|max:1000',
            'serviceActive' => 'boolean',
        ]);

        $payload = [
            'name' => $validated['serviceName'],
            'description' => $validated['serviceDescription'] !== '' ? $validated['serviceDescription'] : null,
            'is_active' => $validated['serviceActive'],
            'updated_by_user_id' => Auth::id(),
        ];

        if ($this->serviceFormId) {
            HousingService::findOrFail($this->serviceFormId)->update($payload);
            session()->flash('success', 'County updated.');
        } else {
            $payload['created_by_user_id'] = Auth::id();
            $svc = HousingService::create($payload);
            session()->flash('success', 'County created.');
            $this->editingServiceId = $svc->id;
            $this->loadServiceDetail($svc->id);
        }

        $this->showServiceForm = false;
        $this->resetServiceForm();
    }

    public function deleteService(int $id): void
    {
        $this->authorizeAction('fund_codes.edit');
        HousingService::findOrFail($id)->delete();
        session()->flash('success', 'County moved to trash.');
        if ($this->editingServiceId === $id) {
            $this->editingServiceId = null;
        }
    }

    public function openService(int $id): void
    {
        $this->authorizeAction('fund_codes.view');
        $this->editingServiceId = $id;
        $this->loadServiceDetail($id);
    }

    public function closeService(): void
    {
        $this->editingServiceId = null;
        $this->serviceLinkedFundCodeIds = [];
        $this->serviceLinkedAtomIds = [];
    }

    private function loadServiceDetail(int $id): void
    {
        $svc = HousingService::with(['fundCodes', 'requirementDefinitions'])->find($id);
        if (! $svc) {
            $this->editingServiceId = null;

            return;
        }
        $this->serviceLinkedFundCodeIds = $svc->fundCodes->pluck('id')->all();
        $this->serviceLinkedAtomIds = $svc->requirementDefinitions->pluck('id')->all();
    }

    public function toggleServiceFundCode(int $id): void
    {
        $this->serviceLinkedFundCodeIds = $this->toggleId($this->serviceLinkedFundCodeIds, $id);
    }

    public function toggleServiceAtom(int $id): void
    {
        $this->serviceLinkedAtomIds = $this->toggleId($this->serviceLinkedAtomIds, $id);
    }

    public function saveServiceLinks(): void
    {
        $this->authorizeAction('fund_codes.edit');
        if (! $this->editingServiceId) {
            return;
        }
        $svc = HousingService::findOrFail($this->editingServiceId);
        $payload = fn (array $ids) => collect($ids)
            ->mapWithKeys(fn ($id) => [(int) $id => ['created_by_user_id' => Auth::id(), 'created_at' => now()]])
            ->all();
        $svc->fundCodes()->sync($payload($this->serviceLinkedFundCodeIds));
        $svc->requirementDefinitions()->sync($payload($this->serviceLinkedAtomIds));
        session()->flash('success', 'County links updated.');
    }

    private function toggleId(array $list, int $id): array
    {
        $intList = array_map('intval', $list);
        $id = (int) $id;
        if (in_array($id, $intList, true)) {
            return array_values(array_filter($intList, fn ($v) => $v !== $id));
        }
        $intList[] = $id;

        return $intList;
    }

    private function resetServiceForm(): void
    {
        $this->serviceFormId = null;
        $this->serviceName = '';
        $this->serviceDescription = '';
        $this->serviceActive = true;
        $this->resetErrorBag();
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

        // Phase 10: data for atom-link modal and the housing-services tab.
        $allAtoms = RequirementDefinition::orderBy('label')->get();

        $housingServicesQuery = HousingService::query()
            ->withCount(['fundCodes', 'requirementDefinitions'])
            ->when($this->search !== '' && $this->activeTab === 'housing_services',
                fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name');

        $housingServices = $this->activeTab === 'housing_services'
            ? $housingServicesQuery->paginate(20)
            : collect();

        $servicesDetail = null;
        $servicesAllFundCodes = collect();
        $servicesAllAtoms = collect();
        if ($this->editingServiceId) {
            $servicesDetail = HousingService::withTrashed()->find($this->editingServiceId);
            if ($servicesDetail) {
                $servicesAllFundCodes = FundCode::active()->orderBy('code')->get();
                $servicesAllAtoms = $allAtoms;
            }
        }

        $atomLinkFundCode = $this->atomLinkFundCodeId
            ? FundCode::find($this->atomLinkFundCodeId)
            : null;

        return view('livewire.fund-code-management', [
            'fundCodes' => $fundCodes,
            'contracts' => $contracts,
            // Phase 10 additions
            'allAtoms' => $allAtoms,
            'atomLinkFundCode' => $atomLinkFundCode,
            'housingServices' => $housingServices,
            'servicesDetail' => $servicesDetail,
            'servicesAllFundCodes' => $servicesAllFundCodes,
            'servicesAllAtoms' => $servicesAllAtoms,
        ]);
    }
}
