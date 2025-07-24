<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractReport;
use App\Models\ContractReportDueDate;
use App\Models\DueDateEmail;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithFileUploads;

class ContractShow extends Component
{
    use WithPermissionChecks, WithFileUploads;

    public $contract;
    public $contractId;
    
    // Due Date Form Properties
    public $showDueDateForm = false;
    public $selectedDueDate = null;
    public $due_date = '';
    public $description = '';
    public $report_type = 'quarterly';
    public $reminder_intervals = [30, 15, 7, 3, 1];
    public $active = true;
    public $assigned_users = [];
    
    // Report Upload Properties
    public $showReportForm = false;
    public $selectedDueDateForReport = null;
    public $report_title = '';
    public $report_description = '';
    public $report_documents = [];
    public $report_notes = '';
    
    // Modal states
    public $showAssignedUsersModal = false;
    public $selectedDueDateForUsers = null;
    
    // Available options
    public $users = [];
    public $reportTypes = [
        'quarterly' => 'Quarterly',
        'annual' => 'Annual',
        'semi-annual' => 'Semi-Annual',
        'monthly' => 'Monthly',
        'final' => 'Final',
        'other' => 'Other'
    ];

    public function mount($id)
    {
        // Check if user has permission to view contracts
        $this->authorizeAction('contracts.view', 'You do not have permission to view contracts.');
        
        $this->contractId = $id;
        $this->loadContract();
        $this->loadUsers();
    }
    
    public function loadContract()
    {
        $this->contract = Contract::with([
            'funder',
            'program',
            'reportDueDates.emails.user',
            'reportDueDates.contractReports.media',
            'media',
            'createdBy',
            'updatedBy'
        ])->findOrFail($this->contractId);
    }
    
    public function loadUsers()
    {
        $this->users = User::where('active', true)->orderBy('name')->get();
    }

    public function editContract()
    {
        return redirect()->route('contract.management')->with('selectedContract', $this->contractId);
    }
    
    // Due Date Management Methods
    public function showCreateDueDate()
    {
        $this->authorizeAction('due_dates.create');
        $this->resetDueDateForm();
        $this->showDueDateForm = true;
    }
    
    public function showEditDueDate($dueDateId)
    {
        $this->authorizeAction('due_dates.edit');
        $this->selectedDueDate = ContractReportDueDate::with('emails')->findOrFail($dueDateId);
        $this->fillDueDateForm();
        $this->showDueDateForm = true;
    }
    
    public function resetDueDateForm()
    {
        $this->selectedDueDate = null;
        $this->due_date = '';
        $this->description = '';
        $this->report_type = 'quarterly';
        $this->reminder_intervals = [30, 15, 7, 3, 1];
        $this->active = true;
        $this->assigned_users = [];
    }
    
    public function fillDueDateForm()
    {
        if ($this->selectedDueDate) {
            $this->due_date = $this->selectedDueDate->due_date?->format('Y-m-d') ?? '';
            $this->description = $this->selectedDueDate->description ?? '';
            $this->report_type = $this->selectedDueDate->report_type ?? 'quarterly';
            $this->reminder_intervals = $this->selectedDueDate->reminder_intervals ?? [30, 15, 7, 3, 1];
            $this->active = $this->selectedDueDate->active ?? true;
            
            // Get assigned users by matching emails since user_id might be null
            $assignedEmails = $this->selectedDueDate->emails->pluck('email')->toArray();
            $this->assigned_users = User::whereIn('email', $assignedEmails)->pluck('id')->toArray();
        }
    }
    
