<?php

namespace App\Livewire;

use App\Models\ReferenceData;
use App\Models\ReferenceDataSet;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class ReferenceDataManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $selectedCategory = '';
    public $selectedDataSetId = null;
    public $showCreateForm = false;
    public $showEditForm = false;
    public $selectedItem = null;

    // Form Fields
    public $category = '';
    public $value = '';
    public $label = '';
    public $description = '';
    public $sort_order = 0;
    public $active = true;

    public function mount()
    {
        if (!$this->can('reference_data.view') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to access this page.');
        }
        
        // Default to active data set
        $activeSet = ReferenceDataSet::getActive();
        $this->selectedDataSetId = $activeSet?->id;
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedDataSetId()
    {
        $this->resetPage();
        $this->selectedCategory = '';
    }

    public function updatedSelectedCategory()
    {
        $this->resetPage();
    }

    public function showCreateItem()
    {
        if (!$this->can('reference_data.create') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to create reference data.');
        }
        
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedItem = null;
        $this->resetForm();
        
        if ($this->selectedCategory) {
            $this->category = $this->selectedCategory;
        }
    }

    public function showEditItem($itemId)
    {
        if (!$this->can('reference_data.edit') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to edit reference data.');
        }
        
        $this->selectedItem = ReferenceData::find($itemId);
        if ($this->selectedItem) {
            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->fillForm();
        }
    }

    public function resetForm()
    {
        $this->category = '';
        $this->value = '';
        $this->label = '';
        $this->description = '';
        $this->sort_order = 0;
        $this->active = true;
    }

    public function fillForm()
    {
        if ($this->selectedItem) {
            $this->category = $this->selectedItem->category;
            $this->value = $this->selectedItem->value;
            $this->label = $this->selectedItem->label;
            $this->description = $this->selectedItem->description;
            $this->sort_order = $this->selectedItem->sort_order;
            $this->active = $this->selectedItem->active;
        }
    }

    public function createItem()
    {
        if (!$this->can('reference_data.create') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to create reference data.');
        }
        
        if (!$this->selectedDataSetId) {
            session()->flash('error', 'Please select a data set first.');
            return;
        }

        $dataSet = ReferenceDataSet::find($this->selectedDataSetId);
        if (!$dataSet || !$dataSet->is_draft) {
            session()->flash('error', 'Can only add items to draft data sets.');
            return;
        }

        $this->validate([
            'category' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'required|integer|min:0',
        ]);

        try {
            ReferenceData::create([
                'reference_data_set_id' => $this->selectedDataSetId,
                'category' => $this->category,
                'value' => $this->value,
                'label' => $this->label,
                'description' => $this->description,
                'sort_order' => $this->sort_order,
                'active' => $this->active,
            ]);

            session()->flash('success', 'Reference data item created successfully.');
            $this->showCreateForm = false;
            $this->resetForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateItem()
    {
        if (!$this->can('reference_data.edit') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to edit reference data.');
        }
        
        if (!$this->selectedItem || !$this->selectedItem->referenceDataSet->is_draft) {
            session()->flash('error', 'Can only edit items in draft data sets.');
            return;
        }

        $this->validate([
            'category' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'required|integer|min:0',
        ]);

        try {
            $this->selectedItem->update([
                'category' => $this->category,
                'value' => $this->value,
                'label' => $this->label,
                'description' => $this->description,
                'sort_order' => $this->sort_order,
                'active' => $this->active,
            ]);

            session()->flash('success', 'Reference data item updated successfully.');
            $this->showEditForm = false;
            $this->selectedItem = null;
            $this->resetForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function toggleStatus($itemId)
    {
        if (!$this->can('reference_data.edit') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to edit reference data.');
        }
        
        try {
            $item = ReferenceData::find($itemId);
            if ($item) {
                $item->update(['active' => !$item->active]);
                session()->flash('success', 'Item status updated successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot update status: ' . $e->getMessage());
        }
    }

    public function deleteItem($itemId)
    {
        if (!$this->can('reference_data.delete') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to delete reference data.');
        }
        
        try {
            $item = ReferenceData::find($itemId);
            if ($item) {
                $item->delete();
                session()->flash('success', 'Reference data item deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete item: ' . $e->getMessage());
        }
    }

    public function createEditableVersionOfCurrentSet()
    {
        if (!$this->can('reference_data.create') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to create reference data.');
        }
        
        if (!$this->selectedDataSetId) {
            session()->flash('error', 'No data set selected.');
            return;
        }

        try {
            $originalSet = ReferenceDataSet::find($this->selectedDataSetId);
            if (!$originalSet) {
                session()->flash('error', 'Data set not found.');
                return;
            }

            // Generate a new version number (increment minor version)
            $versionParts = explode('.', $originalSet->version);
            if (count($versionParts) >= 2) {
                $versionParts[1] = (int)$versionParts[1] + 1;
                if (count($versionParts) >= 3) {
                    $versionParts[2] = 0; // Reset patch version
                }
            } else {
                $versionParts = ['1', '1', '0'];
            }
            $newVersion = implode('.', $versionParts);

            // Check if version already exists, if so increment until unique
            $counter = 0;
            $testVersion = $newVersion;
            while (ReferenceDataSet::where('version', $testVersion)->exists()) {
                $counter++;
                $testVersion = $newVersion . '-' . $counter;
            }

            $newSet = $originalSet->duplicate($testVersion, $originalSet->name . ' (Editable)');
            
            // Switch to the new draft set
            $this->selectedDataSetId = $newSet->id;
            
            session()->flash('success', 'Created editable version ' . $testVersion . '. You can now modify the reference data.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = ReferenceData::query();

        // Filter by selected data set
        if ($this->selectedDataSetId) {
            $query->where('reference_data_set_id', $this->selectedDataSetId);
        }

        if ($this->selectedCategory) {
            $query->byCategory($this->selectedCategory);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('value', 'like', '%' . $this->search . '%')
                  ->orWhere('label', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        $items = $query->ordered()->paginate(20);
        $categories = ReferenceData::getCategories();
        $dataSets = ReferenceDataSet::orderBy('is_active', 'desc')
                                   ->orderBy('activated_at', 'desc')
                                   ->orderBy('created_at', 'desc')
                                   ->get();
        $selectedDataSet = $this->selectedDataSetId ? ReferenceDataSet::find($this->selectedDataSetId) : null;

        return view('livewire.reference-data-management', [
            'items' => $items,
            'categories' => $categories,
            'dataSets' => $dataSets,
            'selectedDataSet' => $selectedDataSet,
        ])->layout('components.layouts.app');
    }
}