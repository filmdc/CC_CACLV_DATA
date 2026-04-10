<?php

namespace App\Livewire\Mobile;

use App\Models\ApprovalSubmission;
use App\Models\ApprovalType;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MySubmissions extends Component
{
    use WithPagination;

    public $filterStatus = 'all';
    public $search = '';

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = ApprovalSubmission::where('submitted_by', Auth::id())
            ->with(['approvalType', 'program', 'approvedBy', 'media']);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->search) {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        $submissions = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('livewire.mobile.my-submissions', [
            'submissions' => $submissions,
        ])->layout('components.layouts.approvals');
    }
}
