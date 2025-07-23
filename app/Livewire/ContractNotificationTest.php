<?php

namespace App\Livewire;

use App\Models\Contract;
use App\Models\ContractReportDueDate;
use App\Models\SiteSetting;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class ContractNotificationTest extends Component
{
    use WithPagination, WithPermissionChecks;
    
    public $selectedContractId = '';
    public $selectedDueDateId = '';
    public $selectedDayInterval = null;
    public $testEmailRecipient = '';
    public $showPreview = false;
    public $emailPreview = [];
    public $executionLog = [];
    public $isTestMode = false;
    
    public function mount()
    {
        $this->authorizeAction('contracts.manage', 'You do not have permission to access the notification test dashboard.');
        
        // Load test mode status and default test email
        $this->isTestMode = (bool) SiteSetting::get('contract_reminder_test_mode', '0');
        $this->testEmailRecipient = SiteSetting::get('contract_reminder_test_email', auth()->user()->email);
    }
    
    public function getContractsProperty()
    {
        return Contract::with(['reportDueDates' => function($query) {
            $query->where('active', true)
                  ->where('status', '!=', 'completed')
                  ->orderBy('due_date');
        }])
        ->whereHas('reportDueDates', function($query) {
            $query->where('active', true)
                  ->where('status', '!=', 'completed');
        })
        ->orderBy('title')
        ->get();
    }
    
    public function getDueDatesProperty()
    {
        if (!$this->selectedContractId) {
            return collect();
        }
        
        return ContractReportDueDate::where('contract_id', $this->selectedContractId)
            ->where('active', true)
            ->where('status', '!=', 'completed')
            ->with(['emails.user'])
            ->orderBy('due_date')
            ->get();
    }
    
    public function getIntervalOptionsProperty()
    {
        $intervals = json_decode(SiteSetting::get('contract_reminder_intervals', '[30,15,7,3,1,0]'), true);
        $options = [];
        
        foreach ($intervals as $days) {
            if ($days === 0) {
                $options[0] = 'On Due Date';
            } elseif ($days === 1) {
                $options[1] = '1 Day Before';
            } else {
                $options[$days] = $days . ' Days Before';
            }
        }
        
        return $options;
    }
    
    public function updatedSelectedContractId()
    {
        $this->selectedDueDateId = '';
        $this->selectedDayInterval = null;
        $this->showPreview = false;
        $this->emailPreview = [];
    }
    
    public function updatedSelectedDueDateId()
    {
        $this->showPreview = false;
        $this->emailPreview = [];
    }
    
    public function previewEmail()
    {
        $this->validate([
            'selectedContractId' => 'required',
            'selectedDueDateId' => 'required',
            'selectedDayInterval' => 'required|numeric',
            'testEmailRecipient' => 'required|email',
        ]);
        
        try {
            $dueDate = ContractReportDueDate::with(['contract.program', 'contract.funder'])->find($this->selectedDueDateId);
            
            if (!$dueDate) {
                session()->flash('error', 'Selected due date not found.');
                return;
            }
            
            // Calculate days until due
            $daysUntilDue = (int) $this->selectedDayInterval;
            
            // Get email template and subject
            $subject = SiteSetting::get('contract_reminder_email_subject', 'Contract Report Reminder: {{report_name}} - Due in {{days_until}} days');
            $template = SiteSetting::get('contract_reminder_email_template', '');
            
            // Replace variables
            $variables = [
                '{{report_name}}' => strip_tags($dueDate->report_name ?? ucfirst($dueDate->report_type) . ' Report'),
                '{{contract_title}}' => $dueDate->contract->title,
                '{{program}}' => $dueDate->contract->program->name ?? 'N/A',
                '{{funder}}' => $dueDate->contract->funder->name ?? 'N/A',
                '{{due_date}}' => $dueDate->due_date->format('F j, Y'),
                '{{days_until}}' => $daysUntilDue,
                '{{description}}' => strip_tags($dueDate->description ?? ''),
            ];
            
            foreach ($variables as $key => $value) {
                $subject = str_replace($key, $value, $subject);
                $template = str_replace($key, $value, $template);
            }
            
            // Add urgency prefix
            if ($daysUntilDue <= 0) {
                $subject = '[OVERDUE] ' . $subject;
            } elseif ($daysUntilDue <= 1) {
                $subject = '[URGENT] ' . $subject;
            } elseif ($daysUntilDue <= 3) {
                $subject = '[HIGH PRIORITY] ' . $subject;
            }
            
            // Add test mode prefix
            if ($this->isTestMode) {
                $subject = '[TEST] ' . $subject;
            }
            
            $this->emailPreview = [
                'subject' => $subject,
                'body' => $template,
                'recipient' => $this->testEmailRecipient,
                'assigned_users' => $dueDate->emails->map(function($email) {
                    return [
                        'name' => $email->name ?: 'No Name',
                        'email' => $email->email,
                    ];
                }),
            ];
            
            $this->showPreview = true;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error generating preview: ' . $e->getMessage());
            Log::error('Contract notification preview error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    public function sendTestNotification()
    {
        $this->validate([
            'selectedContractId' => 'required',
            'selectedDueDateId' => 'required',
            'selectedDayInterval' => 'required|numeric',
            'testEmailRecipient' => 'required|email',
        ]);
        
        try {
            $this->executionLog = [];
            $this->executionLog[] = ['time' => now()->format('H:i:s'), 'message' => 'Starting test notification...'];
            
            // Build command
            $command = 'contract:send-reminders';
            $params = [
                '--contract-id' => $this->selectedContractId,
                '--days' => $this->selectedDayInterval,
                '--test-mode' => true,
                '--test-email' => $this->testEmailRecipient,
            ];
            
            $this->executionLog[] = [
                'time' => now()->format('H:i:s'), 
                'message' => 'Executing command: php artisan ' . $command . ' ' . collect($params)->map(fn($v, $k) => "$k=$v")->implode(' ')
            ];
            
            // Execute command
            $exitCode = Artisan::call($command, $params);
            $output = Artisan::output();
            
            // Parse output
            $outputLines = explode("\n", trim($output));
            foreach ($outputLines as $line) {
                if (!empty($line)) {
                    $this->executionLog[] = [
                        'time' => now()->format('H:i:s'),
                        'message' => $line,
                    ];
                }
            }
            
            if ($exitCode === 0) {
                session()->flash('success', 'Test notification sent successfully! Check your email.');
                $this->executionLog[] = ['time' => now()->format('H:i:s'), 'message' => 'Test completed successfully.'];
            } else {
                session()->flash('error', 'Test notification failed. Check the execution log for details.');
                $this->executionLog[] = ['time' => now()->format('H:i:s'), 'message' => 'Test failed with exit code: ' . $exitCode];
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error sending test notification: ' . $e->getMessage());
            $this->executionLog[] = [
                'time' => now()->format('H:i:s'),
                'message' => 'ERROR: ' . $e->getMessage(),
            ];
            Log::error('Contract notification test error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    public function getReminderHistory()
    {
        if (!$this->selectedDueDateId) {
            return collect();
        }
        
        $dueDate = ContractReportDueDate::find($this->selectedDueDateId);
        if (!$dueDate) {
            return collect();
        }
        
        $history = [];
        
        foreach ($dueDate->dueDateEmails as $emailRecord) {
            $remindersSent = $emailRecord->reminders_sent ?? [];
            foreach ($remindersSent as $dayInterval) {
                $history[] = [
                    'user' => $emailRecord->dueDateEmail->name ?? $emailRecord->dueDateEmail->email,
                    'email' => $emailRecord->dueDateEmail->email,
                    'day_interval' => $dayInterval,
                    'sent_at' => $emailRecord->last_reminder_sent,
                ];
            }
        }
        
        return collect($history)->sortByDesc('sent_at');
    }
    
    public function render()
    {
        return view('livewire.contract-notification-test', [
            'contracts' => $this->contracts,
            'dueDates' => $this->dueDates,
            'intervalOptions' => $this->intervalOptions,
            'reminderHistory' => $this->getReminderHistory(),
        ])->layout('components.layouts.app');
    }
}