<?php

namespace App\Livewire;

use App\Models\PdfFormTemplate;
use App\Models\PdfFormFieldMapping;
use App\Services\PdfFormService;
use App\Services\PdfFormFieldSuggestionService;
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
    public $selectedFieldMapping = '';  // The selected field from dropdown
    public $suggestedMapping = null;   // Auto-suggested mapping

    // Available fields for mapping
    public $availableFields = [];

    public function mount($id)
    {
        $this->authorizeAction('pdf_templates.edit');

        $this->templateId = $id;
        $this->template = PdfFormTemplate::with('fieldMappings')->findOrFail($id);

        // Load available fields
        $this->availableFields = PdfFormFieldSuggestionService::getAllAvailableFields();
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

        // If already mapped, find the matching field ID
        if ($field->isMapped()) {
            $this->selectedFieldMapping = $this->buildFieldId($field);
        } else {
            $this->selectedFieldMapping = '';
            // Auto-suggest a mapping
            $this->suggestedMapping = PdfFormFieldSuggestionService::suggestMapping(
                $field->field_name,
                $this->availableFields
            );
        }

        $this->showEditModal = true;
    }

    /**
     * Build field ID from existing mapping
     */
    protected function buildFieldId(PdfFormFieldMapping $field): string
    {
        $parts = [strtolower($field->model_name ?? 'housingjob')];

        if ($field->relationship_path) {
            $parts[] = $field->relationship_path;
        }

        if ($field->model_field) {
            $parts[] = $field->model_field;
        }

        return implode('.', $parts);
    }

    public function saveFieldMapping()
    {
        $field = PdfFormFieldMapping::findOrFail($this->editingFieldId);

        if (empty($this->selectedFieldMapping)) {
            // Unmap the field
            $field->update([
                'mapping_type' => 'unmapped',
                'model_name' => null,
                'model_field' => null,
                'relationship_path' => null,
                'custom_value' => null,
                'computed_method' => null,
            ]);

            session()->flash('success', "Field '{$field->field_name}' unmapped");
        } else {
            // Find the selected field configuration
            $selectedField = collect($this->availableFields)
                ->firstWhere('id', $this->selectedFieldMapping);

            if (!$selectedField) {
                session()->flash('error', 'Invalid field selection');
                return;
            }

            // Update with the selected mapping
            $field->update([
                'mapping_type' => 'model_field',
                'model_name' => $selectedField['model_name'],
                'model_field' => $selectedField['model_field'],
                'relationship_path' => $selectedField['relationship_path'],
                'custom_value' => null,
                'computed_method' => null,
            ]);

            session()->flash('success', "Field '{$field->field_name}' mapped to {$selectedField['display']}");
        }

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
        $this->selectedFieldMapping = '';
        $this->suggestedMapping = null;
    }

    public function handleFieldSelected($value)
    {
        $this->selectedFieldMapping = $value;
    }

    protected function getListeners()
    {
        return [
            'item-selected-field-mapping' => 'handleFieldSelected',
        ];
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
