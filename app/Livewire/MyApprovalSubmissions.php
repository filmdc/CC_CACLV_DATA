<?php

namespace App\Livewire;

use App\Models\ApprovalSubmission;
use App\Models\ApprovalType;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyApprovalSubmissions extends Component
{
    use WithPagination, WithPermissionChecks;

    public $filterStatus = 'all';

    public $filterType = '';

    public $search = '';

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterType()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function getSubmissionsProperty()
    {
        $query = ApprovalSubmission::where('submitted_by', Auth::id())
            ->with(['approvalType', 'program', 'approvedBy']);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterType) {
            $query->where('approval_type_id', $this->filterType);
        }

        if ($this->search) {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function render()
    {
        $approvalTypes = ApprovalType::active()
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->get();

        return view('livewire.my-approval-submissions', [
            'submissions' => $this->submissions,
            'approvalTypes' => $approvalTypes,
        ])->layout('components.layouts.app');
    }
}
