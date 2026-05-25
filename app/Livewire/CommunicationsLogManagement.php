<?php

namespace App\Livewire;

use App\Models\CommunicationLog;
use App\Models\CommunicationLogUpdate;
use App\Models\Contractor;
use App\Models\FundCode;
use App\Models\Household;
use App\Models\HousingJob;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Property;
use App\Models\Service;
use App\Models\Tag;
use App\Support\AddressNormalizer;
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

    /**
     * P-03 (Phase 3B): follow-up status is multi-select. Three checkbox
     * options: pending (future or today, not done), overdue (past, not
     * done), completed. The legacy scopePendingFollowUp on the model
     * defines pending more loosely (it counts overdue as pending too)
     * and is left intact for the dashboard count badge — the multi-
     * select filter inlines stricter semantics in render().
     *
     * @var array<int, string>
     */
    public $followUpFilter = [];

    public $dateFrom = '';

    public $dateTo = '';

    public $serviceFilter = '';

    public $tagFilter = '';

    /**
     * P-03 (Phase 3B): fund code filter is multi-select.
     *
     * @var array<int, int|string>
     */
    public $fundCodeFilter = [];

    /**
     * P-03 (Phase 3B): new conversion-status filter, multi-select.
     * Options: 'converted' (linked to a housing job), 'not_converted'
     * (not yet linked).
     *
     * @var array<int, string>
     */
    public $conversionFilter = [];

    /**
     * P-04 (Phase 3B): two view modes for the log list. 'table' is the
     * existing dense one-row-per-entry layout; 'card' surfaces full
     * notes body, fund codes, and contact metadata per entry. URL-only
     * persistence per phase scope — no per-user default.
     */
    public $viewMode = 'table';

    // Form state
    public $showForm = false;

    public $editingId = null;

    // P-01 duplicate-address warning state. Populated as the user fills in
    // contact_address/city; cleared on form reset. Save short-circuits with
    // a warning panel when this is non-empty AND not yet acknowledged.
    public $duplicateMatches = [];

    public $duplicateAcknowledged = false;

    // Detail view state
    public $viewingId = null;

    public $showDetailModal = false;

    // Convert to project state
    public $showConvertModal = false;

    /**
     * v2 (Phase 7): the Requirements Gate on the Convert-to-Project
     * modal. When the intake has any unmet required application
     * requirements, the user MUST tick this checkbox AND provide a
     * written reason to proceed; the values are persisted on the new
     * housing_job's requirement_override_* audit columns.
     */
    public bool $requirementOverride = false;

    public string $requirementOverrideReason = '';

    public $convertingLogId = null;

    public $convertJobTitle = '';

    public $convertJobDescription = '';

    public $convertProgramId = '';

    public $convertServiceId = '';

    // Duplicate detection state for conversion
    public $convertStep = 1; // 1 = basic info, 2 = review duplicates, 3 = confirm

    public $potentialParticipantMatches = [];

    public $potentialPropertyMatches = [];

    public $selectedParticipantAction = 'create_new'; // 'create_new', 'use_existing', or participant ID

    public $selectedPropertyAction = 'create_new'; // 'create_new', 'use_existing', 'skip', or property ID

    public $convertingLog = null; // Holds the log being converted for display

    // Update/follow-up entry state
    public $newUpdateContent = '';

    public $newUpdateType = 'note';

    public $newUpdateDate = '';

    // Fund codes
    public $selectedFundCodeIds = [];

    /**
     * v2 (Phase 4): snapshot of fund-code IDs that were already
     * attached when the form opened. Used by the save-time validator
     * to distinguish "already-checked expired fund" (allowed; preserves
     * historical data) from "newly checking an expired fund" (blocked).
     */
    public array $originallySelectedFundCodeIds = [];

    // Entity search fields
    public $participantSearch = '';

    public $householdSearch = '';

    public $propertySearch = '';

    public $contractorSearch = '';

    // Form fields
    public $housing_job_id = '';

    // v2 state machine: 'intake' (no project) | 'project' (attached).
    // Derived from housing_job_id at form-open; explicit user intent via
    // the anchor radio at the top of the v2 edit form.
    public string $anchor = 'intake';

    // v2: opt-in reveal of fund codes / services in INTAKE state. Per
    // spec §4.1, those sections are hidden by default in INTAKE and
    // accessed via a single "show" link.
    public bool $showFundCodesInIntake = false;

    public bool $showServicesInIntake = false;

    // v2 project picker (⌘P): search-bound by the parent so the picker
    // can render filtered results without owning Livewire state itself.
    public string $projectPickerSearch = '';

    public $participant_id = '';

    public $household_id = '';

    public $property_id = '';

    public $contractor_id = '';

    public $communication_date = '';

    public $communication_type = 'phone_call';

    public $direction = 'outgoing';

    public $create_project_enrollment = false;

    public $contact_first_name = '';

    public $contact_last_name = '';

    public $contact_phone = '';

    public $contact_email = '';

    public $contact_address = '';

    public $contact_city = '';

    public $contact_state = 'PA';

    public $contact_zip = '';

    public $contact_organization = '';

    public $contact_role = '';

    public $subject = '';

    /**
     * v2 (Phase 5): subject is editable; once the user touches it, the
     * override is sticky. Persisted as subject_overridden_at on the
     * model. Loaded on showEditForm; flipped to "now" on save when the
     * subjectTouched transient is set.
     */
    public ?string $subject_overridden_at = null;

    /**
     * Transient. Set true by the updatedSubject() lifecycle hook when
     * the user types in the Subject input. Not persisted directly —
     * triggers $subject_overridden_at = now() on save.
     */
    public bool $subjectTouched = false;

    public $summary = '';

    public $notes = '';

    public $outcome = '';

    public $category = 'general';

    public $priority = 'normal';

    public $follow_up_date = '';

    public $follow_up_action = '';

    // Service multi-select (legacy program services)
    public $selectedServices = [];

    /**
     * Comm Log v2 / Phase 9: housing services (Kaitlyn's custom catalog,
     * decoupled from program services). On toggle, the linked fund codes
     * are merged into $selectedFundCodeIds. On save(), each selected
     * housing service triggers IntakeRequirementInstantiator::forHousingService.
     */
    public array $selectedHousingServiceIds = [];

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

    public $newPropertyState = 'PA';

    public $newPropertyZip = '';

    // Quick create contractor fields
    public $newContractorCompanyName = '';

    public $newContractorContactFirstName = '';

    public $newContractorContactLastName = '';

    public $newContractorPhone = '';

    public $newContractorEmail = '';

    // Address entry fields
    public $showAddressEntry = false;

    public $addressLine = '';

    public $addressCity = '';

    public $addressState = 'PA';

    public $addressZip = '';

    public $createAsProperty = false;

    public $linkAddressToParticipant = false;

    /**
     * P-03 (Phase 3B): every visible filter is bookmarkable. URL state
     * round-trips for the full filter set so a filtered view can be
     * shared as a link.
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
        'directionFilter' => ['except' => ''],
        'serviceFilter' => ['except' => ''],
        'tagFilter' => ['except' => ''],
        'fundCodeFilter' => ['except' => []],
        'followUpFilter' => ['except' => []],
        'conversionFilter' => ['except' => []],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        // P-04: view-mode toggle persists in URL only — no per-user default.
        'viewMode' => ['except' => 'table'],
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

    public function updatedFundCodeFilter()
    {
        $this->resetPage();
    }

    public function updatedDirectionFilter()
    {
        $this->resetPage();
    }

    public function updatedFollowUpFilter()
    {
        $this->resetPage();
    }

    public function updatedConversionFilter()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    /**
     * P-03 (Phase 3B): clear every active filter in one click. Pagination
     * resets too. URL state round-trips alongside via queryString.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->typeFilter = '';
        $this->categoryFilter = '';
        $this->priorityFilter = '';
        $this->directionFilter = '';
        $this->serviceFilter = '';
        $this->tagFilter = '';
        $this->fundCodeFilter = [];
        $this->followUpFilter = [];
        $this->conversionFilter = [];
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function updatedPropertyId($value)
    {
        if ($value) {
            $property = Property::find($value);
            if ($property && ! $this->subjectIsOverridden()) {
                $this->subject = $property->address_line_1;
                if ($property->city) {
                    $this->subject .= ', '.$property->city;
                }
            }
        }
    }

    /**
     * v2 (Phase 5): user-typed Subject locks the auto-derivation off.
     * Set the transient touched flag so save() persists the override.
     */
    public function updatedSubject($value): void
    {
        $this->subjectTouched = true;
    }

    /**
     * True when the user has overridden the subject this session
     * (just typed it) OR previously (subject_overridden_at is set).
     */
    protected function subjectIsOverridden(): bool
    {
        return $this->subjectTouched || $this->subject_overridden_at !== null;
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

    public function updatedContactAddress()
    {
        $this->updateSubjectFromAddress();
        $this->duplicateAcknowledged = false;
        $this->checkAddressDuplicates();
    }

    public function updatedContactCity()
    {
        $this->updateSubjectFromAddress();
        $this->duplicateAcknowledged = false;
        $this->checkAddressDuplicates();
    }

    public function updatedContactState()
    {
        $this->updateSubjectFromAddress();
    }

    public function updatedContactZip()
    {
        $this->updateSubjectFromAddress();
    }

    protected function updateSubjectFromAddress()
    {
        // v2 (Phase 5): never clobber a user-overridden Subject.
        if ($this->subjectIsOverridden()) {
            return;
        }

        if ($this->contact_address && $this->contact_city) {
            $parts = [trim($this->contact_address), trim($this->contact_city)];

            if ($this->contact_state) {
                $parts[] = $this->contact_state;
            }

            if ($this->contact_zip) {
                $parts[] = $this->contact_zip;
            }

            $this->subject = implode(', ', $parts);
        }
    }

    // Quick create methods
    /**
     * Toggle the address entry section visibility
     *
     * @return void
     */
    public function toggleAddressEntry()
    {
        $this->showAddressEntry = ! $this->showAddressEntry;
        if (! $this->showAddressEntry) {
            $this->resetAddressEntryFields();
        }
    }

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
        $this->contact_first_name = $participant->first_name;
        $this->contact_last_name = $participant->last_name;
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
        $this->contact_first_name = $contractor->contact_first_name ?? '';
        $this->contact_last_name = $contractor->contact_last_name ?? '';
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
        $this->newPropertyState = 'PA';
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

    /**
     * Reset all address entry form fields to default values
     *
     * @return void
     */
    protected function resetAddressEntryFields()
    {
        $this->addressLine = '';
        $this->addressCity = '';
        $this->addressState = 'PA';
        $this->addressZip = '';
        $this->createAsProperty = false;
        $this->linkAddressToParticipant = false;
    }

    public function showCreateForm()
    {
        $this->authorizeAction('communications_log.create');
        $this->resetForm();
        $this->communication_date = now()->format('Y-m-d');
        $this->anchor = 'intake'; // new entries default to general intake
        $this->showForm = true;
    }

    /**
     * v2 state machine: derive the form's composition state from the
     * presence of housing_job_id. 'intake' hides fund codes + services
     * by default (per spec §4.1); 'project' scopes them to the project.
     */
    public function getStateProperty(): string
    {
        return ! empty($this->housing_job_id) ? 'project' : 'intake';
    }

    /**
     * Anchor radio handler. 'intake' clears the project and snaps the
     * form to INTAKE; 'project' opens the picker so the user can choose.
     */
    public function setAnchor(string $value): void
    {
        if ($value === 'intake') {
            $this->housing_job_id = '';
            $this->anchor = 'intake';
            $this->showFundCodesInIntake = false;
            $this->showServicesInIntake = false;
        } elseif ($value === 'project') {
            $this->anchor = 'project';
            // If no project yet, open the picker so the user can choose.
            if (empty($this->housing_job_id)) {
                $this->dispatch('open-project-picker');
            }
        }
    }

    /**
     * Called by the x-comm.project-picker when the user picks a row.
     * Passing null is the "General intake" sentinel that detaches.
     */
    public function selectProject(?int $id): void
    {
        $this->housing_job_id = $id ?: '';
        $this->anchor = $id ? 'project' : 'intake';
        if (! $id) {
            $this->showFundCodesInIntake = false;
            $this->showServicesInIntake = false;
        }
    }

    public function revealFundCodes(): void
    {
        $this->showFundCodesInIntake = true;
    }

    public function revealServices(): void
    {
        $this->showServicesInIntake = true;
    }

    /**
     * Comm Log v2 / Phase 9: toggle a housing service on the entry's
     * selection. When ADDED, auto-merges the service's linked fund-code
     * IDs into $selectedFundCodeIds. When REMOVED, leaves fund codes
     * alone (the user already saw them and may have a reason to keep
     * them — explicit detach if undesired).
     */
    public function toggleHousingService(int $id): void
    {
        $current = array_map('intval', $this->selectedHousingServiceIds);
        if (in_array($id, $current, true)) {
            $this->selectedHousingServiceIds = array_values(array_filter($current, fn ($v) => $v !== $id));
            return;
        }

        $this->selectedHousingServiceIds = [...$current, $id];

        // Merge the service's fund codes into the selection so the user
        // sees the "funds for budgeting" auto-checked in real time.
        $svc = \App\Models\HousingService::with('fundCodes')->find($id);
        if ($svc) {
            $svcFundIds = $svc->fundCodes->pluck('id')->map(fn ($v) => (int) $v)->all();
            $existingFundIds = array_map('intval', $this->selectedFundCodeIds);
            $this->selectedFundCodeIds = array_values(array_unique([...$existingFundIds, ...$svcFundIds]));
        }
    }

    public function showEditForm($id)
    {
        $this->authorizeAction('communications_log.edit');
        $log = CommunicationLog::with(['services', 'tags', 'housingServices'])->findOrFail($id);

        $this->editingId = $id;
        $this->housing_job_id = $log->housing_job_id ?? '';
        // v2: derive anchor from the loaded log's project linkage.
        $this->anchor = $log->housing_job_id ? 'project' : 'intake';
        $this->showFundCodesInIntake = false;
        $this->showServicesInIntake = false;
        $this->participant_id = $log->participant_id ?? '';
        $this->household_id = $log->household_id ?? '';
        $this->property_id = $log->property_id ?? '';
        $this->contractor_id = $log->contractor_id ?? '';
        $this->communication_date = $log->communication_date?->format('Y-m-d');
        $this->communication_type = $log->communication_type;
        $this->direction = $log->direction;
        $this->contact_first_name = $log->contact_first_name ?? '';
        $this->contact_last_name = $log->contact_last_name ?? '';
        $this->contact_phone = $log->contact_phone ?? '';
        $this->contact_email = $log->contact_email ?? '';
        $this->contact_address = $log->contact_address ?? '';
        $this->contact_city = $log->contact_city ?? '';
        $this->contact_state = $log->contact_state ?? 'PA';
        $this->contact_zip = $log->contact_zip ?? '';
        $this->contact_organization = $log->contact_organization ?? '';
        $this->contact_role = $log->contact_role ?? '';
        $this->subject = $log->subject;
        $this->subject_overridden_at = $log->subject_overridden_at?->toDateTimeString();
        $this->subjectTouched = false;
        $this->summary = $log->summary ?? '';
        $this->notes = $log->notes ?? '';
        $this->outcome = $log->outcome ?? '';
        $this->category = $log->category;
        $this->priority = $log->priority;
        $this->follow_up_date = $log->follow_up_date?->format('Y-m-d') ?? '';
        $this->follow_up_action = $log->follow_up_action ?? '';
        $this->selectedServices = $log->services->pluck('id')->toArray();
        $this->selectedHousingServiceIds = $log->housingServices->pluck('id')->toArray();
        $this->selectedTagIds = $log->tags->pluck('id')->toArray();
        $this->selectedFundCodeIds = $log->anticipatedFundCodes->pluck('id')->toArray();
        $this->originallySelectedFundCodeIds = $this->selectedFundCodeIds;
        $this->create_project_enrollment = false; // Always false when editing

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

    /**
     * v2 (Phase 8): viewer hotkey "E" — close the viewer and open the
     * Edit form on the same entry.
     */
    public function editFromViewer(): void
    {
        if (! $this->viewingId) {
            return;
        }
        $id = $this->viewingId;
        $this->closeDetailModal();
        $this->showEditForm($id);
    }

    /**
     * v2 (Phase 8): form hotkey ⌘⇧⏎ — save the current entry and
     * immediately open a blank Create form. Persists nothing on the
     * second open beyond what showCreateForm() initializes.
     */
    public function saveAndNew(): void
    {
        $this->save();
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }
        // save() closes the form via $showForm = false; re-open as new.
        $this->showCreateForm();
    }

    public function addUpdate()
    {
        $this->validate([
            'newUpdateContent' => 'required|string|min:1',
            'newUpdateType' => 'required|in:note,follow_up,status_change,outcome,other,action,email',
            'newUpdateDate' => 'nullable|date',
        ]);

        $log = CommunicationLog::findOrFail($this->viewingId);

        // Create update with optional date
        $log->updates()->create([
            'update_content' => $this->newUpdateContent,
            'update_type' => $this->newUpdateType,
            'update_date' => $this->newUpdateDate ?: null,
            'created_by' => Auth::id(),
        ]);

        $this->newUpdateContent = '';
        $this->newUpdateType = 'note';
        $this->newUpdateDate = '';

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
        \Log::info('openConvertModal called', ['logId' => $logId, 'showConvertModal_before' => $this->showConvertModal]);

        $this->authorizeAction('projects.create');
        $log = CommunicationLog::with(['participant', 'property', 'services.program'])->findOrFail($logId);

        $this->convertingLogId = $logId;
        $this->convertingLog = $log;
        $this->convertJobTitle = $log->subject;
        $this->convertJobDescription = $log->summary ?? $log->notes ?? '';
        $this->convertStep = 1;
        $this->potentialParticipantMatches = [];
        $this->potentialPropertyMatches = [];
        $this->selectedParticipantAction = 'create_new';
        $this->selectedPropertyAction = 'create_new';

        // Auto-select program: First try from linked services, then default to first housing-enabled program
        $this->convertProgramId = '';
        $this->convertServiceId = '';

        // Try to get program from linked services (if any have housing enabled)
        if ($log->services->isNotEmpty()) {
            foreach ($log->services as $service) {
                if ($service->program && $service->program->housing_module_enabled && $service->program->active) {
                    $this->convertProgramId = $service->program->id;
                    $this->convertServiceId = $service->id;
                    break;
                }
            }
        }

        // If no program from services, get first housing-enabled program user has access to
        if (empty($this->convertProgramId)) {
            $housingProgram = $this->getAccessiblePrograms()
                ->where('active', true)
                ->where('housing_module_enabled', true)
                ->first();

            if ($housingProgram) {
                $this->convertProgramId = $housingProgram->id;
            }
        }

        $this->showConvertModal = true;

        \Log::info('openConvertModal completed', [
            'showConvertModal_after' => $this->showConvertModal,
            'convertingLogId' => $this->convertingLogId,
            'autoSelectedProgramId' => $this->convertProgramId,
            'autoSelectedServiceId' => $this->convertServiceId,
        ]);
    }

    /**
     * Move to step 2: Check for duplicates and show review screen
     */
    /**
     * P-01: surface possible duplicate communication logs / properties at the
     * address the user just typed. Warning only — never blocks save. Up to 5
     * matches are returned; ordered by recency.
     *
     * Two-pass match using App\Support\AddressNormalizer:
     *   1. exact-match on normalized (street, city) — catches case, suffix
     *      ("Street" vs "St"), directional ("North" vs "N"), and punctuation
     *      drift.
     *   2. same-city + same leading street number + same first non-direction
     *      word — catches typos in the rest of the street name.
     *
     * Editing an existing log excludes itself from the result.
     */
    public function checkAddressDuplicates(): void
    {
        $this->duplicateMatches = [];

        $address = trim((string) $this->contact_address);
        $city = trim((string) $this->contact_city);
        if ($address === '' || $city === '') {
            return;
        }

        $norm = AddressNormalizer::normalize($address, $city);
        $numAndWord = AddressNormalizer::streetNumberAndFirstWord($address);

        $matches = collect();

        // Pass 1: existing communication logs at the same normalized address.
        $logs = CommunicationLog::query()
            ->whereNotNull('contact_address')
            ->whereNotNull('contact_city')
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->orderByDesc('communication_date')
            ->limit(50)
            ->get(['id', 'contact_address', 'contact_city', 'contact_state', 'contact_zip', 'subject', 'communication_date', 'contact_first_name', 'contact_last_name']);

        foreach ($logs as $log) {
            $candidate = AddressNormalizer::normalize($log->contact_address, $log->contact_city);
            $candNumWord = AddressNormalizer::streetNumberAndFirstWord($log->contact_address);

            $isMatch = $candidate['fingerprint'] === $norm['fingerprint']
                || ($numAndWord !== null
                    && $candNumWord !== null
                    && $candidate['city'] === $norm['city']
                    && $candNumWord === $numAndWord);

            if ($isMatch) {
                $matches->push([
                    'kind' => 'log',
                    'id' => $log->id,
                    'address' => trim($log->contact_address.', '.$log->contact_city.', '.$log->contact_state.' '.$log->contact_zip),
                    'subject' => $log->subject,
                    'date' => optional($log->communication_date)->format('Y-m-d'),
                    'contact' => trim($log->contact_first_name.' '.$log->contact_last_name),
                ]);
            }
        }

        // Pass 1+2: existing properties at the same normalized address.
        $properties = Property::query()
            ->whereNotNull('address_line_1')
            ->whereNotNull('city')
            ->limit(50)
            ->get(['id', 'address_line_1', 'city', 'state', 'zip_code']);

        foreach ($properties as $property) {
            $candidate = AddressNormalizer::normalize($property->address_line_1, $property->city);
            $candNumWord = AddressNormalizer::streetNumberAndFirstWord($property->address_line_1);

            $isMatch = $candidate['fingerprint'] === $norm['fingerprint']
                || ($numAndWord !== null
                    && $candNumWord !== null
                    && $candidate['city'] === $norm['city']
                    && $candNumWord === $numAndWord);

            if ($isMatch) {
                $matches->push([
                    'kind' => 'property',
                    'id' => $property->id,
                    'address' => trim($property->address_line_1.', '.$property->city.', '.$property->state.' '.$property->zip_code),
                    'subject' => null,
                    'date' => null,
                    'contact' => null,
                ]);
            }
        }

        $this->duplicateMatches = $matches->take(5)->values()->all();
    }

    /**
     * P-01: user clicked "Continue anyway" on the duplicate warning.
     * Marks the warning acknowledged and re-runs save.
     */
    public function acknowledgeDuplicatesAndSave(): void
    {
        $this->duplicateAcknowledged = true;
        $this->save();
    }

    public function checkForDuplicates()
    {
        $this->validate([
            'convertJobTitle' => 'required|string|max:255',
            'convertJobDescription' => 'nullable|string',
            'convertProgramId' => 'required|exists:programs,id',
        ]);

        $log = CommunicationLog::findOrFail($this->convertingLogId);
        $this->convertingLog = $log;

        // Reset matches
        $this->potentialParticipantMatches = [];
        $this->potentialPropertyMatches = [];

        $needsReview = false;

        // Check for participant duplicates if no participant is linked
        if (! $log->participant_id && $log->contact_first_name && $log->contact_last_name) {
            // Search for similar participants (case-insensitive, partial match)
            $matches = Participant::with('household')
                ->where(function ($query) use ($log) {
                    // Exact name match (case-insensitive)
                    $query->whereRaw('LOWER(first_name) = ?', [strtolower($log->contact_first_name)])
                        ->whereRaw('LOWER(last_name) = ?', [strtolower($log->contact_last_name)]);
                })
                ->orWhere(function ($query) use ($log) {
                    // Partial match - same last name and similar first name
                    $query->whereRaw('LOWER(last_name) = ?', [strtolower($log->contact_last_name)])
                        ->where(function ($q) use ($log) {
                            $q->whereRaw('LOWER(first_name) LIKE ?', [strtolower($log->contact_first_name).'%'])
                                ->orWhereRaw('? LIKE CONCAT(LOWER(first_name), "%")', [strtolower($log->contact_first_name)]);
                        });
                })
                ->limit(10)
                ->get();

            // Also check by phone or email if available
            if ($log->contact_phone || $log->contact_email) {
                $contactMatches = Participant::with('household')
                    ->where(function ($query) use ($log) {
                        if ($log->contact_phone) {
                            $query->orWhere('telephone', 'LIKE', '%'.preg_replace('/[^0-9]/', '', $log->contact_phone).'%');
                        }
                        if ($log->contact_email) {
                            $query->orWhereRaw('LOWER(email) = ?', [strtolower($log->contact_email)]);
                        }
                    })
                    ->limit(5)
                    ->get();

                $matches = $matches->merge($contactMatches)->unique('id');
            }

            if ($matches->isNotEmpty()) {
                $this->potentialParticipantMatches = $matches->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->full_name,
                        'phone' => $p->telephone,
                        'email' => $p->email,
                        'household_code' => $p->household?->household_code,
                        'household_id' => $p->household_id,
                    ];
                })->toArray();
                $needsReview = true;
            }
        }

        // Check for property duplicates if no property is linked but address is provided.
        // Routed through App\Support\AddressNormalizer so the conversion-flow
        // and the P-01 save-time warning use the same matching rules.
        if (! $log->property_id && $log->contact_address && $log->contact_city) {
            $logNorm = AddressNormalizer::normalize($log->contact_address, $log->contact_city);
            $logNumWord = AddressNormalizer::streetNumberAndFirstWord($log->contact_address);

            $matches = Property::query()
                ->whereNotNull('address_line_1')
                ->whereNotNull('city')
                ->limit(50)
                ->get()
                ->filter(function ($property) use ($logNorm, $logNumWord) {
                    $cand = AddressNormalizer::normalize($property->address_line_1, $property->city);
                    $candNumWord = AddressNormalizer::streetNumberAndFirstWord($property->address_line_1);

                    return $cand['fingerprint'] === $logNorm['fingerprint']
                        || ($logNumWord !== null
                            && $candNumWord !== null
                            && $cand['city'] === $logNorm['city']
                            && $candNumWord === $logNumWord);
                })
                ->take(10);

            if ($matches->isNotEmpty()) {
                $this->potentialPropertyMatches = $matches->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'address' => $p->address_line_1,
                        'city' => $p->city,
                        'state' => $p->state,
                        'zip' => $p->zip_code,
                        'full_address' => $p->address_line_1.', '.$p->city.', '.$p->state.' '.$p->zip_code,
                    ];
                })->values()->toArray();
                $needsReview = true;
            }
        }

        if ($needsReview) {
            $this->convertStep = 2;
        } else {
            // No duplicates found, go straight to conversion
            $this->convertToProject();
        }
    }

    /**
     * Select an existing participant from the duplicate list
     */
    public function selectExistingParticipant($participantId)
    {
        $this->selectedParticipantAction = $participantId;
    }

    /**
     * Select to create a new participant
     */
    public function selectCreateNewParticipant()
    {
        $this->selectedParticipantAction = 'create_new';
    }

    /**
     * Select an existing property from the duplicate list
     */
    public function selectExistingProperty($propertyId)
    {
        $this->selectedPropertyAction = $propertyId;
    }

    /**
     * Select to create a new property
     */
    public function selectCreateNewProperty()
    {
        $this->selectedPropertyAction = 'create_new';
    }

    /**
     * Skip property creation (proceed without property)
     */
    public function selectSkipProperty()
    {
        $this->selectedPropertyAction = 'skip';
    }

    /**
     * Go back to step 1
     */
    public function backToStep1()
    {
        $this->convertStep = 1;
    }

    public function closeConvertModal()
    {
        \Log::info('closeConvertModal called', ['caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown']);

        $this->showConvertModal = false;
        $this->convertingLogId = null;
        $this->convertingLog = null;
        $this->convertJobTitle = '';
        $this->convertJobDescription = '';
        $this->convertProgramId = '';
        $this->convertServiceId = '';
        $this->convertStep = 1;
        $this->potentialParticipantMatches = [];
        $this->potentialPropertyMatches = [];
        $this->selectedParticipantAction = 'create_new';
        $this->selectedPropertyAction = 'create_new';
        // v2 (Phase 7): clear gate state.
        $this->requirementOverride = false;
        $this->requirementOverrideReason = '';
    }

    public function convertToProject()
    {
        \Log::info('convertToProject started', ['convertingLogId' => $this->convertingLogId]);

        $this->authorizeAction('projects.create');

        $this->validate([
            'convertJobTitle' => 'required|string|max:255',
            'convertJobDescription' => 'nullable|string',
            'convertProgramId' => 'required|exists:programs,id',
        ]);

        $log = CommunicationLog::findOrFail($this->convertingLogId);

        \Log::info('convertToProject log loaded', [
            'log_id' => $log->id,
            'already_converted' => $log->converted_to_job_id ? 'yes' : 'no',
            'converted_to_job_id' => $log->converted_to_job_id,
            'participant_id' => $log->participant_id,
            'property_id' => $log->property_id,
            'contact_first_name' => $log->contact_first_name,
            'contact_last_name' => $log->contact_last_name,
            'contact_address' => $log->contact_address,
            'contact_city' => $log->contact_city,
        ]);

        // Check if already converted and block (user must use resetConversion first)
        if ($log->converted_to_job_id) {
            $existingJob = HousingJob::find($log->converted_to_job_id);
            $jobNumber = $existingJob?->job_number ?? "ID: {$log->converted_to_job_id}";
            session()->flash('error', "This communication log has already been converted to project {$jobNumber}. Use 'Reset Conversion' option first if you need to re-convert.");
            $this->closeConvertModal();
            \Log::info('convertToProject blocked - already converted', ['job_number' => $jobNumber]);

            return;
        }

        DB::beginTransaction();
        try {
            $participant = null;
            $household = null;
            $property = null;
            $createdResources = [];

            \Log::info('convertToProject: User selections', [
                'selectedParticipantAction' => $this->selectedParticipantAction,
                'selectedPropertyAction' => $this->selectedPropertyAction,
            ]);

            // Handle participant based on user selection or existing link
            if ($log->participant_id) {
                // Already has a participant linked
                \Log::info('convertToProject: Using already-linked participant', ['participant_id' => $log->participant_id]);
                $participant = Participant::find($log->participant_id);
                $household = $participant?->household;
            } elseif (is_numeric($this->selectedParticipantAction)) {
                // User selected an existing participant from duplicates
                \Log::info('convertToProject: User selected existing participant', ['participant_id' => $this->selectedParticipantAction]);
                $participant = Participant::find($this->selectedParticipantAction);
                $household = $participant?->household;
                $createdResources[] = "linked to existing participant: {$participant->full_name}";

                // Update the communication log with the participant
                $log->update([
                    'participant_id' => $participant->id,
                    'household_id' => $household?->id,
                ]);
            } elseif ($this->selectedParticipantAction === 'create_new') {
                // User chose to create a new participant
                if ($log->contact_first_name && $log->contact_last_name) {
                    \Log::info('convertToProject: Creating new participant per user selection');

                    // Create new household
                    $household = Household::create([
                        'household_type' => 'Single Person',
                        'household_size' => 1,
                        'housing_status' => 'Unknown/not reported',
                    ]);

                    // Add household address if available
                    if ($log->contact_address && $log->contact_city) {
                        $household->addresses()->create([
                            'address' => $log->contact_address,
                            'city' => $log->contact_city,
                            'state' => strtoupper($log->contact_state ?? 'PA'),
                            'zip_code' => $log->contact_zip ?? '',
                            'is_current' => true,
                        ]);
                    }

                    // Create new participant
                    $participant = Participant::create([
                        'household_id' => $household->id,
                        'first_name' => $log->contact_first_name,
                        'last_name' => $log->contact_last_name,
                        'telephone' => $log->contact_phone ?: null,
                        'email' => $log->contact_email ?: null,
                        'relationship_to_head' => 'self',
                        'is_head_of_household' => true,
                    ]);

                    $createdResources[] = "created participant: {$participant->full_name}";

                    // Update the communication log with the participant
                    $log->update([
                        'participant_id' => $participant->id,
                        'household_id' => $household->id,
                    ]);
                } else {
                    \Log::info('convertToProject: No contact name, cannot create participant');
                    DB::rollBack();
                    session()->flash('error', 'Cannot convert to project: No participant is linked and contact name is incomplete. Please edit the communication log and add contact first/last name or select a participant.');
                    $this->closeConvertModal();

                    return;
                }
            }

            // Handle property based on user selection or existing link
            if ($log->property_id) {
                // Already has a property linked
                \Log::info('convertToProject: Using already-linked property', ['property_id' => $log->property_id]);
                $property = Property::find($log->property_id);
            } elseif (is_numeric($this->selectedPropertyAction)) {
                // User selected an existing property from duplicates
                \Log::info('convertToProject: User selected existing property', ['property_id' => $this->selectedPropertyAction]);
                $property = Property::find($this->selectedPropertyAction);
                $createdResources[] = "linked to existing property: {$property->address_line_1}";

                // Update the communication log with the property
                $log->update(['property_id' => $property->id]);
            } elseif ($this->selectedPropertyAction === 'create_new' && $log->contact_address && $log->contact_city) {
                // User chose to create a new property
                \Log::info('convertToProject: Creating new property per user selection');
                $property = Property::create([
                    'address_line_1' => $log->contact_address,
                    'city' => $log->contact_city,
                    'state' => strtoupper($log->contact_state ?? 'PA'),
                    'zip_code' => $log->contact_zip ?? '',
                    'participant_id' => $participant?->id,
                    'household_id' => $household?->id,
                    'status' => 'active',
                    'created_by' => Auth::id(),
                ]);

                $createdResources[] = "created property: {$property->address_line_1}";

                // Update the communication log with the property
                $log->update(['property_id' => $property->id]);
            } elseif ($this->selectedPropertyAction === 'skip') {
                // User chose to skip property creation
                \Log::info('convertToProject: User chose to skip property creation');
                $property = null;
            } else {
                // No property linked and no address to create from
                \Log::info('convertToProject: No property available', [
                    'has_contact_address' => (bool) $log->contact_address,
                    'has_contact_city' => (bool) $log->contact_city,
                ]);
                $property = null;
            }

            // Check for duplicate property assignment if property exists
            if ($property) {
                $existingJob = HousingJob::where('property_id', $property->id)
                    ->whereIn('status', ['pending', 'approved', 'in_progress', 'on_hold'])
                    ->first();

                if ($existingJob) {
                    $createdResources[] = "Note: Active project already exists for this property (Job #{$existingJob->job_number})";
                }
            }

            \Log::info('convertToProject: Creating HousingJob', [
                'job_title' => $this->convertJobTitle,
                'program_id' => $this->convertProgramId,
                'participant_id' => $participant?->id,
                'household_id' => $household?->id,
                'property_id' => $property?->id,
            ]);

            // v2 (Phase 7): requirements gate — block conversion if any
            // required-and-non-orphaned intake requirement is unmet AND
            // the user did not tick the audit-logged override.
            $unmetCount = $log->intakeRequirements()
                ->where('is_orphaned', false)
                ->whereNotIn('status', \App\Models\IntakeRequirement::MET_STATUSES)
                ->count();

            if ($unmetCount > 0 && ! $this->requirementOverride) {
                DB::rollBack();
                $this->addError('requirementOverride', "{$unmetCount} required application requirement(s) are unmet. Tick the override box and provide a reason to proceed.");
                return;
            }

            if ($this->requirementOverride && trim($this->requirementOverrideReason) === '') {
                DB::rollBack();
                $this->addError('requirementOverrideReason', 'A reason is required when overriding unmet requirements.');
                return;
            }

            $job = HousingJob::create([
                'job_title' => $this->convertJobTitle,
                'job_description' => $this->convertJobDescription,
                'program_id' => $this->convertProgramId,
                'service_id' => $this->convertServiceId ?: null,
                'participant_id' => $participant?->id,
                'household_id' => $household?->id,
                'property_id' => $property?->id,
                'contractor_id' => $log->contractor_id,
                'status' => 'pending',
                'priority' => $log->priority === 'urgent' ? 'emergency' : ($log->priority === 'high' ? 'high' : 'medium'),
                'request_date' => now(),
                'created_by' => Auth::id(),
                'project_manager_id' => Auth::id(),
            ]);

            \Log::info('convertToProject: HousingJob created', ['job_id' => $job->id, 'job_number' => $job->job_number]);

            $log->convertToProject($job, Auth::id());

            // v2 (Phase 7): record the override audit + (eventually) copy
            // the intake_requirements forward into project_requirements.
            app(\App\Services\IntakeRequirementInstantiator::class)->carryForwardToProject(
                $log,
                $job,
                Auth::user(),
                $this->requirementOverride ? $this->requirementOverrideReason : null
            );

            DB::commit();

            \Log::info('convertToProject: Transaction committed successfully');

            $this->closeConvertModal();

            $message = "Communication converted to project: {$job->job_number}";
            if (! empty($createdResources)) {
                $message .= ' ('.implode(', ', $createdResources).')';
            }
            if (! $property) {
                $message .= ' (Note: No property assigned - you can add one by editing the project)';
            }
            session()->flash('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Convert to project failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            session()->flash('error', 'Failed to convert to project: '.$e->getMessage());
        }
    }

    /**
     * Process address entry - creates property and/or household address if needed
     *
     * This method handles three scenarios:
     * 1. Create Property record for project tracking
     * 2. Link address to participant's household
     * 3. Use address as communication subject only
     *
     *
     * @throws \Exception if address creation fails
     */
    protected function processAddressEntry(): void
    {
        if (! $this->showAddressEntry || empty($this->addressLine)) {
            return;
        }

        // Validate address fields
        $this->validate([
            'addressLine' => 'required|string|max:255',
            'addressCity' => 'required|string|max:100',
            'addressState' => 'required|string|size:2|regex:/^[A-Z]{2}$/',
            'addressZip' => 'required|string|max:10',
        ]);

        // If linking to participant, ensure participant is selected or create new one
        if ($this->linkAddressToParticipant && ! $this->participant_id) {
            // If no participant selected but we want to link, they need to select or create one
            $this->addError('participant_id', 'Please select or create a participant to link this address.');

            return;
        }

        try {
            $createdResources = []; // Track what was created for success message

            // Create property if toggled
            if ($this->createAsProperty) {
                // Check for duplicate property first
                $existingProperty = Property::where('address_line_1', $this->addressLine)
                    ->where('city', $this->addressCity)
                    ->where('zip_code', $this->addressZip)
                    ->first();

                if ($existingProperty) {
                    // Use existing property instead of creating duplicate
                    $this->property_id = $existingProperty->id;

                    // Set subject from property address if not already set
                    if (empty($this->subject)) {
                        $this->subject = $existingProperty->address_line_1.', '.$existingProperty->city;
                    }

                    $createdResources[] = 'linked to existing property';
                } else {
                    // Create new property
                    $property = Property::create([
                        'address_line_1' => $this->addressLine,
                        'city' => $this->addressCity,
                        'state' => strtoupper($this->addressState),
                        'zip_code' => $this->addressZip,
                        'participant_id' => $this->participant_id ?: null,
                        'household_id' => $this->household_id ?: null,
                        'status' => 'active',
                        'created_by' => Auth::id(),
                    ]);

                    $this->property_id = $property->id;

                    // Set subject from property address if not already set
                    if (empty($this->subject)) {
                        $this->subject = $property->address_line_1.', '.$property->city;
                    }

                    $createdResources[] = 'property created';
                }
            }

            // Create household address if linked to participant with household
            if ($this->linkAddressToParticipant && $this->household_id) {
                $household = Household::find($this->household_id);
                if ($household) {
                    // Check if this address already exists for the household
                    $existingAddress = $household->addresses()
                        ->where('address', $this->addressLine)
                        ->where('city', $this->addressCity)
                        ->where('zip_code', $this->addressZip)
                        ->first();

                    if (! $existingAddress) {
                        $household->addresses()->create([
                            'address' => $this->addressLine,
                            'city' => $this->addressCity,
                            'state' => strtoupper($this->addressState),
                            'zip_code' => $this->addressZip,
                            'is_current' => true,
                        ]);

                        $createdResources[] = 'household address linked';
                    } else {
                        $createdResources[] = 'address already linked to household';
                    }
                }
            }

            // Set subject from address if not already set and not a property
            if (empty($this->subject) && ! $this->createAsProperty) {
                $this->subject = $this->addressLine.', '.$this->addressCity;
            }

            // Show success message if any resources were created/linked
            if (! empty($createdResources)) {
                $message = 'Address processed: '.implode(', ', $createdResources);
                session()->flash('address_success', $message);
            }
        } catch (\Exception $e) {
            // Log the error and show user-friendly message
            \Log::error('Failed to process address entry', [
                'address' => $this->addressLine,
                'city' => $this->addressCity,
                'error' => $e->getMessage(),
            ]);

            $this->addError('addressLine', 'Failed to save address. Please try again or contact support.');

            throw $e;
        }
    }

    protected function processProjectEnrollment(): void
    {
        if (! $this->create_project_enrollment) {
            return;
        }

        // Validate required fields for project enrollment
        $this->validate([
            'contact_address' => 'required|string|max:255',
            'contact_city' => 'required|string|max:100',
            'contact_state' => 'required|string|size:2',
            'contact_zip' => 'required|string|max:10',
            'contact_first_name' => 'required|string|max:255',
            'contact_last_name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Step 1: Check for duplicate property
            $existingProperty = Property::where('address_line_1', $this->contact_address)
                ->where('city', $this->contact_city)
                ->where('zip_code', $this->contact_zip)
                ->first();

            if ($existingProperty) {
                // Use existing property
                $property = $existingProperty;
                session()->flash('info', 'Using existing property: '.$property->address_line_1.', '.$property->city);
            } else {
                // Create new property
                $property = Property::create([
                    'address_line_1' => $this->contact_address,
                    'city' => $this->contact_city,
                    'state' => strtoupper($this->contact_state),
                    'zip_code' => $this->contact_zip,
                    'status' => 'active',
                    'created_by' => Auth::id(),
                ]);
            }

            // Step 2: Check for duplicate participant
            $existingParticipant = Participant::where('first_name', $this->contact_first_name)
                ->where('last_name', $this->contact_last_name)
                ->first();

            if ($existingParticipant) {
                // Use existing participant
                $participant = $existingParticipant;
                $household = $participant->household;
                session()->flash('info', 'Using existing participant: '.$participant->full_name);
            } else {
                // Step 3: Create household with the address
                $household = Household::create([
                    'household_type' => 'Single Person',
                    'household_size' => 1,
                    'housing_status' => 'Unknown/not reported',
                ]);

                // Create household address
                $household->addresses()->create([
                    'address' => $this->contact_address,
                    'city' => $this->contact_city,
                    'state' => strtoupper($this->contact_state),
                    'zip_code' => $this->contact_zip,
                    'is_current' => true,
                ]);

                // Step 4: Create participant as head of household
                $participant = Participant::create([
                    'household_id' => $household->id,
                    'first_name' => $this->contact_first_name,
                    'last_name' => $this->contact_last_name,
                    'telephone' => $this->contact_phone ?: null,
                    'email' => $this->contact_email ?: null,
                    'relationship_to_head' => 'self',
                    'is_head_of_household' => true,
                ]);
            }

            // Update property with participant and household
            $property->update([
                'participant_id' => $participant->id,
                'household_id' => $household->id,
            ]);

            // Step 5: Create housing job
            $fallbackTitle = $this->contact_address.', '.$this->contact_city;
            if ($this->contact_state) {
                $fallbackTitle .= ', '.$this->contact_state;
            }
            if ($this->contact_zip) {
                $fallbackTitle .= ', '.$this->contact_zip;
            }

            $housingJob = HousingJob::create([
                'job_title' => $this->subject ?: $fallbackTitle,
                'job_description' => $this->summary ?? 'Created from communication log',
                'participant_id' => $participant->id,
                'household_id' => $household->id,
                'property_id' => $property->id,
                'status' => 'pending',
                'priority' => $this->priority === 'urgent' ? 'emergency' : ($this->priority === 'high' ? 'high' : 'medium'),
                'request_date' => now(),
                'created_by' => Auth::id(),
            ]);

            // Set the relationships for the communication log
            $this->participant_id = $participant->id;
            $this->household_id = $household->id;
            $this->property_id = $property->id;
            $this->housing_job_id = $housingJob->id;

            DB::commit();

            session()->flash('success', 'Project and enrollment created successfully! Job #'.$housingJob->job_number);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create project enrollment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->addError('create_project_enrollment', 'Failed to create project and enrollment: '.$e->getMessage());
            throw $e;
        }
    }

    public function save()
    {
        // Process project enrollment if checkbox is checked
        if ($this->create_project_enrollment) {
            $this->processProjectEnrollment();

            // Check for errors from project enrollment
            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
        }

        // Process address entry first
        $this->processAddressEntry();

        // Check for validation errors from address processing
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

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
            'contact_first_name' => 'required|string|max:255',
            'contact_last_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'contact_address' => 'nullable|string|max:255',
            'contact_city' => 'nullable|string|max:100',
            'contact_state' => 'nullable|string|size:2',
            'contact_zip' => 'nullable|string|max:10',
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

        // v2 §5.5: cannot NEWLY check an expired fund code. Existing
        // attachments to expired funds (originallySelectedFundCodeIds)
        // are preserved untouched so historical data isn't disturbed.
        $newlyChecked = array_diff(
            array_map('intval', $this->selectedFundCodeIds),
            array_map('intval', $this->originallySelectedFundCodeIds)
        );
        if (! empty($newlyChecked)) {
            $expiredNew = \App\Models\FundCode::query()
                ->whereIn('id', $newlyChecked)
                ->get()
                ->filter(fn ($f) => $f->expiry_state === 'expired');

            if ($expiredNew->isNotEmpty()) {
                $codes = $expiredNew->pluck('code')->implode(', ');
                $this->addError('selectedFundCodeIds', "Cannot tag expired fund codes ({$codes}). They can no longer accept new attachments.");
                return;
            }
        }

        // P-01: warn (but never block) on duplicate-address entries. The user
        // can confirm via the "Continue anyway" panel which sets
        // duplicateAcknowledged and re-enters save().
        if (! $this->duplicateAcknowledged) {
            $this->checkAddressDuplicates();
            if (! empty($this->duplicateMatches)) {
                return;
            }
        }

        $data = [
            'housing_job_id' => $this->housing_job_id !== '' ? $this->housing_job_id : null,
            'participant_id' => $this->participant_id !== '' ? $this->participant_id : null,
            'household_id' => $this->household_id !== '' ? $this->household_id : null,
            'property_id' => $this->property_id !== '' ? $this->property_id : null,
            'contractor_id' => $this->contractor_id !== '' ? $this->contractor_id : null,
            'communication_date' => $this->communication_date,
            'communication_type' => $this->communication_type,
            'direction' => $this->direction,
            'contact_first_name' => $this->contact_first_name,
            'contact_last_name' => $this->contact_last_name !== '' ? $this->contact_last_name : null,
            'contact_phone' => $this->contact_phone !== '' ? $this->contact_phone : null,
            'contact_email' => $this->contact_email !== '' ? $this->contact_email : null,
            'contact_address' => $this->contact_address !== '' ? $this->contact_address : null,
            'contact_city' => $this->contact_city !== '' ? $this->contact_city : null,
            'contact_state' => $this->contact_state !== '' ? $this->contact_state : null,
            'contact_zip' => $this->contact_zip !== '' ? $this->contact_zip : null,
            'contact_organization' => $this->contact_organization !== '' ? $this->contact_organization : null,
            'contact_role' => $this->contact_role !== '' ? $this->contact_role : null,
            'subject' => $this->subject,
            // v2 (Phase 5): persist the override flag if the user typed
            // in the Subject this session. Once set, the field is sticky.
            'subject_overridden_at' => $this->subjectTouched
                ? now()
                : ($this->subject_overridden_at ?: null),
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

            // Sync anticipated fund codes (P-39b: legacy tag pivot dropped).
            $log->anticipatedFundCodes()->sync($this->selectedFundCodeIds);

            // v2 / Phase 9: sync housing services + run the instantiator
            // for any newly-attached ones so requirements auto-stamp.
            $this->syncHousingServices($log);

            session()->flash('success', 'Communication log updated successfully.');
        } else {
            $this->authorizeAction('communications_log.create');
            $data['created_by'] = Auth::id();
            $log = CommunicationLog::create($data);

            // Attach services
            $log->services()->sync($this->selectedServices);

            // Attach tags
            $log->syncTags($this->selectedTagIds);

            // Attach anticipated fund codes (P-39b: legacy tag pivot dropped).
            $log->anticipatedFundCodes()->sync($this->selectedFundCodeIds);

            // v2 / Phase 9: sync housing services + auto-stamp requirements.
            $this->syncHousingServices($log);

            session()->flash('success', 'Communication log created successfully.');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Comm Log v2 / Phase 9: sync the comm-log ↔ housing-service pivot
     * AND run IntakeRequirementInstantiator::forHousingService for every
     * newly-attached service so requirements + fund codes auto-stamp.
     *
     * Idempotent — re-running for an already-attached service merges
     * via tags without creating duplicate IntakeRequirement rows.
     */
    protected function syncHousingServices(CommunicationLog $log): void
    {
        $currentIds = $log->housingServices()->pluck('housing_services.id')->map(fn ($v) => (int) $v)->all();
        $targetIds = array_map('intval', $this->selectedHousingServiceIds);

        $toAdd = array_diff($targetIds, $currentIds);
        $toRemove = array_diff($currentIds, $targetIds);

        if (! empty($toRemove)) {
            $log->housingServices()->detach($toRemove);
        }

        $instantiator = app(\App\Services\IntakeRequirementInstantiator::class);
        $actor = Auth::user();

        foreach ($toAdd as $serviceId) {
            $log->housingServices()->attach($serviceId, [
                'created_by_user_id' => $actor->id,
                'created_at' => now(),
            ]);

            $svc = \App\Models\HousingService::find($serviceId);
            if ($svc !== null) {
                $instantiator->forHousingService($log, $svc, $actor);
            }
        }
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

    public function resetConversion($id)
    {
        // Only admins can reset conversions
        if (! Auth::user()->isAdmin()) {
            session()->flash('error', 'Only administrators can reset project conversions.');

            return;
        }

        $log = CommunicationLog::with('convertedToJob')->findOrFail($id);

        if (! $log->converted_to_job_id) {
            session()->flash('error', 'This communication log has not been converted to a project.');

            return;
        }

        $oldJobNumber = $log->convertedToJob?->job_number ?? 'Unknown';

        $log->resetConversion(Auth::id());

        session()->flash('success', "Conversion reset for {$oldJobNumber}. You can now re-convert this communication log with updated validation and automation.");
    }

    /**
     * Unlink a participant from a communication log
     */
    public function unlinkParticipant($logId)
    {
        // Only admins can unlink entities
        if (! Auth::user()->isAdmin()) {
            session()->flash('error', 'Only administrators can unlink records.');

            return;
        }

        $log = CommunicationLog::findOrFail($logId);
        $participantName = $log->participant?->full_name ?? 'Unknown';

        $log->update([
            'participant_id' => null,
            'household_id' => null, // Also clear household since it's linked through participant
            'updated_by' => Auth::id(),
        ]);

        // Add an update note about the unlink
        $log->addUpdate("Participant unlinked by admin: {$participantName}", 'status_change', Auth::id());

        // Also clear form state if editing this log
        if ($this->editingId == $logId) {
            $this->participant_id = '';
            $this->household_id = '';
        }

        session()->flash('success', "Participant '{$participantName}' has been unlinked from this communication log.");
    }

    /**
     * Unlink a property from a communication log
     */
    public function unlinkProperty($logId)
    {
        // Only admins can unlink entities
        if (! Auth::user()->isAdmin()) {
            session()->flash('error', 'Only administrators can unlink records.');

            return;
        }

        $log = CommunicationLog::findOrFail($logId);
        $propertyAddress = $log->property?->address_line_1 ?? 'Unknown';

        $log->update([
            'property_id' => null,
            'updated_by' => Auth::id(),
        ]);

        // Add an update note about the unlink
        $log->addUpdate("Property unlinked by admin: {$propertyAddress}", 'status_change', Auth::id());

        // Also clear form state if editing this log
        if ($this->editingId == $logId) {
            $this->property_id = '';
        }

        session()->flash('success', "Property '{$propertyAddress}' has been unlinked from this communication log.");
    }

    /**
     * Unlink a contractor from a communication log
     */
    public function unlinkContractor($logId)
    {
        // Only admins can unlink entities
        if (! Auth::user()->isAdmin()) {
            session()->flash('error', 'Only administrators can unlink records.');

            return;
        }

        $log = CommunicationLog::findOrFail($logId);
        $contractorName = $log->contractor?->company_name ?? 'Unknown';

        $log->update([
            'contractor_id' => null,
            'updated_by' => Auth::id(),
        ]);

        // Add an update note about the unlink
        $log->addUpdate("Contractor unlinked by admin: {$contractorName}", 'status_change', Auth::id());

        // Also clear form state if editing this log
        if ($this->editingId == $logId) {
            $this->contractor_id = '';
        }

        session()->flash('success', "Contractor '{$contractorName}' has been unlinked from this communication log.");
    }

    /**
     * Unlink a housing job from a communication log
     */
    public function unlinkHousingJob($logId)
    {
        // Only admins can unlink entities
        if (! Auth::user()->isAdmin()) {
            session()->flash('error', 'Only administrators can unlink records.');

            return;
        }

        $log = CommunicationLog::findOrFail($logId);
        $jobNumber = $log->housingJob?->job_number ?? 'Unknown';

        $log->update([
            'housing_job_id' => null,
            'updated_by' => Auth::id(),
        ]);

        // Add an update note about the unlink
        $log->addUpdate("Project unlinked by admin: {$jobNumber}", 'status_change', Auth::id());

        // Also clear form state if editing this log
        if ($this->editingId == $logId) {
            $this->housing_job_id = '';
        }

        session()->flash('success', "Project '{$jobNumber}' has been unlinked from this communication log.");
    }

    protected function resetForm()
    {
        $this->editingId = null;
        $this->housing_job_id = '';
        // v2 state-machine resets
        $this->anchor = 'intake';
        $this->showFundCodesInIntake = false;
        $this->showServicesInIntake = false;
        $this->projectPickerSearch = '';
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
        $this->create_project_enrollment = false;
        $this->contact_first_name = '';
        $this->contact_last_name = '';
        $this->contact_phone = '';
        $this->contact_email = '';
        $this->contact_address = '';
        $this->contact_city = '';
        $this->contact_state = 'PA';
        $this->contact_zip = '';
        $this->contact_organization = '';
        $this->contact_role = '';
        $this->subject = '';
        $this->subject_overridden_at = null;
        $this->subjectTouched = false;
        $this->summary = '';
        $this->notes = '';
        $this->outcome = '';
        $this->category = 'general';
        $this->priority = 'normal';
        $this->follow_up_date = '';
        $this->follow_up_action = '';
        $this->selectedServices = [];
        $this->selectedHousingServiceIds = [];
        $this->selectedTagIds = [];
        $this->selectedFundCodeIds = [];
        $this->originallySelectedFundCodeIds = [];
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

        // Reset address entry
        $this->showAddressEntry = false;
        $this->resetAddressEntryFields();

        // Reset duplicate-address warning state.
        $this->duplicateMatches = [];
        $this->duplicateAcknowledged = false;
    }

    public function render()
    {
        // Eager-load anticipatedFundCodes alongside the existing relations
        // so the card view can render fund-code badges per row without N+1.
        // Cheap on table view too (typically 0–3 codes per log).
        // (Relation renamed in P-39b — addendum D-2 — from the legacy tag pivot.)
        $query = CommunicationLog::with(['participant', 'household', 'property', 'contractor', 'createdBy', 'services', 'tags', 'convertedToJob', 'anticipatedFundCodes'])
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

        // P-03 (Phase 3B): follow-up status multi-select. Stricter
        // semantics than the loose scopePendingFollowUp (which is kept
        // for the dashboard count badge): "pending" here means future-
        // or-today AND not done; "overdue" is past AND not done;
        // "completed" is done. OR-within-category, AND-across-
        // categories.
        if (! empty($this->followUpFilter)) {
            $today = now()->startOfDay();
            $query->where(function ($q) use ($today) {
                if (in_array('pending', $this->followUpFilter, true)) {
                    $q->orWhere(function ($qq) use ($today) {
                        $qq->whereNotNull('follow_up_date')
                            ->where('follow_up_completed', false)
                            ->where('follow_up_date', '>=', $today);
                    });
                }
                if (in_array('overdue', $this->followUpFilter, true)) {
                    $q->orWhere(function ($qq) use ($today) {
                        $qq->whereNotNull('follow_up_date')
                            ->where('follow_up_completed', false)
                            ->where('follow_up_date', '<', $today);
                    });
                }
                if (in_array('completed', $this->followUpFilter, true)) {
                    $q->orWhere('follow_up_completed', true);
                }
            });
        }

        // P-03 (Phase 3B): conversion-status multi-select.
        if (! empty($this->conversionFilter)) {
            $query->where(function ($q) {
                if (in_array('converted', $this->conversionFilter, true)) {
                    $q->orWhereNotNull('converted_to_job_id');
                }
                if (in_array('not_converted', $this->conversionFilter, true)) {
                    $q->orWhereNull('converted_to_job_id');
                }
            });
        }

        if ($this->serviceFilter) {
            $query->forService($this->serviceFilter);
        }

        if ($this->tagFilter) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $this->tagFilter));
        }

        // P-03 (Phase 3B): fund code multi-select.
        if (! empty($this->fundCodeFilter)) {
            $query->whereHas('anticipatedFundCodes', fn ($q) => $q->whereIn('fund_codes.id', $this->fundCodeFilter));
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

        // Get housing-enabled programs for convert to project modal
        $housingPrograms = $accessiblePrograms->where('housing_module_enabled', true);

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
                'anticipatedFundCodes',
                'updates.createdBy',
                'convertedToJob',
                'convertedBy',
                'housingJob',
            ])->find($this->viewingId)
            : null;

        // Get housing jobs for project selection dropdown
        $housingJobs = HousingJob::select('id', 'job_number', 'job_title', 'status')
            ->when($this->housing_job_id, function ($query) {
                $query->orWhere('id', $this->housing_job_id);
            })
            ->whereIn('status', ['pending', 'in_progress', 'on_hold'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Get active fund codes for selection
        $allFundCodes = FundCode::active()->orderBy('code')->get();

        // v2 project picker: filtered + shaped for x-comm.project-picker.
        $pickerProjects = HousingJob::query()
            ->select('id', 'job_number', 'job_title', 'status', 'updated_at')
            ->when($this->projectPickerSearch !== '', function ($q) {
                $term = '%'.$this->projectPickerSearch.'%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('job_number', 'like', $term)
                       ->orWhere('job_title', 'like', $term);
                });
            })
            ->whereIn('status', ['pending', 'in_progress', 'on_hold'])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($j) => [
                'id'         => $j->id,
                'code'       => $j->job_number,
                'name'       => $j->job_title,
                'meta'       => 'Status: '.ucwords(str_replace('_', ' ', $j->status)).' · updated '.$j->updated_at->diffForHumans(),
            ])
            ->all();

        // v2 requirements pill / panel: load current intake requirements for the
        // editing entry (or empty when creating).
        $currentRequirements = $this->editingId
            ? \App\Models\IntakeRequirement::query()
                ->where('communication_log_id', $this->editingId)
                ->where('is_orphaned', false)
                ->get()
            : collect();

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
            'housingPrograms' => $housingPrograms,
            'allServices' => $allServices,
            'allTags' => $allTags,
            'searchedTags' => $searchedTags,
            'viewingLog' => $viewingLog,
            'housingJobs' => $housingJobs,
            'allFundCodes' => $allFundCodes,
            'pickerProjects' => $pickerProjects,
            'currentRequirements' => $currentRequirements,
            'allHousingServices' => \App\Models\HousingService::active()->orderBy('name')->get(),
        ]);
    }
}
