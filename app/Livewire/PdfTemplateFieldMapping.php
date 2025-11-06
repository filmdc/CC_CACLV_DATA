<?php

namespace App\Livewire;

use App\Models\PdfFormTemplate;
use App\Models\PdfFormFieldMapping;
use App\Services\PdfFormService;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class PdfTemplateFieldMapping extends Component
{
    use WithPermissionChecks, WithPagination;

    public $templateId;
    public $template;

    // Filters
    public $search = '';
    public $filterMappingType = '';
    public $filterFieldType = '';

    // Bulk edit
    public $selectedFields = [];
    public $showBulkEditModal = false;

    // Edit modal
    public $showEditModal = false;
    public $editingFieldId = null;
    public $mapping_type = 'unmapped';
    public $model_name = '';
    public $model_field = '';
    public $relationship_path = '';
    public $custom_value = '';
    public $computed_method = '';

    // Available options for dropdowns
    public $availableModels = [];
    public $availableComputedMethods = [];

    public function mount($id)
    {
        $this->authorizeAction('pdf_templates.edit');

        $this->templateId = $id;
        $this->template = PdfFormTemplate::with('fieldMappings')->findOrFail($id);

        // Load available options
        $this->availableModels = PdfFormService::getAvailableModels();
        $this->availableComputedMethods = PdfFormService::getComputedMethods();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterMappingType()
    {
        $this->resetPage();
    }

    public function updatedFilterFieldType()
    {
        $this->resetPage();
    }

    public function showEditField($id)
    {
        $field = PdfFormFieldMapping::findOrFail($id);
        $this->editingFieldId = $id;
        $this->mapping_type = $field->mapping_type;
        $this->model_name = $field->model_name ?? '';
        $this->model_field = $field->model_field ?? '';
        $this->relationship_path = $field->relationship_path ?? '';
        $this->custom_value = $field->custom_value ?? '';
        $this->computed_method = $field->computed_method ?? '';
        $this->showEditModal = true;
    }

    public function saveFieldMapping()
    {
        $validated = $this->validate([
            'mapping_type' => 'required|in:unmapped,model_field,custom_value,computed',
            'model_name' => 'nullable|string',
            'model_field' => 'nullable|string',
            'relationship_path' => 'nullable|string',
            'custom_value' => 'nullable|string',
            'computed_method' => 'nullable|string',
        ]);

        $field = PdfFormFieldMapping::findOrFail($this->editingFieldId);
        $field->update([
            'mapping_type' => $this->mapping_type,
            'model_name' => $this->model_name !== '' ? $this->model_name : null,
            'model_field' => $this->model_field !== '' ? $this->model_field : null,
            'relationship_path' => $this->relationship_path !== '' ? $this->relationship_path : null,
            'custom_value' => $this->custom_value !== '' ? $this->custom_value : null,
            'computed_method' => $this->computed_method !== '' ? $this->computed_method : null,
        ]);

        session()->flash('success', "Field '{$field->field_name}' mapped successfully");
        $this->showEditModal = false;
        $this->resetEditForm();
        $this->template->refresh();
    }

    public function unmapField($id)
    {
        $field = PdfFormFieldMapping::findOrFail($id);
        $field->update([
            'mapping_type' => 'unmapped',
            'model_name' => null,
            'model_field' => null,
            'relationship_path' => null,
            'custom_value' => null,
            'computed_method' => null,
        ]);

        session()->flash('success', 'Field unmapped');
        $this->template->refresh();
    }

    protected function resetEditForm()
    {
        $this->editingFieldId = null;
        $this->mapping_type = 'unmapped';
        $this->model_name = '';
        $this->model_field = '';
        $this->relationship_path = '';
        $this->custom_value = '';
        $this->computed_method = '';
    }

    public function render()
    {
        $fields = $this->template->fieldMappings()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('field_name', 'like', '%' . $this->search . '%')
                      ->orWhere('field_label', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterMappingType, fn($q) => $q->where('mapping_type', $this->filterMappingType))
            ->when($this->filterFieldType, fn($q) => $q->where('field_type', $this->filterFieldType))
            ->orderBy('display_order')
            ->paginate(50);

        return view('livewire.pdf-template-field-mapping', [
            'fields' => $fields,
        ]);
    }
}
