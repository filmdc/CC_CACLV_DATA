<?php

namespace App\Livewire;

use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Models\User;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FeedbackManagement extends Component
{
    use WithPagination;
    use WithPermissionChecks;

    // Search and Filters
    public $search = '';

    public $categoryFilter = '';

    public $statusFilter = '';

    public $priorityFilter = '';

    public $assigneeFilter = '';

    // View/Edit Modal
    public $showViewModal = false;

    public $showEditModal = false;

    public $selectedFeedback = null;

    // Edit form fields
    public $editStatus = '';

    public $editPriority = '';

    public $editAssignedTo = '';

    public $editAdminNotes = '';

    public $editResolutionNotes = '';

    // Comment form
    public $newComment = '';

    public $commentIsInternal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'priorityFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $this->authorizeAction('feedback.view_all');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter()
    {
        $this->resetPage();
    }

    public function updatedAssigneeFilter()
    {
        $this->resetPage();
    }

    public function viewFeedback($id)
    {
        $this->selectedFeedback = Feedback::with(['user', 'assignedTo', 'resolvedBy', 'comments.user'])->findOrFail($id);
        $this->showViewModal = true;
        $this->showEditModal = false;
    }

    public function editFeedback($id)
    {
        $this->authorizeAction('feedback.manage');
        $this->selectedFeedback = Feedback::with(['user', 'assignedTo', 'resolvedBy', 'comments.user'])->findOrFail($id);

        $this->editStatus = $this->selectedFeedback->status;
        $this->editPriority = $this->selectedFeedback->priority;
        $this->editAssignedTo = $this->selectedFeedback->assigned_to ?? '';
        $this->editAdminNotes = $this->selectedFeedback->admin_notes ?? '';
        $this->editResolutionNotes = $this->selectedFeedback->resolution_notes ?? '';

        $this->showEditModal = true;
        $this->showViewModal = false;
    }

    public function updateFeedback()
    {
        $this->authorizeAction('feedback.manage');

        $this->validate([
            'editStatus' => 'required|in:new,acknowledged,in_progress,resolved,closed,wont_fix',
            'editPriority' => 'required|in:low,medium,high,critical',
            'editAssignedTo' => 'nullable|exists:users,id',
            'editAdminNotes' => 'nullable|string|max:5000',
            'editResolutionNotes' => 'nullable|string|max:5000',
        ]);

        $oldStatus = $this->selectedFeedback->status;

        $updateData = [
            'status' => $this->editStatus,
            'priority' => $this->editPriority,
            'assigned_to' => $this->editAssignedTo !== '' ? $this->editAssignedTo : null,
            'admin_notes' => $this->editAdminNotes !== '' ? $this->editAdminNotes : null,
            'resolution_notes' => $this->editResolutionNotes !== '' ? $this->editResolutionNotes : null,
        ];

        // If status changed to resolved, track resolver
        if ($this->editStatus === 'resolved' && $oldStatus !== 'resolved') {
            $updateData['resolved_by'] = Auth::id();
            $updateData['resolved_at'] = now();
        }

        $this->selectedFeedback->update($updateData);

        // Add system comment if status changed
        if ($oldStatus !== $this->editStatus) {
            FeedbackComment::create([
                'feedback_id' => $this->selectedFeedback->id,
                'user_id' => Auth::id(),
                'comment' => "Status changed from '{$oldStatus}' to '{$this->editStatus}'",
                'is_internal' => true,
            ]);
        }

        session()->flash('success', 'Feedback updated successfully.');
        $this->closeModals();
    }

    public function quickStatusUpdate($id, $status)
    {
        $this->authorizeAction('feedback.manage');

        $feedback = Feedback::findOrFail($id);
        $oldStatus = $feedback->status;

        $updateData = ['status' => $status];

        if ($status === 'resolved' && $oldStatus !== 'resolved') {
            $updateData['resolved_by'] = Auth::id();
            $updateData['resolved_at'] = now();
        }

        $feedback->update($updateData);

        // Add system comment
        FeedbackComment::create([
            'feedback_id' => $feedback->id,
            'user_id' => Auth::id(),
            'comment' => "Status changed from '{$oldStatus}' to '{$status}'",
            'is_internal' => true,
        ]);

        session()->flash('success', 'Status updated.');
    }

    public function addComment()
    {
        $this->validate([
            'newComment' => 'required|min:2|max:2000',
        ]);

        FeedbackComment::create([
            'feedback_id' => $this->selectedFeedback->id,
            'user_id' => Auth::id(),
            'comment' => $this->newComment,
            'is_internal' => $this->commentIsInternal,
        ]);

        // Refresh the feedback with comments
        $this->selectedFeedback = Feedback::with(['user', 'assignedTo', 'resolvedBy', 'comments.user'])->findOrFail($this->selectedFeedback->id);

        $this->newComment = '';
        $this->commentIsInternal = false;

        session()->flash('comment-success', 'Comment added.');
    }

    public function deleteFeedback($id)
    {
        $this->authorizeAction('feedback.delete');
        Feedback::findOrFail($id)->delete();
        session()->flash('success', 'Feedback deleted.');
        $this->closeModals();
    }

    public function closeModals()
    {
        $this->showViewModal = false;
        $this->showEditModal = false;
        $this->selectedFeedback = null;
        $this->newComment = '';
        $this->commentIsInternal = false;
    }

    public function render()
    {
        $query = Feedback::with(['user', 'assignedTo'])
            ->orderBy('created_at', 'desc');

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->categoryFilter) {
            $query->where('category', $this->categoryFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }

        if ($this->assigneeFilter === 'unassigned') {
            $query->unassigned();
        } elseif ($this->assigneeFilter) {
            $query->assignedTo($this->assigneeFilter);
        }

        $feedbacks = $query->paginate(15);

        // Stats
        $newCount = Feedback::new()->count();
        $openCount = Feedback::open()->count();
        $bugCount = Feedback::bugs()->open()->count();
        $featureCount = Feedback::features()->open()->count();

        // Users for assignment dropdown
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Administrator', 'project_manager']);
        })->orderBy('name')->get();

        return view('livewire.feedback-management', [
            'feedbacks' => $feedbacks,
            'newCount' => $newCount,
            'openCount' => $openCount,
            'bugCount' => $bugCount,
            'featureCount' => $featureCount,
            'users' => $users,
        ])->layout('components.layouts.app');
    }
}
