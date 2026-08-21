<?php

namespace App\Livewire\Homes\Settings;

use App\Models\Contractor;
use App\Models\ReferenceData;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Contractors (homes.caclv.org settings) — licensing, insurance, and contact
 * details with expiry warnings. Document approval stays on the main app's
 * ContractorShow page (its own permission + workflow).
 */
#[Layout('components.layouts.homes')]
class Contractors extends Component
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

    public array $form = [];

    public array $selectedServices = [];

    protected function blankForm(): array
    {
        return [
            'company_name' => '',
            'contact_first_name' => '',
            'contact_last_name' => '',
            'email' => '',
            'phone' => '',
            'address_line_1' => '',
            'address_line_2' => '',
            'city' => '',
            'state' => 'PA',
            'zip_code' => '',
            'license_number' => '',
            'license_expiration' => '',
            'insurance_provider' => '',
            'insurance_policy_number' => '',
            'insurance_expiration' => '',
            'liability_coverage_amount' => '',
            'ein' => '',
            'status' => 'active',
            'notes' => '',
        ];
    }

    public function mount(): void
    {
        $this->authorizeAction('contractors.view');
        $this->form = $this->blankForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setPill(string $pill): void
    {
        $this->pill = in_array($pill, ['active', 'expiring', 'all'], true) ? $pill : 'active';
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorizeAction('contractors.create');
        $this->editingId = null;
        $this->form = $this->blankForm();
        $this->selectedServices = [];
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorizeAction('contractors.edit');

        $contractor = Contractor::findOrFail($id);
        $this->editingId = $contractor->id;
        $this->form = [
            'company_name' => (string) $contractor->company_name,
            'contact_first_name' => (string) ($contractor->contact_first_name ?? ''),
            'contact_last_name' => (string) ($contractor->contact_last_name ?? ''),
            'email' => (string) ($contractor->email ?? ''),
            'phone' => (string) ($contractor->phone ?? ''),
            'address_line_1' => (string) ($contractor->address_line_1 ?? ''),
            'address_line_2' => (string) ($contractor->address_line_2 ?? ''),
            'city' => (string) ($contractor->city ?? ''),
            'state' => (string) ($contractor->state ?? 'PA'),
            'zip_code' => (string) ($contractor->zip_code ?? ''),
            'license_number' => (string) ($contractor->license_number ?? ''),
            'license_expiration' => $contractor->license_expiration?->toDateString() ?? '',
            'insurance_provider' => (string) ($contractor->insurance_provider ?? ''),
            'insurance_policy_number' => (string) ($contractor->insurance_policy_number ?? ''),
            'insurance_expiration' => $contractor->insurance_expiration?->toDateString() ?? '',
            'liability_coverage_amount' => $contractor->liability_coverage_amount !== null ? (string) $contractor->liability_coverage_amount : '',
            'ein' => (string) ($contractor->ein ?? ''),
            'status' => (string) $contractor->status,
            'notes' => (string) ($contractor->notes ?? ''),
        ];
        $this->selectedServices = array_map('intval', $contractor->services_offered ?? []);
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function toggleService(int $serviceId): void
    {
        if (in_array($serviceId, $this->selectedServices, true)) {
            $this->selectedServices = array_values(array_filter($this->selectedServices, fn ($id) => $id !== $serviceId));
        } else {
            $this->selectedServices[] = $serviceId;
        }
    }

    public function save(): void
    {
        $this->authorizeAction($this->editingId ? 'contractors.edit' : 'contractors.create');

        $validated = $this->validate([
            'form.company_name' => 'required|string|max:255',
            'form.contact_first_name' => 'nullable|string|max:100',
            'form.contact_last_name' => 'nullable|string|max:100',
            'form.email' => 'nullable|email|max:255',
            'form.phone' => 'required|string|max:20',
            'form.address_line_1' => 'required|string|max:255',
            'form.address_line_2' => 'nullable|string|max:255',
            'form.city' => 'required|string|max:100',
            'form.state' => 'required|string|size:2',
            'form.zip_code' => ['required', 'regex:/^\d{5}(-\d{4})?$/'],
            'form.license_number' => 'nullable|string|max:100',
            'form.license_expiration' => 'nullable|date',
            'form.insurance_provider' => 'nullable|string|max:255',
            'form.insurance_policy_number' => 'nullable|string|max:100',
            'form.insurance_expiration' => 'nullable|date',
            'form.liability_coverage_amount' => 'nullable|numeric|min:0',
            'form.ein' => 'nullable|string|max:20',
            'form.status' => 'required|in:active,inactive,suspended',
            'form.notes' => 'nullable|string',
        ])['form'];

        $payload = collect($validated)
            ->map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v)
            ->all();
        $payload['company_name'] = $validated['company_name'];
        $payload['state'] = strtoupper($validated['state']);
        $payload['services_offered'] = $this->selectedServices;
        $payload['updated_by'] = Auth::id();

        if ($this->editingId) {
            Contractor::findOrFail($this->editingId)->update($payload);
            session()->flash('homes_message', 'Contractor updated.');
        } else {
            Contractor::create($payload + ['created_by' => Auth::id()]);
            session()->flash('homes_message', 'Contractor added.');
        }

        $this->showForm = false;
    }

    public function toggleStatus(int $id): void
    {
        $this->authorizeAction('contractors.edit');

        $contractor = Contractor::findOrFail($id);
        $contractor->update([
            'status' => $contractor->status === 'active' ? 'inactive' : 'active',
            'updated_by' => Auth::id(),
        ]);
    }

    public function delete(int $id): void
    {
        $this->authorizeAction('contractors.delete');

        $contractor = Contractor::findOrFail($id);

        if ($contractor->jobs()->exists()) {
            session()->flash('homes_message', 'This contractor has projects — set them inactive instead of deleting.');

            return;
        }

        $contractor->delete();
        session()->flash('homes_message', 'Contractor deleted.');
    }

    public function render()
    {
        $contractors = Contractor::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('company_name', 'like', "%{$this->search}%")
                ->orWhere('contact_first_name', 'like', "%{$this->search}%")
                ->orWhere('contact_last_name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orWhere('license_number', 'like', "%{$this->search}%")))
            ->when($this->pill === 'active', fn ($q) => $q->where('status', 'active'))
            ->when($this->pill === 'expiring', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('license_expiration', '<', now()->addDays(60))
                ->orWhere('insurance_expiration', '<', now()->addDays(60))))
            ->orderBy('company_name')
            ->paginate(20);

        return view('livewire.homes.settings.contractors', [
            'contractors' => $contractors,
            'serviceOptions' => ReferenceData::getOptions('contractor_service'),
            'canManage' => $this->canAny(['contractors.create', 'contractors.edit']),
            'canDelete' => $this->can('contractors.delete'),
        ]);
    }
}
