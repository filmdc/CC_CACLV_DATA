<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Property;
use App\Models\ReferenceData;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PropertyManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    // UI State
    public $search = '';

    public $showCreateForm = false;

    public $showEditForm = false;

    public $selectedProperty = null;

    public $showDetails = false;

    public $selectedPropertyId = null;

    public $filterStatus = '';

    public $filterType = '';

    public $filterProgram = '';

    // Form Fields
    public $participant_id = '';

    public $household_id = '';

    public $property_type_id = '';

    public $address_line_1 = '';

    public $address_line_2 = '';

    public $city = '';

    public $state = '';

    public $zip_code = '';

    public $county = '';

    public $year_built = '';

    public $bedrooms = '';

    public $bathrooms = '';

    public $square_footage = '';

    public $property_description = '';

    public $status = 'active';

    // Notes form
    public $showNotesForm = false;

    public $noteContent = '';

    // Document approval
    public $showApprovalModal = false;

    public $rejectionReason = '';

    public $documentToReject = null;

    protected function rules()
    {
        return [
            'participant_id' => 'nullable|exists:participants,id',
            'household_id' => 'required|exists:households,id',
            'property_type_id' => 'required|exists:reference_data,id',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|size:2',
            'zip_code' => 'required|string|regex:/^\d{5}(-\d{4})?$/',
            'county' => 'required|string|max:100',
            'year_built' => 'nullable|integer|min:1800|max:'.(date('Y') + 1),
            'bedrooms' => 'nullable|integer|min:0|max:20',
            'bathrooms' => 'nullable|numeric|min:0|max:20',
            'square_footage' => 'nullable|integer|min:0',
            'property_description' => 'nullable|string',
            'status' => 'required|in:active,inactive,sold,demolished',
        ];
    }

    public function mount()
    {
        $this->authorizeAction('properties.view');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedFilterProgram()
    {
        $this->resetPage();
    }

    public function showPropertyCreateForm()
    {
        $this->authorizeAction('properties.create');
        $this->resetFormFields();
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->showDetails = false;
    }

    public function showEditForm($propertyId)
    {
        $this->authorizeAction('properties.edit');
        $this->selectedProperty = Property::findOrFail($propertyId);
        $this->fillFormFromProperty();
        $this->showEditForm = true;
        $this->showCreateForm = false;
        $this->showDetails = false;
    }

    public function viewDetails($propertyId)
    {
        $this->selectedProperty = Property::with([
            'participant',
            'household.participants',
            'propertyType',
            'jobs.contractor',
            'notes.createdBy',
            'propertyDocuments.approvedBy',
            'propertyDocuments.uploadedBy',
        ])->findOrFail($propertyId);

        $this->showDetails = true;
        $this->showCreateForm = false;
        $this->showEditForm = false;
    }

    public function save()
    {
        $this->authorizeAction('properties.create');
        $this->validate();

        try {
            $property = Property::create([
                'participant_id' => $this->participant_id ?: null,
                'household_id' => $this->household_id,
                'property_type_id' => $this->property_type_id,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2,
                'city' => $this->city,
                'state' => strtoupper($this->state),
                'zip_code' => $this->zip_code,
                'county' => $this->county,
                'year_built' => $this->year_built ?: null,
                'bedrooms' => $this->bedrooms ?: null,
                'bathrooms' => $this->bathrooms ?: null,
                'square_footage' => $this->square_footage ?: null,
                'property_description' => $this->property_description,
                'status' => $this->status,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            session()->flash('message', 'Property created successfully.');
            $this->showCreateForm = false;
            $this->resetFormFields();
        } catch (\Exception $e) {
            session()->flash('error', 'Error creating property: '.$e->getMessage());
        }
    }

    public function update()
    {
        $this->authorizeAction('properties.edit');
        $this->validate();

        try {
            $this->selectedProperty->update([
                'participant_id' => $this->participant_id ?: null,
                'household_id' => $this->household_id,
                'property_type_id' => $this->property_type_id,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2,
                'city' => $this->city,
                'state' => strtoupper($this->state),
                'zip_code' => $this->zip_code,
                'county' => $this->county,
                'year_built' => $this->year_built ?: null,
                'bedrooms' => $this->bedrooms ?: null,
                'bathrooms' => $this->bathrooms ?: null,
                'square_footage' => $this->square_footage ?: null,
                'property_description' => $this->property_description,
                'status' => $this->status,
                'updated_by' => Auth::id(),
            ]);

            session()->flash('message', 'Property updated successfully.');
            $this->showEditForm = false;
            $this->resetFormFields();
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating property: '.$e->getMessage());
        }
    }

    public function delete($propertyId)
    {
        $this->authorizeAction('properties.delete');

        try {
            $property = Property::findOrFail($propertyId);

            // Check if property has associated jobs
            if ($property->jobs()->exists()) {
                session()->flash('error', 'Cannot delete property with associated housing jobs.');

                return;
            }

            $property->delete();
            session()->flash('message', 'Property deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting property: '.$e->getMessage());
        }
    }

    public function addNote()
    {
        $this->authorizeAction('properties.notes');
        $this->validate(['noteContent' => 'required|string']);

        try {
            $this->selectedProperty->notes()->create([
                'note' => $this->noteContent,
                'created_by' => Auth::id(),
            ]);

            $this->noteContent = '';
            $this->showNotesForm = false;

            // Refresh the selected property to get updated notes
            $this->selectedProperty->load('notes.createdBy');

            session()->flash('message', 'Note added successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error adding note: '.$e->getMessage());
        }
    }

    public function resetFormFields()
    {
        $this->reset([
            'participant_id',
            'household_id',
            'property_type_id',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'zip_code',
            'county',
            'year_built',
            'bedrooms',
            'bathrooms',
            'square_footage',
            'property_description',
            'status',
            'selectedProperty',
            'noteContent',
            'showNotesForm',
        ]);
        $this->status = 'active';
    }

    public function resetForm()
    {
        $this->resetFormFields();
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->showDetails = false;
    }

    public function fillFormFromProperty()
    {
        $this->participant_id = $this->selectedProperty->participant_id;
        $this->household_id = $this->selectedProperty->household_id;
        $this->property_type_id = $this->selectedProperty->property_type_id;
        $this->address_line_1 = $this->selectedProperty->address_line_1;
        $this->address_line_2 = $this->selectedProperty->address_line_2;
        $this->city = $this->selectedProperty->city;
        $this->state = $this->selectedProperty->state;
        $this->zip_code = $this->selectedProperty->zip_code;
        $this->county = $this->selectedProperty->county;
        $this->year_built = $this->selectedProperty->year_built;
        $this->bedrooms = $this->selectedProperty->bedrooms;
        $this->bathrooms = $this->selectedProperty->bathrooms;
        $this->square_footage = $this->selectedProperty->square_footage;
        $this->property_description = $this->selectedProperty->property_description;
        $this->status = $this->selectedProperty->status;
    }

    public function showRejectModal($documentId)
    {
        $this->documentToReject = $documentId;
        $this->rejectionReason = '';
        $this->showApprovalModal = true;
    }

    public function resetApprovalModal()
    {
        $this->showApprovalModal = false;
        $this->rejectionReason = '';
        $this->documentToReject = null;
    }

    public function approveDocument($documentId)
    {
        $this->authorizeAction('properties.edit');

        try {
            $document = \App\Models\PropertyDocument::findOrFail($documentId);
            $document->update([
                'approval_status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $this->selectedProperty->load('propertyDocuments.approvedBy');
            session()->flash('message', 'Document approved successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error approving document: '.$e->getMessage());
        }
    }

    public function rejectDocument()
    {
        $this->authorizeAction('properties.edit');

        $this->validate([
            'rejectionReason' => 'required|string|min:10',
        ]);

        try {
            $document = \App\Models\PropertyDocument::findOrFail($this->documentToReject);
            $document->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $this->rejectionReason,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $this->selectedProperty->load('propertyDocuments.approvedBy');
            $this->resetApprovalModal();
            session()->flash('message', 'Document rejected successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error rejecting document: '.$e->getMessage());
        }
    }

    public function toggleRequiresApproval($documentId)
    {
        $this->authorizeAction('properties.edit');

        try {
            $document = \App\Models\PropertyDocument::findOrFail($documentId);
            $document->update([
                'requires_approval' => ! $document->requires_approval,
            ]);

            $this->selectedProperty->load('propertyDocuments.approvedBy');
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating document: '.$e->getMessage());
        }
    }

    public function render()
    {
        // Get programs with housing module enabled
        $housingPrograms = Program::where('housing_module_enabled', true)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        // Build the properties query
        $query = Property::with(['participant', 'household', 'propertyType', 'jobs'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('address_line_1', 'like', '%'.$this->search.'%')
                        ->orWhere('city', 'like', '%'.$this->search.'%')
                        ->orWhere('zip_code', 'like', '%'.$this->search.'%')
                        ->orWhereHas('participant', function ($q) {
                            $q->where('first_name', 'like', '%'.$this->search.'%')
                                ->orWhere('last_name', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('household', function ($q) {
                            $q->where('code', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterType, function ($q) {
                $q->where('property_type_id', $this->filterType);
            })
            ->when($this->filterProgram, function ($q) {
                $q->whereHas('jobs', function ($query) {
                    $query->where('program_id', $this->filterProgram);
                });
            });

        $properties = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get reference data for dropdowns
        $propertyTypes = ReferenceData::getOptions('property_type');

        $participants = Participant::orderBy('last_name')->orderBy('first_name')->get();
        $households = Household::with('participants')->orderBy('household_code')->get();

        return view('livewire.property-management', [
            'properties' => $properties,
            'propertyTypes' => $propertyTypes,
            'participants' => $participants,
            'households' => $households,
            'housingPrograms' => $housingPrograms,
        ])->layout('components.layouts.app');
    }
}
