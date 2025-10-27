<?php

namespace App\Livewire;

use App\Models\DueDateEmail;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationRecipientsManagement extends Component
{
    use WithPagination, WithPermissionChecks;

    public $activeTab = 'users'; // 'users' or 'distribution-lists'

    public $search = '';

    // Form fields for distribution list
    public $showDistributionListForm = false;

    public $editingDistributionListId = null;

    public $email = '';

    public $name = '';

    public $description = '';

    public $active = true;

    protected $rules = [
        'email' => 'required|email',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:500',
        'active' => 'boolean',
    ];

    public function mount()
    {
        // Check if user has permission to view this page
        $this->authorizeAction('contracts.view');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->search = '';
        $this->resetPage();
    }

    public function showAddDistributionListForm()
    {
        $this->authorizeAction('contracts.manage_notifications');

        $this->resetDistributionListForm();
        $this->showDistributionListForm = true;
    }

    public function editDistributionList($id)
    {
        $this->authorizeAction('contracts.manage_notifications');

        $distributionList = DueDateEmail::whereNull('user_id')->findOrFail($id);

        $this->editingDistributionListId = $id;
        $this->email = $distributionList->email;
        $this->name = $distributionList->name;
        $this->description = $distributionList->description ?? '';
        $this->active = $distributionList->active;
        $this->showDistributionListForm = true;
    }

    public function saveDistributionList()
    {
        $this->authorizeAction('contracts.manage_notifications');

        $this->validate();

        // Validate domain
        $domain = substr(strrchr($this->email, '@'), 1);
        $approvedDomains = config('contracts.notifications.approved_domains', []);

        if (! in_array($domain, $approvedDomains)) {
            $this->addError('email', 'Email domain must be one of: '.implode(', ', $approvedDomains));

            return;
        }

        // Check for duplicates (excluding current record if editing)
        $query = DueDateEmail::where('email', $this->email);
        if ($this->editingDistributionListId) {
            $query->where('id', '!=', $this->editingDistributionListId);
        }

        if ($query->exists()) {
            $this->addError('email', 'This email address already exists in the system.');

            return;
        }

        if ($this->editingDistributionListId) {
            // Update existing
            $distributionList = DueDateEmail::findOrFail($this->editingDistributionListId);
            $distributionList->update([
                'email' => $this->email,
                'name' => $this->name,
                'description' => $this->description,
                'active' => $this->active,
                'updated_by' => auth()->id(),
            ]);

            session()->flash('message', 'Distribution list updated successfully.');
        } else {
            // Create new
            DueDateEmail::create([
                'email' => $this->email,
                'user_id' => null, // Distribution lists have no user_id
                'name' => $this->name,
                'description' => $this->description,
                'active' => $this->active,
                'preferences' => [
                    'reminder_intervals' => config('contracts.notifications.default_reminder_intervals'),
                    'receive_overdue_notifications' => true,
                    'receive_completion_notifications' => false,
                ],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            session()->flash('message', 'Distribution list added successfully.');
        }

        $this->closeDistributionListForm();
    }

    public function toggleActive($id)
    {
        $this->authorizeAction('contracts.manage_notifications');

        $distributionList = DueDateEmail::whereNull('user_id')->findOrFail($id);
        $distributionList->update([
            'active' => ! $distributionList->active,
            'updated_by' => auth()->id(),
        ]);

        session()->flash('message', 'Distribution list status updated.');
    }

    public function closeDistributionListForm()
    {
        $this->showDistributionListForm = false;
        $this->resetDistributionListForm();
    }

    protected function resetDistributionListForm()
    {
        $this->editingDistributionListId = null;
        $this->email = '';
        $this->name = '';
        $this->description = '';
        $this->active = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $users = [];
        $distributionLists = [];

        if ($this->activeTab === 'users') {
            // Get users with their email status
            $query = User::query()
                ->where('active', true)
                ->when($this->search, function ($q) {
                    $q->where(function ($query) {
                        $query->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('email', 'like', '%'.$this->search.'%');
                    });
                })
                ->orderBy('name');

            $users = $query->paginate(20);

            // Check which users are in due_date_emails
            $userIds = $users->pluck('id');
            $inNotificationSystem = DueDateEmail::whereIn('user_id', $userIds)->pluck('user_id')->toArray();

            foreach ($users as $user) {
                $user->in_notification_system = in_array($user->id, $inNotificationSystem);
            }

        } else {
            // Get distribution lists (emails with no user_id)
            $query = DueDateEmail::query()
                ->whereNull('user_id')
                ->when($this->search, function ($q) {
                    $q->where(function ($query) {
                        $query->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('email', 'like', '%'.$this->search.'%');
                    });
                })
                ->orderBy('name');

            $distributionLists = $query->paginate(20);

            // Get usage count for each
            foreach ($distributionLists as $list) {
                $list->usage_count = $list->contractReportDueDates()->count();
            }
        }

        return view('livewire.notification-recipients-management', [
            'users' => $users,
            'distributionLists' => $distributionLists,
            'canManage' => $this->can('contracts.manage_notifications'),
            'approvedDomains' => config('contracts.notifications.approved_domains', []),
        ]);
    }
}
