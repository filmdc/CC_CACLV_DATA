<?php

namespace App\Livewire;

use App\Models\CommunicationLog;
use App\Models\CommunicationLogUpdate;
use App\Models\Contractor;
use App\Models\Household;
use App\Models\HousingJob;
use App\Models\Participant;
use App\Models\Property;
use App\Models\Service;
use App\Models\Tag;
use App\Traits\WithPermissionChecks;
use App\Traits\WithProgramAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CommunicationsLogManagement extends Component
{
    use WithPagination, WithPermissionChecks, WithProgramAccess;

    // Search and Filters
    public $search = '';

    public $typeFilter = '';

    public $categoryFilter = '';

    public $priorityFilter = '';

    public $directionFilter = '';

    public $followUpFilter = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $serviceFilter = '';

    public $tagFilter = '';

    // Form state
    public $showForm = false;

    public $editingId = null;

    // Detail view state
    public $viewingId = null;

    public $showDetailModal = false;

    // Convert to project state
    public $showConvertModal = false;

    public $convertingLogId = null;

    public $convertJobTitle = '';

    public $convertJobDescription = '';

    public $convertProgramId = '';

    public $convertServiceId = '';

    // Update/follow-up entry state
    public $newUpdateContent = '';

    public $newUpdateType = 'note';

    // Entity search fields
    public $participantSearch = '';

    public $householdSearch = '';

    public $propertySearch = '';

    public $contractorSearch = '';

    // Form fields
    public $participant_id = '';

    public $household_id = '';

    public $property_id = '';

    public $contractor_id = '';

    public $communication_date = '';

    public $communication_type = 'phone_call';

    public $direction = 'outgoing';

    public $contact_name = '';

    public $contact_phone = '';

    public $contact_email = '';

    public $contact_organization = '';

    public $contact_role = '';

    public $subject = '';

    public $summary = '';

    public $notes = '';

    public $outcome = '';

    public $category = 'general';

    public $priority = 'normal';

    public $follow_up_date = '';

    public $follow_up_action = '';

    // Service multi-select
    public $selectedServices = [];

    // Tag management
    public $selectedTagIds = [];

    public $newTagName = '';

    public $tagSearch = '';

    // Quick create states
    public $showQuickCreateParticipant = false;

    public $showQuickCreateProperty = false;

    public $showQuickCreateContractor = false;

    public $showPropertyAddressPrompt = false;

    public $newlyCreatedParticipantName = '';

    // Quick create participant fields
    public $newParticipantFirstName = '';

    public $newParticipantLastName = '';

    public $newParticipantPhone = '';

    public $newParticipantEmail = '';

    // Quick create property fields
    public $newPropertyAddress = '';

    public $newPropertyCity = '';

    public $newPropertyState = 'NV';

    public $newPropertyZip = '';

    // Quick create contractor fields
    public $newContractorCompanyName = '';

    public $newContractorContactFirstName = '';

    public $newContractorContactLastName = '';

    public $newContractorPhone = '';

    public $newContractorEmail = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
        'serviceFilter' => ['except' => ''],
        'tagFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $this->authorizeAction('communications_log.view');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedTypeFilter()
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter()
    {
        $this->resetPage();
    }

    public function updatedServiceFilter()
    {
        $this->resetPage();
    }

    public function updatedTagFilter()
    {
        $this->resetPage();
    }

    public function updatedPropertyId($value)
    {
        if ($value) {
            $property = Property::find($value);
            if ($property) {
                $this->subject = $property->address_line_1;
                if ($property->city) {
                    $this->subject .= ', '.$property->city;
                }
            }
        }
    }

    public function updatedParticipantId($value)
    {
        if ($value) {
            $participant = Participant::find($value);
            if ($participant && $participant->household_id) {
                $this->household_id = $participant->household_id;
            }
        } else {
            $this->household_id = '';
        }
    }

    // Quick create methods
    public function toggleQuickCreateParticipant()
    {
        $this->showQuickCreateParticipant = ! $this->showQuickCreateParticipant;
        $this->resetQuickCreateParticipantFields();
    }

    public function toggleQuickCreateProperty()
    {
        $this->showQuickCreateProperty = ! $this->showQuickCreateProperty;
        $this->resetQuickCreatePropertyFields();
    }

    public function toggleQuickCreateContractor()
    {
        $this->showQuickCreateContractor = ! $this->showQuickCreateContractor;
        $this->resetQuickCreateContractorFields();
    }

    public function createQuickParticipant()
    {
        $this->validate([
            'newParticipantFirstName' => 'required|string|max:255',
            'newParticipantLastName' => 'required|string|max:255',
            'newParticipantPhone' => 'nullable|string|max:50',
            'newParticipantEmail' => 'nullable|email|max:255',
        ]);

        // Create a new household for the participant (matching ProjectsManagement approach)
        $household = Household::create([
            'household_type' => 'Single Person',
            'household_size' => 1,
            'housing_status' => 'Unknown/not reported',
        ]);

        // Create participant as head of household
        $participant = Participant::create([
            'household_id' => $household->id,
            'first_name' => $this->newParticipantFirstName,
            'last_name' => $this->newParticipantLastName,
            'telephone' => $this->newParticipantPhone ?: null,
            'email' => $this->newParticipantEmail ?: null,
            'relationship_to_head' => 'self',
            'is_head_of_household' => true,
        ]);

        $this->participant_id = $participant->id;
        $this->household_id = $household->id;
        $this->contact_name = $participant->full_name;
        if ($this->newParticipantPhone) {
            $this->contact_phone = $this->newParticipantPhone;
        }
        if ($this->newParticipantEmail) {
            $this->contact_email = $this->newParticipantEmail;
        }

        $this->showQuickCreateParticipant = false;
        $this->resetQuickCreateParticipantFields();

        // Show property address prompt for the newly created participant
        $this->newlyCreatedParticipantName = $participant->first_name.' '.$participant->last_name;
        $this->showPropertyAddressPrompt = true;
        $this->resetQuickCreatePropertyFields();

        session()->flash('success', 'Participant created: '.$participant->first_name.' '.$participant->last_name.' (Household: '.$household->household_code.')');
    }

    public function savePropertyFromPrompt()
    {
        $this->validate([
            'newPropertyAddress' => 'required|string|max:255',
            'newPropertyCity' => 'required|string|max:100',
            'newPropertyState' => 'required|string|max:2',
            'newPropertyZip' => 'required|string|max:10',
        ]);

        $property = Property::create([
            'address_line_1' => $this->newPropertyAddress,
            'city' => $this->newPropertyCity,
            'state' => strtoupper($this->newPropertyState),
            'zip_code' => $this->newPropertyZip,
            'participant_id' => $this->participant_id,
            'household_id' => $this->household_id,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        // Also create household address record
        $household = Household::find($this->household_id);
        if ($household) {
            $household->addresses()->create([
                'address' => $this->newPropertyAddress,
                'city' => $this->newPropertyCity,
                'state' => strtoupper($this->newPropertyState),
                'zip_code' => $this->newPropertyZip,
                'is_current' => true,
            ]);
        }

        $this->property_id = $property->id;
        $this->subject = $property->address_line_1.', '.$property->city;

        $this->showPropertyAddressPrompt = false;
        $this->resetQuickCreatePropertyFields();

        session()->flash('success', 'Property created: '.$property->address_line_1.', '.$property->city);
    }

    public function skipPropertyPrompt()
    {
        $this->showPropertyAddressPrompt = false;
        $this->resetQuickCreatePropertyFields();
    }

    public function createQuickProperty()
    {
        $this->validate([
            'newPropertyAddress' => 'required|string|max:255',
            'newPropertyCity' => 'required|string|max:100',
            'newPropertyState' => 'required|string|max:2',
            'newPropertyZip' => 'required|string|max:10',
        ]);

        $property = Property::create([
            'address_line_1' => $this->newPropertyAddress,
            'city' => $this->newPropertyCity,
            'state' => $this->newPropertyState,
            'zip_code' => $this->newPropertyZip,
            'participant_id' => $this->participant_id ?: null,
            'household_id' => $this->household_id ?: null,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        $this->property_id = $property->id;
        $this->subject = $property->address_line_1.', '.$property->city;

        $this->showQuickCreateProperty = false;
        $this->resetQuickCreatePropertyFields();

        session()->flash('success', 'Property created successfully.');
    }

    public function createQuickContractor()
    {
        $this->validate([
            'newContractorCompanyName' => 'required|string|max:255',
            'newContractorContactFirstName' => 'nullable|string|max:255',
            'newContractorContactLastName' => 'nullable|string|max:255',
            'newContractorPhone' => 'nullable|string|max:50',
            'newContractorEmail' => 'nullable|email|max:255',
        ]);

        $contractor = Contractor::create([
            'company_name' => $this->newContractorCompanyName,
            'contact_first_name' => $this->newContractorContactFirstName ?: null,
            'contact_last_name' => $this->newContractorContactLastName ?: null,
            'phone' => $this->newContractorPhone ?: null,
            'email' => $this->newContractorEmail ?: null,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]);

        $this->contractor_id = $contractor->id;
        $this->contact_name = $contractor->company_name;
        $this->contact_organization = $contractor->company_name;
        if ($this->newContractorPhone) {
            $this->contact_phone = $this->newContractorPhone;
        }
        if ($this->newContractorEmail) {
            $this->contact_email = $this->newContractorEmail;
        }

        $this->showQuickCreateContractor = false;
        $this->resetQuickCreateContractorFields();

        session()->flash('success', 'Contractor created successfully.');
    }

    protected function resetQuickCreateParticipantFields()
    {
        $this->newParticipantFirstName = '';
        $this->newParticipantLastName = '';
        $this->newParticipantPhone = '';
        $this->newParticipantEmail = '';
    }

    protected function resetQuickCreatePropertyFields()
    {
        $this->newPropertyAddress = '';
        $this->newPropertyCity = '';
        $this->newPropertyState = 'NV';
        $this->newPropertyZip = '';
    }

    protected function resetQuickCreateContractorFields()
    {
        $this->newContractorCompanyName = '';
        $this->newContractorContactFirstName = '';
        $this->newContractorContactLastName = '';
        $this->newContractorPhone = '';
        $this->newContractorEmail = '';
    }

    public function showCreateForm()
    {
        $this->authorizeAction('communications_log.create');
        $this->resetForm();
        $this->communication_date = now()->format('Y-m-d');
        $this->showForm = true;
    }

    public function showEditForm($id)
    {
        $this->authorizeAction('communications_log.edit');
        $log = CommunicationLog::with(['services', 'tags'])->findOrFail($id);

        $this->editingId = $id;
        $this->participant_id = $log->participant_id ?? '';
        $this->household_id = $log->household_id ?? '';
        $this->property_id = $log->property_id ?? '';
        $this->contractor_id = $log->contractor_id ?? '';
        $this->communication_date = $log->communication_date?->format('Y-m-d');
        $this->communication_type = $log->communication_type;
        $this->direction = $log->direction;
        $this->contact_name = $log->contact_name;
        $this->contact_phone = $log->contact_phone ?? '';
        $this->contact_email = $log->contact_email ?? '';
        $this->contact_organization = $log->contact_organization ?? '';
        $this->contact_role = $log->contact_role ?? '';
        $this->subject = $log->subject;
        $this->summary = $log->summary ?? '';
        $this->notes = $log->notes ?? '';
        $this->outcome = $log->outcome ?? '';
        $this->category = $log->category;
        $this->priority = $log->priority;
        $this->follow_up_date = $log->follow_up_date?->format('Y-m-d') ?? '';
        $this->follow_up_action = $log->follow_up_action ?? '';
        $this->selectedServices = $log->services->pluck('id')->toArray();
        $this->selectedTagIds = $log->tags->pluck('id')->toArray();

        $this->showForm = true;
    }

    public function viewDetail($id)
    {
        $this->viewingId = $id;
        $this->showDetailModal = true;
        $this->newUpdateContent = '';
        $this->newUpdateType = 'note';
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->viewingId = null;
    }

    public function addUpdate()
    {
        $this->validate([
            'newUpdateContent' => 'required|string|min:1',
            'newUpdateType' => 'required|in:note,follow_up,status_change,outcome,other',
        ]);

        $log = CommunicationLog::findOrFail($this->viewingId);
        $log->addUpdate($this->newUpdateContent, $this->newUpdateType);

        $this->newUpdateContent = '';
        $this->newUpdateType = 'note';

        session()->flash('success', 'Update added successfully.');
    }

    public function deleteUpdate($updateId)
    {
        $this->authorizeAction('communications_log.edit');
        CommunicationLogUpdate::findOrFail($updateId)->delete();
        session()->flash('success', 'Update deleted.');
    }

    // Tag management methods
    public function addTag()
    {
        if (empty(trim($this->newTagName))) {
            return;
        }

        $tag = Tag::findOrCreateByName(trim($this->newTagName));

        if (! in_array($tag->id, $this->selectedTagIds)) {
            $this->selectedTagIds[] = $tag->id;
        }

        $this->newTagName = '';
    }

    public function removeTag($tagId)
    {
        $this->selectedTagIds = array_filter($this->selectedTagIds, fn ($id) => $id != $tagId);
    }

    public function selectTag($tagId)
    {
        if (! in_array($tagId, $this->selectedTagIds)) {
            $this->selectedTagIds[] = $tagId;
        }
        $this->tagSearch = '';
    }

    // Convert to project methods
    public function openConvertModal($logId)
    {
        $this->authorizeAction('projects.create');
        $log = CommunicationLog::findOrFail($logId);

        $this->convertingLogId = $logId;
        $this->convertJobTitle = $log->subject;
        $this->convertJobDescription = $log->summary ?? $log->notes ?? '';
        $this->convertProgramId = '';
        $this->convertServiceId = '';
        $this->showConvertModal = true;
    }

    public function closeConvertModal()
    {
        $this->showConvertModal = false;
        $this->convertingLogId = null;
        $this->convertJobTitle = '';
        $this->convertJobDescription = '';
        $this->convertProgramId = '';
        $this->convertServiceId = '';
    }

    public function convertToProject()
    {
        $this->authorizeAction('projects.create');

        $this->validate([
            'convertJobTitle' => 'required|string|max:255',
            'convertJobDescription' => 'nullable|string',
            'convertProgramId' => 'required|exists:programs,id',
        ]);

        $log = CommunicationLog::findOrFail($this->convertingLogId);

        DB::beginTransaction();
        try {
            $job = HousingJob::create([
                'job_title' => $this->convertJobTitle,
                'job_description' => $this->convertJobDescription,
                'program_id' => $this->convertProgramId,
                'service_id' => $this->convertServiceId ?: null,
                'participant_id' => $log->participant_id,
                'household_id' => $log->household_id,
                'property_id' => $log->property_id,
                'contractor_id' => $log->contractor_id,
                'status' => 'pending',
                'priority' => $log->priority === 'urgent' ? 'emergency' : ($log->priority === 'high' ? 'high' : 'medium'),
                'request_date' => now(),
                'created_by' => Auth::id(),
            ]);

            $log->convertToProject($job, Auth::id());

            DB::commit();

            $this->closeConvertModal();
            session()->flash('success', "Communication converted to project: {$job->job_number}");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to convert to project: '.$e->getMessage());
        }
    }

    public function save()
    {
        if (empty($this->subject) && $this->property_id) {
            $property = Property::find($this->property_id);
            if ($property) {
                $this->subject = $property->address_line_1;
                if ($property->city) {
                    $this->subject .= ', '.$property->city;
                }
            }
        }

        if (empty($this->subject)) {
            $this->subject = 'Communication on '.now()->format('M d, Y');
        }

        $this->validate([
            'communication_date' => 'required|date',
            'communication_type' => 'required|in:phone_call,email,in_person,site_visit,video_call,text,mail,voicemail,fax,other',
            'direction' => 'required|in:incoming,outgoing',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'contact_organization' => 'nullable|string|max:255',
            'contact_role' => 'nullable|in:homeowner,contractor,inspector,vendor,agency,applicant,family_member,other',
            'subject' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'notes' => 'nullable|string',
            'outcome' => 'nullable|string',
            'category' => 'required|in:general,intake,follow_up,complaint,inquiry,referral,outreach,scheduling,documentation,other',
            'priority' => 'required|in:low,normal,high,urgent',
            'follow_up_date' => 'nullable|date',
            'follow_up_action' => 'nullable|string',
            'participant_id' => 'nullable|exists:participants,id',
            'household_id' => 'nullable|exists:households,id',
            'property_id' => 'nullable|exists:properties,id',
            'contractor_id' => 'nullable|exists:contractors,id',
            'selectedServices' => 'nullable|array',
            'selectedServices.*' => 'exists:services,id',
        ]);

        $data = [
            'participant_id' => $this->participant_id !== '' ? $this->participant_id : null,
            'household_id' => $this->household_id !== '' ? $this->household_id : null,
            'property_id' => $this->property_id !== '' ? $this->property_id : null,
            'contractor_id' => $this->contractor_id !== '' ? $this->contractor_id : null,
            'communication_date' => $this->communication_date,
            'communication_type' => $this->communication_type,
            'direction' => $this->direction,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone !== '' ? $this->contact_phone : null,
            'contact_email' => $this->contact_email !== '' ? $this->contact_email : null,
            'contact_organization' => $this->contact_organization !== '' ? $this->contact_organization : null,
            'contact_role' => $this->contact_role !== '' ? $this->contact_role : null,
            'subject' => $this->subject,
            'summary' => $this->summary !== '' ? $this->summary : null,
            'notes' => $this->notes !== '' ? $this->notes : null,
            'outcome' => $this->outcome !== '' ? $this->outcome : null,
            'category' => $this->category,
            'priority' => $this->priority,
            'follow_up_date' => $this->follow_up_date !== '' ? $this->follow_up_date : null,
            'follow_up_action' => $this->follow_up_action !== '' ? $this->follow_up_action : null,
        ];

        if ($this->editingId) {
            $this->authorizeAction('communications_log.edit');
            $log = CommunicationLog::findOrFail($this->editingId);
            $data['updated_by'] = Auth::id();
            $log->update($data);

            // Sync services
            $log->services()->sync($this->selectedServices);

            // Sync tags
            $log->syncTags($this->selectedTagIds);

            session()->flash('success', 'Communication log updated successfully.');
        } else {
            $this->authorizeAction('communications_log.create');
            $data['created_by'] = Auth::id();
            $log = CommunicationLog::create($data);

            // Attach services
            $log->services()->sync($this->selectedServices);

            // Attach tags
            $log->syncTags($this->selectedTagIds);

            session()->flash('success', 'Communication log created successfully.');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        $this->authorizeAction('communications_log.delete');
        CommunicationLog::findOrFail($id)->delete();
        session()->flash('success', 'Communication log deleted successfully.');
    }

    public function markFollowUpComplete($id)
    {
        $this->authorizeAction('communications_log.edit');
        $log = CommunicationLog::findOrFail($id);
        $log->markFollowUpCompleted(Auth::id());
        session()->flash('success', 'Follow-up marked as complete.');
    }

    protected function resetForm()
    {
        $this->editingId = null;
        $this->participant_id = '';
        $this->household_id = '';
        $this->property_id = '';
        $this->contractor_id = '';
        $this->participantSearch = '';
        $this->householdSearch = '';
        $this->propertySearch = '';
        $this->contractorSearch = '';
        $this->communication_date = '';
        $this->communication_type = 'phone_call';
        $this->direction = 'outgoing';
        $this->contact_name = '';
        $this->contact_phone = '';
        $this->contact_email = '';
        $this->contact_organization = '';
        $this->contact_role = '';
        $this->subject = '';
        $this->summary = '';
        $this->notes = '';
        $this->outcome = '';
        $this->category = 'general';
        $this->priority = 'normal';
        $this->follow_up_date = '';
        $this->follow_up_action = '';
        $this->selectedServices = [];
        $this->selectedTagIds = [];
        $this->newTagName = '';
        $this->tagSearch = '';

        // Reset quick create states
        $this->showQuickCreateParticipant = false;
        $this->showQuickCreateProperty = false;
        $this->showQuickCreateContractor = false;
        $this->showPropertyAddressPrompt = false;
        $this->newlyCreatedParticipantName = '';
        $this->resetQuickCreateParticipantFields();
        $this->resetQuickCreatePropertyFields();
        $this->resetQuickCreateContractorFields();
    }

    public function render()
    {
        $query = CommunicationLog::with(['participant', 'household', 'property', 'contractor', 'createdBy', 'services', 'tags', 'convertedToJob'])
            ->orderBy('communication_date', 'desc');

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->typeFilter) {
            $query->byType($this->typeFilter);
        }

        if ($this->categoryFilter) {
            $query->byCategory($this->categoryFilter);
        }

        if ($this->priorityFilter) {
            $query->byPriority($this->priorityFilter);
        }

        if ($this->directionFilter) {
            $query->byDirection($this->directionFilter);
        }

        if ($this->followUpFilter === 'pending') {
            $query->pendingFollowUp();
        } elseif ($this->followUpFilter === 'overdue') {
            $query->overdueFollowUp();
        }

        if ($this->serviceFilter) {
            $query->forService($this->serviceFilter);
        }

        if ($this->tagFilter) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $this->tagFilter));
        }

        if ($this->dateFrom) {
            $query->where('communication_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('communication_date', '<=', $this->dateTo.' 23:59:59');
        }

        $logs = $query->paginate(20);

        // Get counts for quick stats
        $pendingFollowUps = CommunicationLog::pendingFollowUp()->count();
        $overdueFollowUps = CommunicationLog::overdueFollowUp()->count();
        $todayCount = CommunicationLog::whereDate('communication_date', today())->count();

        // Get options for linking with search filtering
        $participants = Participant::select('id', 'first_name', 'last_name')
            ->when($this->participantSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%'.$this->participantSearch.'%')
                        ->orWhere('last_name', 'like', '%'.$this->participantSearch.'%')
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%'.$this->participantSearch.'%'])
                        ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%'.$this->participantSearch.'%']);
                });
            })
            ->when($this->participant_id, function ($query) {
                $query->orWhere('id', $this->participant_id);
            })
            ->orderBy('last_name')
            ->limit(50)
            ->get();

        $households = Household::select('id', 'household_code')
            ->when($this->householdSearch, function ($query) {
                $query->where('household_code', 'like', '%'.$this->householdSearch.'%');
            })
            ->when($this->household_id, function ($query) {
                $query->orWhere('id', $this->household_id);
            })
            ->orderBy('household_code')
            ->limit(50)
            ->get();

        $properties = Property::select('id', 'address_line_1', 'city', 'state', 'zip_code')
            ->when($this->propertySearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('address_line_1', 'like', '%'.$this->propertySearch.'%')
                        ->orWhere('city', 'like', '%'.$this->propertySearch.'%')
                        ->orWhere('zip_code', 'like', '%'.$this->propertySearch.'%');
                });
            })
            ->when($this->property_id, function ($query) {
                $query->orWhere('id', $this->property_id);
            })
            ->orderBy('address_line_1')
            ->limit(50)
            ->get();

        $contractors = Contractor::select('id', 'company_name', 'contact_first_name', 'contact_last_name')
            ->when($this->contractorSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('company_name', 'like', '%'.$this->contractorSearch.'%')
                        ->orWhere('contact_first_name', 'like', '%'.$this->contractorSearch.'%')
                        ->orWhere('contact_last_name', 'like', '%'.$this->contractorSearch.'%')
                        ->orWhereRaw("CONCAT(contact_first_name, ' ', contact_last_name) LIKE ?", ['%'.$this->contractorSearch.'%']);
                });
            })
            ->when($this->contractor_id, function ($query) {
                $query->orWhere('id', $this->contractor_id);
            })
            ->orderBy('company_name')
            ->limit(50)
            ->get();

        // Get programs the user has access to for service selection
        $accessiblePrograms = $this->getAccessiblePrograms()
            ->where('active', true)
            ->with(['services' => fn ($q) => $q->where('active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        // Get all active services from accessible programs for filtering
        $accessibleProgramIds = $accessiblePrograms->pluck('id')->toArray();
        $allServices = Service::where('active', true)
            ->whereIn('program_id', $accessibleProgramIds)
            ->orderBy('name')
            ->get();

        // Get all tags for filtering and selection
        $allTags = Tag::orderBy('name')->get();

        // Get tags matching search for typeahead
        $searchedTags = $this->tagSearch
            ? Tag::search($this->tagSearch)->limit(10)->get()
            : collect();

        // Get viewing log details
        $viewingLog = $this->viewingId
            ? CommunicationLog::with([
                'participant',
                'household',
                'property',
                'contractor',
                'createdBy',
                'updatedBy',
                'services',
                'tags',
                'updates.createdBy',
                'convertedToJob',
                'convertedBy',
            ])->find($this->viewingId)
            : null;

        return view('livewire.communications-log-management', [
            'logs' => $logs,
            'pendingFollowUps' => $pendingFollowUps,
            'overdueFollowUps' => $overdueFollowUps,
            'todayCount' => $todayCount,
            'participants' => $participants,
            'households' => $households,
            'properties' => $properties,
            'contractors' => $contractors,
            'accessiblePrograms' => $accessiblePrograms,
            'allServices' => $allServices,
            'allTags' => $allTags,
            'searchedTags' => $searchedTags,
            'viewingLog' => $viewingLog,
        ]);
    }
}
