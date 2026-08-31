<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractUtilizationNotification;
use App\Models\SiteSetting;
use App\Services\ContractUtilizationService;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ContractUtilizationSettings extends Component
{
    use WithPermissionChecks;

    // Settings
    public $notificationsEnabled = true;

    public $notifyContractManagers = true;

    public $notifyDueDateEmails = true;

    public $testMode = false;

    public $testEmail = '';

    public $thresholds = [];

    // Test runner
    public $runContractId = '';

    public $executionLog = [];

    public $hasRun = false;

    public function mount()
    {
        $this->authorizeAction('contracts.manage', 'You do not have permission to manage utilization notification settings.');

        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        $this->notificationsEnabled = ContractUtilizationService::notificationsEnabled();
        $this->notifyContractManagers = SiteSetting::get('utilization_notify_contract_managers', '1') === '1';
        $this->notifyDueDateEmails = SiteSetting::get('utilization_notify_due_date_emails', '1') === '1';
        $this->testMode = ContractUtilizationService::testModeEnabled();
        $this->testEmail = ContractUtilizationService::testEmail() ?: (string) (auth()->user()->email ?? '');
        $this->thresholds = ContractUtilizationService::getThresholds();
    }

    protected function rules(): array
    {
        return [
            'notificationsEnabled' => 'boolean',
            'notifyContractManagers' => 'boolean',
            'notifyDueDateEmails' => 'boolean',
            'testMode' => 'boolean',
            'testEmail' => 'required_if:testMode,true|nullable|email|max:255',
            'thresholds' => 'required|array|min:1',
            'thresholds.*' => 'required|integer|min:0|max:100',
        ];
    }

    protected function messages(): array
    {
        return [
            'testEmail.required_if' => 'A test email address is required while test mode is on, otherwise the alerts have nowhere to go.',
        ];
    }

    public function addThreshold(): void
    {
        $this->thresholds[] = 0;
    }

    public function removeThreshold(int $index): void
    {
        unset($this->thresholds[$index]);
        $this->thresholds = array_values($this->thresholds);
    }

    public function save()
    {
        $this->authorizeAction('contracts.manage');

        $this->validate();

        try {
            // Normalise: unique, descending, so "next threshold crossed" logic
            // reads the way the dashboard displays it.
            $cleanThresholds = collect($this->thresholds)
                ->map(fn ($t) => (int) $t)
                ->unique()
                ->sortDesc()
                ->values()
                ->all();

            SiteSetting::set('utilization_notification_enabled', $this->notificationsEnabled ? '1' : '0', 'boolean');
            SiteSetting::set('utilization_notify_contract_managers', $this->notifyContractManagers ? '1' : '0', 'boolean');
            SiteSetting::set('utilization_notify_due_date_emails', $this->notifyDueDateEmails ? '1' : '0', 'boolean');
            SiteSetting::set('utilization_test_mode', $this->testMode ? '1' : '0', 'boolean');
            SiteSetting::set('utilization_test_email', trim((string) $this->testEmail), 'text');
            SiteSetting::set('utilization_thresholds', json_encode($cleanThresholds), 'json');

            SiteSetting::clearCache();

            $this->thresholds = $cleanThresholds;

            Log::info('Contract utilization notification settings updated', [
                'user_id' => auth()->id(),
                'enabled' => $this->notificationsEnabled,
                'test_mode' => $this->testMode,
                'test_email' => $this->testEmail,
                'thresholds' => $cleanThresholds,
            ]);

            session()->flash('success', 'Utilization notification settings saved.');
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: '.$e->getMessage());
        }
    }

    /**
     * Evaluate overspend without sending or recording anything.
     */
    public function runDryRun()
    {
        $this->runCheck(true);
    }

    /**
     * Evaluate overspend and route every resulting alert to the test address.
     */
    public function runTestSend()
    {
        $this->validate([
            'testEmail' => 'required|email|max:255',
        ], [
            'testEmail.required' => 'Enter a test email address before sending a test.',
        ]);

        $this->runCheck(false);
    }

    protected function runCheck(bool $dryRun): void
    {
        $this->authorizeAction('contracts.manage');

        $this->executionLog = [];
        $this->hasRun = true;

        $params = [];

        if ($dryRun) {
            $params['--dry-run'] = true;
        } else {
            $params['--test-mode'] = true;
            $params['--test-email'] = trim((string) $this->testEmail);
        }

        if ($this->runContractId !== '' && $this->runContractId !== null) {
            $params['--contract-id'] = $this->runContractId;
        }

        $this->log($dryRun
            ? 'Starting dry run — evaluating overspend, sending nothing, recording nothing.'
            : 'Starting test run — every alert will be routed to '.$this->testEmail.'.');

        try {
            $exitCode = Artisan::call('contract:check-utilization', $params);

            foreach (explode("\n", trim(Artisan::output())) as $line) {
                if (trim($line) !== '') {
                    $this->log($line);
                }
            }

            if ($exitCode === 0) {
                if ($dryRun) {
                    session()->flash('success', 'Dry run complete. Nothing was sent and no thresholds were recorded.');
                } else {
                    // Deliberately "queued", not "sent": these notifications
                    // implement ShouldQueue, so the mail leaves only once a
                    // queue worker picks the job up.
                    session()->flash('success', 'Test run complete. Any resulting alerts were queued to '.$this->testEmail.' and will send when the queue worker processes them.');
                }
            } else {
                session()->flash('error', 'The check exited with code '.$exitCode.'. See the run log below.');
            }
        } catch (\Exception $e) {
            $this->log('ERROR: '.$e->getMessage());
            session()->flash('error', 'Error running utilization check: '.$e->getMessage());

            Log::error('Contract utilization test run failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }
    }

    protected function log(string $message): void
    {
        $this->executionLog[] = [
            'time' => now()->format('H:i:s'),
            'message' => $message,
        ];
    }

    /**
     * Crossings recorded while the master switch was off. These are the alerts
     * that would have gone out, and they will NOT be replayed when it is turned
     * back on.
     */
    public function getSuppressedProperty()
    {
        return ContractUtilizationNotification::with('contract:id,title,contract_number')
            ->where('notification_type', 'suppressed')
            ->orderByDesc('notified_at')
            ->limit(25)
            ->get();
    }

    public function getSuppressedCountProperty(): int
    {
        return ContractUtilizationNotification::where('notification_type', 'suppressed')->count();
    }

    /**
     * Queue depth for this notification class. Surfaced because a test that
     * "sent" nothing is almost always a stalled worker, not a bad setting.
     */
    public function getQueuedAlertCountProperty(): int
    {
        try {
            return DB::table('jobs')
                ->where('payload', 'like', '%ContractUtilizationThresholdNotification%')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function render()
    {
        return view('livewire.contract-utilization-settings', [
            'contracts' => Contract::query()
                ->where('amount', '>', 0)
                ->orderBy('title')
                ->get(['id', 'title', 'contract_number']),
            'suppressed' => $this->suppressed,
            'suppressedCount' => $this->suppressedCount,
            'queuedAlertCount' => $this->queuedAlertCount,
        ])->layout('components.layouts.app');
    }
}
