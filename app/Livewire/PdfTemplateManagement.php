<?php

namespace App\Livewire;

use App\Models\PdfFormTemplate;
use App\Services\PdfFormService;
use App\Services\PdfFormFieldExtractor;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class PdfTemplateManagement extends Component
{
    use WithPagination, WithPermissionChecks, WithFileUploads;

    // UI State
    public $search = '';
    public $showUploadForm = false;
    public $showEditForm = false;
    public $filterCategory = '';
    public $filterActive = 'active';

    // Form Fields
    public $editingTemplateId = null;
    public $name = '';
    public $description = '';
    public $category = '';
    public $templateFile = null;

    // System Status
    public $pdftkInstalled = false;
    public $pdftkVersion = null;

    public function mount()
    {
        $this->authorizeAction('pdf_templates.view');

        // Check pdftk availability
        $this->pdftkInstalled = PdfFormFieldExtractor::isPdftkAvailable();
        if ($this->pdftkInstalled) {
            $this->pdftkVersion = PdfFormFieldExtractor::getPdftkVersion();
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterCategory()
    {
        $this->resetPage();
    }

    public function updatedFilterActive()
    {
        $this->resetPage();
    }

    public function showUploadTemplateForm()
    {
        $this->authorizeAction('pdf_templates.create');

        if (!$this->pdftkInstalled) {
            session()->flash('error', 'PDFtk is not installed. Cannot upload templates. Please contact system administrator.');
            return;
        }

        $this->resetForm();
        $this->showUploadForm = true;
    }

    public function showEditTemplate($id)
    {
        $this->authorizeAction('pdf_templates.edit');

        $template = PdfFormTemplate::findOrFail($id);
        $this->editingTemplateId = $id;
        $this->name = $template->name;
        $this->description = $template->description;
        $this->category = $template->category;
        $this->showEditForm = true;
    }

    public function uploadTemplate()
    {
        $this->authorizeAction('pdf_templates.create');

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|in:housing,participant,contract,other',
            'templateFile' => 'required|file|mimes:pdf|max:51200', // 50MB max
        ]);

        try {
            \Log::info('Starting template upload', [
                'name' => $this->name,
                'file_original_name' => $this->templateFile->getClientOriginalName(),
                'file_size' => $this->templateFile->getSize(),
                'file_path' => $this->templateFile->getRealPath(),
                'file_exists_at_temp' => file_exists($this->templateFile->getRealPath()),
            ]);

            $template = PdfFormService::createTemplate($this->templateFile, [
                'name' => $this->name,
                'description' => $this->description,
                'category' => $this->category,
            ]);

            session()->flash('success', "Template '{$template->name}' uploaded successfully! Found {$template->field_count} form fields.");

            $this->showUploadForm = false;
            $this->resetForm();

            // Redirect to field mapping page
            return redirect()->route('pdf-templates.map-fields', $template->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to upload template: ' . $e->getMessage());
            \Log::error('Template upload failed', [
                'name' => $this->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updateTemplate()
    {
        $this->authorizeAction('pdf_templates.edit');

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|in:housing,participant,contract,other',
        ]);

        $template = PdfFormTemplate::findOrFail($this->editingTemplateId);
        $template->update([
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', 'Template updated successfully');
        $this->showEditForm = false;
        $this->resetForm();
    }

    public function rescanFields($id)
    {
        $this->authorizeAction('pdf_templates.edit');

        try {
            $template = PdfFormTemplate::findOrFail($id);
            $fieldCount = PdfFormService::scanTemplateFields($template);

            session()->flash('success', "Template rescanned successfully! Found {$fieldCount} form fields.");
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to rescan template: ' . $e->getMessage());
        }
    }

    public function toggleActive($id)
    {
        $this->authorizeAction('pdf_templates.edit');

        $template = PdfFormTemplate::findOrFail($id);
        $template->update([
            'is_active' => !$template->is_active,
            'updated_by' => Auth::id(),
        ]);

        $status = $template->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Template {$status} successfully");
    }

    public function deleteTemplate($id)
    {
        $this->authorizeAction('pdf_templates.delete');

        try {
            $template = PdfFormTemplate::findOrFail($id);
            $name = $template->name;

            if (PdfFormService::deleteTemplate($template)) {
                session()->flash('success', "Template '{$name}' deleted successfully");
            } else {
                session()->flash('error', 'Failed to delete template');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete template: ' . $e->getMessage());
        }
    }

    public function downloadTemplate($id)
    {
        $template = PdfFormTemplate::findOrFail($id);

        if (!$template->fileExists()) {
            session()->flash('error', 'Template file not found on disk');
            return;
        }

        return response()->download($template->full_path, $template->original_filename);
    }

    protected function resetForm()
    {
        $this->editingTemplateId = null;
        $this->name = '';
        $this->description = '';
        $this->category = '';
        $this->templateFile = null;
    }

    public function render()
    {
        $templates = PdfFormTemplate::query()
            ->with(['creator', 'fieldMappings'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%')
                      ->orWhere('original_filename', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterCategory, fn($q) => $q->where('category', $this->filterCategory))
            ->when($this->filterActive === 'active', fn($q) => $q->where('is_active', true))
            ->when($this->filterActive === 'inactive', fn($q) => $q->where('is_active', false))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('livewire.pdf-template-management', [
            'templates' => $templates,
        ]);
    }
}
