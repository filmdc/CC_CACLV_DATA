<?php

namespace App\Livewire;

use App\Models\FundCode;
use App\Models\HousingService;
use App\Models\RequirementDefinition;
use App\Models\RequirementTemplateSet;
use App\Models\Service;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Comm Log v2 (Option A): Kaitlyn's requirements library curation UI.
 *
 * Two top-level views ("atoms" and "bundles") plus a per-bundle detail
 * drill-down for managing items + service auto-apply links. All CRUD
 * lives in this single component so the state machine is easy to follow
 * and the permission gate (`requirements_library.manage`) only needs to
 * be enforced once.
 */
#[Layout('components.layouts.app')]
class RequirementsLibraryManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    // ── Tab + drill state ─────────────────────────────────────────
    public string $activeTab = 'atoms';     // 'atoms' | 'bundles' | 'services'

    public ?int $editingBundleId = null;    // when set: bundle detail view

    public ?int $editingServiceId = null;   // when set: housing-service detail view

    public bool $showTrashed = false;

    public string $search = '';

    public ?string $kindFilter = null;      // atoms tab only

    /** Phase 10: filter atoms by workflow-phase category. */
    public ?string $categoryFilter = null;

    // ── Atom form state ───────────────────────────────────────────
    public bool $showAtomForm = false;

    public ?int $atomFormId = null;         // null = create, int = edit

    public string $atomKey = '';

    public string $atomLabel = '';

    public string $atomKind = 'doc';

    public string $atomCategory = 'eligibility';

    public bool $atomDefaultRequired = true;

    public string $atomHint = '';

    // ── Bundle form state ─────────────────────────────────────────
    public bool $showBundleForm = false;

    public ?int $bundleFormId = null;

    public string $bundleName = '';

    public string $bundleDescription = '';

    // ── Bundle-detail state (items + services) ────────────────────
    public string $itemPickerSearch = '';   // atom search when adding to a bundle

    public ?int $newItemAtomId = null;      // id selected for add

    public array $linkedServiceIds = [];    // service ids currently linked

    // ── Service form + detail state ───────────────────────────────
    public bool $showServiceForm = false;

    public ?int $serviceFormId = null;

    public string $serviceName = '';

    public string $serviceDescription = '';

    public bool $serviceActive = true;

    public array $serviceLinkedFundCodeIds = [];

    public array $serviceLinkedBundleIds = [];

    public array $serviceLinkedAtomIds = [];

    public bool $canManage = false;

    protected $queryString = [
        'activeTab' => ['as' => 'tab', 'except' => 'atoms'],
        'editingBundleId' => ['as' => 'bundle'],
        'editingServiceId' => ['as' => 'service'],
        'showTrashed' => ['as' => 'trashed', 'except' => false],
        'search' => ['except' => ''],
        'kindFilter' => ['as' => 'kind', 'except' => null],
    ];

    public function mount(): void
    {
        $this->authorizeAction('requirements_library.view');
        $this->canManage = $this->can('requirements_library.manage');

        // Phase 10: library is atoms-only. Pin the tab and clear any
        // stale bundle/service drill state inherited from query string.
        $this->activeTab = 'atoms';
        $this->editingBundleId = null;
        $this->editingServiceId = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
        $this->search = '';
        $this->kindFilter = null;
        $this->editingBundleId = null;
        $this->editingServiceId = null;
    }

    public function updatedShowTrashed(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['atoms', 'bundles', 'services'], true)) {
            return;
        }
        $this->activeTab = $tab;
        $this->updatedActiveTab();
    }

    // ─────────────────────────────────────────────────────────────
    // ATOM CRUD
    // ─────────────────────────────────────────────────────────────

    public function showCreateAtom(): void
    {
        $this->authorizeAction('requirements_library.manage');
        $this->resetAtomForm();
        $this->atomFormId = null;
        $this->showAtomForm = true;
    }

    public function showEditAtom(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');
        $atom = RequirementDefinition::withTrashed()->findOrFail($id);

        $this->atomFormId = $atom->id;
        $this->atomKey = $atom->key;
        $this->atomLabel = $atom->label;
        $this->atomKind = $atom->kind;
        $this->atomCategory = $atom->category ?? 'eligibility';
        $this->atomDefaultRequired = (bool) $atom->default_required;
        $this->atomHint = $atom->hint ?? '';
        $this->showAtomForm = true;
    }

    public function saveAtom(): void
    {
        $this->authorizeAction('requirements_library.manage');

        $validated = $this->validate([
            'atomKey'             => 'required|string|max:80|regex:/^[a-z0-9-]+$/',
            'atomLabel'           => 'required|string|max:255',
            'atomKind'            => 'required|in:'.implode(',', RequirementDefinition::KINDS),
            'atomCategory'        => 'required|in:'.implode(',', RequirementDefinition::CATEGORIES),
            'atomDefaultRequired' => 'boolean',
            'atomHint'            => 'nullable|string|max:1000',
        ], [
            'atomKey.regex' => 'Key must be lowercase letters, numbers, and hyphens only (e.g. "photo-id").',
        ]);

        // Uniqueness against non-trashed rows (excluding self).
        $dupe = RequirementDefinition::where('key', $validated['atomKey'])
            ->when($this->atomFormId, fn ($q) => $q->where('id', '!=', $this->atomFormId))
            ->whereNull('deleted_at')
            ->exists();
        if ($dupe) {
            $this->addError('atomKey', 'Another active atom already uses this key.');
            return;
        }

        $payload = [
            'key'              => $validated['atomKey'],
            'label'            => $validated['atomLabel'],
            'kind'             => $validated['atomKind'],
            'category'         => $validated['atomCategory'],
            'default_required' => $validated['atomDefaultRequired'],
            'hint'             => $validated['atomHint'] !== '' ? $validated['atomHint'] : null,
            'updated_by_user_id' => Auth::id(),
        ];

        if ($this->atomFormId) {
            RequirementDefinition::findOrFail($this->atomFormId)->update($payload);
            session()->flash('message', 'Atom updated.');
        } else {
            $payload['created_by_user_id'] = Auth::id();
            RequirementDefinition::create($payload);
            session()->flash('message', 'Atom created.');
        }

        $this->showAtomForm = false;
        $this->resetAtomForm();
    }

    public function deleteAtom(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');
        RequirementDefinition::findOrFail($id)->delete();
        session()->flash('message', 'Atom moved to trash. Historical instances are preserved.');
    }

    public function restoreAtom(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');
        RequirementDefinition::withTrashed()->findOrFail($id)->restore();
        session()->flash('message', 'Atom restored.');
    }

    public function suggestKey(): void
    {
        // Live key suggestion as the user types the label, but only when
        // creating a new atom (don't clobber an edit).
        if ($this->atomFormId !== null) {
            return;
        }
        $this->atomKey = Str::slug($this->atomLabel);
    }

    public function updatedAtomLabel(): void
    {
        $this->suggestKey();
    }

    private function resetAtomForm(): void
    {
        $this->atomFormId = null;
        $this->atomKey = '';
        $this->atomLabel = '';
        $this->atomKind = 'doc';
        $this->atomCategory = 'eligibility';
        $this->atomDefaultRequired = true;
        $this->atomHint = '';
        $this->resetErrorBag();
    }

    // ─────────────────────────────────────────────────────────────
    // BUNDLE CRUD
    // ─────────────────────────────────────────────────────────────

    public function showCreateBundle(): void
    {
        $this->authorizeAction('requirements_library.manage');
        $this->resetBundleForm();
        $this->bundleFormId = null;
        $this->showBundleForm = true;
    }

    public function showEditBundle(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');
        $bundle = RequirementTemplateSet::withTrashed()->findOrFail($id);
        $this->bundleFormId = $bundle->id;
        $this->bundleName = $bundle->name;
        $this->bundleDescription = $bundle->description ?? '';
        $this->showBundleForm = true;
    }

    public function saveBundle(): void
    {
        $this->authorizeAction('requirements_library.manage');

        $validated = $this->validate([
            'bundleName' => 'required|string|max:255',
            'bundleDescription' => 'nullable|string|max:1000',
        ]);

        $payload = [
            'name' => $validated['bundleName'],
            'description' => $validated['bundleDescription'] !== '' ? $validated['bundleDescription'] : null,
            'updated_by_user_id' => Auth::id(),
        ];

        if ($this->bundleFormId) {
            RequirementTemplateSet::findOrFail($this->bundleFormId)->update($payload);
            session()->flash('message', 'Bundle updated.');
        } else {
            $payload['created_by_user_id'] = Auth::id();
            $bundle = RequirementTemplateSet::create($payload);
            session()->flash('message', 'Bundle created.');
            $this->editingBundleId = $bundle->id;
            $this->loadBundleDetail($bundle->id);
        }

        $this->showBundleForm = false;
        $this->resetBundleForm();
    }

    public function deleteBundle(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');
        RequirementTemplateSet::findOrFail($id)->delete();
        session()->flash('message', 'Bundle moved to trash.');
        if ($this->editingBundleId === $id) {
            $this->editingBundleId = null;
        }
    }

    public function restoreBundle(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');
        RequirementTemplateSet::withTrashed()->findOrFail($id)->restore();
        session()->flash('message', 'Bundle restored.');
    }

    private function resetBundleForm(): void
    {
        $this->bundleFormId = null;
        $this->bundleName = '';
        $this->bundleDescription = '';
        $this->resetErrorBag();
    }

    // ─────────────────────────────────────────────────────────────
    // BUNDLE DETAIL (items + services)
    // ─────────────────────────────────────────────────────────────

    public function openBundle(int $id): void
    {
        $this->authorizeAction('requirements_library.view');
        $this->editingBundleId = $id;
        $this->loadBundleDetail($id);
    }

    public function closeBundle(): void
    {
        $this->editingBundleId = null;
        $this->newItemAtomId = null;
        $this->itemPickerSearch = '';
        $this->linkedServiceIds = [];
    }

    private function loadBundleDetail(int $id): void
    {
        $bundle = RequirementTemplateSet::with('services')->find($id);
        if (! $bundle) {
            $this->editingBundleId = null;

            return;
        }
        $this->linkedServiceIds = $bundle->services->pluck('id')->all();
    }

    public function addItem(): void
    {
        $this->authorizeAction('requirements_library.manage');

        if (! $this->editingBundleId || ! $this->newItemAtomId) {
            return;
        }

        $bundle = RequirementTemplateSet::findOrFail($this->editingBundleId);
        $atom = RequirementDefinition::findOrFail($this->newItemAtomId);

        $position = (int) ($bundle->items()->max('requirement_template_set_items.position') ?? -1) + 1;

        $bundle->items()->syncWithoutDetaching([
            $atom->id => ['position' => $position],
        ]);

        $this->newItemAtomId = null;
        $this->itemPickerSearch = '';
        session()->flash('message', 'Added "'.$atom->label.'" to bundle.');
    }

    public function removeItem(int $atomId): void
    {
        $this->authorizeAction('requirements_library.manage');
        if (! $this->editingBundleId) {
            return;
        }
        RequirementTemplateSet::findOrFail($this->editingBundleId)
            ->items()
            ->detach($atomId);
        session()->flash('message', 'Removed from bundle.');
    }

    public function toggleItemRequired(int $atomId, ?bool $value): void
    {
        $this->authorizeAction('requirements_library.manage');
        if (! $this->editingBundleId) {
            return;
        }
        RequirementTemplateSet::findOrFail($this->editingBundleId)
            ->items()
            ->updateExistingPivot($atomId, ['required_override' => $value]);
    }

    public function moveItem(int $atomId, string $direction): void
    {
        $this->authorizeAction('requirements_library.manage');
        if (! $this->editingBundleId) {
            return;
        }
        $bundle = RequirementTemplateSet::findOrFail($this->editingBundleId);

        // Read pivot rows directly to avoid the ambiguous `id` column that
        // belongsToMany inner-joins produce.
        $ids = \Illuminate\Support\Facades\DB::table('requirement_template_set_items')
            ->where('template_set_id', $bundle->id)
            ->orderBy('position')
            ->pluck('requirement_definition_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $idx = array_search($atomId, $ids, true);
        if ($idx === false) {
            return;
        }
        $swap = $direction === 'up' ? $idx - 1 : $idx + 1;
        if ($swap < 0 || $swap >= count($ids)) {
            return;
        }
        [$ids[$idx], $ids[$swap]] = [$ids[$swap], $ids[$idx]];

        foreach ($ids as $position => $id) {
            $bundle->items()->updateExistingPivot($id, ['position' => $position]);
        }
    }

    public function saveServiceLinks(): void
    {
        $this->authorizeAction('requirements_library.manage');
        if (! $this->editingBundleId) {
            return;
        }

        $bundle = RequirementTemplateSet::findOrFail($this->editingBundleId);

        // sync() with created_by + created_at on the pivot.
        $payload = [];
        foreach (array_filter($this->linkedServiceIds) as $sid) {
            $payload[(int) $sid] = [
                'created_by_user_id' => Auth::id(),
                'created_at' => now(),
            ];
        }
        $bundle->services()->sync($payload);

        session()->flash('message', 'Service links updated.');
    }

    public function toggleServiceLink(int $serviceId): void
    {
        $this->authorizeAction('requirements_library.manage');
        if (in_array($serviceId, $this->linkedServiceIds, true)) {
            $this->linkedServiceIds = array_values(array_filter(
                $this->linkedServiceIds,
                fn ($id) => $id !== $serviceId
            ));
        } else {
            $this->linkedServiceIds[] = $serviceId;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // HOUSING SERVICE CRUD + DETAIL
    // ─────────────────────────────────────────────────────────────

    public function showCreateService(): void
    {
        $this->authorizeAction('requirements_library.manage');
        $this->resetServiceForm();
        $this->serviceFormId = null;
        $this->showServiceForm = true;
    }

    public function showEditService(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');
        $svc = HousingService::withTrashed()->findOrFail($id);
        $this->serviceFormId = $svc->id;
        $this->serviceName = $svc->name;
        $this->serviceDescription = $svc->description ?? '';
        $this->serviceActive = (bool) $svc->is_active;
        $this->showServiceForm = true;
    }

    public function saveService(): void
    {
        $this->authorizeAction('requirements_library.manage');

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
            session()->flash('message', 'Service updated.');
        } else {
            $payload['created_by_user_id'] = Auth::id();
            $svc = HousingService::create($payload);
            session()->flash('message', 'Service created.');
            $this->editingServiceId = $svc->id;
            $this->loadServiceDetail($svc->id);
        }

        $this->showServiceForm = false;
        $this->resetServiceForm();
    }

    public function deleteService(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');
        HousingService::findOrFail($id)->delete();
        session()->flash('message', 'Service moved to trash. Historical comm-log references are preserved.');
        if ($this->editingServiceId === $id) {
            $this->editingServiceId = null;
        }
    }

    public function restoreService(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');
        HousingService::withTrashed()->findOrFail($id)->restore();
        session()->flash('message', 'Service restored.');
    }

    public function openService(int $id): void
    {
        $this->authorizeAction('requirements_library.view');
        $this->editingServiceId = $id;
        $this->loadServiceDetail($id);
    }

    public function closeService(): void
    {
        $this->editingServiceId = null;
        $this->serviceLinkedFundCodeIds = [];
        $this->serviceLinkedBundleIds = [];
        $this->serviceLinkedAtomIds = [];
    }

    private function loadServiceDetail(int $id): void
    {
        $svc = HousingService::with(['fundCodes', 'templateSets', 'requirementDefinitions'])->find($id);
        if (! $svc) {
            $this->editingServiceId = null;

            return;
        }
        $this->serviceLinkedFundCodeIds = $svc->fundCodes->pluck('id')->all();
        $this->serviceLinkedBundleIds = $svc->templateSets->pluck('id')->all();
        $this->serviceLinkedAtomIds = $svc->requirementDefinitions->pluck('id')->all();
    }

    public function toggleServiceFundCode(int $id): void
    {
        $this->serviceLinkedFundCodeIds = $this->toggleId($this->serviceLinkedFundCodeIds, $id);
    }

    public function toggleServiceBundle(int $id): void
    {
        $this->serviceLinkedBundleIds = $this->toggleId($this->serviceLinkedBundleIds, $id);
    }

    public function toggleServiceAtom(int $id): void
    {
        $this->serviceLinkedAtomIds = $this->toggleId($this->serviceLinkedAtomIds, $id);
    }

    public function saveServiceLinks2(): void
    {
        $this->authorizeAction('requirements_library.manage');
        if (! $this->editingServiceId) {
            return;
        }
        $svc = HousingService::findOrFail($this->editingServiceId);
        $userId = Auth::id();
        $now = now();
        $sync = fn (array $ids) => collect($ids)
            ->mapWithKeys(fn ($id) => [(int) $id => ['created_by_user_id' => $userId, 'created_at' => $now]])
            ->all();

        $svc->fundCodes()->sync($sync($this->serviceLinkedFundCodeIds));
        $svc->templateSets()->sync($sync($this->serviceLinkedBundleIds));
        $svc->requirementDefinitions()->sync($sync($this->serviceLinkedAtomIds));

        session()->flash('message', 'Service links updated.');
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

    // ─────────────────────────────────────────────────────────────
    // RENDER
    // ─────────────────────────────────────────────────────────────

    public function render()
    {
        $atomsQuery = RequirementDefinition::query()
            ->when($this->showTrashed, fn ($q) => $q->withTrashed())
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $q->where('label', 'like', "%{$this->search}%")
                  ->orWhere('key', 'like', "%{$this->search}%");
            }))
            ->when($this->kindFilter, fn ($q) => $q->where('kind', $this->kindFilter))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderBy('label');

        $bundlesQuery = RequirementTemplateSet::query()
            ->withCount(['items', 'services'])
            ->when($this->showTrashed, fn ($q) => $q->withTrashed())
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name');

        $bundleDetail = null;
        $bundleItems = collect();
        $atomsForPicker = collect();
        $allServices = collect();
        if ($this->editingBundleId) {
            $bundleDetail = RequirementTemplateSet::withTrashed()->find($this->editingBundleId);
            if ($bundleDetail) {
                $bundleItems = $bundleDetail->items()
                    ->orderBy('requirement_template_set_items.position')
                    ->get();

                $usedAtomIds = $bundleItems->pluck('id')->all();
                $atomsForPicker = RequirementDefinition::query()
                    ->whereNotIn('id', $usedAtomIds)
                    ->when($this->itemPickerSearch !== '', fn ($q) => $q->where(function ($q) {
                        $q->where('label', 'like', "%{$this->itemPickerSearch}%")
                          ->orWhere('key', 'like', "%{$this->itemPickerSearch}%");
                    }))
                    ->orderBy('label')
                    ->limit(20)
                    ->get();

                $allServices = Service::with('program:id,name')
                    ->orderBy('name')
                    ->get();
            }
        }

        // Services tab data
        $housingServicesQuery = HousingService::query()
            ->withCount(['fundCodes', 'templateSets', 'requirementDefinitions'])
            ->when($this->showTrashed, fn ($q) => $q->withTrashed())
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name');

        $housingServiceDetail = null;
        $serviceAllFundCodes = collect();
        $serviceAllBundles = collect();
        $serviceAllAtoms = collect();
        if ($this->editingServiceId) {
            $housingServiceDetail = HousingService::withTrashed()->find($this->editingServiceId);
            if ($housingServiceDetail) {
                $serviceAllFundCodes = FundCode::active()->orderBy('code')->get();
                $serviceAllBundles = RequirementTemplateSet::orderBy('name')->get();
                $serviceAllAtoms = RequirementDefinition::orderBy('label')->get();
            }
        }

        return view('livewire.requirements-library-management', [
            'atoms' => $this->activeTab === 'atoms' ? $atomsQuery->paginate(25) : collect(),
            'bundles' => $this->activeTab === 'bundles' ? $bundlesQuery->paginate(25) : collect(),
            'housingServices' => $this->activeTab === 'services' ? $housingServicesQuery->paginate(25) : collect(),
            'kinds' => RequirementDefinition::KINDS,
            'categories' => RequirementDefinition::CATEGORIES,
            'bundleDetail' => $bundleDetail,
            'bundleItems' => $bundleItems,
            'atomsForPicker' => $atomsForPicker,
            'allServices' => $allServices,
            'housingServiceDetail' => $housingServiceDetail,
            'serviceAllFundCodes' => $serviceAllFundCodes,
            'serviceAllBundles' => $serviceAllBundles,
            'serviceAllAtoms' => $serviceAllAtoms,
            'canManage' => $this->can('requirements_library.manage'),
        ]);
    }
}
