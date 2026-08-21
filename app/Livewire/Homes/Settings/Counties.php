<?php

namespace App\Livewire\Homes\Settings;

use App\Models\FundCode;
use App\Models\HousingService;
use App\Models\RequirementDefinition;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Counties / housing services (homes.caclv.org settings) — the catalog of
 * "what are we doing for this client?" answers. Each county carries linked
 * fund codes (for budgeting) and requirement items that stamp onto an
 * intake when the county is attached to a call. Mirrors the main app's
 * Housing Services tab (same fund_codes.* gates, same pivot syncing).
 */
#[Layout('components.layouts.homes')]
class Counties extends Component
{
    use WithPermissionChecks;

    #[Url]
    public string $search = '';

    public bool $showTrashed = false;

    // ── Form modal ────────────────────────────────────────────────
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $serviceName = '';

    public string $serviceDescription = '';

    public bool $serviceActive = true;

    // ── Inline link editor (expanded row) ─────────────────────────
    public ?int $openId = null;

    public array $linkedFundCodeIds = [];

    public array $linkedAtomIds = [];

    public function mount(): void
    {
        $this->authorizeAction('fund_codes.view');
    }

    // ── CRUD ──────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->authorizeAction('fund_codes.edit');
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorizeAction('fund_codes.edit');

        $svc = HousingService::withTrashed()->findOrFail($id);
        $this->editingId = $svc->id;
        $this->serviceName = (string) $svc->name;
        $this->serviceDescription = (string) ($svc->description ?? '');
        $this->serviceActive = (bool) $svc->is_active;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->serviceName = '';
        $this->serviceDescription = '';
        $this->serviceActive = true;
        $this->resetErrorBag();
    }

    public function save(): void
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

        if ($this->editingId) {
            HousingService::withTrashed()->findOrFail($this->editingId)->update($payload);
            session()->flash('homes_message', 'County updated.');
        } else {
            $svc = HousingService::create($payload + ['created_by_user_id' => Auth::id()]);
            session()->flash('homes_message', 'County created — now link its funds and required items.');
            $this->openLinks($svc->id);
        }

        $this->closeForm();
    }

    /** Soft delete — historical comm-log → county references survive. */
    public function delete(int $id): void
    {
        $this->authorizeAction('fund_codes.edit');

        HousingService::findOrFail($id)->delete();

        if ($this->openId === $id) {
            $this->openId = null;
        }

        session()->flash('homes_message', 'County retired (existing intakes keep their history).');
    }

    public function restore(int $id): void
    {
        $this->authorizeAction('fund_codes.edit');

        HousingService::withTrashed()->findOrFail($id)->restore();
        session()->flash('homes_message', 'County restored.');
    }

    // ── Fund / requirement links ──────────────────────────────────

    public function openLinks(int $id): void
    {
        $this->authorizeAction('fund_codes.view');

        if ($this->openId === $id) {
            $this->openId = null;

            return;
        }

        $svc = HousingService::with(['fundCodes', 'requirementDefinitions'])->findOrFail($id);
        $this->openId = $svc->id;
        $this->linkedFundCodeIds = $svc->fundCodes->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->linkedAtomIds = $svc->requirementDefinitions->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    public function toggleFund(int $id): void
    {
        $this->linkedFundCodeIds = $this->toggleId($this->linkedFundCodeIds, $id);
    }

    public function toggleAtom(int $id): void
    {
        $this->linkedAtomIds = $this->toggleId($this->linkedAtomIds, $id);
    }

    public function saveLinks(): void
    {
        $this->authorizeAction('fund_codes.edit');

        if (! $this->openId) {
            return;
        }

        $svc = HousingService::findOrFail($this->openId);

        // Same pivot payload as the main app's saveServiceLinks().
        $payload = fn (array $ids) => collect($ids)
            ->mapWithKeys(fn ($id) => [(int) $id => ['created_by_user_id' => Auth::id(), 'created_at' => now()]])
            ->all();

        $svc->fundCodes()->sync($payload($this->linkedFundCodeIds));
        $svc->requirementDefinitions()->sync($payload($this->linkedAtomIds));

        session()->flash('homes_message', 'County links updated — new intakes picking this county get the new set.');
    }

    protected function toggleId(array $list, int $id): array
    {
        $intList = array_map('intval', $list);

        if (in_array($id, $intList, true)) {
            return array_values(array_filter($intList, fn ($v) => $v !== $id));
        }

        $intList[] = $id;

        return $intList;
    }

    public function render()
    {
        $counties = HousingService::query()
            ->when($this->showTrashed, fn ($q) => $q->onlyTrashed())
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount(['fundCodes', 'requirementDefinitions', 'communicationLogs'])
            ->orderBy('name')
            ->get();

        return view('livewire.homes.settings.counties', [
            'counties' => $counties,
            'allFunds' => $this->openId
                ? FundCode::where('is_active', true)->orderBy('code')->get(['id', 'code', 'description', 'expires_at'])
                : collect(),
            'allAtoms' => $this->openId
                ? RequirementDefinition::orderBy('category')->orderBy('label')->get(['id', 'label', 'category'])
                : collect(),
            'canManage' => $this->can('fund_codes.edit'),
        ]);
    }
}
