<?php

namespace App\Livewire;

use App\Models\Property;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class PropertyShow extends Component
{
    use WithPermissionChecks;

    public $property;
    public $propertyId;

    public function mount($id)
    {
        // Check if user has permission to view properties
        $this->authorizeAction('properties.view', 'You do not have permission to view properties.');
        
        $this->propertyId = $id;
        $this->property = Property::with([
            'participant',
            'household',
            'program',
            'housingJobs.contractor',
            'propertyNotes',
            'propertyDocuments'
        ])->findOrFail($id);
    }

    public function editProperty()
    {
        return redirect()->route('property.management')->with('selectedProperty', $this->propertyId);
    }

    public function render()
    {
        return view('livewire.property-show')->layout('components.layouts.app');
    }
}