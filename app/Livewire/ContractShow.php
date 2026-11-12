<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractReport;
use App\Models\ContractReportDueDate;
use App\Models\DueDateEmail;
use App\Models\User;
use App\Services\MediaValidationService;
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

    // Orphan Report Properties (reports without due dates)
    public $showOrphanReportForm = false;
    public $orphan_report_title = '';
    public $orphan_report_description = '';
    public $orphan_report_documents = [];
    public $orphan_report_notes = '';
    public $selectedOrphanReport = null;
    public $showAssociateModal = false;
    public $orphanReportToAssociate = null;
    public $targetDueDateId = null;

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
            'orphanReports.media',
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
                           ->usingFileName(MediaValidationService::sanitizeFileName($document->getClientOriginalName()))
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

    // ========================================
    // Orphan Report Methods (Reports Without Due Dates)
    // ========================================

    public function showCreateOrphanReport()
    {
        $this->authorizeAction('contract_reports.create_orphan');
        $this->resetOrphanReportForm();
        $this->showOrphanReportForm = true;
    }

    public function resetOrphanReportForm()
    {
        $this->orphan_report_title = '';
        $this->orphan_report_description = '';
        $this->orphan_report_documents = [];
        $this->orphan_report_notes = '';
        $this->selectedOrphanReport = null;
    }

    public function saveOrphanReport()
    {
        $this->authorizeAction('contract_reports.create_orphan');

        $this->validate([
            'orphan_report_title' => 'required|string|max:255',
            'orphan_report_description' => 'nullable|string',
            'orphan_report_documents.*' => 'nullable|file|max:10240',
            'orphan_report_notes' => 'nullable|string',
        ], [
            'orphan_report_title.required' => 'Please enter a report title.',
            'orphan_report_documents.*.max' => 'Each document must not exceed 10MB.',
        ]);

        try {
            $report = ContractReport::create([
                'contract_id' => $this->contract->id,
                'contract_report_due_date_id' => null, // Orphan report
                'title' => $this->orphan_report_title,
                'description' => $this->orphan_report_description,
                'submitted_date' => now()->toDateString(),
                'status' => 'submitted',
                'notes' => $this->orphan_report_notes,
                'metadata' => [
                    'is_orphan_report' => true,
                    'created_via_ui' => true,
                ],
                'created_by' => auth()->id(),
            ]);

            // Handle document uploads
            if (!empty($this->orphan_report_documents)) {
                foreach ($this->orphan_report_documents as $document) {
                    $report->addMedia($document->getRealPath())
                        ->usingName($document->getClientOriginalName())
                        ->usingFileName($document->getClientOriginalName())
                        ->toMediaCollection('submissions');
                }
            }

            session()->flash('success', 'General report created successfully.');
            $this->loadContract(); // Refresh contract data
            $this->closeOrphanReportForm();

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create report: ' . $e->getMessage());
        }
    }

    public function showAssociateOrphanReport($reportId)
    {
        $this->authorizeAction('contract_reports.associate');
        $this->orphanReportToAssociate = $reportId;
        $this->targetDueDateId = null;
        $this->showAssociateModal = true;
    }

    public function associateOrphanWithDueDate()
    {
        $this->authorizeAction('contract_reports.associate');

        $this->validate([
            'targetDueDateId' => 'required|exists:contract_report_due_dates,id',
        ], [
            'targetDueDateId.required' => 'Please select a due date.',
        ]);

        try {
            $report = ContractReport::findOrFail($this->orphanReportToAssociate);

            // Verify the due date belongs to the same contract
            $dueDate = ContractReportDueDate::findOrFail($this->targetDueDateId);
            if ($dueDate->contract_id !== $this->contract->id) {
                session()->flash('error', 'Selected due date does not belong to this contract.');
                return;
            }

            $report->update([
                'contract_report_due_date_id' => $this->targetDueDateId,
                'updated_by' => auth()->id(),
            ]);

            // Update due date status if needed
            if ($dueDate->status !== 'completed') {
                $dueDate->update(['status' => 'completed']);
            }

            session()->flash('success', 'Report successfully associated with due date.');
            $this->loadContract(); // Refresh contract data
            $this->closeAssociateModal();

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to associate report: ' . $e->getMessage());
        }
    }

    public function deleteOrphanReport($reportId)
    {
        $this->authorizeAction('contract_reports.delete');

        try {
            $report = ContractReport::findOrFail($reportId);

            // Verify report belongs to this contract and is an orphan
            if ($report->contract_id !== $this->contract->id || !is_null($report->contract_report_due_date_id)) {
                session()->flash('error', 'Invalid report.');
                return;
            }

            $report->update(['updated_by' => auth()->id()]);
            $report->delete();

            session()->flash('success', 'General report deleted successfully.');
            $this->loadContract(); // Refresh contract data

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete report: ' . $e->getMessage());
        }
    }

    public function closeOrphanReportForm()
    {
        $this->showOrphanReportForm = false;
        $this->resetOrphanReportForm();
    }

    public function closeAssociateModal()
    {
        $this->showAssociateModal = false;
        $this->orphanReportToAssociate = null;
        $this->targetDueDateId = null;
    }


    public function render()
    {
        return view('livewire.contract-show')->layout('components.layouts.app');
    }
}