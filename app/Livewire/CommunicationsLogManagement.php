<?php

namespace App\Livewire;

use App\Models\CommunicationLog;
use App\Models\Participant;
use App\Models\Household;
use App\Models\Property;
use App\Models\Contractor;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CommunicationsLogManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    // Search and Filters
    public $search = '';
    public $typeFilter = '';
    public $categoryFilter = '';
    public $priorityFilter = '';
    public $directionFilter = '';
    public $followUpFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    // Form state
    public $showForm = false;
    public $editingId = null;

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

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
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

    public function showCreateForm()
    {
        $this->authorizeAction('communications_log.create');
        $this->resetForm();
        $this->communication_date = now()->format('Y-m-d\TH:i');
        $this->showForm = true;
    }

    public function showEditForm($id)
    {
        $this->authorizeAction('communications_log.update');
        $log = CommunicationLog::findOrFail($id);

        $this->editingId = $id;
        $this->participant_id = $log->participant_id ?? '';
        $this->household_id = $log->household_id ?? '';
        $this->property_id = $log->property_id ?? '';
        $this->contractor_id = $log->contractor_id ?? '';
        $this->communication_date = $log->communication_date?->format('Y-m-d\TH:i');
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

        $this->showForm = true;
    }

    public function save()
    {
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
            $this->authorizeAction('communications_log.update');
            $log = CommunicationLog::findOrFail($this->editingId);
            $data['updated_by'] = Auth::id();
            $log->update($data);
            session()->flash('success', 'Communication log updated successfully.');
        } else {
            $this->authorizeAction('communications_log.create');
            $data['created_by'] = Auth::id();
            CommunicationLog::create($data);
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
        $this->authorizeAction('communications_log.update');
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
    }

    public function render()
    {
        $query = CommunicationLog::with(['participant', 'household', 'property', 'contractor', 'createdBy'])
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

        if ($this->dateFrom) {
            $query->where('communication_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('communication_date', '<=', $this->dateTo . ' 23:59:59');
        }

        $logs = $query->paginate(20);

        // Get counts for quick stats
        $pendingFollowUps = CommunicationLog::pendingFollowUp()->count();
        $overdueFollowUps = CommunicationLog::overdueFollowUp()->count();
        $todayCount = CommunicationLog::whereDate('communication_date', today())->count();

        // Get options for linking
        $participants = Participant::select('id', 'first_name', 'last_name')->orderBy('last_name')->get();
        $households = Household::select('id', 'household_code')->orderBy('household_code')->get();
        $properties = Property::select('id', 'address_line_1', 'city')->orderBy('address_line_1')->get();
        $contractors = Contractor::select('id', 'company_name')->orderBy('company_name')->get();

        return view('livewire.communications-log-management', [
            'logs' => $logs,
            'pendingFollowUps' => $pendingFollowUps,
            'overdueFollowUps' => $overdueFollowUps,
            'todayCount' => $todayCount,
            'participants' => $participants,
            'households' => $households,
            'properties' => $properties,
            'contractors' => $contractors,
        ]);
    }
}
