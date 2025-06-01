<?php

namespace App\Livewire;

use App\Models\ReferenceDataSet;
use Livewire\Component;
use Livewire\WithPagination;

class ReferenceDataSetManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $showCreateForm = false;
    public $showDuplicateForm = false;
    public $showEditForm = false;
    public $selectedDataSet = null;

    // Form fields
    public $name = '';
    public $description = '';
    public $version = '';
    public $duplicateFromId = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateDataSet()
    {
        $this->resetForm();
        $this->showCreateForm = true;
    }

    public function showDuplicateDataSet($dataSetId)
    {
        $this->resetForm();
        $this->selectedDataSet = ReferenceDataSet::find($dataSetId);
        $this->duplicateFromId = $dataSetId;
        $this->name = $this->selectedDataSet->name . ' (Copy)';
        $this->showDuplicateForm = true;
    }

    public function showEditDataSet($dataSetId)
    {
        $this->resetForm();
        $this->selectedDataSet = ReferenceDataSet::find($dataSetId);
        if ($this->selectedDataSet) {
            $this->name = $this->selectedDataSet->name;
            $this->description = $this->selectedDataSet->description;
            $this->version = $this->selectedDataSet->version;
            $this->showEditForm = true;
        }
    }

    public function createEditableVersion($dataSetId)
    {
        try {
            $originalSet = ReferenceDataSet::find($dataSetId);
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
            
            session()->flash('success', 'Created editable version ' . $testVersion . '. You can now modify the reference data.');
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function createDataSet()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'version' => 'required|string|max:20|unique:reference_data_sets,version'
        ]);

        try {
            ReferenceDataSet::create([
                'name' => $this->name,
                'description' => $this->description,
                'version' => $this->version,
                'is_active' => false,
                'is_draft' => true,
                'created_by' => auth()->id()
            ]);

            session()->flash('success', 'Reference data set created successfully.');
            $this->resetForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function duplicateDataSet()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'version' => 'required|string|max:20|unique:reference_data_sets,version'
        ]);

        try {
            $originalSet = ReferenceDataSet::find($this->duplicateFromId);
            if (!$originalSet) {
                session()->flash('error', 'Original data set not found.');
                return;
            }

            $originalSet->duplicate($this->version, $this->name);

            session()->flash('success', 'Reference data set duplicated successfully.');
            $this->resetForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateDataSet()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'version' => 'required|string|max:20|unique:reference_data_sets,version,' . $this->selectedDataSet->id
        ]);

        try {
            if (!$this->selectedDataSet->is_draft) {
                session()->flash('error', 'Can only edit draft data sets.');
                return;
            }

            $this->selectedDataSet->update([
                'name' => $this->name,
                'description' => $this->description,
                'version' => $this->version,
            ]);

            session()->flash('success', 'Reference data set updated successfully.');
            $this->resetForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function publishDataSet($dataSetId)
    {
        try {
            $dataSet = ReferenceDataSet::find($dataSetId);
            if ($dataSet && $dataSet->publish()) {
                session()->flash('success', 'Data set published successfully.');
            } else {
                session()->flash('error', 'Cannot publish this data set.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function activateDataSet($dataSetId)
    {
        try {
            $dataSet = ReferenceDataSet::find($dataSetId);
            if ($dataSet && $dataSet->activate()) {
                session()->flash('success', 'Data set activated successfully.');
            } else {
                session()->flash('error', 'Cannot activate this data set. It must be published first.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteDataSet($dataSetId)
    {
        try {
            $dataSet = ReferenceDataSet::find($dataSetId);
            if (!$dataSet) {
                session()->flash('error', 'Data set not found.');
                return;
            }

            if ($dataSet->is_active) {
                session()->flash('error', 'Cannot delete the active data set.');
                return;
            }

            if ($dataSet->participants()->count() > 0) {
                session()->flash('error', 'Cannot delete data set that is referenced by participant records.');
                return;
            }

            $dataSet->delete();
            session()->flash('success', 'Data set deleted successfully.');

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->version = '';
        $this->duplicateFromId = null;
        $this->selectedDataSet = null;
        $this->showCreateForm = false;
        $this->showDuplicateForm = false;
        $this->showEditForm = false;
    }

    public function render()
    {
        $dataSets = ReferenceDataSet::with('createdBy')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('version', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.reference-data-set-management', [
            'dataSets' => $dataSets
        ])->layout('components.layouts.app');
    }
}
