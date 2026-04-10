<?php

namespace App\Livewire;

use App\Services\ImportExportService;
use App\Services\HMISPennsylvaniaImportService;
use App\Services\RXOfficeHousingImportService;
use App\Models\CustomImportTemplate;
use App\Models\ImportAuditLog;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ParticipantImportExport extends Component
{
    use WithFileUploads, WithPermissionChecks;

    public $activeTab = 'export';
    
    // Custom Import properties
    public $selectedCustomTemplate = '';
    public $selectedCustomTemplateData = null;
    public $customImportFile = null;
    public $customValidationResult = null;
    public $customImportResult = null;
    public $customUpdateExisting = false;
    public $customSkipErrors = true;
    public $customTempFilePath = '';
    
    // Import History properties
    public $expandedErrors = [];
    
    // Export properties
    public $exportType = 'all';
    public $householdId = '';
    public $startDate = '';
    public $endDate = '';
    public $dateField = 'created_at';
    public $householdCodes = '';
    public $programId = '';
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
    public $selectedReferenceDataSet = ''; // New property for reference data set selection
    public $showTransformationPreview = false;
    public $transformationPreview = null;

    protected ImportExportService $importExportService;
    protected HMISPennsylvaniaImportService $hmisImportService;
    protected RXOfficeHousingImportService $rxOfficeImportService;

    public function boot(
        ImportExportService $importExportService,
        HMISPennsylvaniaImportService $hmisImportService,
        RXOfficeHousingImportService $rxOfficeImportService
    ) {
        $this->importExportService = $importExportService;
        $this->hmisImportService = $hmisImportService;
        $this->rxOfficeImportService = $rxOfficeImportService;
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

        $rules = $this->getExportValidationRules();
        if (!empty($rules)) {
            $this->validate($rules);
        }
        
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
                // Create a secure download token
                $downloadToken = \Str::random(32);
                $downloadData = [
                    'file_path' => $result['file_path'],
                    'filename' => $result['filename'],
                    'expires_at' => now()->addMinutes(5)->timestamp
                ];
                
                // Store in cache for 5 minutes
                \Cache::put('download_' . $downloadToken, $downloadData, now()->addMinutes(5));
                
                // Trigger JavaScript download (use relative URL to avoid APP_URL mismatch)
                $this->dispatch('triggerDownload', [
                    'url' => '/secure-download/' . $downloadToken,
                    'filename' => $result['filename']
                ]);

                session()->flash('success', 'Template download started.');
            } else {
                session()->flash('error', 'Template generation failed: ' . $result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Template generation failed: ' . $e->getMessage());
        }
    }

    public function downloadExport()
    {
        if (!$this->exportResult || !isset($this->exportResult['file_path'])) {
            session()->flash('error', 'No export file available for download.');
            return;
        }

        try {
            // Create a secure download token
            $downloadToken = \Str::random(32);
            $downloadData = [
                'file_path' => $this->exportResult['file_path'],
                'filename' => $this->exportResult['filename'],
                'expires_at' => now()->addMinutes(5)->timestamp
            ];

            // Store in cache for 5 minutes
            \Cache::put('download_' . $downloadToken, $downloadData, now()->addMinutes(5));

            // Trigger JavaScript download (use relative URL to avoid APP_URL mismatch)
            $this->dispatch('triggerDownload', [
                'url' => '/secure-download/' . $downloadToken,
                'filename' => $this->exportResult['filename']
            ]);

            session()->flash('success', 'Export download started.');
                
        } catch (\Exception $e) {
            session()->flash('error', 'Download failed: ' . $e->getMessage());
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
            'importFile' => [
                'required',
                'file',
                'mimes:csv,txt',
                'mimetypes:text/csv,text/plain,application/csv,application/excel,application/vnd.ms-excel,application/vnd.msexcel,text/anytext',
                'max:10240', // 10MB max
                function ($attribute, $value, $fail) {
                    // Additional security checks
                    $content = file_get_contents($value->getRealPath());
                    
                    // Check for potentially malicious content
                    if (preg_match('/<\?php|<script|eval\(|exec\(|system\(|shell_exec/i', $content)) {
                        $fail('The file contains potentially malicious content.');
                    }
                    
                    // Check for excessive line length (potential DoS)
                    $lines = explode("\n", $content);
                    foreach ($lines as $line) {
                        if (strlen($line) > 10000) {
                            $fail('The file contains lines that are too long.');
                        }
                    }
                }
            ]
        ]);

        $this->isValidating = true;
        $this->validationResult = null;
        $this->previewData = null;
        $this->showPreview = false;

        try {
            // Pass the selected reference data set ID for validation
            $options = [];
            if ($this->selectedReferenceDataSet) {
                $options['reference_data_set_id'] = $this->selectedReferenceDataSet;
            }
            
            $result = $this->importExportService->validateImportFile($this->importFile, $options);

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
            
            // Pass the selected reference data set ID for import
            if ($this->selectedReferenceDataSet) {
                $options['reference_data_set_id'] = $this->selectedReferenceDataSet;
            }

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

    // Custom Import Methods

    public function updatedSelectedCustomTemplate($value)
    {
        if ($value) {
            $template = CustomImportTemplate::find($value);
            $this->selectedCustomTemplateData = $template ? $template->toArray() : null;
        } else {
            $this->selectedCustomTemplateData = null;
        }
        
        // Reset custom import state
        $this->resetCustomImportForm();
    }

    public function validateCustomImportFile()
    {
        if (!$this->can('participants.import')) {
            session()->flash('error', 'You do not have permission to import participants.');
            return;
        }

        $this->validate([
            'selectedCustomTemplate' => 'required|exists:custom_import_templates,id',
            'customImportFile' => 'required|file|mimes:csv,txt|mimetypes:text/csv,text/plain,application/csv,application/excel,application/vnd.ms-excel,application/vnd.msexcel,text/anytext|max:10240'
        ]);

        $this->customValidationResult = null;

        try {
            $template = CustomImportTemplate::find($this->selectedCustomTemplate);
            
            if (!$template) {
                session()->flash('error', 'Selected template not found.');
                return;
            }

            // Store file temporarily
            $uniqueFilename = 'custom_import_' . uniqid() . '_' . time() . '.csv';
            $this->customTempFilePath = $this->customImportFile->storeAs('', $uniqueFilename, 'local');
            $fullPath = Storage::disk('local')->path($this->customTempFilePath);

            // Use appropriate validation based on template type
            if ($template->source_system === 'HMIS_Pennsylvania') {
                $result = $this->hmisImportService->validateFile($fullPath);
            } elseif ($template->source_system === 'RX_Office_Housing') {
                $result = $this->rxOfficeImportService->validateFile($fullPath);
            } else {
                // For future custom templates, we'd implement generic validation here
                $result = ['valid' => false, 'errors' => ['Custom template validation not yet implemented'], 'warnings' => []];
            }

            $this->customValidationResult = $result;

            if ($result['valid']) {
                session()->flash('success', 
                    "File validated successfully! {$result['record_count']} records found" .
                    (isset($result['household_count']) ? ", {$result['household_count']} households." : ".")
                );
            } else {
                session()->flash('error', 'File validation failed. Please check the errors below.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'File validation failed: ' . $e->getMessage());
        }
    }

    public function processCustomImport()
    {
        if (!$this->can('participants.import')) {
            session()->flash('error', 'You do not have permission to import participants.');
            return;
        }

        if (!$this->customTempFilePath) {
            session()->flash('error', 'Please validate the file first.');
            return;
        }

        $this->customImportResult = null;
        $auditLog = null;

        try {
            $template = CustomImportTemplate::find($this->selectedCustomTemplate);

            if (!$template) {
                session()->flash('error', 'Selected template not found.');
                return;
            }

            $fullPath = Storage::disk('local')->path($this->customTempFilePath);
            $fileSize = Storage::disk('local')->size($this->customTempFilePath);

            // Create audit log
            $auditLog = ImportAuditLog::logImport([
                'import_type' => 'custom_import',
                'file_name' => $this->customImportFile->getClientOriginalName(),
                'file_size' => $fileSize,
                'options' => [
                    'template' => $template->name,
                    'source_system' => $template->source_system,
                    'update_existing' => $this->customUpdateExisting,
                    'skip_errors' => $this->customSkipErrors
                ]
            ]);

            $options = [
                'update_existing' => $this->customUpdateExisting,
                'skip_errors' => $this->customSkipErrors
            ];

            // Use appropriate import service based on template type
            if ($template->source_system === 'HMIS_Pennsylvania') {
                $result = $this->hmisImportService->processImport($fullPath, $options);
            } elseif ($template->source_system === 'RX_Office_Housing') {
                $result = $this->rxOfficeImportService->importFromCsv($fullPath);
            } else {
                // For future custom templates, we'd implement generic import here
                $result = ['success' => false, 'error' => 'Custom template import not yet implemented'];
            }

            $this->customImportResult = $result;

            // Mark audit log as completed
            if ($auditLog) {
                $auditLog->markCompleted($result);
            }

            if ($result['success']) {
                $message = "Import completed successfully! ";
                $message .= "Processed: {$result['processed_participants']} participants";
                
                if (isset($result['processed_households']) && $result['processed_households'] > 0) {
                    $message .= ", {$result['processed_households']} households";
                }
                
                if (isset($result['processed_enrollments']) && $result['processed_enrollments'] > 0) {
                    $message .= ", {$result['processed_enrollments']} enrollments";
                }
                
                $message .= ".";
                
                if (($result['updated_households'] ?? 0) > 0 || $result['updated_participants'] > 0) {
                    $message .= " Updated: {$result['updated_participants']} participants";
                    if (($result['updated_households'] ?? 0) > 0) {
                        $message .= ", {$result['updated_households']} households";
                    }
                    $message .= ".";
                }
                
                if ($result['skipped_records'] > 0) {
                    $message .= " Skipped: {$result['skipped_records']} records with errors.";
                }

                session()->flash('success', $message);
                
                // Reset form
                $this->resetCustomImportForm();
            } else {
                session()->flash('error', 'Import failed: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: ' . $e->getMessage());

            // Mark audit log as failed
            if ($auditLog) {
                $auditLog->markCompleted([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'processed_participants' => 0,
                    'updated_participants' => 0,
                    'skipped_records' => 0
                ]);
            }
        } finally {
            // Clean up temp file
            if ($this->customTempFilePath) {
                Storage::disk('local')->delete($this->customTempFilePath);
                $this->customTempFilePath = '';
            }
        }
    }

    public function resetCustomImportForm()
    {
        $this->customImportFile = null;
        $this->customValidationResult = null;
        $this->customImportResult = null;

        // Clean up temp file if exists
        if ($this->customTempFilePath) {
            Storage::disk('local')->delete($this->customTempFilePath);
            $this->customTempFilePath = '';
        }
    }

    public function downloadCustomTemplate()
    {
        if (!$this->can('participants.import')) {
            session()->flash('error', 'You do not have permission to download import template.');
            return;
        }

        if (!$this->selectedCustomTemplate) {
            session()->flash('error', 'Please select a template first.');
            return;
        }

        try {
            $template = CustomImportTemplate::find($this->selectedCustomTemplate);

            if (!$template) {
                session()->flash('error', 'Selected template not found.');
                return;
            }

            // Generate template based on source system
            $templateContent = null;
            $filename = null;

            if ($template->source_system === 'HMIS_Pennsylvania') {
                $templateContent = $this->hmisImportService->generateTemplate();
                $filename = 'HMIS_Pennsylvania_Import_Template_' . now()->format('Y-m-d_H-i-s') . '.csv';
            } elseif ($template->source_system === 'RX_Office_Housing') {
                // Default to detailed_case format
                // In the future, we could add a dropdown to let users choose the format
                $templateContent = $this->rxOfficeImportService->generateTemplate('detailed_case');
                $filename = 'RX_Office_Housing_Import_Template_' . now()->format('Y-m-d_H-i-s') . '.csv';
            } else {
                session()->flash('error', 'Template generation not yet implemented for this template type.');
                return;
            }

            if (!$templateContent) {
                session()->flash('error', 'Failed to generate template content.');
                return;
            }

            // Store template temporarily in exports directory (same as standard template)
            $uniqueFilename = 'custom_template_' . uniqid() . '_' . time() . '.csv';
            $tempPath = 'exports/' . $uniqueFilename;
            Storage::disk('local')->put($tempPath, $templateContent);

            // Create a secure download token
            $downloadToken = \Str::random(32);
            $downloadData = [
                'file_path' => $tempPath, // Store relative path, not absolute
                'filename' => $filename,
                'expires_at' => now()->addMinutes(5)->timestamp
            ];

            // Store in cache for 5 minutes
            \Cache::put('download_' . $downloadToken, $downloadData, now()->addMinutes(5));

            // Trigger JavaScript download (use relative URL to avoid APP_URL mismatch)
            $this->dispatch('triggerDownload', [
                'url' => '/secure-download/' . $downloadToken,
                'filename' => $filename
            ]);

            session()->flash('success', 'Template download started.');

        } catch (\Exception $e) {
            session()->flash('error', 'Template generation failed: ' . $e->getMessage());
        }
    }

    public function resetExportForm()
    {
        $this->exportType = 'all';
        $this->householdId = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->householdCodes = '';
        $this->programId = '';
        $this->exportResult = null;
    }

    // Validation and Helper Methods

    public function rules()
    {
        // Base rules for the component
        return [
            'activeTab' => 'string|in:export,import,custom-import,import-history',
            'exportType' => 'string|in:all,household,date_range,household_codes,program,program_date_range',
            'importFile' => 'nullable|file|mimes:csv,txt|max:10240',
        ];
    }

    protected function getExportValidationRules()
    {
        $rules = [];

        switch ($this->exportType) {
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
            case 'program':
                $rules['programId'] = 'required|integer|exists:programs,id';
                break;
            case 'program_date_range':
                $rules['programId'] = 'required|integer|exists:programs,id';
                $rules['startDate'] = 'required|date';
                $rules['endDate'] = 'required|date|after_or_equal:startDate';
                break;
        }

        return $rules;
    }

    protected function buildExportCriteria()
    {
        $criteria = ['type' => $this->exportType];

        switch ($this->exportType) {
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
            case 'program':
                $criteria['program_id'] = $this->programId;
                break;
            case 'program_date_range':
                $criteria['program_id'] = $this->programId;
                $criteria['start_date'] = $this->startDate;
                $criteria['end_date'] = $this->endDate;
                // Default to enrollment_date if not set or if it's still the old default
                $criteria['date_field'] = in_array($this->dateField, ['enrollment_date', 'exit_date'])
                    ? $this->dateField
                    : 'enrollment_date';
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

    public function getCustomImportTemplatesProperty()
    {
        return CustomImportTemplate::active()->orderBy('type')->orderBy('name')->get();
    }
    
    public function getImportHistoryProperty()
    {
        return \App\Models\ImportAuditLog::with(['user', 'referenceDataSet'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }
    
    public function getAvailableReferenceDataSetsProperty()
    {
        return \App\Models\ReferenceDataSet::where('is_draft', false)
            ->orderBy('is_import_default', 'desc')
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAvailableProgramsProperty()
    {
        return \App\Models\Program::orderBy('name')->get();
    }
    
    // Import History Methods
    
    public function toggleErrorDetails($logId)
    {
        if (in_array($logId, $this->expandedErrors)) {
            $this->expandedErrors = array_diff($this->expandedErrors, [$logId]);
        } else {
            $this->expandedErrors[] = $logId;
        }
    }
    
    public function refreshImportHistory()
    {
        // Reset expanded errors and refresh the data
        $this->expandedErrors = [];
        $this->dispatch('$refresh');
        session()->flash('success', 'Import history refreshed.');
    }
    
    public function previewTransformations()
    {
        if (!$this->tempFilePath) {
            session()->flash('error', 'Please validate the file first.');
            return;
        }
        
        try {
            $this->showTransformationPreview = true;
            $this->transformationPreview = null;
            
            // Read CSV file directly without parsing to avoid issues
            $fullPath = Storage::disk('local')->path($this->tempFilePath);
            
            // Read the CSV file
            $csv = \League\Csv\Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0);
            
            // Get first record
            $records = iterator_to_array($csv->getRecords());
            $firstRecord = reset($records);
            
            if ($firstRecord) {
                $transformationService = app(\App\Services\ReferenceDataTransformationService::class);
                
                // Set the selected reference data set if provided
                if ($this->selectedReferenceDataSet) {
                    $referenceDataSet = \App\Models\ReferenceDataSet::find($this->selectedReferenceDataSet);
                    $transformationService->setSelectedReferenceDataSet($referenceDataSet);
                }
                
                $transformations = [];
                $transformedCount = 0;
                
                // Fields to check for transformation
                $fieldsToCheck = [
                    'ethnicity', 'race', 'gender', 'work_status', 'military_status',
                    'health_insurance', 'education_level', 'disability_status',
                    'english_proficiency', 'housing_status'
                ];
                
                foreach ($fieldsToCheck as $field) {
                    if (isset($firstRecord[$field]) && !empty($firstRecord[$field])) {
                        $original = $firstRecord[$field];
                        $transformed = $transformationService->transformValue($field, $original);
                        
                        $transformations[$field] = [
                            'original' => $original,
                            'transformed' => $transformed,
                            'changed' => $original !== $transformed
                        ];
                        
                        if ($original !== $transformed) {
                            $transformedCount++;
                        }
                    }
                }
                
                $this->transformationPreview = [
                    'transformations' => $transformations,
                    'summary' => [
                        'total_fields' => count($transformations),
                        'transformed_count' => $transformedCount,
                        'unchanged_count' => count($transformations) - $transformedCount
                    ]
                ];
            } else {
                session()->flash('error', 'No data found in file.');
                $this->showTransformationPreview = false;
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error generating transformation preview: ' . $e->getMessage());
            $this->showTransformationPreview = false;
        }
    }

    public function render()
    {
        return view('livewire.participant-import-export')->layout('components.layouts.app');
    }
}