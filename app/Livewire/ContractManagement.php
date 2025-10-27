<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractReport;
use App\Models\ContractReportDueDate;
use App\Models\DueDateEmail;
use App\Models\Funder;
use App\Models\Program;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContractManagement extends Component
{
    use WithFileUploads, WithPagination, WithPermissionChecks;

    public $search = '';

    public $statusFilter = 'active';

    // Individual column filters
    public $filterGrantCode = '';

    public $filterTitle = '';

    public $filterProgramId = '';

    public $filterFunderId = '';

    public $filterStartDate = '';

    public $filterEndDate = '';

    public $selectedContract = null;

    public $showCreateForm = false;

    public $showEditForm = false;

    public $showArchivedContracts = false;

    public $showViewModal = false;

    public $showDueDateForm = false;

    public $showReportForm = false;

    public $showReportView = false;

    public $showDueDateReportsModal = false;

    // Tab management
    public $activeTab = 'contracts'; // contracts, upcoming-deadlines

    // Modal states
    public $showAssignedUsersModal = false;

    public $selectedDueDateForUsers = null;

    // Contract Form Fields
    public $title = '';

    public $contract_number = '';

    public $cfda_number = '';

    public $purpose = '';

    public $program_id = '';

    public $funder_id = '';

    public $date_of_award = '';

    public $amount = '';

    public $start_date = '';

    public $end_date = '';

    public $grant_code = '';

    public $notes = '';

    // File upload
    public $documents = [];

    // Report Due Date Form Fields
    public $selectedDueDate = null;

    public $selectedReport = null;

    public $report_name = '';

    public $due_date = '';

    public $description = '';

    public $reminder_intervals = [];

    public $active = true;

    public $assigned_users = [];

    // Report Form Fields
    public $report_title = '';

    public $report_description = '';

    public $report_documents = [];

    public $report_status = 'draft';

    public $report_notes = '';

    // Available options
    public $users = [];

    public $reminderIntervalOptions = [
        30 => '30 days before',
        15 => '15 days before',
        7 => '7 days before',
        3 => '3 days before',
        1 => '1 day before',
        0 => 'On due date',
    ];

    public function mount()
    {
        $this->authorizeAction('contracts.view');
        $this->loadFormData();
        $this->reminder_intervals = [30, 15, 7, 3, 1];
    }

    public function loadFormData()
    {
        $this->users = User::where('active', true)->orderBy('name')->get();
    }

    public function resetContractForm()
    {
        $this->title = '';
        $this->contract_number = '';
        $this->cfda_number = '';
        $this->purpose = '';
        $this->program_id = '';
        $this->funder_id = '';
        $this->date_of_award = '';
        $this->amount = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->grant_code = '';
        $this->notes = '';
        $this->documents = [];
        $this->showViewModal = false;
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->selectedContract = null;
    }

    public function cancelForm()
    {
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->resetContractForm();
    }

    public function viewContract($contractId)
    {
        // Redirect to the contract show page instead of opening a modal
        return redirect()->route('contract.show', $contractId);
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->selectedContract = null;
    }

    public function showCreateContract()
    {
        try {
            // Debug logging
            \Log::info('showCreateContract called', [
                'user' => auth()->user()->id,
                'can_create' => $this->can('contracts.create'),
            ]);

            if (! $this->can('contracts.create')) {
                session()->flash('error', 'You do not have permission to create contracts.');

                return;
            }

            // Reset form BEFORE setting visibility flags
            $this->resetContractForm();
            $this->showCreateForm = true;
            $this->showEditForm = false;
            $this->selectedContract = null;

            // Force a refresh to ensure the form is displayed
            $this->dispatch('$refresh');

            \Log::info('showCreateContract completed', [
                'showCreateForm' => $this->showCreateForm,
                'showEditForm' => $this->showEditForm,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in showCreateContract: '.$e->getMessage());
            session()->flash('error', 'Error opening create form: '.$e->getMessage());
        }
    }

    public function showEditContract($contractId)
    {
        $this->authorizeAction('contracts.edit');
        $this->selectedContract = Contract::findOrFail($contractId);
        $this->showEditForm = true;
        $this->showCreateForm = false;
        $this->fillContractForm();
    }

    public function fillContractForm()
    {
        if ($this->selectedContract) {
            $this->title = $this->selectedContract->title;
            $this->contract_number = $this->selectedContract->contract_number;
            $this->cfda_number = $this->selectedContract->cfda_number;
            $this->purpose = $this->selectedContract->purpose;

            // Use primary program (handles both single program_id and many-to-many relationships)
            $primaryProgram = $this->selectedContract->getPrimaryProgram();
            $this->program_id = $primaryProgram ? $primaryProgram->id : '';

            $this->funder_id = $this->selectedContract->funder_id;
            $this->date_of_award = $this->selectedContract->date_of_award ? $this->selectedContract->date_of_award->format('Y-m-d') : '';
            $this->amount = $this->selectedContract->amount;
            $this->start_date = $this->selectedContract->start_date ? $this->selectedContract->start_date->format('Y-m-d') : '';
            $this->end_date = $this->selectedContract->end_date ? $this->selectedContract->end_date->format('Y-m-d') : '';
            $this->grant_code = $this->selectedContract->grant_code;
            $this->notes = $this->selectedContract->notes;
        }
    }

    public function createContract()
    {
        $this->authorizeAction('contracts.create');

        $this->validate([
            'title' => 'required|string|max:255',
            'contract_number' => 'nullable|string|max:255',
            'cfda_number' => 'nullable|string|max:255',
            'purpose' => 'nullable|string',
            'program_id' => 'required|exists:programs,id',
            'funder_id' => 'required|exists:funders,id',
            'date_of_award' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'grant_code' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'documents.*' => 'nullable|file|max:10240', // 10MB max
        ]);

        try {
            $contract = Contract::create([
                'title' => $this->title,
                'contract_number' => $this->contract_number,
                'cfda_number' => $this->cfda_number,
                'purpose' => $this->purpose,
                'program_id' => $this->program_id,
                'funder_id' => $this->funder_id,
                'date_of_award' => $this->date_of_award ?: null,
                'amount' => $this->amount,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date ?: null,
                'grant_code' => $this->grant_code,
                'notes' => $this->notes,
                'created_by' => auth()->id(),
            ]);

            // Handle document uploads
            if (! empty($this->documents)) {
                foreach ($this->documents as $document) {
                    $contract->addMediaFromStream($document->readStream())
                        ->usingName($document->getClientOriginalName())
                        ->toMediaCollection('documents');
                }
            }

            session()->flash('success', 'Contract created successfully.');
            $this->showCreateForm = false;
            $this->resetContractForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function updateContract()
    {
        $this->authorizeAction('contracts.edit');

        $this->validate([
            'title' => 'required|string|max:255',
            'contract_number' => 'nullable|string|max:255',
            'cfda_number' => 'nullable|string|max:255',
            'purpose' => 'nullable|string',
            'program_id' => 'required|exists:programs,id',
            'funder_id' => 'required|exists:funders,id',
            'date_of_award' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'grant_code' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'documents.*' => 'nullable|file|max:10240', // 10MB max
        ]);

        try {
            $this->selectedContract->update([
                'title' => $this->title,
                'contract_number' => $this->contract_number,
                'cfda_number' => $this->cfda_number,
                'purpose' => $this->purpose,
                'program_id' => $this->program_id,
                'funder_id' => $this->funder_id,
                'date_of_award' => $this->date_of_award ?: null,
                'amount' => $this->amount,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date ?: null,
                'grant_code' => $this->grant_code,
                'notes' => $this->notes,
                'updated_by' => auth()->id(),
            ]);

            // Handle new document uploads
            if (! empty($this->documents)) {
                foreach ($this->documents as $document) {
                    $this->selectedContract->addMediaFromStream($document->readStream())
                        ->usingName($document->getClientOriginalName())
                        ->toMediaCollection('documents');
                }
            }

            session()->flash('success', 'Contract updated successfully.');
            $this->showEditForm = false;
            $this->selectedContract = null;
            $this->resetContractForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function deleteContract($contractId)
    {
        $this->authorizeAction('contracts.delete');

        try {
            $contract = Contract::findOrFail($contractId);
            $contract->update(['updated_by' => auth()->id()]);
            $contract->delete();
            session()->flash('success', 'Contract deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete contract: '.$e->getMessage());
        }
    }

    public function archiveContract($contractId)
    {
        $this->authorizeAction('contracts.edit');

        try {
            $contract = Contract::findOrFail($contractId);
            $contract->archive();
            $contract->update(['updated_by' => auth()->id()]);
            session()->flash('success', 'Contract archived successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot archive contract: '.$e->getMessage());
        }
    }

    public function unarchiveContract($contractId)
    {
        $this->authorizeAction('contracts.edit');

        try {
            $contract = Contract::findOrFail($contractId);
            $contract->unarchive();
            $contract->update(['updated_by' => auth()->id()]);
            session()->flash('success', 'Contract unarchived successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot unarchive contract: '.$e->getMessage());
        }
    }

    public function removeDocument($documentId)
    {
        $this->authorizeAction('contracts.edit');

        try {
            $media = Media::findOrFail($documentId);
            $media->delete();
            session()->flash('success', 'Document removed successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot remove document: '.$e->getMessage());
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedFilterGrantCode()
    {
        $this->resetPage();
    }

    public function updatedFilterTitle()
    {
        $this->resetPage();
    }

    public function updatedFilterProgramId()
    {
        $this->resetPage();
    }

    public function updatedFilterFunderId()
    {
        $this->resetPage();
    }

    public function updatedFilterStartDate()
    {
        $this->resetPage();
    }

    public function updatedFilterEndDate()
    {
        $this->resetPage();
    }

    public function toggleArchivedView()
    {
        $this->showArchivedContracts = ! $this->showArchivedContracts;
        $this->resetPage();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
        $this->resetContractForm();
        $this->dispatch('tab-changed', ['tab' => $tab]);
    }

    public function massDeleteDueDates($ids)
    {
        $this->authorizeAction('due_dates.delete');

        try {
            ContractReportDueDate::whereIn('id', $ids)->delete();
            session()->flash('success', 'Selected due dates deleted successfully.');
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting due dates: '.$e->getMessage());
        }
    }

    // Report Due Date Management Methods
    public function showCreateDueDate()
    {
        $this->authorizeAction('due_dates.create');
        $this->showDueDateForm = true;
        $this->selectedDueDate = null;
        $this->resetDueDateForm();
    }

    public function showEditDueDate($dueDateId)
    {
        $this->authorizeAction('due_dates.edit');
        $this->selectedDueDate = ContractReportDueDate::with('emails')->findOrFail($dueDateId);
        $this->showDueDateForm = true;
        $this->fillDueDateForm();
    }

    public function viewDueDate($dueDateId)
    {
        $this->authorizeAction('due_dates.view');
        $this->selectedDueDate = ContractReportDueDate::with(['emails', 'contractReports'])->findOrFail($dueDateId);
        $this->showDueDateForm = true;
    }

    public function resetDueDateForm()
    {
        $this->report_name = '';
        $this->due_date = '';
        $this->description = '';
        $this->reminder_intervals = [30, 15, 7, 3, 1];
        $this->active = true;
        $this->assigned_users = [];
    }

    public function fillDueDateForm()
    {
        if ($this->selectedDueDate) {
            $this->report_name = $this->selectedDueDate->report_name ?? '';
            $this->due_date = $this->selectedDueDate->due_date?->format('Y-m-d') ?? '';
            $this->description = $this->selectedDueDate->description ?? '';
            $this->reminder_intervals = $this->selectedDueDate->reminder_intervals ?? [30, 15, 7, 3, 1];
            $this->active = $this->selectedDueDate->active ?? true;
            $this->assigned_users = $this->selectedDueDate->emails->pluck('user_id')->filter()->toArray();
        }
    }

    public function saveDueDate()
    {
        if ($this->selectedDueDate) {
            $this->updateDueDate();
        } else {
            $this->createDueDate();
        }
    }

    public function createDueDate()
    {
        $this->authorizeAction('due_dates.create');

        $this->validate([
            'report_name' => 'required|string|max:255',
            'due_date' => 'required|date|after:today',
            'description' => 'nullable|string',
            'reminder_intervals' => 'array',
            'reminder_intervals.*' => 'integer|min:0|max:365',
            'active' => 'boolean',
            'assigned_users' => 'array',
            'assigned_users.*' => 'exists:users,id',
        ]);

        try {
            $dueDate = ContractReportDueDate::create([
                'contract_id' => $this->selectedContract->id,
                'report_name' => $this->report_name,
                'due_date' => $this->due_date,
                'description' => $this->description,
                'reminder_intervals' => $this->reminder_intervals,
                'active' => $this->active,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            // Assign users
            if (! empty($this->assigned_users)) {
                foreach ($this->assigned_users as $userId) {
                    $user = User::find($userId);
                    if ($user) {
                        // Create or find DueDateEmail record
                        $dueDateEmail = DueDateEmail::firstOrCreate([
                            'user_id' => $userId,
                            'email' => $user->email,
                        ], [
                            'name' => $user->name,
                            'active' => true,
                            'created_by' => auth()->id(),
                        ]);

                        // Attach to due date
                        $dueDate->emails()->attach($dueDateEmail->id, [
                            'reminder_intervals' => $this->reminder_intervals,
                            'active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            session()->flash('success', 'Report due date created successfully.');
            $this->showDueDateForm = false;
            $this->resetDueDateForm();

            // Refresh the selected contract
            $this->selectedContract = $this->selectedContract->fresh(['reportDueDates']);

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function updateDueDate()
    {
        $this->authorizeAction('due_dates.edit');

        $this->validate([
            'report_name' => 'required|string|max:255',
            'due_date' => 'required|date',
            'description' => 'nullable|string',
            'reminder_intervals' => 'array',
            'reminder_intervals.*' => 'integer|min:0|max:365',
            'active' => 'boolean',
            'assigned_users' => 'array',
            'assigned_users.*' => 'exists:users,id',
        ]);

        try {
            $this->selectedDueDate->update([
                'report_name' => $this->report_name,
                'due_date' => $this->due_date,
                'description' => $this->description,
                'reminder_intervals' => $this->reminder_intervals,
                'active' => $this->active,
                'updated_by' => auth()->id(),
            ]);

            // Update assigned users
            $this->selectedDueDate->emails()->detach();
            if (! empty($this->assigned_users)) {
                foreach ($this->assigned_users as $userId) {
                    $user = User::find($userId);
                    if ($user) {
                        // Create or find DueDateEmail record
                        $dueDateEmail = DueDateEmail::firstOrCreate([
                            'user_id' => $userId,
                            'email' => $user->email,
                        ], [
                            'name' => $user->name,
                            'active' => true,
                            'created_by' => auth()->id(),
                        ]);

                        // Attach to due date
                        $this->selectedDueDate->emails()->attach($dueDateEmail->id, [
                            'reminder_intervals' => $this->reminder_intervals,
                            'active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            session()->flash('success', 'Report due date updated successfully.');
            $this->showDueDateForm = false;
            $this->selectedDueDate = null;
            $this->resetDueDateForm();

            // Refresh the selected contract
            $this->selectedContract = $this->selectedContract->fresh(['reportDueDates']);

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
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

            // Refresh the selected contract
            $this->selectedContract = $this->selectedContract->fresh(['reportDueDates']);

        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete due date: '.$e->getMessage());
        }
    }

    public function toggleDueDateStatus($dueDateId)
    {
        $this->authorizeAction('due_dates.edit');

        try {
            $dueDate = ContractReportDueDate::findOrFail($dueDateId);
            $dueDate->update([
                'status' => $dueDate->status === 'completed' ? 'pending' : 'completed',
                'updated_by' => auth()->id(),
            ]);

            session()->flash('success', 'Due date status updated successfully.');

            // Refresh the selected contract
            $this->selectedContract = $this->selectedContract->fresh(['reportDueDates']);

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function viewDueDateReports($dueDateId)
    {
        $this->authorizeAction('contract_reports.view');

        $this->selectedDueDate = ContractReportDueDate::with([
            'contractReports.media',
            'contractReports.creator',
            'contract',
        ])->findOrFail($dueDateId);

        $this->showDueDateReportsModal = true;
    }

    public function closeDueDateReportsModal()
    {
        $this->showDueDateReportsModal = false;
        $this->selectedDueDate = null;
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
            session()->flash('error', 'Cannot download file: '.$e->getMessage());
        }
    }

    public function closeDueDateForm()
    {
        $this->showDueDateForm = false;
        $this->selectedDueDate = null;
        $this->resetDueDateForm();
    }

    // Report Management Methods
    public function showCreateReport($dueDateId = null)
    {
        $this->authorizeAction('contract_reports.create');

        if ($dueDateId) {
            $this->selectedDueDate = ContractReportDueDate::findOrFail($dueDateId);
        }

        $this->showReportForm = true;
        $this->selectedReport = null;
        $this->resetReportForm();
    }

    public function showEditReport($reportId)
    {
        $this->authorizeAction('reports.edit');

        $this->selectedReport = ContractReport::findOrFail($reportId);
        $this->showReportForm = true;
        $this->fillReportForm();
    }

    public function viewReport($reportId)
    {
        $this->authorizeAction('reports.view');

        $this->selectedReport = ContractReport::with(['contractReportDueDate', 'contract'])->findOrFail($reportId);
        $this->showReportView = true;
    }

    public function resetReportForm()
    {
        $this->report_title = '';
        $this->report_description = '';
        $this->report_documents = [];
        $this->report_status = 'draft';
        $this->report_notes = '';
    }

    public function fillReportForm()
    {
        if ($this->selectedReport) {
            $this->report_title = $this->selectedReport->title;
            $this->report_description = $this->selectedReport->description;
            $this->report_status = $this->selectedReport->status;
            $this->report_notes = $this->selectedReport->notes;
            $this->selectedDueDate = $this->selectedReport->contractReportDueDate;
        }
    }

    public function saveReport()
    {
        if ($this->selectedReport) {
            $this->updateReport();
        } else {
            $this->createReport();
        }
    }

    public function createReport()
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
                'contract_id' => $this->selectedContract->id,
                'contract_report_due_date_id' => $this->selectedDueDate ? $this->selectedDueDate->id : null,
                'title' => $this->report_title,
                'description' => $this->report_description,
                'submitted_date' => now(),
                'status' => 'submitted', // Auto-submit when uploaded from due date
                'notes' => $this->report_notes,
                'created_by' => auth()->id(),
            ]);

            // Handle document uploads
            if (! empty($this->report_documents)) {
                foreach ($this->report_documents as $document) {
                    $report->addMedia($document->getRealPath())
                        ->usingName($document->getClientOriginalName())
                        ->usingFileName($document->hashName())
                        ->toMediaCollection('documents');
                }
            }

            // Update due date status if needed
            if ($this->selectedDueDate && $this->selectedDueDate->status !== 'completed') {
                $this->selectedDueDate->update([
                    'status' => 'completed',
                    'updated_by' => auth()->id(),
                ]);
            }

            session()->flash('success', 'Report uploaded successfully.');
            $this->showReportForm = false;
            $this->resetReportForm();

            // Refresh the selected contract with all relationships
            $this->selectedContract = Contract::with([
                'program',
                'funder',
                'reportDueDates.contractReports.media',
                'reportDueDates.contractReports.creator',
                'reportDueDates.emails.user',
            ])->findOrFail($this->selectedContract->id);

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function updateReport()
    {
        $this->authorizeAction('reports.edit');

        $this->validate([
            'report_title' => 'required|string|max:255',
            'report_description' => 'required|string',
            'report_notes' => 'nullable|string',
            'report_documents.*' => 'nullable|file|max:10240', // 10MB max
        ]);

        try {
            $this->selectedReport->update([
                'title' => $this->report_title,
                'description' => $this->report_description,
                'status' => $this->report_status,
                'notes' => $this->report_notes,
                'updated_by' => auth()->id(),
            ]);

            // Handle document uploads
            if (! empty($this->report_documents)) {
                foreach ($this->report_documents as $document) {
                    $this->selectedReport->addMedia($document->getRealPath())
                        ->usingName($document->getClientOriginalName())
                        ->toMediaCollection('documents');
                }
            }

            session()->flash('success', 'Report updated successfully.');
            $this->showReportForm = false;
            $this->selectedReport = null;
            $this->resetReportForm();

            // Refresh the selected contract
            $this->selectedContract = $this->selectedContract->fresh(['reportDueDates', 'contractReports']);

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function submitReport($reportId)
    {
        $this->authorizeAction('reports.edit');

        try {
            $report = ContractReport::findOrFail($reportId);

            if ($report->submit()) {
                session()->flash('success', 'Report submitted successfully.');

                // Refresh the selected contract
                $this->selectedContract = $this->selectedContract->fresh(['reportDueDates', 'contractReports']);
            } else {
                session()->flash('error', 'Report cannot be submitted. Please check that all required fields are filled.');
            }

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function deleteReport($reportId)
    {
        $this->authorizeAction('reports.delete');

        try {
            $report = ContractReport::findOrFail($reportId);
            $report->update(['updated_by' => auth()->id()]);
            $report->delete();

            session()->flash('success', 'Report deleted successfully.');

            // Refresh the selected contract
            $this->selectedContract = $this->selectedContract->fresh(['reportDueDates', 'contractReports']);

        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete report: '.$e->getMessage());
        }
    }

    public function closeReportForm()
    {
        $this->showReportForm = false;
        $this->selectedReport = null;
        $this->selectedDueDate = null;
        $this->resetReportForm();
    }

    public function closeReportView()
    {
        $this->showReportView = false;
        $this->selectedReport = null;
    }

    public function render()
    {
        // Get contracts if on contracts tab
        $contracts = collect();
        if ($this->activeTab === 'contracts') {
            $query = Contract::with(['program', 'funder', 'reportDueDates'])
                ->when($this->showArchivedContracts, function ($q) {
                    $q->archived();
                }, function ($q) {
                    $q->active();
                })
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('title', 'like', '%'.$this->search.'%')
                            ->orWhere('contract_number', 'like', '%'.$this->search.'%')
                            ->orWhere('cfda_number', 'like', '%'.$this->search.'%')
                            ->orWhere('grant_code', 'like', '%'.$this->search.'%')
                            ->orWhereHas('program', function ($q) {
                                $q->where('name', 'like', '%'.$this->search.'%');
                            })
                            ->orWhereHas('funder', function ($q) {
                                $q->where('name', 'like', '%'.$this->search.'%');
                            });
                    });
                })
                // Individual column filters
                ->when($this->filterGrantCode, function ($query) {
                    $query->where('grant_code', 'like', '%'.$this->filterGrantCode.'%');
                })
                ->when($this->filterTitle, function ($query) {
                    $query->where('title', 'like', '%'.$this->filterTitle.'%');
                })
                ->when($this->filterProgramId, function ($query) {
                    $query->where('program_id', $this->filterProgramId);
                })
                ->when($this->filterFunderId, function ($query) {
                    $query->where('funder_id', $this->filterFunderId);
                })
                ->when($this->filterStartDate, function ($query) {
                    $query->whereDate('start_date', '>=', $this->filterStartDate);
                })
                ->when($this->filterEndDate, function ($query) {
                    $query->whereDate('end_date', '<=', $this->filterEndDate);
                })
                ->when($this->statusFilter !== 'all', function ($query) {
                    switch ($this->statusFilter) {
                        case 'active':
                            $query->where('start_date', '<=', now())
                                ->where('end_date', '>=', now());
                            break;
                        case 'upcoming':
                            $query->where('start_date', '>', now());
                            break;
                        case 'expired':
                            $query->where('end_date', '<', now());
                            break;
                    }
                });

            $contracts = $query->orderBy('created_at', 'desc')->paginate(10);
        }

        // Get upcoming deadlines if on upcoming-deadlines tab
        $upcomingDeadlines = collect();
        if ($this->activeTab === 'upcoming-deadlines') {
            $upcomingDeadlines = $this->getUpcomingDeadlines();
        }

        $programs = Program::active()->orderBy('name')->get();
        $funders = Funder::active()->orderBy('name')->get();

        return view('livewire.contract-management', [
            'contracts' => $contracts,
            'upcomingDeadlines' => $upcomingDeadlines,
            'programs' => $programs,
            'funders' => $funders,
        ])->layout('components.layouts.app');
    }

    private function getUpcomingDeadlines()
    {
        $user = auth()->user();

        // Get upcoming report due dates
        // If user is admin or has special permissions, show all upcoming deadlines
        // Otherwise, show only deadlines assigned to the user
        $query = ContractReportDueDate::with(['contract.program', 'contract.funder', 'contractReports', 'emails'])
            ->where('active', true)
            ->where('due_date', '>=', now()->format('Y-m-d')); // Only future dates like monitor.blade.php

        // Filter by user assignment unless admin
        if (! $user->hasRole('Administrator') && ! $user->can('contracts.view_all_deadlines')) {
            $query->whereHas('emails', function ($q) use ($user) {
                $q->where('email', $user->email)
                    ->where('contract_report_due_date_emails.active', true);
            });
        }

        // Get all results for DataTables (no pagination)
        return $query->orderBy('due_date', 'asc')->get();
    }

    public function getPrograms()
    {
        return Program::active()->orderBy('name')->get();
    }

    public function getFunders()
    {
        return Funder::active()->orderBy('name')->get();
    }
}
