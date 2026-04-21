<?php

namespace App\Livewire;

use App\Jobs\SyncFundExpensesJob;
use App\Models\Contract;
use App\Models\Funder;
use App\Models\IntacctFundBudget;
use App\Models\IntacctFundExpense;
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

        // Full fund-keyed sync. Enumerates every PROJECTID with activity in
        // the window from both our contracts table and Intacct's GL, so
        // Sage-only funds also populate. Takes 5–15 minutes for ~100+ funds.
        $args = [
            $this->resolvePhpCliBinary(),
            base_path('artisan'),
            'intacct:sync-fund-expenses',
            '--force',
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
        session()->flash('message', 'Fund sync started in the background. Syncs all funds with activity in this window from Intacct (including Sage-only funds). Typically takes 5–15 minutes. Refresh periodically to see totals populate.');
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
                // sum. Effective budget = intacct_budget_amount when synced,
                // else contracts.amount. Keep these SQL expressions in sync
                // with getEffectiveSpent() / getEffectiveBudget() in PHP.
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

                $budgetExpr = '(CASE WHEN contracts.intacct_budget_amount IS NOT NULL AND contracts.intacct_budget_amount > 0 THEN contracts.intacct_budget_amount ELSE contracts.amount END)';

                // Placeholders per $spentExpr occurrence (4 when windowed, 0 otherwise)
                $b = $windowed ? [$from, $to, $from, $to] : [];

                $query->whereRaw("{$budgetExpr} > 0");

                match ($this->filterUtilization) {
                    'critical' => $query
                        ->whereRaw("({$budgetExpr} - {$spentExpr}) / {$budgetExpr} <= 0.10", $b)
                        ->whereRaw("{$spentExpr} < {$budgetExpr}", $b),
                    'warning' => $query
                        ->whereRaw("({$budgetExpr} - {$spentExpr}) / {$budgetExpr} > 0.10", $b)
                        ->whereRaw("({$budgetExpr} - {$spentExpr}) / {$budgetExpr} <= 0.25", $b),
                    'caution' => $query
                        ->whereRaw("({$budgetExpr} - {$spentExpr}) / {$budgetExpr} > 0.25", $b)
                        ->whereRaw("({$budgetExpr} - {$spentExpr}) / {$budgetExpr} <= 0.50", $b),
                    'healthy' => $query->whereRaw("({$budgetExpr} - {$spentExpr}) / {$budgetExpr} > 0.50", $b),
                    'depleted' => $query->whereRaw("{$spentExpr} >= {$budgetExpr}", $b),
                    default => $query,
                };
            });

        // SQL expressions for effective spent + effective budget, mirrored from
        // getEffectiveSpent() / getEffectiveBudget() so sort ranks match what
        // users see on cards/rows.
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
        $effectiveBudgetSql = '(CASE WHEN contracts.intacct_budget_amount IS NOT NULL AND contracts.intacct_budget_amount > 0 THEN contracts.intacct_budget_amount ELSE contracts.amount END)';

        // Apply sorting
        match ($this->sortBy) {
            'utilization_desc' => $query->orderByRaw(
                "{$effectiveSpentSql} / NULLIF({$effectiveBudgetSql}, 0) DESC",
                $sortBindings
            ),
            'utilization_asc' => $query->orderByRaw(
                "{$effectiveSpentSql} / NULLIF({$effectiveBudgetSql}, 0) ASC",
                $sortBindings
            ),
            'amount_desc' => $query->orderByRaw("{$effectiveBudgetSql} DESC"),
            'remaining_asc' => $query->orderByRaw(
                "{$effectiveBudgetSql} - {$effectiveSpentSql} ASC",
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
     * Resolve the effective budget (utilization denominator). Intacct budget
     * takes priority when synced; otherwise fall back to the contract's
     * awarded amount.
     */
    public function getEffectiveBudget($contract): float
    {
        $intacctBudget = (float) ($contract->intacct_budget_amount ?? 0);

        if ($intacctBudget > 0) {
            return $intacctBudget;
        }

        return (float) $contract->amount;
    }

    /**
     * Calculate utilization percentage for a contract (percentage SPENT)
     */
    public function calculateUtilization($contract): float
    {
        $budget = $this->getEffectiveBudget($contract);
        $spent = $this->getEffectiveSpent($contract);

        if ($budget <= 0) {
            return 0;
        }

        return round(($spent / $budget) * 100, 1);
    }

    /**
     * Get utilization color class based on percentage remaining
     */
    public function getUtilizationColorClass($contract): string
    {
        $budget = $this->getEffectiveBudget($contract);
        $spent = $this->getEffectiveSpent($contract);
        $remaining = $budget - $spent;

        if ($budget <= 0) {
            return 'bg-gray-500';
        }

        $remainingPercentage = ($remaining / $budget) * 100;

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
        $budget = $this->getEffectiveBudget($contract);
        $spent = $this->getEffectiveSpent($contract);
        $remaining = $budget - $spent;

        if ($budget <= 0) {
            return 'No Budget';
        }

        $remainingPercentage = ($remaining / $budget) * 100;

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
        $this->transactionsFundProjectId = null;
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
        // Fund-level drill-down (from Funds view)
        if ($this->transactionsFundProjectId) {
            return $this->buildFundTransactionsPayload($this->transactionsFundProjectId);
        }

        if (! $this->transactionsContractId) {
            return ['contract' => null, 'source' => 'none', 'lines' => collect(), 'total' => 0.0];
        }

        $contract = Contract::with(['program', 'funder'])->find($this->transactionsContractId);

        if (! $contract) {
            return ['contract' => null, 'source' => 'none', 'lines' => collect(), 'total' => 0.0];
        }

        $from = $this->dateFrom ?: null;
        $to = $this->dateTo ?: null;

        // Pull Intacct lines for this contract's fund using the fund-keyed
        // table directly — join on project_id/dept_id rather than contract_id
        // so shared funds with null contract_id still surface.
        $projectId = trim((string) $contract->grant_code);
        $deptId = trim((string) ($contract->service_code ?? ''));

        $intacctQuery = IntacctFundExpense::where('intacct_project_id', $projectId);
        if ($deptId !== '') {
            $intacctQuery->where('intacct_department_id', $deptId);
        }
        if ($from && $to) {
            $intacctQuery->whereBetween('posting_date', [$from, $to]);
        }
        $intacctLines = $intacctQuery->orderByDesc('posting_date')->orderBy('id')->get();

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

    /**
     * Build the transactions drill-down for a fund (all lines for the
     * project_id across any departments + any contract association).
     */
    private function buildFundTransactionsPayload(string $projectId): array
    {
        $from = $this->dateFrom ?: null;
        $to = $this->dateTo ?: null;

        $q = IntacctFundExpense::where('intacct_project_id', $projectId);
        if ($from && $to) {
            $q->whereBetween('posting_date', [$from, $to]);
        }
        $lines = $q->orderByDesc('posting_date')->orderBy('id')->get();

        $projectName = IntacctFundBudget::where('intacct_project_id', $projectId)->value('intacct_project_name')
            ?? $lines->first()?->intacct_project_name;

        // Synthesize a contract-like shell so the existing modal markup works
        // unchanged — title/grant_code used for display + CSV filename.
        $contract = new Contract([
            'title' => ($projectName ?: 'Fund '.$projectId),
            'grant_code' => $projectId,
            'service_code' => null,
            'amount' => IntacctFundBudget::where('intacct_project_id', $projectId)->sum('amount'),
        ]);
        $contract->id = 'fund-'.$projectId;

        return [
            'contract' => $contract,
            'source' => 'intacct',
            'lines' => $lines,
            'total' => (float) $lines->sum('amount'),
            'is_fund_view' => true,
        ];
    }

    /**
     * Fund-level aggregation for the "Funds" view mode. Groups
     * intacct_fund_expenses by project_id (summed across all departments),
     * joins budget + linked contracts, and returns a paginator-like
     * collection with the same filters as the contract view.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getFundsWithUtilization()
    {
        $from = $this->dateFrom ?: null;
        $to = $this->dateTo ?: null;

        // Union project_ids that appear in either expenses (within window) or
        // budgets so we capture Sage-only funds plus budget-only funds.
        $expenseQuery = IntacctFundExpense::query()
            ->select('intacct_project_id')
            ->groupBy('intacct_project_id');
        if ($from && $to) {
            $expenseQuery->whereBetween('posting_date', [$from, $to]);
        }
        $expenseProjects = $expenseQuery->pluck('intacct_project_id');

        $budgetProjects = IntacctFundBudget::query()
            ->select('intacct_project_id')
            ->distinct()
            ->pluck('intacct_project_id');

        $allProjects = $expenseProjects->merge($budgetProjects)->unique()->values();

        // Search / filter by project_id or linked contract's title/funder/program
        if ($this->search) {
            $s = strtolower($this->search);
            $matchedViaContracts = Contract::where(function ($q) use ($s) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$s}%"])
                    ->orWhereRaw('LOWER(contract_number) LIKE ?', ["%{$s}%"])
                    ->orWhereRaw('LOWER(grant_code) LIKE ?', ["%{$s}%"]);
            })->pluck('grant_code')->filter()->map(fn ($c) => trim($c))->unique();

            $allProjects = $allProjects->filter(function ($pid) use ($s, $matchedViaContracts) {
                return str_contains(strtolower($pid), $s) || $matchedViaContracts->contains($pid);
            })->values();
        }

        // Build fund rows
        $rows = $allProjects->map(function ($projectId) use ($from, $to) {
            $expQuery = IntacctFundExpense::where('intacct_project_id', $projectId);
            if ($from && $to) {
                $expQuery->whereBetween('posting_date', [$from, $to]);
            }
            $spent = (float) $expQuery->sum('amount');
            $expenseLinesCount = (int) $expQuery->count();
            $lastSyncedAt = $expQuery->max('last_synced_at');

            $budget = (float) IntacctFundBudget::where('intacct_project_id', $projectId)->sum('amount');
            $budgetTuples = (int) IntacctFundBudget::where('intacct_project_id', $projectId)->count();
            $budgetRow = IntacctFundBudget::where('intacct_project_id', $projectId)->first();

            $projectName = $budgetRow?->intacct_project_name
                ?? IntacctFundExpense::where('intacct_project_id', $projectId)->value('intacct_project_name');

            $contracts = Contract::where('grant_code', $projectId)->get(['id', 'title', 'service_code', 'archived', 'program_id', 'funder_id']);

            return (object) [
                'project_id' => $projectId,
                'project_name' => $projectName,
                'spent' => $spent,
                'budget' => $budget,
                'remaining' => $budget - $spent,
                'utilization' => $budget > 0 ? round($spent / $budget * 100, 1) : 0.0,
                'expense_lines_count' => $expenseLinesCount,
                'budget_tuples' => $budgetTuples,
                'budget_source' => $budgetRow?->budget_source,
                'last_synced_at' => $lastSyncedAt,
                'contracts' => $contracts,
                'is_sage_only' => $contracts->isEmpty(),
            ];
        });

        // Filter by program / funder based on linked contracts
        if ($this->filterProgram) {
            $rows = $rows->filter(fn ($r) => $r->contracts->contains('program_id', $this->filterProgram))->values();
        }
        if ($this->filterFunder) {
            $rows = $rows->filter(fn ($r) => $r->contracts->contains('funder_id', $this->filterFunder))->values();
        }

        // Utilization-level filter
        if ($this->filterUtilization) {
            $rows = $rows->filter(function ($r) {
                if ($r->budget <= 0) return false;
                $remPct = ($r->remaining / $r->budget) * 100;
                return match ($this->filterUtilization) {
                    'critical' => $remPct > 0 && $remPct <= 10,
                    'warning' => $remPct > 10 && $remPct <= 25,
                    'caution' => $remPct > 25 && $remPct <= 50,
                    'healthy' => $remPct > 50,
                    'depleted' => $r->spent >= $r->budget,
                    default => true,
                };
            })->values();
        }

        // Sort
        $rows = match ($this->sortBy) {
            'utilization_desc' => $rows->sortByDesc('utilization')->values(),
            'utilization_asc' => $rows->sortBy('utilization')->values(),
            'amount_desc' => $rows->sortByDesc('budget')->values(),
            'remaining_asc' => $rows->sortBy('remaining')->values(),
            'title_asc' => $rows->sortBy('project_name')->values(),
            'id_desc', 'id_asc', 'created_desc', 'created_asc', 'start_date_desc', 'start_date_asc' => $rows->sortBy('project_id')->values(),
            default => $rows->sortByDesc('utilization')->values(),
        };

        // Paginate manually
        $page = (int) request()->query('page', 1);
        $perPage = 12;
        $total = $rows->count();
        $paginated = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paginated,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Transactions drill-down for a fund (called from fund view). Shows all
     * expense lines for the project_id within the current date window,
     * regardless of which contract they're linked to.
     */
    public function viewFundTransactions(string $projectId): void
    {
        $this->transactionsContractId = null;
        $this->transactionsFundProjectId = $projectId;
        $this->showTransactionsModal = true;
    }

    public $transactionsFundProjectId = null;

    public function render()
    {
        $data = [
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
        ];

        if ($this->viewMode === 'funds') {
            $data['funds'] = $this->getFundsWithUtilization();
            $data['contracts'] = null;
        } else {
            $data['contracts'] = $this->getContractsWithUtilization();
            $data['funds'] = null;
        }

        return view('livewire.contract-utilization-dashboard', $data)
            ->layout('components.layouts.app');
    }
}
