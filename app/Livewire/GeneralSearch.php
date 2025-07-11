<?php

namespace App\Livewire;

use App\Models\Participant;
use App\Models\Household;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class GeneralSearch extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $searchType = 'all'; // all, participants, households
    public $showResults = false;

    protected $queryString = ['search', 'searchType'];

    public function mount()
    {
        if ($this->search) {
            $this->showResults = true;
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->showResults = !empty($this->search);
    }

    public function updatedSearchType()
    {
        $this->resetPage();
    }

    public function render()
    {
        $participants = collect();
        $households = collect();

        if ($this->showResults && $this->search) {
            if (($this->searchType === 'all' || $this->searchType === 'participants') && $this->can('participants.view')) {
                $participants = $this->searchParticipants();
            }

            if (($this->searchType === 'all' || $this->searchType === 'households') && $this->can('households.view')) {
                $households = $this->searchHouseholds();
            }
        }

        return view('livewire.general-search', [
            'participants' => $participants,
            'households' => $households,
        ]);
    }

    protected function searchParticipants()
    {
        return Participant::with('household')
            ->where(function ($query) {
                $query->where('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('telephone', 'like', '%' . $this->search . '%');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(10)
            ->get();
    }

    protected function searchHouseholds()
    {
        return Household::withCount('participants')
            ->where(function ($query) {
                $query->where('household_code', 'like', '%' . $this->search . '%')
                    ->orWhere('street_address', 'like', '%' . $this->search . '%')
                    ->orWhere('city', 'like', '%' . $this->search . '%')
                    ->orWhere('zip_code', 'like', '%' . $this->search . '%')
                    ->orWhereHas('participants', function ($q) {
                        $q->where('first_name', 'like', '%' . $this->search . '%')
                            ->orWhere('last_name', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy('household_code')
            ->limit(10)
            ->get();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->showResults = false;
        $this->resetPage();
    }
}