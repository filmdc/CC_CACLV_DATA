<?php

namespace App\Livewire;

use App\Models\HousingJob;
use App\Models\Property;
use App\Models\Contractor;
use App\Models\Program;
use App\Models\Service;
use App\Models\Participant;
use App\Models\Household;
use App\Models\ReferenceData;
use App\Models\JobContactLedger;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HousingJobManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    // UI State
    public $search = '';
    public $showCreateForm = false;
    public $showEditForm = false;
    public $selectedJob = null;
    public $showDetails = false;
    public $selectedJobId = null;
    public $filterStatus = '';
    public $filterProgram = '';
    public $filterContractor = '';
    public $filterPriority = '';
    public $filterOverdue = false;
    public $activeTab = 'details';

    // Form Fields
    public $participant_id = '';
    public $household_id = '';
    public $property_id = '';
    public $contractor_id = '';
    public $program_id = '';
    public $service_id = '';
    public $job_title = '';
    public $job_description = '';
    public $job_type_id = '';
    public $status = 'pending';
    public $request_date = '';
    public $approval_date = '';
    public $start_date = '';
    public $target_completion_date = '';
    public $actual_completion_date = '';
    public $estimated_cost = '';
    public $approved_amount = '';
    public $actual_cost = '';
    public $funding_source = '';
    public $work_order_number = '';
    public $contract_number = '';
    public $scope_of_work = [];
    public $priority = 'medium';
    public $cancellation_reason = '';
    public $internal_notes = '';

    // Contact Ledger
    public $showContactForm = false;
    public $contactType = '';
    public $contactDate = '';
    public $contactNotes = '';
    public $contactNextAction = '';

    // Scope of Work
    public $newScopeItem = '';

    protected $rules = [
        'participant_id' => 'nullable|exists:participants,id',
        'household_id' => 'required|exists:households,id',
        'property_id' => 'required|exists:properties,id',
        'contractor_id' => 'nullable|exists:contractors,id',
        'program_id' => 'required|exists:programs,id',
        'service_id' => 'nullable|exists:services,id',
        'job_title' => 'required|string|max:255',
        'job_description' => 'nullable|string',
        'job_type_id' => 'required|exists:reference_data,id',
        'status' => 'required|in:pending,approved,in_progress,on_hold,completed,cancelled,rejected',
        'request_date' => 'required|date',
        'approval_date' => 'nullable|date|after_or_equal:request_date',
        'start_date' => 'nullable|date|after_or_equal:approval_date',
        'target_completion_date' => 'nullable|date|after_or_equal:start_date',
        'actual_completion_date' => 'nullable|date',
        'estimated_cost' => 'nullable|numeric|min:0',
        'approved_amount' => 'nullable|numeric|min:0',
        'actual_cost' => 'nullable|numeric|min:0',
        'funding_source' => 'nullable|string|max:255',
        'work_order_number' => 'nullable|string|max:100',
        'contract_number' => 'nullable|string|max:100',
        'priority' => 'required|in:low,medium,high,emergency',
        'cancellation_reason' => 'nullable|string',
        'internal_notes' => 'nullable|string',
    ];

    public function mount()
    {
        $this->authorizeAction('housing_jobs.view');
        $this->request_date = date('Y-m-d');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterProgram()
    {
        $this->resetPage();
    }

    public function updatedFilterContractor()
    {
        $this->resetPage();
    }

    public function updatedFilterPriority()
    {
        $this->resetPage();
    }

    public function updatedFilterOverdue()
    {
        $this->resetPage();
    }

    public function updatedPropertyId()
    {
        if ($this->property_id) {
            $property = Property::find($this->property_id);
            if ($property) {
                $this->household_id = $property->household_id;
                $this->participant_id = $property->participant_id;
            }
        }
    }

    public function updatedProgramId()
    {
        // Reset service when program changes
        $this->service_id = '';
    }

    public function showHousingJobCreateForm()
    {
        $this->authorizeAction('housing_jobs.create');
        $this->resetForm();
        $this->showCreateForm = true;
        $this->showEditForm = false;
        $this->showDetails = false;
    }

    public function showEditForm($jobId)
    {
        $this->authorizeAction('housing_jobs.update');
        $this->selectedJob = HousingJob::findOrFail($jobId);
        $this->fillFormFromJob();
        $this->showEditForm = true;
        $this->showCreateForm = false;
        $this->showDetails = false;
    }

    public function viewDetails($jobId)
    {
        // Redirect to the job show page instead of opening a modal
        return redirect()->route('housing-job.show', $jobId);
    }

    public function save()
    {
        $this->authorizeAction('housing_jobs.create');
        $this->validate();

        try {
            DB::beginTransaction();

            $job = HousingJob::create([
                'participant_id' => $this->participant_id ?: null,
                'household_id' => $this->household_id,
                'property_id' => $this->property_id,
                'contractor_id' => $this->contractor_id ?: null,
                'program_id' => $this->program_id,
                'service_id' => $this->service_id ?: null,
                'job_title' => $this->job_title,
                'job_description' => $this->job_description,
                'job_type_id' => $this->job_type_id,
                'status' => $this->status,
                'request_date' => $this->request_date,
                'approval_date' => $this->approval_date ?: null,
                'start_date' => $this->start_date ?: null,
                'target_completion_date' => $this->target_completion_date ?: null,
                'actual_completion_date' => $this->actual_completion_date ?: null,
                'estimated_cost' => $this->estimated_cost ?: null,
                'approved_amount' => $this->approved_amount ?: null,
                'actual_cost' => $this->actual_cost ?: null,
                'funding_source' => $this->funding_source,
                'work_order_number' => $this->work_order_number,
                'contract_number' => $this->contract_number,
                'scope_of_work' => $this->scope_of_work,
                'priority' => $this->priority,
                'cancellation_reason' => $this->cancellation_reason,
                'internal_notes' => $this->internal_notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Add initial contact ledger entry
            $job->contactLedgers()->create([
                'contact_type' => 'system',
                'contact_date' => now(),
                'notes' => 'Job created',
                'created_by' => Auth::id(),
            ]);

            DB::commit();
            session()->flash('message', 'Housing job created successfully. Job Number: ' . $job->job_number);
            $this->showCreateForm = false;
            $this->resetForm();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error creating housing job: ' . $e->getMessage());
        }
    }

    public function update()
    {
        $this->authorizeAction('housing_jobs.update');
        $this->validate();

        try {
            DB::beginTransaction();

            $oldStatus = $this->selectedJob->status;

            $this->selectedJob->update([
                'participant_id' => $this->participant_id ?: null,
                'household_id' => $this->household_id,
                'property_id' => $this->property_id,
                'contractor_id' => $this->contractor_id ?: null,
                'program_id' => $this->program_id,
                'service_id' => $this->service_id ?: null,
                'job_title' => $this->job_title,
                'job_description' => $this->job_description,
                'job_type_id' => $this->job_type_id,
                'status' => $this->status,
                'request_date' => $this->request_date,
                'approval_date' => $this->approval_date ?: null,
                'start_date' => $this->start_date ?: null,
                'target_completion_date' => $this->target_completion_date ?: null,
                'actual_completion_date' => $this->actual_completion_date ?: null,
                'estimated_cost' => $this->estimated_cost ?: null,
                'approved_amount' => $this->approved_amount ?: null,
                'actual_cost' => $this->actual_cost ?: null,
                'funding_source' => $this->funding_source,
                'work_order_number' => $this->work_order_number,
                'contract_number' => $this->contract_number,
                'scope_of_work' => $this->scope_of_work,
                'priority' => $this->priority,
                'cancellation_reason' => $this->cancellation_reason,
                'internal_notes' => $this->internal_notes,
                'updated_by' => Auth::id(),
            ]);

            // Log status change
            if ($oldStatus !== $this->status) {
                $this->selectedJob->contactLedgers()->create([
                    'contact_type' => 'system',
                    'contact_date' => now(),
                    'notes' => "Status changed from {$oldStatus} to {$this->status}",
                    'created_by' => Auth::id(),
                ]);
            }

            // Handle approval
            if ($this->status === 'approved' && !$this->selectedJob->approved_by) {
                $this->selectedJob->update([
                    'approved_by' => Auth::id(),
                    'approval_date' => $this->approval_date ?: now(),
                ]);
            }

            DB::commit();
            session()->flash('message', 'Housing job updated successfully.');
            $this->showEditForm = false;
            $this->resetForm();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error updating housing job: ' . $e->getMessage());
        }
    }

    public function delete($jobId)
    {
        $this->authorizeAction('housing_jobs.delete');
        
        try {
            $job = HousingJob::findOrFail($jobId);
            
            // Check if job can be deleted
            if (in_array($job->status, ['in_progress', 'completed'])) {
                session()->flash('error', 'Cannot delete a job that is in progress or completed.');
                return;
            }
            
            $job->delete();
            session()->flash('message', 'Housing job deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting housing job: ' . $e->getMessage());
        }
    }

    public function updateStatus($jobId, $newStatus)
    {
        $this->authorizeAction('housing_jobs.update');
        
        try {
            $job = HousingJob::findOrFail($jobId);
            $oldStatus = $job->status;
            
            $job->update([
                'status' => $newStatus,
                'updated_by' => Auth::id(),
            ]);

            // Handle specific status changes
            if ($newStatus === 'approved' && !$job->approved_by) {
                $job->update([
                    'approved_by' => Auth::id(),
                    'approval_date' => now(),
                ]);
            } elseif ($newStatus === 'completed') {
                $job->update([
                    'actual_completion_date' => now(),
                ]);
            }

            // Log status change
            $job->contactLedgers()->create([
                'contact_type' => 'system',
                'contact_date' => now(),
                'notes' => "Status changed from {$oldStatus} to {$newStatus}",
                'created_by' => Auth::id(),
            ]);

            session()->flash('message', 'Job status updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating job status: ' . $e->getMessage());
        }
    }

    public function addContact()
    {
        $this->authorizeAction('housing_jobs.update');
        $this->validate([
            'contactType' => 'required|string',
            'contactDate' => 'required|date',
            'contactNotes' => 'required|string',
        ]);

        try {
            $this->selectedJob->contactLedgers()->create([
                'contact_type' => $this->contactType,
                'contact_date' => $this->contactDate,
                'notes' => $this->contactNotes,
                'next_action' => $this->contactNextAction,
                'created_by' => Auth::id(),
            ]);

            $this->resetContactForm();
            $this->selectedJob->load('contactLedgers.createdBy');
            
            session()->flash('message', 'Contact log added successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error adding contact log: ' . $e->getMessage());
        }
    }

    public function addScopeItem()
    {
        if ($this->newScopeItem) {
            $this->scope_of_work[] = $this->newScopeItem;
            $this->newScopeItem = '';
        }
    }

    public function removeScopeItem($index)
    {
        unset($this->scope_of_work[$index]);
        $this->scope_of_work = array_values($this->scope_of_work);
    }

    public function resetForm()
    {
        $this->reset([
            'participant_id',
            'household_id',
            'property_id',
            'contractor_id',
            'program_id',
            'service_id',
            'job_title',
            'job_description',
            'job_type_id',
            'status',
            'approval_date',
            'start_date',
            'target_completion_date',
            'actual_completion_date',
            'estimated_cost',
            'approved_amount',
            'actual_cost',
            'funding_source',
            'work_order_number',
            'contract_number',
            'scope_of_work',
            'priority',
            'cancellation_reason',
            'internal_notes',
            'selectedJob',
            'newScopeItem',
        ]);
        $this->request_date = date('Y-m-d');
        $this->status = 'pending';
        $this->priority = 'medium';
        $this->scope_of_work = [];
    }

    public function resetContactForm()
    {
        $this->reset([
            'contactType',
            'contactDate',
            'contactNotes',
            'contactNextAction',
            'showContactForm',
        ]);
    }

    public function fillFormFromJob()
    {
        $this->participant_id = $this->selectedJob->participant_id;
        $this->household_id = $this->selectedJob->household_id;
        $this->property_id = $this->selectedJob->property_id;
        $this->contractor_id = $this->selectedJob->contractor_id;
        $this->program_id = $this->selectedJob->program_id;
        $this->service_id = $this->selectedJob->service_id;
        $this->job_title = $this->selectedJob->job_title;
        $this->job_description = $this->selectedJob->job_description;
        $this->job_type_id = $this->selectedJob->job_type_id;
        $this->status = $this->selectedJob->status;
        $this->request_date = $this->selectedJob->request_date->format('Y-m-d');
        $this->approval_date = $this->selectedJob->approval_date ? $this->selectedJob->approval_date->format('Y-m-d') : '';
        $this->start_date = $this->selectedJob->start_date ? $this->selectedJob->start_date->format('Y-m-d') : '';
        $this->target_completion_date = $this->selectedJob->target_completion_date ? $this->selectedJob->target_completion_date->format('Y-m-d') : '';
        $this->actual_completion_date = $this->selectedJob->actual_completion_date ? $this->selectedJob->actual_completion_date->format('Y-m-d') : '';
        $this->estimated_cost = $this->selectedJob->estimated_cost;
        $this->approved_amount = $this->selectedJob->approved_amount;
        $this->actual_cost = $this->selectedJob->actual_cost;
        $this->funding_source = $this->selectedJob->funding_source;
        $this->work_order_number = $this->selectedJob->work_order_number;
        $this->contract_number = $this->selectedJob->contract_number;
        $this->scope_of_work = $this->selectedJob->scope_of_work ?? [];
        $this->priority = $this->selectedJob->priority;
        $this->cancellation_reason = $this->selectedJob->cancellation_reason;
        $this->internal_notes = $this->selectedJob->internal_notes;
    }

    public function render()
    {
        // Get programs with housing module enabled
        $housingPrograms = Program::where('housing_module_enabled', true)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        // Build the jobs query
        $query = HousingJob::with(['property', 'contractor', 'program', 'participant', 'household', 'jobType'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('job_number', 'like', '%' . $this->search . '%')
                        ->orWhere('job_title', 'like', '%' . $this->search . '%')
                        ->orWhere('work_order_number', 'like', '%' . $this->search . '%')
                        ->orWhere('contract_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('property', function ($q) {
                            $q->where('address_line_1', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('contractor', function ($q) {
                            $q->where('company_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->filterStatus, function ($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterProgram, function ($q) {
                $q->where('program_id', $this->filterProgram);
            })
            ->when($this->filterContractor, function ($q) {
                $q->where('contractor_id', $this->filterContractor);
            })
            ->when($this->filterPriority, function ($q) {
                $q->where('priority', $this->filterPriority);
            })
            ->when($this->filterOverdue, function ($q) {
                $q->whereNotNull('target_completion_date')
                    ->where('target_completion_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled', 'rejected']);
            });

        $jobs = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get reference data
        $jobTypes = ReferenceData::where('category', 'housing_job_type')
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();

        $contactTypes = ReferenceData::where('category', 'contact_type')
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();

        // Get related data for dropdowns
        $properties = Property::with('household')->where('status', 'active')->orderBy('address_line_1')->get();
        $contractors = Contractor::where('status', 'active')->orderBy('company_name')->get();
        $participants = Participant::orderBy('last_name')->orderBy('first_name')->get();
        $households = Household::with('participants')->orderBy('household_code')->get();

        // Get services for selected program
        $services = [];
        if ($this->program_id) {
            $services = Service::where('program_id', $this->program_id)
                ->where('active', true)
                ->orderBy('name')
                ->get();
        }

        // Get active contractors for filter
        $activeContractors = Contractor::where('status', 'active')
            ->orderBy('company_name')
            ->get();

        return view('livewire.housing-job-management', [
            'jobs' => $jobs,
            'housingPrograms' => $housingPrograms,
            'jobTypes' => $jobTypes,
            'contactTypes' => $contactTypes,
            'properties' => $properties,
            'contractors' => $contractors,
            'participants' => $participants,
            'households' => $households,
            'services' => $services,
            'activeContractors' => $activeContractors,
        ])->layout('components.layouts.app');
    }
}
