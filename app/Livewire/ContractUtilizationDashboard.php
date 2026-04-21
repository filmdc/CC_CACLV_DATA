<?php

namespace App\Livewire;

use App\Jobs\SyncFundExpensesJob;
use App\Models\Contract;
use App\Models\Funder;
use App\Models\Program;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use App\Models\SageIntacctSetting;
use App\Models\SiteSetting;
use App\Services\ContractUtilizationService;
use App\Services\FiscalYearService;
use App\Traits\WithPermissionChecks;
use Carbon\Carbon;
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

    // Intacct sync window (defaults to current fiscal year)
    public $dateFrom = '';

    public $dateTo = '';

    public $syncDispatched = false;

    // Transactions drill-down modal state
    public $showTransactionsModal = false;

    public $transactionsContractId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterProgram' => ['except' => ''],
        'filterFunder' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterUtilization' => ['except' => ''],
        'viewMode' => ['except' => 'cards'],
        'sortBy' => ['except' => 'utilization_desc'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount()
    {
        $this->authorizeAction('contracts.view');

        if (! $this->dateFrom || ! $this->dateTo) {
            [$from, $to] = FiscalYearService::currentWindow();
            $this->dateFrom = $this->dateFrom ?: $from->toDateString();
            $this->dateTo = $this->dateTo ?: $to->toDateString();
        }
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    /**
     * Resolve the PHP CLI binary. Under Apache/mod_php, PHP_BINARY points at
     * the Apache binary and is useless for spawning a CLI process, so fall
     * back to common Debian/Ubuntu install paths.
     */
    private function resolvePhpCliBinary(): string
    {
        if (defined('PHP_BINARY') && PHP_BINARY && is_executable(PHP_BINARY) && str_contains(PHP_BINARY, 'php')) {
            return PHP_BINARY;
        }

        foreach (['/usr/bin/php', '/usr/local/bin/php', '/usr/bin/php8.3', '/usr/bin/php8.2', '/usr/bin/php8.1'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Could not locate PHP CLI binary. Set a valid path or install /usr/bin/php.');
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function resetDateRange()
    {
        [$from, $to] = FiscalYearService::currentWindow();
        $this->dateFrom = $from->toDateString();
        $this->dateTo = $to->toDateString();
        $this->resetPage();
    }

    /**
     * Trigger an Intacct sync for the currently-filtered contract list as a
     * background artisan process (no queue worker required).
     */
    public function syncFromIntacct()
    {
        $this->authorizeAction('contracts.edit');

        $setting = SageIntacctSetting::getActive();

        if (! $setting || ! $setting->isConfigured() || ! $setting->isXmlApi()) {
            session()->flash('error', 'Sage Intacct is not configured for XML API access.');

            return;
        }

        if (SiteSetting::get('intacct_expense_sync_enabled', '1') !== '1') {
            session()->flash('error', 'Intacct fund expense sync is disabled in site settings.');

            return;
        }

        // Scope to the contracts visible on the current page so users can
        // trigger narrow re-syncs from filtered views without waiting on the
        // full ~300-contract run.
        $contractIds = $this->getContractsWithUtilization()->pluck('id')->all();

        if (empty($contractIds)) {
            session()->flash('error', 'No contracts match the current filters — nothing to sync.');

            return;
        }

        $args = [
            $this->resolvePhpCliBinary(),
            base_path('artisan'),
            'intacct:sync-fund-expenses',
            '--force',
            '--contract-ids='.implode(',', $contractIds),
        ];

        if ($this->dateFrom) {
            $args[] = '--from='.$this->dateFrom;
        }
        if ($this->dateTo) {
            $args[] = '--to='.$this->dateTo;
        }

        $process = new \Symfony\Component\Process\Process($args);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(null);
        $process->disableOutput();
        $process->start();

        $this->syncDispatched = true;
        session()->flash('message', 'Intacct sync started in the background for '.count($contractIds).' contract(s). This typically takes 1–5 minutes depending on GL volume. Refresh to see updated totals.');
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
        $from = $this->dateFrom ?: null;
        $to = $this->dateTo ?: null;

        $query = Contract::with(['program', 'funder'])
            ->withSum('projectBudgets', 'budgeted_amount')
            ->withSum('projectExpenses', 'amount')
            ->withSum(['intacctFundExpenses as intacct_expenses_sum_amount' => function ($q) use ($from, $to) {
                if ($from && $to) {
                    $q->whereBetween('posting_date', [$from, $to]);
                }
            }], 'amount')
            ->withCount(['intacctFundExpenses as intacct_expenses_count' => function ($q) use ($from, $to) {
                if ($from && $to) {
                    $q->whereBetween('posting_date', [$from, $to]);
                }
            }])
            ->withMax('intacctFundExpenses as intacct_last_synced_at', 'last_synced_at')
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
                    'all' => $query,
                    default => $query->active(),
                };
            })
            ->when(! $this->filterStatus, fn ($q) => $q->active())
            ->when($this->filterUtilization, function ($query) use ($from, $to) {
                // Effective spent = Intacct sum when any synced lines exist in
                // the window, otherwise fall back to manual project_expenses
                // sum. Keep this SQL in sync with getEffectiveSpent() in PHP.
                $windowed = $from && $to;

                $spentExpr = $windowed
                    ? '(CASE
                        WHEN (SELECT COUNT(*) FROM intacct_fund_expenses WHERE contract_id = contracts.id AND posting_date BETWEEN ? AND ?) > 0
                        THEN COALESCE((SELECT SUM(amount) FROM intacct_fund_expenses WHERE contract_id = contracts.id AND posting_date BETWEEN ? AND ?), 0)
                        ELSE COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0)
                       END)'
                    : '(CASE
                        WHEN (SELECT COUNT(*) FROM intacct_fund_expenses WHERE contract_id = contracts.id) > 0
                        THEN COALESCE((SELECT SUM(amount) FROM intacct_fund_expenses WHERE contract_id = contracts.id), 0)
                        ELSE COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0)
                       END)';

                // Placeholders per $spentExpr occurrence (4 when windowed, 0 otherwise)
                $b = $windowed ? [$from, $to, $from, $to] : [];

                $query->where('amount', '>', 0);

                match ($this->filterUtilization) {
                    'critical' => $query
                        ->whereRaw("(amount - {$spentExpr}) / amount <= 0.10", $b)
                        ->whereRaw("{$spentExpr} < amount", $b),
                    'warning' => $query
                        ->whereRaw("(amount - {$spentExpr}) / amount > 0.10", $b)
                        ->whereRaw("(amount - {$spentExpr}) / amount <= 0.25", $b),
                    'caution' => $query
                        ->whereRaw("(amount - {$spentExpr}) / amount > 0.25", $b)
                        ->whereRaw("(amount - {$spentExpr}) / amount <= 0.50", $b),
                    'healthy' => $query->whereRaw("(amount - {$spentExpr}) / amount > 0.50", $b),
                    'depleted' => $query->whereRaw("{$spentExpr} >= amount", $b),
                    default => $query,
                };
            });

        // Build SQL expression for effective spent (Intacct when synced, else manual).
        // Kept consistent with getEffectiveSpent() in PHP so sort order matches
        // what users see on cards/rows.
        $sortBindings = [];
        if ($from && $to) {
            $effectiveSpentSql = '(CASE
                WHEN (SELECT COUNT(*) FROM intacct_fund_expenses WHERE contract_id = contracts.id AND posting_date BETWEEN ? AND ?) > 0
                THEN COALESCE((SELECT SUM(amount) FROM intacct_fund_expenses WHERE contract_id = contracts.id AND posting_date BETWEEN ? AND ?), 0)
                ELSE COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0)
               END)';
            $sortBindings = [$from, $to, $from, $to];
        } else {
            $effectiveSpentSql = '(CASE
                WHEN (SELECT COUNT(*) FROM intacct_fund_expenses WHERE contract_id = contracts.id) > 0
                THEN COALESCE((SELECT SUM(amount) FROM intacct_fund_expenses WHERE contract_id = contracts.id), 0)
                ELSE COALESCE((SELECT SUM(amount) FROM project_expenses WHERE contract_id = contracts.id), 0)
               END)';
        }

        // Apply sorting
        match ($this->sortBy) {
            'utilization_desc' => $query->orderByRaw(
                "{$effectiveSpentSql} / NULLIF(contracts.amount, 0) DESC",
                $sortBindings
            ),
            'utilization_asc' => $query->orderByRaw(
                "{$effectiveSpentSql} / NULLIF(contracts.amount, 0) ASC",
                $sortBindings
            ),
            'amount_desc' => $query->orderBy('amount', 'desc'),
            'remaining_asc' => $query->orderByRaw(
                "contracts.amount - {$effectiveSpentSql} ASC",
                $sortBindings
            ),
            'title_asc' => $query->orderBy('title', 'asc'),
            'id_desc' => $query->orderBy('id', 'desc'),
            'id_asc' => $query->orderBy('id', 'asc'),
            'created_desc' => $query->orderBy('created_at', 'desc'),
            'created_asc' => $query->orderBy('created_at', 'asc'),
            'start_date_desc' => $query->orderByRaw('start_date IS NULL, start_date DESC'),
            'start_date_asc' => $query->orderByRaw('start_date IS NULL, start_date ASC'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate(12);
    }

    /**
     * Resolve the effective spent amount for a contract. When Intacct has any
     * synced expense lines, those are the source of truth; otherwise we fall
     * back to the manually-entered project_expenses total.
     */
    public function getEffectiveSpent($contract): float
    {
        if ((int) ($contract->intacct_expenses_count ?? 0) > 0) {
            return (float) ($contract->intacct_expenses_sum_amount ?? 0);
        }

        return (float) ($contract->project_expenses_sum_amount ?? 0);
    }

    /**
     * Calculate utilization percentage for a contract (percentage SPENT)
     */
    public function calculateUtilization($contract): float
    {
        $amount = (float) $contract->amount;
        $spent = $this->getEffectiveSpent($contract);

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
        $spent = $this->getEffectiveSpent($contract);
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
        $spent = $this->getEffectiveSpent($contract);
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

    /**
     * Open the transactions drill-down modal for a specific contract.
     * Shows whichever source is currently driving the "Spent" figure: if
     * Intacct has any synced lines in the window those are displayed,
     * otherwise the manual project_expenses rows.
     */
    public function viewTransactions($contractId)
    {
        $this->transactionsContractId = $contractId;
        $this->showTransactionsModal = true;
    }

    public function closeTransactionsModal()
    {
        $this->showTransactionsModal = false;
        $this->transactionsContractId = null;
    }

    /**
     * Stream a CSV download of the transactions currently shown in the modal.
     */
    public function exportTransactions()
    {
        $payload = $this->getTransactionsPayload();

        if (! $payload['contract']) {
            session()->flash('error', 'No contract selected for export.');
            return;
        }

        $contract = $payload['contract'];
        $source = $payload['source'];
        $lines = $payload['lines'];

        $slug = \Illuminate\Support\Str::slug(
            ($contract->grant_code ?: 'contract').'-'.$contract->id.
            ($contract->service_code ? '-'.$contract->service_code : '')
        );
        $windowPart = ($this->dateFrom && $this->dateTo)
            ? '-'.$this->dateFrom.'-to-'.$this->dateTo
            : '';
        $filename = "utilization-transactions-{$slug}{$windowPart}.csv";

        return response()->streamDownload(function () use ($contract, $source, $lines, $payload) {
            $out = fopen('php://output', 'w');

            // Metadata header rows to make exports self-documenting
            fputcsv($out, ['Contract', $contract->title]);
            fputcsv($out, ['Contract ID', $contract->id]);
            fputcsv($out, ['Fund Code', $contract->grant_code ?? '']);
            fputcsv($out, ['Service Code', $contract->service_code ?? '']);
            fputcsv($out, ['Program', $contract->program?->name ?? '']);
            fputcsv($out, ['Funder', $contract->funder?->name ?? '']);
            fputcsv($out, ['Awarded Amount', number_format((float) $contract->amount, 2, '.', '')]);
            fputcsv($out, ['Window Start', $this->dateFrom ?: '(all time)']);
            fputcsv($out, ['Window End', $this->dateTo ?: '(all time)']);
            fputcsv($out, ['Source', $source === 'intacct' ? 'Sage Intacct GL (GLENTRY)' : 'Manual entry (project_expenses)']);
            fputcsv($out, ['Exported At', now()->toDateTimeString()]);
            fputcsv($out, ['Total', number_format((float) $payload['total'], 2, '.', '')]);
            fputcsv($out, []);

            // Column headers vary by source
            if ($source === 'intacct') {
                fputcsv($out, ['Posting Date', 'GL Account', 'Account Title', 'Description', 'Document #', 'Intacct Record #', 'Debit', 'Credit', 'Signed Amount']);
                foreach ($lines as $line) {
                    $debit = (float) $line->amount > 0 ? (float) $line->amount : 0;
                    $credit = (float) $line->amount < 0 ? abs((float) $line->amount) : 0;
                    fputcsv($out, [
                        $line->posting_date?->toDateString() ?? '',
                        $line->gl_account ?? '',
                        $line->gl_account_title ?? '',
                        $line->description ?? '',
                        $line->document_no ?? '',
                        $line->intacct_record_no ?? '',
                        number_format($debit, 2, '.', ''),
                        number_format($credit, 2, '.', ''),
                        number_format((float) $line->amount, 2, '.', ''),
                    ]);
                }
            } else {
                fputcsv($out, ['Expense Date', 'Category', 'Description', 'Amount']);
                foreach ($lines as $line) {
                    fputcsv($out, [
                        $line->expense_date?->toDateString() ?? '',
                        $line->category ?? '',
                        $line->description ?? '',
                        number_format((float) $line->amount, 2, '.', ''),
                    ]);
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Build the transactions payload for the modal (contract + lines + source).
     *
     * @return array{contract: ?\App\Models\Contract, source: string, lines: \Illuminate\Support\Collection, total: float, manual_also_present?: bool}
     */
    public function getTransactionsPayload(): array
    {
        if (! $this->transactionsContractId) {
            return ['contract' => null, 'source' => 'none', 'lines' => collect(), 'total' => 0.0];
        }

        $contract = Contract::with(['program', 'funder'])->find($this->transactionsContractId);

        if (! $contract) {
            return ['contract' => null, 'source' => 'none', 'lines' => collect(), 'total' => 0.0];
        }

        $from = $this->dateFrom ?: null;
        $to = $this->dateTo ?: null;

        $intacctLines = $contract->intacctFundExpenses()
            ->when($from && $to, fn ($q) => $q->whereBetween('posting_date', [$from, $to]))
            ->orderByDesc('posting_date')
            ->orderBy('id')
            ->get();

        if ($intacctLines->isNotEmpty()) {
            return [
                'contract' => $contract,
                'source' => 'intacct',
                'lines' => $intacctLines,
                'total' => (float) $intacctLines->sum('amount'),
                'manual_also_present' => $contract->projectExpenses()->exists(),
            ];
        }

        $manualLines = $contract->projectExpenses()
            ->orderByDesc('expense_date')
            ->orderBy('id')
            ->get();

        return [
            'contract' => $contract,
            'source' => 'manual',
            'lines' => $manualLines,
            'total' => (float) $manualLines->sum('amount'),
        ];
    }

    public function render()
    {
        return view('livewire.contract-utilization-dashboard', [
            'contracts' => $this->getContractsWithUtilization(),
            'systemMetrics' => $this->getSystemMetrics(),
            'transactionsPayload' => $this->showTransactionsModal ? $this->getTransactionsPayload() : null,
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
                'archived' => 'Archived Only',
                'expired' => 'Expired',
                'upcoming' => 'Upcoming',
                'all' => 'All (Including Archived)',
            ],
            'sortOptions' => [
                'utilization_desc' => 'Most Utilized First',
                'utilization_asc' => 'Least Utilized First',
                'amount_desc' => 'Highest Amount First',
                'remaining_asc' => 'Least Remaining First',
                'title_asc' => 'Title (A-Z)',
                'id_desc' => 'Contract ID (Newest First)',
                'id_asc' => 'Contract ID (Oldest First)',
                'created_desc' => 'Date Created (Newest First)',
                'created_asc' => 'Date Created (Oldest First)',
                'start_date_desc' => 'Start Date (Newest First)',
                'start_date_asc' => 'Start Date (Oldest First)',
            ],
        ])->layout('components.layouts.app');
    }
}
