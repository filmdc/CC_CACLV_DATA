<?php

namespace App\Livewire;

use App\Models\Program;
use App\Models\ReferenceDataSet;
use App\Services\CsbgReportService;
use App\Traits\WithPermissionChecks;
use Carbon\Carbon;
use Livewire\Component;

class ReportingManagement extends Component
{
    use WithPermissionChecks;

    // Filter parameters
    public $start_date = '';
    public $end_date = '';
    public $program_id = '';
    public $reference_data_set_id = '';
    public $heads_of_household_only = true;
    
    // Advanced filtering
    public $filter_gender = [];
    public $filter_ethnicity = [];
    public $filter_age_min = '';
    public $filter_age_max = '';
    public $filter_education_level = [];
    public $filter_work_status = [];
    public $filter_household_type = [];
    public $showAdvancedFilters = false;

    // Report data
    public $reportData = null;
    public $isGenerating = false;
    public $showReport = false;

    // UI state
    public $activeTab = 'demographics';
    
    // Drill-down functionality
    public $showDrillDown = false;
    public $drillDownData = [];
    public $drillDownTitle = '';
    public $drillDownCategory = '';
    public $drillDownValue = '';
    public $drillDownSearch = '';
    public $drillDownSortBy = 'last_name';
    public $drillDownSortDirection = 'asc';

    public function mount()
    {
        $this->authorizeAction('reports.csbg', 'You do not have permission to generate CSBG reports.');
        
        // Set default date range to current fiscal year (October 1 - September 30)
        $currentYear = Carbon::now()->year;
        $fiscalYearStart = Carbon::now()->month >= 10 ? $currentYear : $currentYear - 1;
        
        $this->start_date = Carbon::create($fiscalYearStart, 10, 1)->format('Y-m-d');
        $this->end_date = Carbon::create($fiscalYearStart + 1, 9, 30)->format('Y-m-d');
        
        // Set default reference data set to active
        $activeDataSet = ReferenceDataSet::getActive();
        if ($activeDataSet) {
            $this->reference_data_set_id = $activeDataSet->id;
        }
    }

