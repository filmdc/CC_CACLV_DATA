<?php

namespace App\Livewire;

use App\Models\PovertyGuideline;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class PovertyGuidelineManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $selectedYear = '';
    public $selectedRegion = '48_contiguous';
    public $selectedPeriod = 'annual';
    public $showCreateForm = false;
    public $showEditForm = false;
    public $selectedGuideline = null;

    // Form Fields
    public $year = '';
    public $region = '48_contiguous';
    public $family_size = '';
    public $period = 'annual';
    public $poverty_50 = '';
    public $poverty_75 = '';
    public $poverty_100 = '';
    public $poverty_125 = '';
    public $poverty_130 = '';
    public $poverty_150 = '';
    public $poverty_200 = '';
    public $poverty_250 = '';
    public $poverty_300 = '';
    public $poverty_400 = '';
    public $poverty_500 = '';

    public function mount()
    {
        if (!$this->can('poverty_guidelines.view') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to access this page.');
        }
        
        $this->selectedYear = date('Y');
        $this->year = date('Y');
    }

    public function updatedSelectedYear()
    {
        $this->resetPage();
    }

    public function updatedSelectedRegion()
    {
        $this->resetPage();
    }

    public function updatedSelectedPeriod()
    {
        $this->resetPage();
    }

    public function showCreateGuideline()
    {
        if (!$this->can('poverty_guidelines.create') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to create poverty guidelines.');
        }
        
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedGuideline = null;
        $this->resetGuidelineForm();
    }

    public function showEditGuideline($guidelineId)
    {
        if (!$this->can('poverty_guidelines.edit') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to edit poverty guidelines.');
        }
        
        $this->selectedGuideline = PovertyGuideline::find($guidelineId);
        if ($this->selectedGuideline) {
            $this->showEditForm = true;
            $this->showCreateForm = false;
            $this->fillGuidelineForm();
        }
    }

    public function resetGuidelineForm()
    {
        $this->year = $this->selectedYear;
        $this->region = $this->selectedRegion;
        $this->family_size = '';
        $this->period = $this->selectedPeriod;
        $this->poverty_50 = '';
        $this->poverty_75 = '';
        $this->poverty_100 = '';
        $this->poverty_125 = '';
        $this->poverty_130 = '';
        $this->poverty_150 = '';
        $this->poverty_200 = '';
        $this->poverty_250 = '';
        $this->poverty_300 = '';
        $this->poverty_400 = '';
        $this->poverty_500 = '';
    }

    public function fillGuidelineForm()
    {
        if ($this->selectedGuideline) {
            $this->year = $this->selectedGuideline->year;
            $this->region = $this->selectedGuideline->region;
            $this->family_size = $this->selectedGuideline->family_size;
            $this->period = $this->selectedGuideline->period;
            $this->poverty_50 = $this->selectedGuideline->poverty_50;
            $this->poverty_75 = $this->selectedGuideline->poverty_75;
            $this->poverty_100 = $this->selectedGuideline->poverty_100;
            $this->poverty_125 = $this->selectedGuideline->poverty_125;
            $this->poverty_130 = $this->selectedGuideline->poverty_130;
            $this->poverty_150 = $this->selectedGuideline->poverty_150;
            $this->poverty_200 = $this->selectedGuideline->poverty_200;
            $this->poverty_250 = $this->selectedGuideline->poverty_250;
            $this->poverty_300 = $this->selectedGuideline->poverty_300;
            $this->poverty_400 = $this->selectedGuideline->poverty_400;
            $this->poverty_500 = $this->selectedGuideline->poverty_500;
        }
    }

    public function createGuideline()
    {
        if (!$this->can('poverty_guidelines.create') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to create poverty guidelines.');
        }
        
        $this->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'region' => 'required|string',
            'family_size' => 'required|integer|min:1|max:20',
            'period' => 'required|in:monthly,annual',
            'poverty_100' => 'required|numeric|min:0',
        ]);

        try {
            // Check if guideline already exists
            $existing = PovertyGuideline::where('year', $this->year)
                ->where('region', $this->region)
                ->where('family_size', $this->family_size)
                ->where('period', $this->period)
                ->first();

            if ($existing) {
                session()->flash('error', 'Poverty guideline for this combination already exists.');
                return;
            }

            PovertyGuideline::create([
                'year' => $this->year,
                'region' => $this->region,
                'family_size' => $this->family_size,
                'period' => $this->period,
                'poverty_50' => $this->poverty_50 ?: null,
                'poverty_75' => $this->poverty_75 ?: null,
                'poverty_100' => $this->poverty_100,
                'poverty_125' => $this->poverty_125 ?: null,
                'poverty_130' => $this->poverty_130 ?: null,
                'poverty_150' => $this->poverty_150 ?: null,
                'poverty_200' => $this->poverty_200 ?: null,
                'poverty_250' => $this->poverty_250 ?: null,
                'poverty_300' => $this->poverty_300 ?: null,
                'poverty_400' => $this->poverty_400 ?: null,
                'poverty_500' => $this->poverty_500 ?: null,
            ]);

            session()->flash('success', 'Poverty guideline created successfully.');
            $this->showCreateForm = false;
            $this->resetGuidelineForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateGuideline()
    {
        if (!$this->can('poverty_guidelines.edit') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to edit poverty guidelines.');
        }
        
        $this->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'region' => 'required|string',
            'family_size' => 'required|integer|min:1|max:20',
            'period' => 'required|in:monthly,annual',
            'poverty_100' => 'required|numeric|min:0',
        ]);

        try {
            $this->selectedGuideline->update([
                'year' => $this->year,
                'region' => $this->region,
                'family_size' => $this->family_size,
                'period' => $this->period,
                'poverty_50' => $this->poverty_50 ?: null,
                'poverty_75' => $this->poverty_75 ?: null,
                'poverty_100' => $this->poverty_100,
                'poverty_125' => $this->poverty_125 ?: null,
                'poverty_130' => $this->poverty_130 ?: null,
                'poverty_150' => $this->poverty_150 ?: null,
                'poverty_200' => $this->poverty_200 ?: null,
                'poverty_250' => $this->poverty_250 ?: null,
                'poverty_300' => $this->poverty_300 ?: null,
                'poverty_400' => $this->poverty_400 ?: null,
                'poverty_500' => $this->poverty_500 ?: null,
            ]);

            session()->flash('success', 'Poverty guideline updated successfully.');
            $this->showEditForm = false;
            $this->selectedGuideline = null;
            $this->resetGuidelineForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteGuideline($guidelineId)
    {
        if (!$this->can('poverty_guidelines.delete') && !$this->isAdmin()) {
            abort(403, 'You do not have permission to delete poverty guidelines.');
        }
        
        try {
            $guideline = PovertyGuideline::find($guidelineId);
            if ($guideline) {
                $guideline->delete();
                session()->flash('success', 'Poverty guideline deleted successfully.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete guideline: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $guidelines = PovertyGuideline::when($this->selectedYear, function ($query) {
                $query->where('year', $this->selectedYear);
            })
            ->when($this->selectedRegion, function ($query) {
                $query->where('region', $this->selectedRegion);
            })
            ->when($this->selectedPeriod, function ($query) {
                $query->where('period', $this->selectedPeriod);
            })
            ->orderBy('family_size')
            ->paginate(20);

        $availableYears = PovertyGuideline::distinct()->pluck('year')->sort()->values();
        $availableRegions = PovertyGuideline::distinct()->pluck('region')->sort()->values();

        return view('livewire.poverty-guideline-management', [
            'guidelines' => $guidelines,
            'availableYears' => $availableYears,
            'availableRegions' => $availableRegions,
        ])->layout('components.layouts.app');
    }
}