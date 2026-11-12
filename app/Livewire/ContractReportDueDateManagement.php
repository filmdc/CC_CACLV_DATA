<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractReport;
use App\Models\ContractReportDueDate;
use App\Models\User;
use App\Services\MediaValidationService;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ContractReportDueDateManagement extends Component
{
    use WithFileUploads, WithPagination, WithPermissionChecks;

    protected $listeners = ['recipientsUpdated'];

    public $search = '';

    public $contractFilter = '';

    public $statusFilter = '';

    public $showCreateForm = false;

    public $showEditForm = false;

    public $showUploadForm = false;

    public $selectedDueDate = null;

    public $selectedReport = null;

    // Form Fields
    public $contract_id = '';

    public $report_name = '';

    public $due_date = '';

    public $description = '';

    public $reminder_intervals = [];

    public $active = true;

    public $assigned_recipients = []; // Array of due_date_email IDs (users + distribution lists)

    // Report Upload Fields
    public $report_title = '';

    public $report_description = '';

    public $report_files = [];

    public $uploaded_files = [];

    // Available options
    public $contracts = [];

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
        $this->authorizeAction('due_dates.view');
        $this->loadFormData();
        $this->reminder_intervals = [30, 15, 7, 3, 1];
    }

    public function loadFormData()
    {
        $this->contracts = Contract::active()->with('program', 'funder')->get();
        $this->users = User::active()->orderBy('name')->get();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function recipientsUpdated($recipientIds)
    {
        $this->assigned_recipients = $recipientIds;
    }

    public function updatedContractFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function showCreateDueDate()
    {
        $this->authorizeAction('due_dates.create');
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedDueDate = null;
        $this->resetForm();
    }

    public function showEditDueDate($dueDateId)
    {
        $this->authorizeAction('due_dates.edit');
        $this->selectedDueDate = ContractReportDueDate::with('emails.user')->findOrFail($dueDateId);
        $this->showEditForm = true;
        $this->showCreateForm = false;
        $this->fillForm();
    }

    public function viewDueDate($dueDateId)
    {
        $this->authorizeAction('due_dates.view');
        $this->selectedDueDate = ContractReportDueDate::with(['emails.user', 'contractReports', 'contract'])->findOrFail($dueDateId);
        $this->showEditForm = false;
        $this->showCreateForm = false;
        $this->fillForm();
    }

    public function resetForm()
    {
        $this->contract_id = '';
        $this->report_name = '';
        $this->due_date = '';
        $this->description = '';
        $this->reminder_intervals = [30, 15, 7, 3, 1];
        $this->active = true;
        $this->assigned_recipients = [];
    }

    public function fillForm()
    {
        if ($this->selectedDueDate) {
            $this->contract_id = $this->selectedDueDate->contract_id;
            $this->report_name = $this->selectedDueDate->report_name;
            $this->due_date = $this->selectedDueDate->due_date?->format('Y-m-d');
            $this->description = $this->selectedDueDate->description;
            $this->reminder_intervals = $this->selectedDueDate->reminder_intervals ?? [30, 15, 7, 3, 1];
            $this->active = $this->selectedDueDate->active;
            $this->assigned_recipients = $this->selectedDueDate->emails->pluck('id')->toArray();
        }
    }

    public function createDueDate()
    {
        $this->authorizeAction('due_dates.create');

        $this->validate([
            'contract_id' => 'required|exists:contracts,id',
            'report_name' => 'required|string|max:255',
            'due_date' => 'required|date|after:today',
            'description' => 'nullable|string',
            'reminder_intervals' => 'array',
            'reminder_intervals.*' => 'integer|min:0|max:365',
            'active' => 'boolean',
            'assigned_recipients' => 'array',
            'assigned_recipients.*' => 'exists:due_date_emails,id',
        ]);

        try {
            $dueDate = ContractReportDueDate::create([
                'contract_id' => $this->contract_id,
                'report_name' => $this->report_name,
                'due_date' => $this->due_date,
                'description' => $this->description,
                'reminder_intervals' => $this->reminder_intervals,
                'active' => $this->active,
                'created_by' => auth()->id(),
            ]);

            // Assign recipients (users + distribution lists)
            if (! empty($this->assigned_recipients)) {
                foreach ($this->assigned_recipients as $recipientId) {
                    $dueDate->emails()->attach($recipientId, [
                        'reminder_intervals' => $this->reminder_intervals,
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            session()->flash('success', 'Report due date created successfully.');
            $this->showCreateForm = false;
            $this->resetForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function updateDueDate()
    {
        $this->authorizeAction('due_dates.edit');

        $this->validate([
            'contract_id' => 'required|exists:contracts,id',
            'report_name' => 'required|string|max:255',
            'due_date' => 'required|date',
            'description' => 'nullable|string',
            'reminder_intervals' => 'array',
            'reminder_intervals.*' => 'integer|min:0|max:365',
            'active' => 'boolean',
            'assigned_recipients' => 'array',
            'assigned_recipients.*' => 'exists:due_date_emails,id',
        ]);

        try {
            $this->selectedDueDate->update([
                'contract_id' => $this->contract_id,
                'report_name' => $this->report_name,
                'due_date' => $this->due_date,
                'description' => $this->description,
                'reminder_intervals' => $this->reminder_intervals,
                'active' => $this->active,
                'updated_by' => auth()->id(),
            ]);

            // Update assigned recipients
            $this->selectedDueDate->emails()->detach();
            if (! empty($this->assigned_recipients)) {
                foreach ($this->assigned_recipients as $recipientId) {
                    $this->selectedDueDate->emails()->attach($recipientId, [
                        'reminder_intervals' => $this->reminder_intervals,
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            session()->flash('success', 'Report due date updated successfully.');
            $this->showEditForm = false;
            $this->selectedDueDate = null;
            $this->resetForm();

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
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete due date: '.$e->getMessage());
        }
    }

    public function toggleStatus($dueDateId)
    {
        $this->authorizeAction('due_dates.edit');

        try {
            $dueDate = ContractReportDueDate::findOrFail($dueDateId);
            $dueDate->update([
                'status' => $dueDate->status === 'completed' ? 'pending' : 'completed',
                'updated_by' => auth()->id(),
            ]);
            session()->flash('success', 'Due date status updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function showUploadReport($dueDateId)
    {
        $this->authorizeAction('contract_reports.create');
        $this->selectedDueDate = ContractReportDueDate::with(['contract', 'contractReports'])->findOrFail($dueDateId);
        $this->showUploadForm = true;
        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->resetReportForm();
    }

    public function uploadReport()
    {
        $this->authorizeAction('contract_reports.create');

        $this->validate([
            'report_title' => 'required|string|max:255',
            'report_description' => 'nullable|string',
            'report_files' => 'required|array|min:1',
            'report_files.*' => 'file|max:102400', // 100MB max per file
        ]);

        try {
            // Create the contract report
            $report = ContractReport::create([
                'contract_id' => $this->selectedDueDate->contract_id,
                'contract_report_due_date_id' => $this->selectedDueDate->id,
                'title' => $this->report_title,
                'description' => $this->report_description,
                'submitted_date' => now(),
                'status' => 'submitted',
                'created_by' => auth()->id(),
            ]);

            // Upload files
            foreach ($this->report_files as $file) {
                $report->addMedia($file->getRealPath())
                    ->usingName($file->getClientOriginalName())
                    ->usingFileName(MediaValidationService::sanitizeFileName($file->getClientOriginalName()))
                    ->toMediaCollection('documents');
            }

            // Update due date status if needed
            if ($this->selectedDueDate->status !== 'completed') {
                $this->selectedDueDate->update([
                    'status' => 'completed',
                    'updated_by' => auth()->id(),
                ]);
            }

            session()->flash('success', 'Report uploaded successfully.');
            $this->showUploadForm = false;
            $this->resetReportForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function deleteReport($reportId)
    {
        $this->authorizeAction('contract_reports.delete');

        try {
            $report = ContractReport::findOrFail($reportId);
            $report->update(['updated_by' => auth()->id()]);
            $report->delete();
            session()->flash('success', 'Report deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete report: '.$e->getMessage());
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
            session()->flash('error', 'Cannot download file: '.$e->getMessage());
        }
    }

    public function resetReportForm()
    {
        $this->report_title = '';
        $this->report_description = '';
        $this->report_files = [];
        $this->uploaded_files = [];
        $this->resetValidation();
    }

    public function render()
    {
        $dueDates = ContractReportDueDate::with(['contract.program', 'contract.funder', 'emails', 'contractReports'])
            ->when($this->search, function ($query) {
                $query->where('report_name', 'like', '%'.$this->search.'%')
                    ->orWhereHas('contract', function ($q) {
                        $q->where('title', 'like', '%'.$this->search.'%');
                    });
            })
            ->when($this->contractFilter, function ($query) {
                $query->where('contract_id', $this->contractFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('due_date', 'asc')
            ->paginate(10);

        return view('livewire.contract-report-due-date-management', [
            'dueDates' => $dueDates,
        ])->layout('components.layouts.app');
    }
}
