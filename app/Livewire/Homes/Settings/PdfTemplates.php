<?php

namespace App\Livewire\Homes\Settings;

use App\Models\PdfFormTemplate;
use App\Services\PdfFormFieldExtractor;
use App\Services\PdfFormService;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * PDF form templates (homes.caclv.org settings) — upload, describe, activate,
 * download. Field discovery runs on upload via PdfFormService (pdftk); the
 * per-field MAPPING wizard stays on the main app (deep, rarely touched).
 */
#[Layout('components.layouts.homes')]
class PdfTemplates extends Component
{
    use WithFileUploads;
    use WithPagination;
    use WithPermissionChecks;

    #[Url]
    public string $search = '';

    public bool $pdftkAvailable = false;

    // ── Upload / edit modal ───────────────────────────────────────
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $category = '';

    public $templateFile = null;

    public function mount(): void
    {
        $this->authorizeAction('pdf_templates.view');
        $this->pdftkAvailable = PdfFormFieldExtractor::isPdftkAvailable();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorizeAction('pdf_templates.create');
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorizeAction('pdf_templates.edit');

        $template = PdfFormTemplate::findOrFail($id);
        $this->editingId = $template->id;
        $this->name = (string) $template->name;
        $this->description = (string) ($template->description ?? '');
        $this->category = (string) ($template->category ?? '');
        $this->templateFile = null;
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
        $this->name = '';
        $this->description = '';
        $this->category = '';
        $this->templateFile = null;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        if ($this->editingId) {
            $this->authorizeAction('pdf_templates.edit');

            $this->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|in:housing,participant,contract,other',
            ]);

            PdfFormTemplate::findOrFail($this->editingId)->update([
                'name' => $this->name,
                'description' => $this->description ?: null,
                'category' => $this->category ?: null,
                'updated_by' => Auth::id(),
            ]);

            session()->flash('homes_message', 'Template updated.');
            $this->closeForm();

            return;
        }

        $this->authorizeAction('pdf_templates.create');

        if (! $this->pdftkAvailable) {
            $this->addError('templateFile', 'PDF field scanning (pdftk) is not available on this server — uploads are disabled.');

            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|in:housing,participant,contract,other',
            'templateFile' => 'required|file|mimes:pdf|max:51200', // 50MB, matching the main app
        ]);

        try {
            $template = PdfFormService::createTemplate($this->templateFile, [
                'name' => $this->name,
                'description' => $this->description ?: null,
                'category' => $this->category ?: null,
            ]);
        } catch (\Exception $e) {
            $this->addError('templateFile', 'Failed to upload template: '.$e->getMessage());

            return;
        }

        session()->flash('homes_message', "Template uploaded — found {$template->field_count} form fields. Map them in the main app when ready.");
        $this->closeForm();
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeAction('pdf_templates.edit');

        $template = PdfFormTemplate::findOrFail($id);
        $template->update(['is_active' => ! $template->is_active, 'updated_by' => Auth::id()]);
    }

    public function delete(int $id): void
    {
        $this->authorizeAction('pdf_templates.delete');

        $template = PdfFormTemplate::findOrFail($id);

        if ($template->usage_count > 0) {
            session()->flash('homes_message', 'This template has been used to generate forms — deactivate it instead of deleting.');

            return;
        }

        // Model boot cascades the physical file, mappings, and presets.
        $template->delete();
        session()->flash('homes_message', 'Template deleted.');
    }

    public function render()
    {
        $templates = PdfFormTemplate::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%")
                ->orWhere('original_filename', 'like', "%{$this->search}%")))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.homes.settings.pdf-templates', [
            'templates' => $templates,
            'mainApp' => rtrim(config('app.main_app_url'), '/'),
            'canManage' => $this->canAny(['pdf_templates.create', 'pdf_templates.edit']),
            'canDelete' => $this->can('pdf_templates.delete'),
        ]);
    }
}
