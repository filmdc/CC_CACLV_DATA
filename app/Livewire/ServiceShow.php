<?php

namespace App\Livewire;

use App\Models\Service;
use App\Traits\WithPermissionChecks;
use Livewire\Component;

class ServiceShow extends Component
{
    use WithPermissionChecks;

    public $service;
    public $serviceId;

    public function mount($id)
    {
        // Check if user has permission to view services
        $this->authorizeAction('services.view', 'You do not have permission to view services.');
        
        $this->serviceId = $id;
        $this->service = Service::with([
            'program',
            'serviceEnrollments' => function($query) {
                $query->latest()->limit(10);
            }
        ])->findOrFail($id);
    }

    public function editService()
    {
        return redirect()->route('service.management')->with('selectedService', $this->serviceId);
    }

    public function render()
    {
        return view('livewire.service-show')->layout('components.layouts.app');
    }
}