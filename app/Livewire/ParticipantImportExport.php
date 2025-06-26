<?php

namespace App\Livewire;

use App\Services\ImportExportService;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithFileUploads;
use Carbon\Carbon;

class ParticipantImportExport extends Component
{
    use WithFileUploads, WithPermissionChecks;

    public $activeTab = 'export';
    
    // Export properties
    public $exportType = 'all';
    public $participantId = '';
    public $householdId = '';
    public $startDate = '';
    public $endDate = '';
    public $dateField = 'created_at';
    public $householdCodes = '';
    public $isExporting = false;
    public $exportResult = null;

    // Import properties
    public $importFile = null;
    public $isValidating = false;
    public $isImporting = false;
    public $validationResult = null;
    public $previewData = null;
    public $tempFilePath = '';
    public $updateExisting = false;
    public $skipErrors = true;
    public $showPreview = false;
    public $importResult = null;

    protected ImportExportService $importExportService;

    public function boot(ImportExportService $importExportService)
    {
        $this->importExportService = $importExportService;
    }

    public function mount()
    {
        // Check permissions
        if (!$this->can('participants.export') && !$this->can('participants.import')) {
            $this->authorizeAction('participants.view', 'You do not have permission to access import/export functionality.');
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetMessages();
    }

    // Export Methods

    public function export()
    {
        if (!$this->can('participants.export')) {
            session()->flash('error', 'You do not have permission to export participants.');
            return;
        }

        $this->validate($this->getExportValidationRules());
        
        $this->isExporting = true;
        $this->exportResult = null;

        try {
            $criteria = $this->buildExportCriteria();
            $result = $this->importExportService->exportParticipants($criteria);

            if ($result['success']) {
                $this->exportResult = $result;
                session()->flash('success', "Export completed successfully! {$result['record_count']} records exported.");
            } else {
                session()->flash('error', 'Export failed: ' . $result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Export failed: ' . $e->getMessage());
        } finally {
            $this->isExporting = false;
        }
    }

    public function downloadTemplate()
    {
        if (!$this->can('participants.import')) {
            session()->flash('error', 'You do not have permission to download import template.');
            return;
        }

        try {
            $result = $this->importExportService->generateImportTemplate();

            if ($result['success']) {
                return redirect($result['download_url']);
            } else {
                session()->flash('error', 'Template generation failed: ' . $result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Template generation failed: ' . $e->getMessage());
        }
    }

    public function downloadExport()
    {
        if ($this->exportResult && isset($this->exportResult['download_url'])) {
            return redirect($this->exportResult['download_url']);
        }
    }

    // Import Methods

    public function validateImportFile()
    {
        if (!$this->can('participants.import')) {
            session()->flash('error', 'You do not have permission to import participants.');
            return;
        }

        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:10240' // 10MB max
        ]);

        $this->isValidating = true;
        $this->validationResult = null;
        $this->previewData = null;
        $this->showPreview = false;

        try {
            $result = $this->importExportService->validateImportFile($this->importFile);

            if ($result['success']) {
                $this->validationResult = $result['validation'];
                $this->tempFilePath = $result['temp_file_path'];
                session()->flash('success', 
                    "File validated successfully! {$result['validation']['record_count']} records found, " .
                    "{$result['validation']['household_count']} households."
                );
            } else {
                $this->validationResult = [
                    'valid' => false,
                    'errors' => $result['errors'] ?? [$result['error']],
                    'warnings' => $result['warnings'] ?? []
                ];
                session()->flash('error', 'File validation failed. Please check the errors below.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'File validation failed: ' . $e->getMessage());
        } finally {
            $this->isValidating = false;
        }
    }

    public function previewImport()
    {
        if (!$this->tempFilePath) {
            session()->flash('error', 'Please validate the file first.');
            return;
        }

        try {
            $result = $this->importExportService->previewImport($this->tempFilePath, 10);

            if ($result['success']) {
                $this->previewData = $result;
                $this->showPreview = true;
            } else {
                session()->flash('error', 'Preview generation failed: ' . $result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Preview generation failed: ' . $e->getMessage());
        }
    }

    public function processImport()
    {
        if (!$this->can('participants.import')) {
            session()->flash('error', 'You do not have permission to import participants.');
            return;
        }

        if (!$this->tempFilePath) {
            session()->flash('error', 'Please validate the file first.');
            return;
        }

        $this->isImporting = true;
        $this->importResult = null;

        try {
            $options = [
                'update_existing' => $this->updateExisting,
                'skip_errors' => $this->skipErrors
            ];

            $result = $this->importExportService->processImport($this->tempFilePath, $options);

            if ($result['success']) {
                $this->importResult = $result;
                
                $message = "Import completed successfully! ";
                $message .= "Processed: {$result['processed_households']} households, {$result['processed_participants']} participants.";
                
                if ($result['updated_households'] > 0 || $result['updated_participants'] > 0) {
                    $message .= " Updated: {$result['updated_households']} households, {$result['updated_participants']} participants.";
                }
                
                if ($result['skipped_records'] > 0) {
                    $message .= " Skipped: {$result['skipped_records']} records with errors.";
                }

                session()->flash('success', $message);
                
                // Reset form
                $this->resetImportForm();
            } else {
                $this->importResult = $result;
                session()->flash('error', 'Import failed: ' . $result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        } finally {
            $this->isImporting = false;
        }
    }

    public function resetImportForm()
    {
        $this->importFile = null;
        $this->validationResult = null;
        $this->previewData = null;
        $this->tempFilePath = '';
        $this->showPreview = false;
        $this->importResult = null;
    }

    public function resetExportForm()
    {
        $this->exportType = 'all';
        $this->participantId = '';
        $this->householdId = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->householdCodes = '';
        $this->exportResult = null;
    }

    // Validation and Helper Methods

    protected function getExportValidationRules()
    {
        $rules = [];

        switch ($this->exportType) {
            case 'participant':
                $rules['participantId'] = 'required|integer|exists:participants,id';
                break;
            case 'household':
                $rules['householdId'] = 'required|integer|exists:households,id';
                break;
            case 'date_range':
                $rules['startDate'] = 'required|date';
                $rules['endDate'] = 'required|date|after_or_equal:startDate';
                break;
            case 'household_codes':
                $rules['householdCodes'] = 'required|string';
                break;
        }

        return $rules;
    }

    protected function buildExportCriteria()
    {
        $criteria = ['type' => $this->exportType];

        switch ($this->exportType) {
            case 'participant':
                $criteria['participant_id'] = $this->participantId;
                break;
            case 'household':
                $criteria['household_id'] = $this->householdId;
                break;
            case 'date_range':
                $criteria['start_date'] = $this->startDate;
                $criteria['end_date'] = $this->endDate;
                $criteria['date_field'] = $this->dateField;
                break;
            case 'household_codes':
                $codes = array_map('trim', explode(',', $this->householdCodes));
                $criteria['household_codes'] = array_filter($codes);
                break;
        }

        return $criteria;
    }

    protected function resetMessages()
    {
        session()->forget(['success', 'error']);
    }

    // Computed Properties

    public function getExportStatisticsProperty()
    {
        try {
            return $this->importExportService->getExportStatistics();
        } catch (\Exception $e) {
            return [
                'total_participants' => 0,
                'total_households' => 0,
                'participants_this_month' => 0,
                'households_this_month' => 0,
                'participants_this_year' => 0,
                'households_this_year' => 0,
            ];
        }
    }

    public function getCanExportProperty()
    {
        return $this->can('participants.export');
    }

    public function getCanImportProperty()
    {
        return $this->can('participants.import');
    }

    public function render()
    {
        return view('livewire.participant-import-export')->layout('components.layouts.app');
    }
}