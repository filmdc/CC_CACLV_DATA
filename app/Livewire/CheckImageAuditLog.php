<?php

namespace App\Livewire;

use App\Models\CheckImageAuditLog as AuditLogModel;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class CheckImageAuditLog extends Component
{
    use WithPagination;

    public $search = '';
    public $actionFilter = '';
    public $userFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'actionFilter' => ['except' => ''],
        'userFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount()
    {
        // Check if user is admin
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized - Admin access only');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingActionFilter()
    {
        $this->resetPage();
    }

    public function updatingUserFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->actionFilter = '';
        $this->userFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function render()
    {
        $query = AuditLogModel::with('user')
            ->orderBy('created_at', 'desc');

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('filename', 'like', '%' . $this->search . '%')
                  ->orWhere('gift_id', 'like', '%' . $this->search . '%')
                  ->orWhere('ip_address', 'like', '%' . $this->search . '%');
            });
        }

        // Apply action filter
        if (!empty($this->actionFilter)) {
            $query->where('action', $this->actionFilter);
        }

        // Apply user filter
        if (!empty($this->userFilter)) {
            $query->where('user_id', $this->userFilter);
        }

        // Apply date range filters
        if (!empty($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $logs = $query->paginate($this->perPage);

        // Get unique users who have audit log entries
        $users = User::whereIn('id', function ($query) {
            $query->select('user_id')
                ->from('check_image_audit_logs')
                ->whereNotNull('user_id')
                ->distinct();
        })->orderBy('name')->get();

        // Get action statistics
        $actionStats = AuditLogModel::selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->get()
            ->pluck('count', 'action')
            ->toArray();

        return view('livewire.check-image-audit-log', [
            'logs' => $logs,
            'users' => $users,
            'actionStats' => $actionStats,
        ]);
    }
}
