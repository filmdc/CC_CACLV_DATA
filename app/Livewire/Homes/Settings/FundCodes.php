<?php

namespace App\Livewire\Homes\Settings;

use App\Models\Contract;
use App\Models\FundCode;
use App\Models\Requirement;
use App\Models\RequirementDefinition;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Fund codes (homes.caclv.org settings) — CRUD + expiry dates + the per-fund
 * requirement-atom links, in the portal's design language. The Housing
 * Services sub-surface stays in the main app (counties are attachable from
 * the call detail page day-to-day).
 */
#[Layout('components.layouts.homes')]
class FundCodes extends Component
{
    use WithPagination;
    use WithPermissionChecks;

    #[Url]
    public string $search = '';

    #[Url]
    public string $pill = 'active';

    // ── Form modal ────────────────────────────────────────────────
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $description = '';

    public $contractId = null;

    public string $startsAt = '';

    public string $expiresAt = '';

    public bool $isActive = true;

    // ── Atom-link modal ───────────────────────────────────────────
    public ?int $linkingFundId = null;

    public array $linkedAtomIds = [];

    public function mount(): void
    {
        $this->authorizeAction('fund_codes.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setPill(string $pill): void
    {
        $this->pill = in_array($pill, ['active', 'inactive', 'all'], true) ? $pill : 'active';
        $this->resetPage();
    }

    // ── CRUD ──────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->authorizeAction('fund_codes.create');
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorizeAction('fund_codes.edit');

        $fund = FundCode::findOrFail($id);
        $this->editingId = $fund->id;
        $this->code = (string) $fund->code;
        $this->description = (string) ($fund->description ?? '');
        $this->contractId = $fund->contract_id;
        $this->startsAt = $fund->starts_at?->toDateString() ?? '';
        $this->expiresAt = $fund->expires_at?->toDateString() ?? '';
        $this->isActive = (bool) $fund->is_active;
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
        $this->code = '';
        $this->description = '';
        $this->contractId = null;
        $this->startsAt = '';
        $this->expiresAt = '';
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->authorizeAction($this->editingId ? 'fund_codes.edit' : 'fund_codes.create');

        $this->contractId = $this->contractId === '' ? null : $this->contractId;

        $this->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('fund_codes', 'code')->ignore($this->editingId)],
            'description' => 'required|string|max:255',
            'contractId' => 'nullable|exists:contracts,id',
            'startsAt' => 'nullable|date',
            'expiresAt' => 'nullable|date|after_or_equal:startsAt',
            'isActive' => 'boolean',
        ]);

        $payload = [
            'code' => strtoupper(trim($this->code)),
            'description' => trim($this->description),
            'contract_id' => $this->contractId,
            'starts_at' => $this->startsAt ?: null,
            'expires_at' => $this->expiresAt ?: null,
            'is_active' => $this->isActive,
            'updated_by' => Auth::id(),
        ];

        if ($this->editingId) {
            FundCode::findOrFail($this->editingId)->update($payload);
            session()->flash('homes_message', 'Fund code updated.');
        } else {
            FundCode::create($payload + ['created_by' => Auth::id()]);
            session()->flash('homes_message', 'Fund code created.');
        }

        $this->closeForm();
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeAction('fund_codes.edit');

        $fund = FundCode::findOrFail($id);
        $fund->update(['is_active' => ! $fund->is_active, 'updated_by' => Auth::id()]);
    }

    /**
     * Guarded, conservative delete: the portal only deletes fund codes with
     * NO usage anywhere (comm logs or project requirements). Anything in use
     * should be deactivated instead — the main app keeps the cascade tooling.
     */
    public function delete(int $id): void
    {
        $this->authorizeAction('fund_codes.delete');

        $fund = FundCode::findOrFail($id);

        if ($fund->communicationLogs()->exists()) {
            session()->flash('homes_message', 'This fund code is attached to communication logs — deactivate it instead of deleting.');

            return;
        }

        if (Requirement::where('source_type', Requirement::SOURCE_FUND_CODE)->where('source_id', $fund->id)->exists()) {
            session()->flash('homes_message', 'Projects carry requirements from this fund code — deactivate it instead of deleting.');

            return;
        }

        $fund->delete();
        session()->flash('homes_message', 'Fund code deleted.');
    }

    // ── Requirement-atom links ────────────────────────────────────

    public function openAtomLinks(int $fundId): void
    {
        $this->authorizeAction('fund_codes.edit');

        $fund = FundCode::findOrFail($fundId);
        $this->linkingFundId = $fund->id;
        $this->linkedAtomIds = $fund->requirementDefinitions()->pluck('requirement_definitions.id')->all();
    }

    public function toggleAtom(int $atomId): void
    {
        if (in_array($atomId, $this->linkedAtomIds, true)) {
            $this->linkedAtomIds = array_values(array_filter($this->linkedAtomIds, fn ($id) => $id !== $atomId));
        } else {
            $this->linkedAtomIds[] = $atomId;
        }
    }

    public function saveAtomLinks(): void
    {
        $this->authorizeAction('fund_codes.edit');

        if (! $this->linkingFundId) {
            return;
        }

        $fund = FundCode::findOrFail($this->linkingFundId);
        $pivot = collect($this->linkedAtomIds)
            ->mapWithKeys(fn ($id) => [$id => ['created_by_user_id' => Auth::id(), 'created_at' => now()]])
            ->all();

        $fund->requirementDefinitions()->sync($pivot);

        $this->linkingFundId = null;
        session()->flash('homes_message', 'Requirement links updated.');
    }

    public function render()
    {
        $funds = FundCode::query()
            ->withCount(['communicationLogs', 'requirementDefinitions'])
            ->when($this->search !== '', fn ($q) => $q->search($this->search))
            ->when($this->pill === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->pill === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('code')
            ->paginate(20);

        return view('livewire.homes.settings.fund-codes', [
            'funds' => $funds,
            'contracts' => Contract::where('archived', false)->orderBy('contract_number')->get(['id', 'contract_number', 'title']),
            'atoms' => $this->linkingFundId
                ? RequirementDefinition::orderBy('category')->orderBy('label')->get(['id', 'label', 'kind', 'category'])
                : collect(),
            'canManage' => $this->canAny(['fund_codes.create', 'fund_codes.edit']),
            'canDelete' => $this->can('fund_codes.delete'),
        ]);
    }
}