    public function generateReport()
    {
        $this->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reference_data_set_id' => 'nullable|exists:reference_data_sets,id',
        ]);

        $this->isGenerating = true;
        $this->reportData = null;

        try {
            $filters = [
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'program_id' => $this->program_id ?: null,
                'reference_data_set_id' => $this->reference_data_set_id,
                'heads_of_household_only' => $this->heads_of_household_only,
                // Advanced filters
                'gender' => $this->filter_gender,
                'ethnicity' => $this->filter_ethnicity,
                'age_min' => $this->filter_age_min ?: null,
                'age_max' => $this->filter_age_max ?: null,
                'education_level' => $this->filter_education_level,
                'work_status' => $this->filter_work_status,
                'household_type' => $this->filter_household_type,
            ];

            $reportService = new CsbgReportService();
            $this->reportData = $reportService->generateReport($filters);
            $this->showReport = true;
            $this->activeTab = 'demographics';

            // Dispatch event for chart initialization
            $this->dispatch('reportGenerated', $this->reportData);

            session()->flash('success', 'CSBG report generated successfully!');

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while generating the report: ' . $e->getMessage());
        } finally {
            $this->isGenerating = false;
        }
    }

    public function exportToCsv()
    {
        if (!$this->reportData) {
            session()->flash('error', 'No report data available. Please generate a report first.');
            return;
        }

        try {
            $reportService = new CsbgReportService();
            $csvContent = $reportService->exportToCsv($this->reportData);
            
            $filename = 'csbg_module4c_report_' . $this->start_date . '_to_' . $this->end_date . '.csv';
            
            return response()->streamDownload(function () use ($csvContent) {
                echo $csvContent;
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while exporting the report: ' . $e->getMessage());
        }
    }

    public function exportToPdf()
    {
        if (!$this->reportData) {
            session()->flash('error', 'No report data available. Please generate a report first.');
            return;
        }

        try {
            $reportService = new CsbgReportService();
            $pdfContent = $reportService->exportToPdf($this->reportData);
            
            $filename = 'csbg_module4c_report_' . $this->start_date . '_to_' . $this->end_date . '.pdf';
            
            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while exporting the PDF: ' . $e->getMessage());
        }
    }

    public function exportToExcel()
    {
        if (!$this->reportData) {
            session()->flash('error', 'No report data available. Please generate a report first.');
            return;
        }

        try {
            $reportService = new CsbgReportService();
            $excelContent = $reportService->exportToExcel($this->reportData);
            
            $filename = 'csbg_module4c_report_' . $this->start_date . '_to_' . $this->end_date . '.xlsx';
            
            return response()->streamDownload(function () use ($excelContent) {
                echo $excelContent;
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while exporting the Excel file: ' . $e->getMessage());
        }
    }

    public function clearReport()
    {
        $this->reportData = null;
        $this->showReport = false;
        $this->activeTab = 'demographics';
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        
        // If switching to visualizations and we have report data, trigger chart initialization
        if ($tab === 'visualizations' && $this->reportData) {
            $this->dispatch('initializeCharts', $this->reportData);
        }
    }

    public function toggleAdvancedFilters()
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    public function clearAdvancedFilters()
    {
        $this->filter_gender = [];
        $this->filter_ethnicity = [];
        $this->filter_age_min = '';
        $this->filter_age_max = '';
        $this->filter_education_level = [];
        $this->filter_work_status = [];
        $this->filter_household_type = [];
    }

    public function drillDown($category, $value)
    {
        if (!$this->reportData) {
            session()->flash('error', 'No report data available for drill-down.');
            return;
        }

        $this->drillDownCategory = $category;
        $this->drillDownValue = $value;
        $this->drillDownTitle = ucfirst(str_replace('_', ' ', $category)) . ': ' . $value;
        $this->drillDownSearch = '';
        
        try {
            // Get detailed participant data for this specific category and value
            $reportService = new \App\Services\CsbgReportService();
            $this->drillDownData = $reportService->getDrillDownData(
                $this->getFiltersArray(),
                $category,
                $value
            );
            
            // Convert to array for better Livewire compatibility
            $this->drillDownData = $this->drillDownData->values();
            
            $this->showDrillDown = true;
            
            // Force Livewire to re-render
            $this->dispatch('drill-down-opened');
        } catch (\Exception $e) {
            session()->flash('error', 'Error loading drill-down data: ' . $e->getMessage());
        }
    }

    public function closeDrillDown()
    {
        $this->showDrillDown = false;
        $this->drillDownData = [];
        $this->drillDownTitle = '';
        $this->drillDownCategory = '';
        $this->drillDownValue = '';
        $this->drillDownSearch = '';
    }

    public function sortDrillDown($column)
    {
        if ($this->drillDownSortBy === $column) {
            $this->drillDownSortDirection = $this->drillDownSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->drillDownSortBy = $column;
            $this->drillDownSortDirection = 'asc';
        }
    }

    public function updatedDrillDownSearch()
    {
        // Real-time search filtering happens in the template
    }

    private function getFiltersArray()
    {
        return [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'program_id' => $this->program_id ?: null,
            'reference_data_set_id' => $this->reference_data_set_id,
            'heads_of_household_only' => $this->heads_of_household_only,
            'gender' => $this->filter_gender,
            'ethnicity' => $this->filter_ethnicity,
            'age_min' => $this->filter_age_min ?: null,
            'age_max' => $this->filter_age_max ?: null,
            'education_level' => $this->filter_education_level,
            'work_status' => $this->filter_work_status,
            'household_type' => $this->filter_household_type,
        ];
    }

    public function render()
    {
        $programs = Program::where('active', true)->orderBy('name')->get();
        $referenceDataSets = ReferenceDataSet::orderBy('created_at', 'desc')->get();

        return view('livewire.reporting-management', [
            'programs' => $programs,
            'referenceDataSets' => $referenceDataSets,
        ])->layout('components.layouts.app');
    }
}