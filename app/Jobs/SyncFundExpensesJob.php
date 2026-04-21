<?php

namespace App\Jobs;

use App\Models\Contract;
use App\Models\SageIntacctSetting;
use App\Services\FiscalYearService;
use App\Services\SageIntacctApiService;
use App\Services\SageIntacctExpenseService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued sync of posted Intacct GL expenses into intacct_fund_expenses
 * for one or more contracts. Dispatched by the dashboard "Sync from
 * Intacct" action; mirrors the console command logic so the two paths
 * share behavior.
 */
class SyncFundExpensesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 2;

    /**
     * @param  array<int>  $contractIds  Contracts to sync. Empty = all active.
     */
    public function __construct(
        public array $contractIds = [],
        public ?string $fromDate = null,
        public ?string $toDate = null
    ) {}

    public function handle(): void
    {
        $setting = SageIntacctSetting::getActive();

        if (! $setting || ! $setting->isConfigured() || ! $setting->isXmlApi()) {
            Log::warning('SyncFundExpensesJob: Intacct not configured for XML API — skipping run');

            return;
        }

        [$from, $to] = $this->resolveWindow();

        $apiService = new SageIntacctApiService($setting);
        $expenseService = new SageIntacctExpenseService($apiService);

        $query = Contract::query()
            ->whereNotNull('grant_code')
            ->where('grant_code', '!=', '');

        if (! empty($this->contractIds)) {
            $query->whereIn('id', $this->contractIds);
        } else {
            $query->active();
        }

        $contracts = $query->get();
        $synced = 0;
        $failed = 0;

        foreach ($contracts as $contract) {
            try {
                $result = $expenseService->syncContract($contract, $from, $to, false);
                if (! empty($result['success'])) {
                    $synced++;
                } else {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('SyncFundExpensesJob: contract sync threw', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('SyncFundExpensesJob: finished', [
            'contracts' => $contracts->count(),
            'synced' => $synced,
            'failed' => $failed,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveWindow(): array
    {
        if ($this->fromDate && $this->toDate) {
            return [
                Carbon::parse($this->fromDate)->startOfDay(),
                Carbon::parse($this->toDate)->endOfDay(),
            ];
        }

        return FiscalYearService::currentWindow();
    }
}