    public function saveDueDate()
    {
        $this->validate([
            'due_date' => 'required|date',
            'description' => 'nullable|string',
            'report_type' => 'required|string',
            'reminder_intervals' => 'array',
            'reminder_intervals.*' => 'integer|min:0|max:365',
            'active' => 'boolean',
            'assigned_users' => 'array',
            'assigned_users.*' => 'exists:users,id',
        ]);
        
        try {
            if ($this->selectedDueDate) {
                // Update existing
                $this->selectedDueDate->update([
                    'due_date' => $this->due_date,
                    'description' => $this->description,
                    'report_type' => $this->report_type,
                    'reminder_intervals' => $this->reminder_intervals,
                    'active' => $this->active,
                    'updated_by' => auth()->id(),
                ]);
                $dueDate = $this->selectedDueDate;
            } else {
                // Create new
                $dueDate = ContractReportDueDate::create([
                    'contract_id' => $this->contract->id,
                    'due_date' => $this->due_date,
                    'status' => 'pending',
                    'description' => $this->description,
                    'report_type' => $this->report_type,
                    'reminder_intervals' => $this->reminder_intervals,
                    'active' => $this->active,
                    'created_by' => auth()->id(),
                ]);
            }
            
            // Update assigned users
            $dueDate->emails()->detach();
            if (!empty($this->assigned_users)) {
                foreach ($this->assigned_users as $userId) {
                    $user = User::find($userId);
                    if ($user) {
                        // Create or find DueDateEmail record
                        $dueDateEmail = DueDateEmail::firstOrCreate(
                            ['email' => $user->email],
                            [
                                'name' => $user->name,
                                'active' => true,
                                'created_by' => auth()->id(),
                            ]
                        );
                        
                        // Attach to due date
                        $dueDate->emails()->attach($dueDateEmail->id, [
                            'reminder_intervals' => json_encode($this->reminder_intervals),
                            'active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
            
            session()->flash('success', 'Report due date saved successfully.');
            $this->showDueDateForm = false;
            $this->resetDueDateForm();
            $this->loadContract(); // Refresh contract data
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    
    public function deleteDueDate($dueDateId)
    {
        $this->authorizeAction('due_dates.delete');
        
        try {
            $dueDate = ContractReportDueDate::findOrFail($dueDateId);
            $dueDate->update(['updated_by' => auth()->id()]);
            $dueDate->delete();
            
            session()->flash('success', 'Report due date deleted successfully.');
            $this->loadContract(); // Refresh contract data
            
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete due date: ' . $e->getMessage());
        }
    }
    
    public function closeDueDateForm()
    {
        $this->showDueDateForm = false;
        $this->resetDueDateForm();
    }
    
    // Report Upload Methods
    public function showUploadReport($dueDateId)
    {
        $this->authorizeAction('contract_reports.create');
        
        $this->selectedDueDateForReport = ContractReportDueDate::findOrFail($dueDateId);
        $this->resetReportForm();
        $this->showReportForm = true;
    }
    
    public function resetReportForm()
    {
        $this->report_title = '';
        $this->report_description = '';
        $this->report_documents = [];
        $this->report_notes = '';
    }
    
    public function uploadReport()
    {
        $this->authorizeAction('contract_reports.create');
        
        $this->validate([
            'report_title' => 'required|string|max:255',
            'report_description' => 'nullable|string',
            'report_notes' => 'nullable|string',
            'report_documents' => 'required|array|min:1',
            'report_documents.*' => 'file|max:102400', // 100MB max
        ]);
        
        try {
            $report = ContractReport::create([
                'contract_id' => $this->contract->id,
                'contract_report_due_date_id' => $this->selectedDueDateForReport->id,
                'title' => $this->report_title,
                'description' => $this->report_description,
                'submitted_date' => now(),
                'status' => 'submitted',
                'notes' => $this->report_notes,
                'created_by' => auth()->id(),
            ]);
            
            // Handle document uploads
            if (!empty($this->report_documents)) {
                foreach ($this->report_documents as $document) {
                    $report->addMedia($document->getRealPath())
                           ->usingName($document->getClientOriginalName())
                           ->usingFileName($document->hashName())
                           ->toMediaCollection('documents');
                }
            }
            
            // Update due date status if needed
            if ($this->selectedDueDateForReport->status !== 'completed') {
                $this->selectedDueDateForReport->update([
                    'status' => 'completed',
                    'updated_by' => auth()->id(),
                ]);
            }
            
            session()->flash('success', 'Report uploaded successfully.');
            $this->showReportForm = false;
            $this->resetReportForm();
            $this->loadContract(); // Refresh contract data
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }
    
    public function downloadReport($reportId, $mediaId)
    {
        $this->authorizeAction('contract_reports.view');
        
        try {
            $report = ContractReport::findOrFail($reportId);
            $media = $report->getMedia('documents')->where('id', $mediaId)->first();
            
            if ($media) {
                return response()->download($media->getPath(), $media->name);
            }
            
            session()->flash('error', 'File not found.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot download file: ' . $e->getMessage());
        }
    }
    
    public function deleteReport($reportId)
    {
        $this->authorizeAction('contract_reports.delete');
        
        try {
            $report = ContractReport::findOrFail($reportId);
            
            // Update due date status back to pending
            if ($report->contractReportDueDate) {
                $report->contractReportDueDate->update([
                    'status' => 'pending',
                    'updated_by' => auth()->id(),
                ]);
            }
            
            $report->update(['updated_by' => auth()->id()]);
            $report->delete();
            
            session()->flash('success', 'Report deleted successfully.');
            $this->loadContract(); // Refresh contract data
            
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete report: ' . $e->getMessage());
        }
    }
    
    public function closeReportForm()
    {
        $this->showReportForm = false;
        $this->selectedDueDateForReport = null;
        $this->resetReportForm();
    }
    
    public function showAssignedUsers($dueDateId)
    {
        $this->selectedDueDateForUsers = ContractReportDueDate::with(['emails.user'])->findOrFail($dueDateId);
        $this->showAssignedUsersModal = true;
    }
    
    public function closeAssignedUsersModal()
    {
        $this->showAssignedUsersModal = false;
        $this->selectedDueDateForUsers = null;
    }
    
    public function downloadDocument($mediaId)
    {
        $this->authorizeAction('contracts.view');
        
        try {
            $media = $this->contract->getMedia('documents')->where('id', $mediaId)->first();
            
            if ($media) {
                return response()->download($media->getPath(), $media->name);
            }
            
            session()->flash('error', 'Document not found.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot download document: ' . $e->getMessage());
        }
    }
    
    public function previewDocument($contractId, $mediaId)
    {
        // Check permission
        if (!auth()->user()->can('contracts.view')) {
            abort(403, 'Unauthorized');
        }
        
        try {
            // Load the contract and media
            $contract = Contract::findOrFail($contractId);
            $media = $contract->getMedia('documents')->where('id', $mediaId)->first();
            
            if (!$media) {
                abort(404, 'Document not found');
            }
            
            $path = $media->getPath();
            $mimeType = $media->mime_type;
            
            // For PDFs and images, return inline response
            if (in_array($mimeType, ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'])) {
                return response()->file($path, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . $media->name . '"'
                ]);
            }
            
            // For other types, download
            return response()->download($path, $media->name);
            
        } catch (\Exception $e) {
            abort(500, 'Cannot preview document: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.contract-show')->layout('components.layouts.app');
    }
}