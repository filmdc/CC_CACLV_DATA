<?php

namespace App\Livewire\Homes\Settings;

use App\Models\Household;
use App\Models\Property;
use App\Models\PropertyNote;
use App\Models\ReferenceData;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Properties (homes.caclv.org settings) — addresses, types, and notes.
 * Document review stays on the main app's property page.
 */
#[Layout('components.layouts.homes')]
class Properties extends Component
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

    public string $householdSearch = '';

    // ── Notes ─────────────────────────────────────────────────────
    public ?int $notingPropertyId = null;

    public string $noteText = '';

    protected function blankForm(): array
    {
        return [
            'household_id' => null,
            'property_type_id' => null,
            'address_line_1' => '',
            'address_line_2' => '',
            'city' => '',
            'state' => 'PA',
            'zip_code' => '',
            'county' => '',
            'year_built' => '',
            'bedrooms' => '',
            'bathrooms' => '',
            'square_footage' => '',
            'property_description' => '',
            'status' => 'active',
        ];
    }

    public function mount(): void
    {
        $this->authorizeAction('properties.view');
        $this->form = $this->blankForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setPill(string $pill): void
    {
        $this->pill = in_array($pill, ['active', 'all'], true) ? $pill : 'active';
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorizeAction('properties.create');
        $this->editingId = null;
        $this->form = $this->blankForm();
        $this->householdSearch = '';
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorizeAction('properties.edit');

        $property = Property::findOrFail($id);
        $this->editingId = $property->id;
        $this->form = [
            'household_id' => $property->household_id,
            'property_type_id' => $property->property_type_id,
            'address_line_1' => (string) $property->address_line_1,
            'address_line_2' => (string) ($property->address_line_2 ?? ''),
            'city' => (string) $property->city,
            'state' => (string) ($property->state ?? 'PA'),
            'zip_code' => (string) ($property->zip_code ?? ''),
            'county' => (string) ($property->county ?? ''),
            'year_built' => $property->year_built !== null ? (string) $property->year_built : '',
            'bedrooms' => $property->bedrooms !== null ? (string) $property->bedrooms : '',
            'bathrooms' => $property->bathrooms !== null ? (string) $property->bathrooms : '',
            'square_footage' => $property->square_footage !== null ? (string) $property->square_footage : '',
            'property_description' => (string) ($property->property_description ?? ''),
            'status' => (string) $property->status,
        ];
        $this->householdSearch = '';
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeAction($this->editingId ? 'properties.edit' : 'properties.create');

        foreach (['household_id', 'property_type_id'] as $k) {
            $this->form[$k] = $this->form[$k] === '' ? null : $this->form[$k];
        }

        $validated = $this->validate([
            'form.household_id' => 'required|exists:households,id',
            'form.property_type_id' => 'required|exists:reference_data,id',
            'form.address_line_1' => 'required|string|max:255',
            'form.address_line_2' => 'nullable|string|max:255',
            'form.city' => 'required|string|max:100',
            'form.state' => 'required|string|size:2',
            'form.zip_code' => ['required', 'regex:/^\d{5}(-\d{4})?$/'],
            'form.county' => 'required|string|max:100',
            'form.year_built' => 'nullable|integer|min:1800|max:'.(now()->year + 1),
            'form.bedrooms' => 'nullable|integer|min:0|max:20',
            'form.bathrooms' => 'nullable|numeric|min:0|max:20',
            'form.square_footage' => 'nullable|integer|min:0',
            'form.property_description' => 'nullable|string',
            'form.status' => 'required|in:active,inactive,sold,demolished',
        ])['form'];

        $payload = collect($validated)
            ->map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v)
            ->all();
        $payload['address_line_1'] = $validated['address_line_1'];
        $payload['city'] = $validated['city'];
        $payload['county'] = $validated['county'];
        $payload['state'] = strtoupper($validated['state']);
        // properties.participant_id is NOT NULL — link the household head,
        // falling back to any member; refuse cleanly if the household is empty.
        $household = Household::find($validated['household_id']);
        $participantId = $household?->participants()->where('is_head_of_household', true)->first()?->id
            ?? $household?->participants()->first()?->id;

        if ($participantId === null) {
            $this->addError('form.household_id', 'This household has no members yet — add the homeowner as a participant first.');

            return;
        }

        $payload['participant_id'] = $participantId;
        $payload['updated_by'] = Auth::id();

        if ($this->editingId) {
            Property::findOrFail($this->editingId)->update($payload);
            session()->flash('homes_message', 'Property updated.');
        } else {
            Property::create($payload + ['created_by' => Auth::id()]);
            session()->flash('homes_message', 'Property added.');
        }

        $this->showForm = false;
    }

    public function toggleStatus(int $id): void
    {
        $this->authorizeAction('properties.edit');

        $property = Property::findOrFail($id);
        $property->update([
            'status' => $property->status === 'active' ? 'inactive' : 'active',
            'updated_by' => Auth::id(),
        ]);
    }

    public function delete(int $id): void
    {
        $this->authorizeAction('properties.delete');

        $property = Property::findOrFail($id);

        if ($property->jobs()->exists()) {
            session()->flash('homes_message', 'This property has projects — set it inactive instead of deleting.');

            return;
        }

        $property->delete();
        session()->flash('homes_message', 'Property deleted.');
    }

    // ── Notes ─────────────────────────────────────────────────────

    public function openNote(int $propertyId): void
    {
        $this->authorizeAction('properties.notes');

        Property::findOrFail($propertyId);
        $this->notingPropertyId = $propertyId;
        $this->noteText = '';
    }

    public function saveNote(): void
    {
        $this->authorizeAction('properties.notes');

        if (! $this->notingPropertyId) {
            return;
        }

        $this->validate(['noteText' => 'required|string|max:2000']);

        PropertyNote::create([
            'property_id' => $this->notingPropertyId,
            'note' => trim($this->noteText),
            'created_by' => Auth::id(),
        ]);

        $this->notingPropertyId = null;
        $this->noteText = '';
        session()->flash('homes_message', 'Note added.');
    }

    public function render()
    {
        $properties = Property::query()
            ->with(['household', 'participant', 'propertyType'])
            ->withCount(['jobs', 'notes'])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('address_line_1', 'like', "%{$this->search}%")
                ->orWhere('city', 'like', "%{$this->search}%")
                ->orWhere('zip_code', 'like', "%{$this->search}%")
                ->orWhereHas('participant', fn ($p) => $p
                    ->whereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", ["%{$this->search}%"]))))
            ->when($this->pill === 'active', fn ($q) => $q->where('status', 'active'))
            ->orderBy('address_line_1')
            ->paginate(20);

        // Household picker: only search results (the table is large).
        $households = strlen(trim($this->householdSearch)) >= 2
            ? Household::query()
                ->where('household_code', 'like', "%{$this->householdSearch}%")
                ->orWhereHas('participants', fn ($p) => $p
                    ->whereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", ["%{$this->householdSearch}%"]))
                ->limit(8)
                ->get()
            : collect();

        return view('livewire.homes.settings.properties', [
            'properties' => $properties,
            'propertyTypes' => ReferenceData::getOptions('property_type'),
            'households' => $households,
            'canManage' => $this->canAny(['properties.create', 'properties.edit']),
            'canDelete' => $this->can('properties.delete'),
            'canNote' => $this->can('properties.notes'),
        ]);
    }
}
