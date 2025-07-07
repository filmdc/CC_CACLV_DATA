<?php

namespace App\Livewire;

use App\Models\Funder;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class FunderManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $selectedFunder = null;
    public $showCreateForm = false;
    public $showEditForm = false;

    // Funder Form Fields
    public $name = '';
    public $code = '';
    public $description = '';
    public $contact_person = '';
    public $contact_email = '';
    public $contact_phone = '';
    public $address = '';
    public $website = '';
    public $active = true;

    public function mount()
    {
        $this->authorizeAction('funders.view');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showCreateFunder()
    {
        $this->authorizeAction('funders.create');
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedFunder = null;
        $this->resetFunderForm();
    }

    public function showEditFunder($funderId)
    {
        $this->authorizeAction('funders.edit');
        $this->selectedFunder = Funder::findOrFail($funderId);
        $this->showEditForm = true;
        $this->showCreateForm = false;
        $this->fillFunderForm();
    }

    public function resetFunderForm()
    {
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->contact_person = '';
        $this->contact_email = '';
        $this->contact_phone = '';
        $this->address = '';
        $this->website = '';
        $this->active = true;
    }

    public function fillFunderForm()
    {
        if ($this->selectedFunder) {
            $this->name = $this->selectedFunder->name;
            $this->code = $this->selectedFunder->code;
            $this->description = $this->selectedFunder->description;
            $this->contact_person = $this->selectedFunder->contact_person;
            $this->contact_email = $this->selectedFunder->contact_email;
            $this->contact_phone = $this->selectedFunder->contact_phone;
            $this->address = $this->selectedFunder->address;
            $this->website = $this->selectedFunder->website;
            $this->active = $this->selectedFunder->active;
        }
    }

    public function createFunder()
    {
        $this->authorizeAction('funders.create');
        
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:funders,code',
            'description' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'active' => 'boolean',
        ]);

        try {
            Funder::create([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'contact_person' => $this->contact_person,
                'contact_email' => $this->contact_email,
                'contact_phone' => $this->contact_phone,
                'address' => $this->address,
                'website' => $this->website,
                'active' => $this->active,
                'created_by' => auth()->id(),
            ]);

            session()->flash('success', 'Funder created successfully.');
            $this->showCreateForm = false;
            $this->resetFunderForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateFunder()
    {
        $this->authorizeAction('funders.edit');
        
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:funders,code,' . $this->selectedFunder->id,
            'description' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'active' => 'boolean',
        ]);

        try {
            $this->selectedFunder->update([
                'name' => $this->name,
                'code' => $this->code,
                'description' => $this->description,
                'contact_person' => $this->contact_person,
                'contact_email' => $this->contact_email,
                'contact_phone' => $this->contact_phone,
                'address' => $this->address,
                'website' => $this->website,
                'active' => $this->active,
                'updated_by' => auth()->id(),
            ]);

            session()->flash('success', 'Funder updated successfully.');
            $this->showEditForm = false;
            $this->selectedFunder = null;
            $this->resetFunderForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteFunder($funderId)
    {
        $this->authorizeAction('funders.delete');
        
        try {
            $funder = Funder::findOrFail($funderId);
            
            // Check if funder has contracts
            if ($funder->contracts()->count() > 0) {
                session()->flash('error', 'Cannot delete funder that has associated contracts.');
                return;
            }
            
            $funder->update(['updated_by' => auth()->id()]);
            $funder->delete();
            session()->flash('success', 'Funder deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete funder: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $funders = Funder::when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('code', 'like', '%' . $this->search . '%')
                      ->orWhere('contact_person', 'like', '%' . $this->search . '%')
                      ->orWhere('contact_email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.funder-management', [
            'funders' => $funders,
        ])->layout('components.layouts.app');
    }
}