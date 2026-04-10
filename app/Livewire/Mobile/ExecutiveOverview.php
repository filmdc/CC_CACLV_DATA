<?php

namespace App\Livewire\Mobile;

use App\Models\ApprovalType;
use App\Models\PendingApproval;
use App\Models\Program;
use App\Traits\WithPermissionChecks;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ExecutiveOverview extends Component
{
    use WithPagination, WithPermissionChecks;

    public $filterStatus = 'all';
    public $filterProgram = '';
    public $filterType = '';
    public $search = '';
    public $groupBy = 'none';
    public $dateFrom = '';
    public $dateTo = '';
    public $showDateFilter = false;

    public function mount()
    {
        $this->authorizeAction('approvals.executive_view');
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilterStatus() { $this->resetPage(); }
    public function updatedFilterProgram() { $this->resetPage(); }
    public function updatedFilterType() { $this->resetPage(); }
    public function updatedGroupBy() { $this->resetPage(); }
    public function updatedDateFrom() { $this->resetPage(); }
    public function updatedDateTo() { $this->resetPage(); }

    public function toggleDateFilter()
    {
        $this->showDateFilter = ! $this->showDateFilter;
        if (! $this->showDateFilter) {
            $this->dateFrom = '';
            $this->dateTo = '';
            $this->resetPage();
        }
    }

    public function getSummaryStatsProperty()
    {
        $pending = PendingApproval::where('status', 'pending')->count();
        $approved = PendingApproval::where('status', 'approved')->count();
        $rejected = PendingApproval::where('status', 'rejected')->count();

        $avgResponseTime = PendingApproval::whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('submitted_at')
            ->whereNotNull('actioned_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, submitted_at, actioned_at)) as avg_hours'))
            ->value('avg_hours');

        $avgHours = round($avgResponseTime ?? 0, 1);
        if ($avgHours >= 24) {
            $avgDisplay = round($avgHours / 24, 1) . 'd';
        } else {
            $avgDisplay = $avgHours . 'h';
        }

        return [
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'avgResponseTime' => $avgDisplay,
        ];
    }

    protected function buildFilteredQuery()
    {
        $query = PendingApproval::with(['submittedBy', 'approver', 'program', 'approvalType']);

        if ($this->filterStatus && $this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        }
        if ($this->filterProgram) {
            $query->where('program_id', $this->filterProgram);
        }
        if ($this->filterType) {
            $query->where('approval_type_id', $this->filterType);
        }
        if ($this->dateFrom) {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }
        if ($this->dateTo) {
            $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', '%' . $search . '%')
                  ->orWhere('item_description', 'like', '%' . $search . '%')
                  ->orWhereHas('submittedBy', function ($uq) use ($search) {
                      $uq->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('approver', function ($uq) use ($search) {
                      $uq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function getGroupedDataProperty()
    {
        if ($this->groupBy === 'none') {
            return null;
        }

        $query = $this->buildFilteredQuery();
        $records = $query->get();

        return match ($this->groupBy) {
            'program' => $records->groupBy(fn ($item) => $item->program?->name ?? 'No Program'),
            'type' => $records->groupBy(fn ($item) => $item->approvalType?->display_name ?? 'Unknown Type'),
            'approver' => $records->groupBy(fn ($item) => $item->approver?->name ?? 'Unassigned'),
            'month' => $records->groupBy(fn ($item) => $item->created_at->format('F Y')),
            default => null,
        };
    }

    public function resetFilters()
    {
        $this->filterStatus = 'all';
        $this->filterProgram = '';
        $this->filterType = '';
        $this->search = '';
        $this->groupBy = 'none';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->showDateFilter = false;
        $this->resetPage();
    }

    public function render()
    {
        $approvalTypes = ApprovalType::orderBy('display_name')->get();
        $programs = Program::where('active', true)->orderBy('name')->get();

        $grouped = $this->groupedData;
        $items = $grouped === null ? $this->buildFilteredQuery()->paginate(25) : null;

        return view('livewire.mobile.executive-overview', [
            'summaryStats' => $this->summaryStats,
            'approvalTypes' => $approvalTypes,
            'programs' => $programs,
            'items' => $items,
            'grouped' => $grouped,
        ])->layout('components.layouts.approvals');
    }
}
