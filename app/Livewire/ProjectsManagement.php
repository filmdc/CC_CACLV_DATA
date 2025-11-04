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
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class ProjectsManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    // UI State
    public $search = '';
    public $showCreateForm = false;
    public $showEditForm = false;
    public $showTemplateModal = false;
    public $selectedTemplateId = null;
    public $filterStatus = '';
    public $filterProgram = '';
    public $filterService = '';
    public $filterPriority = '';
    public $filterPmEnabled = ''; // all, pm_only, non_pm
    public $showTemplates = false; // Toggle to show/hide template jobs
    public $selectedJobs = [];
    public $selectAll = false;

    // Form Fields (simplified for now, full form in modal)
    public $editingJobId = null;
    public $participant_id = '';
    public $property_id = '';
    public $contractor_id = '';
    public $program_id = '';
    public $service_id = '';
    public $job_title = '';
    public $job_description = '';
    public $status = 'pending';
    public $request_date = '';
    public $start_date = '';
    public $target_completion_date = '';
    public $estimated_cost = '';
    public $priority = 'medium';

    // Quick Create Forms
    public $showQuickParticipantForm = false;
    public $showQuickPropertyForm = false;

    // Quick Participant Fields
    public $quick_participant_first_name = '';
    public $quick_participant_last_name = '';
    public $quick_participant_phone = '';
    public $quick_participant_email = '';
    public $quick_participant_dob = '';

    // Quick Property Fields
    public $quick_property_address = '';
    public $quick_property_city = '';
    public $quick_property_state = '';
    public $quick_property_zip = '';

    protected function getListeners()
    {
        return [
            'item-selected-participant' => 'handleParticipantSelected',
            'item-selected-property' => 'handlePropertySelected',
            'item-selected-contractor' => 'handleContractorSelected',
            'item-selected-service' => 'handleServiceSelected',
        ];
    }

    public function mount()
    {
        $this->authorizeAny(['housing_jobs.view', 'projects.view']);
        $this->request_date = date('Y-m-d');
    }

    public function handleParticipantSelected($value)
    {
        $this->participant_id = $value;
        // Household will be auto-derived on save
    }

    public function handlePropertySelected($value)
    {
        $this->property_id = $value;
        $this->updatedPropertyId(); // Trigger auto-fill
    }

    public function handleContractorSelected($value)
    {
        $this->contractor_id = $value;
    }

    public function handleServiceSelected($value)
    {
        $this->service_id = $value;
    }

    public function copyPropertyFromParticipant()
    {
        if (!$this->participant_id) {
            session()->flash('error', 'Please select a participant first');
            return;
        }

        $participant = Participant::with(['household.currentAddress'])->findOrFail($this->participant_id);

        if (!$participant->household || !$participant->household->currentAddress) {
            session()->flash('error', 'This participant\'s household has no address on file. Please create property manually.');
            return;
        }

        $address = $participant->household->currentAddress;

        // Create property from household address
        $property = Property::create([
            'participant_id' => $this->participant_id,
            'household_id' => $participant->household_id,
            'address_line_1' => $address->address,
            'address_line_2' => $address->address_line_2,
            'city' => $address->city,
            'state' => $address->state,
            'zip_code' => $address->zip_code,
            'county' => $address->county,
            'created_by' => Auth::id(),
        ]);

        // Auto-select the new property
        $this->property_id = $property->id;

        session()->flash('success', 'Property created from participant\'s household address');
    }

    // Quick Create Methods

    public function showQuickParticipantModal()
    {
        $this->resetQuickParticipantForm();
        $this->showQuickParticipantForm = true;
    }

    public function saveQuickParticipant()
    {
        $validated = $this->validate([
            'quick_participant_first_name' => 'required|string|max:255',
            'quick_participant_last_name' => 'required|string|max:255',
            'quick_participant_phone' => 'nullable|string',
            'quick_participant_email' => 'nullable|email',
            'quick_participant_dob' => 'nullable|date',
        ]);

        // Auto-create household first
        $household = Household::create([
            'household_type' => 'Single Person',
            'household_size' => 1,
            'housing_status' => 'Unknown/not reported',
        ]);

        // Create participant as head of household
        $participant = Participant::create([
            'household_id' => $household->id,
            'first_name' => $this->quick_participant_first_name,
            'last_name' => $this->quick_participant_last_name,
            'telephone' => $this->quick_participant_phone !== '' ? $this->quick_participant_phone : null,
            'email' => $this->quick_participant_email !== '' ? $this->quick_participant_email : null,
            'date_of_birth' => $this->quick_participant_dob !== '' ? $this->quick_participant_dob : null,
            'relationship_to_head' => 'self',
            'is_head_of_household' => true,
            'created_by' => Auth::id(),
        ]);

        $this->participant_id = $participant->id;
        $this->showQuickParticipantForm = false;

        session()->flash('success', 'Participant and household created: ' .
                         $participant->first_name . ' ' . $participant->last_name .
                         ' (Household: ' . $household->household_code . ')');
    }

    public function showQuickPropertyModal()
    {
        if (!$this->participant_id) {
            session()->flash('error', 'Please select or create a participant first.');
            return;
        }
        $this->resetQuickPropertyForm();
        $this->showQuickPropertyForm = true;
    }

    public function saveQuickProperty()
    {
        $validated = $this->validate([
            'quick_property_address' => 'required|string',
            'quick_property_city' => 'required|string',
            'quick_property_state' => 'required|string|size:2',
            'quick_property_zip' => 'required|string',
        ]);

        $participant = Participant::findOrFail($this->participant_id);

        $property = Property::create([
            'household_id' => $participant->household_id,
            'participant_id' => $this->participant_id,
            'address_line_1' => $this->quick_property_address,
            'city' => $this->quick_property_city,
            'state' => $this->quick_property_state,
            'zip_code' => $this->quick_property_zip,
            'created_by' => Auth::id(),
        ]);

        $this->property_id = $property->id;
        $this->showQuickPropertyForm = false;
        session()->flash('success', 'Property created: ' . $property->address_line_1);
    }

    protected function resetQuickParticipantForm()
    {
        $this->quick_participant_first_name = '';
        $this->quick_participant_last_name = '';
        $this->quick_participant_phone = '';
        $this->quick_participant_email = '';
        $this->quick_participant_dob = '';
    }

    protected function resetQuickPropertyForm()
    {
        $this->quick_property_address = '';
        $this->quick_property_city = '';
        $this->quick_property_state = '';
        $this->quick_property_zip = '';
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
        $this->filterService = ''; // Reset service when program changes
    }

    public function updatedFilterService()
    {
        $this->resetPage();
    }

    public function updatedPropertyId()
    {
        if ($this->property_id) {
            $property = Property::find($this->property_id);
            if ($property) {
                $this->participant_id = $property->participant_id;
                // household_id will be auto-derived on save
            }
        }
    }

    public function updatedProgramId()
    {
        // Reset service when program changes in the form
        $this->service_id = '';
    }

    public function showCreateJob()
    {
        $this->resetForm();
        $this->showCreateForm = true;
    }

    public function showEditJob($id)
    {
        $job = HousingJob::findOrFail($id);
        $this->editingJobId = $id;
        $this->participant_id = $job->participant_id;
        $this->household_id = $job->household_id;
        $this->property_id = $job->property_id;
        $this->contractor_id = $job->contractor_id;
        $this->program_id = $job->program_id;
        $this->service_id = $job->service_id;
        $this->job_title = $job->job_title;
        $this->job_description = $job->job_description;
        $this->status = $job->status;
        $this->request_date = $job->request_date?->format('Y-m-d');
        $this->start_date = $job->start_date?->format('Y-m-d');
        $this->target_completion_date = $job->target_completion_date?->format('Y-m-d');
        $this->estimated_cost = $job->estimated_cost;
        $this->priority = $job->priority;
        $this->showEditForm = true;
    }

    public function saveJob()
    {
        // If creating from template, use special method
        if ($this->selectedTemplateId) {
            return $this->saveJobFromTemplate();
        }

        $validated = $this->validate([
            'participant_id' => 'required|exists:participants,id',
            'property_id' => 'required|exists:properties,id',
            'program_id' => 'required|exists:programs,id',
            'job_title' => 'required|string|max:255',
            'status' => 'required|in:pending,approved,in_progress,on_hold,completed,cancelled',
            'request_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,emergency',
        ]);

        // Get participant to derive household and verify they are head of household
        $participant = Participant::findOrFail($this->participant_id);

        // Verify participant is head of household
        if (!$participant->is_head_of_household) {
            session()->flash('error', 'Selected participant must be head of household');
            return;
        }

        // Convert empty strings to null for nullable foreign keys
        $jobData = [
            'participant_id' => $this->participant_id,
            'household_id' => $participant->household_id, // AUTO-DERIVED from participant
            'property_id' => $this->property_id,
            'contractor_id' => $this->contractor_id !== '' ? $this->contractor_id : null,
            'program_id' => $this->program_id,
            'service_id' => $this->service_id !== '' ? $this->service_id : null,
            'job_title' => $this->job_title,
            'job_description' => $this->job_description,
            'status' => $this->status,
            'request_date' => $this->request_date,
            'start_date' => $this->start_date !== '' ? $this->start_date : null,
            'target_completion_date' => $this->target_completion_date !== '' ? $this->target_completion_date : null,
            'estimated_cost' => $this->estimated_cost !== '' ? $this->estimated_cost : null,
            'priority' => $this->priority,
        ];

        if ($this->editingJobId) {
            $job = HousingJob::findOrFail($this->editingJobId);
            $job->update(array_merge($jobData, ['updated_by' => Auth::id()]));
            session()->flash('success', 'Project updated successfully');
        } else {
            $job = HousingJob::create(array_merge($jobData, ['created_by' => Auth::id()]));
            session()->flash('success', 'Project created successfully! ID: ' . $job->id);
        }

        $this->showCreateForm = false;
        $this->showEditForm = false;
        $this->resetForm();
    }

    public function deleteJob($id)
    {
        $job = HousingJob::findOrFail($id);
        $job->delete();
        session()->flash('success', 'Project deleted successfully');
    }

    public function showTemplateLibrary()
    {
        $this->showTemplateModal = true;
    }

    public function createFromTemplate($templateId)
    {
        $this->selectedTemplateId = $templateId;
        $this->showTemplateModal = false;
        $this->showCreateForm = true;

        // Template will be applied when form is saved
        session()->flash('info', 'Fill in project details. Milestones will be copied from template.');
    }

    public function saveJobFromTemplate()
    {
        $validated = $this->validate([
            'participant_id' => 'required|exists:participants,id',
            'property_id' => 'required|exists:properties,id',
            'program_id' => 'required|exists:programs,id',
            'job_title' => 'required|string|max:255',
            'status' => 'required|in:pending,approved,in_progress,on_hold,completed,cancelled',
            'request_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,emergency',
        ]);

        // Get participant to derive household and verify they are head of household
        $participant = Participant::findOrFail($this->participant_id);

        // Verify participant is head of household
        if (!$participant->is_head_of_household) {
            session()->flash('error', 'Selected participant must be head of household');
            return;
        }

        $template = HousingJob::findOrFail($this->selectedTemplateId);

        // Convert empty strings to null for nullable fields
        $job = HousingJob::createFromTemplate($template, [
            'participant_id' => $this->participant_id,
            'household_id' => $participant->household_id, // AUTO-DERIVED from participant
            'property_id' => $this->property_id,
            'contractor_id' => $this->contractor_id !== '' ? $this->contractor_id : null,
            'program_id' => $this->program_id,
            'service_id' => $this->service_id !== '' ? $this->service_id : null,
            'job_title' => $this->job_title,
            'job_description' => $this->job_description,
            'status' => $this->status,
            'request_date' => $this->request_date,
            'start_date' => $this->start_date !== '' ? $this->start_date : null,
            'target_completion_date' => $this->target_completion_date !== '' ? $this->target_completion_date : null,
            'estimated_cost' => $this->estimated_cost !== '' ? $this->estimated_cost : null,
            'priority' => $this->priority,
        ]);

        session()->flash('success', 'Project created from template with ' . $job->milestones()->count() . ' milestones!');
        $this->showCreateForm = false;
        $this->selectedTemplateId = null;
        $this->resetForm();

        return redirect()->route('project.management', $job->id);
    }

    public function toggleSelection($jobId)
    {
        if (in_array($jobId, $this->selectedJobs)) {
            $this->selectedJobs = array_diff($this->selectedJobs, [$jobId]);
        } else {
            $this->selectedJobs[] = $jobId;
        }
    }

    public function bulkUpdateStatus($status)
    {
        if (empty($this->selectedJobs)) {
            session()->flash('error', 'No projects selected');
            return;
        }

        HousingJob::whereIn('id', $this->selectedJobs)->update([
            'status' => $status,
            'updated_by' => Auth::id(),
        ]);

        session()->flash('success', count($this->selectedJobs) . ' projects updated');
        $this->selectedJobs = [];
    }

    public function bulkDelete()
    {
        if (empty($this->selectedJobs)) {
            session()->flash('error', 'No projects selected');
            return;
        }

        HousingJob::whereIn('id', $this->selectedJobs)->delete();

        session()->flash('success', count($this->selectedJobs) . ' projects deleted');
        $this->selectedJobs = [];
    }

    protected function resetForm()
    {
        $this->editingJobId = null;
        $this->participant_id = '';
        $this->property_id = '';
        $this->contractor_id = '';
        $this->program_id = '';
        $this->service_id = '';
        $this->job_title = '';
        $this->job_description = '';
        $this->status = 'pending';
        $this->request_date = date('Y-m-d');
        $this->start_date = '';
        $this->target_completion_date = '';
        $this->estimated_cost = '';
        $this->priority = 'medium';
    }

    public function render()
    {
        // Get templates separately
        $templates = HousingJob::templates()
            ->with(['milestones', 'budgets'])
            ->orderBy('template_name')
            ->get();

        $projects = HousingJob::query()
            ->activeJobs() // Exclude templates by default
            ->with(['program', 'service', 'participant', 'property', 'contractor', 'milestones.tasks', 'budgets'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('job_title', 'like', '%' . $this->search . '%')
                      ->orWhere('job_number', 'like', '%' . $this->search . '%')
                      ->orWhereHas('participant', function ($pq) {
                          $pq->where('first_name', 'like', '%' . $this->search . '%')
                             ->orWhere('last_name', 'like', '%' . $this->search . '%');
                      })
                      ->orWhereHas('property', function ($propq) {
                          $propq->where('address_line_1', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->filterProgram, fn($q) => $q->where('program_id', $this->filterProgram))
            ->when($this->filterService, fn($q) => $q->where('service_id', $this->filterService))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterPriority, fn($q) => $q->where('priority', $this->filterPriority))
            ->when($this->filterPmEnabled === 'pm_only', fn($q) => $q->whereHas('milestones'))
            ->when($this->filterPmEnabled === 'non_pm', fn($q) => $q->whereDoesntHave('milestones'))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $programs = Program::orderBy('name')->get();

        // For filter dropdowns (objects)
        $servicesForFilter = $this->filterProgram
            ? Service::where('program_id', $this->filterProgram)->orderBy('name')->get()
            : Service::orderBy('name')->get();

        // For searchable selects in create form (arrays)
        $propertiesSearchable = Property::orderBy('address_line_1')->get()->map(function($p) {
            return [
                'id' => $p->id,
                'display' => $p->address_line_1 . ', ' . $p->city,
                'secondary' => $p->state . ' ' . $p->zip_code,
            ];
        })->toArray();

        // For participants - only show heads of household
        $participantsSearchable = Participant::where('is_head_of_household', true)
            ->orderBy('last_name')
            ->get()
            ->map(function($p) {
                $secondaryInfo = [];
                if ($p->household && $p->household->household_code) {
                    $secondaryInfo[] = 'HH: ' . $p->household->household_code;
                }
                if ($p->email) {
                    $secondaryInfo[] = $p->email;
                } elseif ($p->telephone) {
                    $secondaryInfo[] = $p->telephone;
                }

                return [
                    'id' => $p->id,
                    'display' => $p->first_name . ' ' . $p->last_name,
                    'secondary' => implode(' - ', $secondaryInfo),
                ];
            })->toArray();

        $contractorsSearchable = Contractor::orderBy('company_name')->get()->map(function($c) {
            return [
                'id' => $c->id,
                'display' => $c->company_name,
                'secondary' => $c->phone ?: $c->email,
            ];
        })->toArray();

        // For create form - filter by the selected program in the form
        $servicesSearchable = Service::when($this->program_id, function($q) {
            $q->where('program_id', $this->program_id);
        })->orderBy('name')->get()->map(function($s) {
            return [
                'id' => $s->id,
                'display' => $s->name,
                'secondary' => $s->service_type,
            ];
        })->toArray();

        return view('livewire.projects-management', [
            'projects' => $projects,
            'templates' => $templates,
            'programs' => $programs,
            'servicesForFilter' => $servicesForFilter,
            'services' => $servicesSearchable,
            'properties' => $propertiesSearchable,
            'participants' => $participantsSearchable,
            'contractors' => $contractorsSearchable,
        ]);
    }
}
