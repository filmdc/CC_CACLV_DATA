<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractReportDueDate;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class ContractReportDueDateManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $search = '';
    public $contractFilter = '';
    public $statusFilter = '';
    public $showCreateForm = false;
    public $showEditForm = false;
    public $selectedDueDate = null;

    // Form Fields
    public $contract_id = '';
    public $report_name = '';
    public $due_date = '';
    public $description = '';
    public $reminder_intervals = [];
    public $active = true;
    public $assigned_users = [];

    // Available options
    public $contracts = [];
    public $users = [];
    public $reminderIntervalOptions = [
        30 => '30 days before',
        15 => '15 days before',
        7 => '7 days before',
        3 => '3 days before',
        1 => '1 day before',
        0 => 'On due date'
    ];

    public function mount()
    {
        $this->authorizeAction('contract_report_due_dates.view');
        $this->loadFormData();
        $this->reminder_intervals = [30, 15, 7, 3, 1];
    }

    public function loadFormData()
    {
        $this->contracts = Contract::active()->with('program', 'funder')->get();
        $this->users = User::active()->get();
    }

    public function updatedSearch()
    {
        $this->resetPage();
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
        $this->authorizeAction('contract_report_due_dates.create');
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->selectedDueDate = null;
        $this->resetForm();
    }

    public function showEditDueDate($dueDateId)
    {
        $this->authorizeAction('contract_report_due_dates.edit');
        $this->selectedDueDate = ContractReportDueDate::with('assignedUsers')->findOrFail($dueDateId);
        $this->showEditForm = true;
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
        $this->assigned_users = [];
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
            $this->assigned_users = $this->selectedDueDate->assignedUsers->pluck('id')->toArray();
        }
    }

    public function createDueDate()
    {
        $this->authorizeAction('contract_report_due_dates.create');

        $this->validate([
            'contract_id' => 'required|exists:contracts,id',
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
                'contract_id' => $this->contract_id,
                'report_name' => $this->report_name,
                'due_date' => $this->due_date,
                'description' => $this->description,
                'reminder_intervals' => $this->reminder_intervals,
                'active' => $this->active,
                'created_by' => auth()->id(),
            ]);

            // Assign users
            if (!empty($this->assigned_users)) {
                foreach ($this->assigned_users as $userId) {
                    $dueDate->dueDateEmails()->create([
                        'user_id' => $userId,
                        'active' => true,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            session()->flash('success', 'Report due date created successfully.');
            $this->showCreateForm = false;
            $this->resetForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function updateDueDate()
    {
        $this->authorizeAction('contract_report_due_dates.edit');

        $this->validate([
            'contract_id' => 'required|exists:contracts,id',
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
                'contract_id' => $this->contract_id,
                'report_name' => $this->report_name,
                'due_date' => $this->due_date,
                'description' => $this->description,
                'reminder_intervals' => $this->reminder_intervals,
                'active' => $this->active,
                'updated_by' => auth()->id(),
            ]);

            // Update assigned users
            $this->selectedDueDate->dueDateEmails()->delete();
            if (!empty($this->assigned_users)) {
                foreach ($this->assigned_users as $userId) {
                    $this->selectedDueDate->dueDateEmails()->create([
                        'user_id' => $userId,
                        'active' => true,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            session()->flash('success', 'Report due date updated successfully.');
            $this->showEditForm = false;
            $this->selectedDueDate = null;
            $this->resetForm();

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteDueDate($dueDateId)
    {
        $this->authorizeAction('contract_report_due_dates.delete');

        try {
            $dueDate = ContractReportDueDate::findOrFail($dueDateId);
            $dueDate->update(['updated_by' => auth()->id()]);
            $dueDate->delete();
            session()->flash('success', 'Report due date deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Cannot delete due date: ' . $e->getMessage());
        }
    }

    public function toggleStatus($dueDateId)
    {
        $this->authorizeAction('contract_report_due_dates.edit');

        try {
            $dueDate = ContractReportDueDate::findOrFail($dueDateId);
            $dueDate->update([
                'status' => $dueDate->status === 'completed' ? 'pending' : 'completed',
                'updated_by' => auth()->id(),
            ]);
            session()->flash('success', 'Due date status updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $dueDates = ContractReportDueDate::with(['contract.program', 'contract.funder', 'assignedUsers'])
            ->when($this->search, function ($query) {
                $query->where('report_name', 'like', '%' . $this->search . '%')
                      ->orWhereHas('contract', function ($q) {
                          $q->where('title', 'like', '%' . $this->search . '%');
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