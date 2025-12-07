<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\Funder;
use App\Models\Program;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use App\Services\ContractUtilizationService;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ContractUtilizationDashboard extends Component
{
    use WithPagination, WithPermissionChecks;

    // Filter Properties
    public $search = '';

    public $filterProgram = '';

    public $filterFunder = '';

    public $filterStatus = '';

    public $filterUtilization = '';

    // View Settings
    public $viewMode = 'cards';

    public $sortBy = 'utilization_desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterProgram' => ['except' => ''],
        'filterFunder' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterUtilization' => ['except' => ''],
        'viewMode' => ['except' => 'cards'],
        'sortBy' => ['except' => 'utilization_desc'],
    ];

    public function mount()
    {
        $this->authorizeAction('contracts.view');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterProgram()
    {
        $this->resetPage();
    }

    public function updatedFilterFunder()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterUtilization()
    {
        $this->resetPage();
    }

    public function updatedSortBy()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterProgram = '';
        $this->filterFunder = '';
        $this->filterStatus = '';
        $this->filterUtilization = '';
        $this->sortBy = 'utilization_desc';
        $this->resetPage();
    }

    /**
     * Get system-wide aggregate metrics
     */
    public function getSystemMetrics(): array
    {
        return ContractUtilizationService::getSystemMetrics();
    }

    /**
     * Get contracts with calculated utilization data
     */
    public function getContractsWithUtilization()
    {
        $query = Contract::with(['program', 'funder'])
            ->withSum('projectBudgets', 'budgeted_amount')
            ->withSum('projectExpenses', 'amount')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('contract_number', 'like', '%'.$this->search.'%')
                        ->orWhere('grant_code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->filterProgram, fn ($q) => $q->where('program_id', $this->filterProgram))
            ->when($this->filterFunder, fn ($q) => $q->where('funder_id', $this->filterFunder))
            ->when($this->filterStatus, function ($query) {
                match ($this->filterStatus) {
                    'active' => $query->active()->where('start_date', '<=', now())->where(function ($q) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                    }),
                    'archived' => $query->archived(),
                    'expired' => $query->active()->where('end_date', '<', now()),
                    'upcoming' => $query->active()->where('start_date', '>', now()),
                    default => $query->active(),
                };
            })
            ->when(! $this->filterStatus, fn ($q) => $q->active())
            ->when($this->filterUtilization, function ($query) {
                // Use subquery for utilization filtering
                $query->where('amount', '>', 0);

                match ($this->filterUtilization) {
                    'critical' => $query->whereRaw(
                        '(amount - COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0)) / amount <= 0.10'
                    )->whereRaw(
                        'COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0) < amount'
                    ),
                    'warning' => $query->whereRaw(
                        '(amount - COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0)) / amount > 0.10'
                    )->whereRaw(
                        '(amount - COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0)) / amount <= 0.25'
                    ),
                    'caution' => $query->whereRaw(
                        '(amount - COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0)) / amount > 0.25'
                    )->whereRaw(
                        '(amount - COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0)) / amount <= 0.50'
                    ),
                    'healthy' => $query->whereRaw(
                        '(amount - COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0)) / amount > 0.50'
                    ),
                    'depleted' => $query->whereRaw(
                        'COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0) >= amount'
                    ),
                    default => $query,
                };
            });

        // Apply sorting
        match ($this->sortBy) {
            'utilization_desc' => $query->orderByRaw(
                'COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0) / NULLIF(contracts.amount, 0) DESC'
            ),
            'utilization_asc' => $query->orderByRaw(
                'COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0) / NULLIF(contracts.amount, 0) ASC'
            ),
            'amount_desc' => $query->orderBy('amount', 'desc'),
            'remaining_asc' => $query->orderByRaw(
                'contracts.amount - COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0) ASC'
            ),
            'title_asc' => $query->orderBy('title', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate(12);
    }

    /**
     * Calculate utilization percentage for a contract (percentage SPENT)
     */
    public function calculateUtilization($contract): float
    {
        $amount = (float) $contract->amount;
        $spent = (float) ($contract->project_expenses_sum_amount ?? 0);

        if ($amount <= 0) {
            return 0;
        }

        return round(($spent / $amount) * 100, 1);
    }

    /**
     * Get utilization color class based on percentage remaining
     */
    public function getUtilizationColorClass($contract): string
    {
        $amount = (float) $contract->amount;
        $spent = (float) ($contract->project_expenses_sum_amount ?? 0);
        $remaining = $amount - $spent;

        if ($amount <= 0) {
            return 'bg-gray-500';
        }

        $remainingPercentage = ($remaining / $amount) * 100;

        if ($remainingPercentage <= 0) {
            return 'bg-red-600';
        }

        if ($remainingPercentage < 5) {
            return 'bg-red-500';
        }

        if ($remainingPercentage < 10) {
            return 'bg-orange-500';
        }

        if ($remainingPercentage < 25) {
            return 'bg-yellow-500';
        }

        if ($remainingPercentage < 50) {
            return 'bg-yellow-400';
        }

        if ($remainingPercentage < 75) {
            return 'bg-green-400';
        }

        return 'bg-green-500';
    }

    /**
     * Get utilization status label
     */
    public function getUtilizationStatus($contract): string
    {
        $amount = (float) $contract->amount;
        $spent = (float) ($contract->project_expenses_sum_amount ?? 0);
        $remaining = $amount - $spent;

        if ($amount <= 0) {
            return 'No Budget';
        }

        $remainingPercentage = ($remaining / $amount) * 100;

        if ($remainingPercentage <= 0) {
            return 'Depleted';
        }

        if ($remainingPercentage < 10) {
            return 'Critical';
        }

        if ($remainingPercentage < 25) {
            return 'Warning';
        }

        if ($remainingPercentage < 50) {
            return 'Caution';
        }

        return 'Healthy';
    }

    /**
     * View contract details (redirect to contract show page)
     */
    public function viewContract($contractId)
    {
        return redirect()->route('contract.show', $contractId);
    }

    public function render()
    {
        return view('livewire.contract-utilization-dashboard', [
            'contracts' => $this->getContractsWithUtilization(),
            'systemMetrics' => $this->getSystemMetrics(),
            'programs' => Program::orderBy('name')->get(),
            'funders' => Funder::orderBy('name')->get(),
            'utilizationOptions' => [
                'critical' => 'Critical (< 10% remaining)',
                'warning' => 'Warning (10-25% remaining)',
                'caution' => 'Caution (25-50% remaining)',
                'healthy' => 'Healthy (> 50% remaining)',
                'depleted' => 'Depleted (100% used)',
            ],
            'statusOptions' => [
                'active' => 'Active',
                'archived' => 'Archived',
                'expired' => 'Expired',
                'upcoming' => 'Upcoming',
            ],
            'sortOptions' => [
                'utilization_desc' => 'Most Utilized First',
                'utilization_asc' => 'Least Utilized First',
                'amount_desc' => 'Highest Amount First',
                'remaining_asc' => 'Least Remaining First',
                'title_asc' => 'Title (A-Z)',
            ],
        ])->layout('components.layouts.app');
    }
}
