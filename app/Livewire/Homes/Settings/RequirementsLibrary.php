<?php

namespace App\Livewire\Homes\Settings;

use App\Models\RequirementDefinition;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Requirements library (homes.caclv.org settings) — the reusable checklist
 * atoms behind intakes and projects. Atoms-only, matching the main app's
 * live surface (bundles/services machinery is dormant there too).
 */
#[Layout('components.layouts.homes')]
class RequirementsLibrary extends Component
{
    use WithPagination;
    use WithPermissionChecks;

    #[Url]
    public string $search = '';

    #[Url]
    public string $categoryFilter = '';

    public bool $showTrashed = false;

    // ── Form modal ────────────────────────────────────────────────
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $atomLabel = '';

    public string $atomKey = '';

    public string $atomKind = 'doc';

    public string $atomCategory = RequirementDefinition::CATEGORY_ELIGIBILITY;

    public bool $atomDefaultRequired = true;

    public bool $atomIsDefaultMilestone = false;

    public string $atomHint = '';

    public function mount(): void
    {
        $this->authorizeAction('requirements_library.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    /** Auto-slug the key from the label while creating (same as the main app). */
    public function updatedAtomLabel(): void
    {
        if (! $this->editingId) {
            $this->atomKey = Str::slug($this->atomLabel);
        }
    }

    public function openCreate(): void
    {
        $this->authorizeAction('requirements_library.manage');
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');

        $atom = RequirementDefinition::withTrashed()->findOrFail($id);
        $this->editingId = $atom->id;
        $this->atomLabel = (string) $atom->label;
        $this->atomKey = (string) $atom->key;
        $this->atomKind = (string) $atom->kind;
        $this->atomCategory = (string) $atom->category;
        $this->atomDefaultRequired = (bool) $atom->default_required;
        $this->atomIsDefaultMilestone = (bool) $atom->is_default_milestone;
        $this->atomHint = (string) ($atom->hint ?? '');
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
        $this->atomLabel = '';
        $this->atomKey = '';
        $this->atomKind = 'doc';
        $this->atomCategory = RequirementDefinition::CATEGORY_ELIGIBILITY;
        $this->atomDefaultRequired = true;
        $this->atomIsDefaultMilestone = false;
        $this->atomHint = '';
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $this->authorizeAction('requirements_library.manage');

        $this->validate([
            'atomLabel' => 'required|string|max:255',
            'atomKey' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9-]+$/'],
            'atomKind' => 'required|in:'.implode(',', RequirementDefinition::KINDS),
            'atomCategory' => 'required|in:'.implode(',', RequirementDefinition::CATEGORIES),
            'atomDefaultRequired' => 'boolean',
            'atomIsDefaultMilestone' => 'boolean',
            'atomHint' => 'nullable|string|max:1000',
        ]);

        // Manual uniqueness against non-trashed rows (matches the main app).
        $keyTaken = RequirementDefinition::where('key', $this->atomKey)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($keyTaken) {
            $this->addError('atomKey', 'This key is already in use.');

            return;
        }

        $payload = [
            'label' => trim($this->atomLabel),
            'key' => $this->atomKey,
            'kind' => $this->atomKind,
            'category' => $this->atomCategory,
            'default_required' => $this->atomDefaultRequired,
            'is_default_milestone' => $this->atomIsDefaultMilestone,
            'hint' => trim($this->atomHint) ?: null,
            'updated_by_user_id' => Auth::id(),
        ];

        if ($this->editingId) {
            RequirementDefinition::withTrashed()->findOrFail($this->editingId)->update($payload);
            session()->flash('homes_message', 'Item updated.');
        } else {
            RequirementDefinition::create($payload + ['created_by_user_id' => Auth::id()]);
            session()->flash('homes_message', 'Item added to the library.');
        }

        $this->closeForm();
    }

    /** Soft delete — historical intake/project references stay intact. */
    public function delete(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');

        RequirementDefinition::findOrFail($id)->delete();
        session()->flash('homes_message', 'Item retired (existing cases keep their copies).');
    }

    public function restore(int $id): void
    {
        $this->authorizeAction('requirements_library.manage');

        RequirementDefinition::withTrashed()->findOrFail($id)->restore();
        session()->flash('homes_message', 'Item restored.');
    }

    public function render()
    {
        $atoms = RequirementDefinition::query()
            // Shown per row so it is obvious which items reach an intake.
            ->with(['housingServices', 'fundCodes'])
            ->when($this->showTrashed, fn ($q) => $q->onlyTrashed())
            ->when($this->search !== '', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('label', 'like', "%{$this->search}%")
                ->orWhere('key', 'like', "%{$this->search}%")))
            ->when($this->categoryFilter !== '', fn ($q) => $q->where('category', $this->categoryFilter))
            ->orderBy('category')
            ->orderBy('label')
            ->paginate(25);

        return view('livewire.homes.settings.requirements-library', [
            'atoms' => $atoms,
            'categories' => RequirementDefinition::CATEGORIES,
            'kinds' => RequirementDefinition::KINDS,
            'canManage' => $this->can('requirements_library.manage'),
        ]);
    }
}
