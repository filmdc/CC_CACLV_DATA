<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\SiteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ProcessScheduledTestReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contract:process-scheduled-tests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled test reminder emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled test reminders...');
        
        // Get scheduled tests
        $scheduledTests = json_decode(SiteSetting::get('scheduled_test_reminders', '[]'), true);
        $processedTests = [];
        $remainingTests = [];
        
        foreach ($scheduledTests as $test) {
            $scheduledAt = Carbon::parse($test['scheduled_at']);
            
            // Check if it's time to send this test
            if ($scheduledAt->isPast() || $scheduledAt->isCurrentMinute()) {
                $this->info("Processing test for contract ID: {$test['contract_id']}");
                
                try {
                    // Get contract details
                    $contract = Contract::with(['program', 'funder'])->find($test['contract_id']);
                    
                    if (!$contract) {
                        $this->error("Contract {$test['contract_id']} not found");
                        continue;
                    }
                    
                    // Prepare email
                    $subject = "[TEST] [SCHEDULED] Contract Reminder: {$contract->title}";
                    $body = "This is a scheduled test reminder for contract: {$contract->title}\n\n" .
                           "Program: " . ($contract->program->name ?? 'N/A') . "\n" .
                           "Funder: " . ($contract->funder->name ?? 'N/A') . "\n\n" .
                           "Scheduled for: {$scheduledAt->format('M j, Y g:i A')}\n" .
                           "Processed at: " . now()->format('M j, Y g:i A') . "\n\n" .
                           "This email was sent as a scheduled test from the Contract Notification Test Dashboard.";
                    
                    // Send email
                    Mail::raw($body, function ($message) use ($test, $subject) {
                        $message->to($test['test_email'])
                               ->subject($subject);
                    });
                    
                    $this->info("Email sent to: {$test['test_email']}");
                    $processedTests[] = $test['id'];
                    
                    // Log the action
                    Log::info('Scheduled test reminder sent', [
                        'contract_id' => $test['contract_id'],
                        'scheduled_at' => $scheduledAt->toDateTimeString(),
                        'sent_to' => $test['test_email'],
                    ]);
                    
                } catch (\Exception $e) {
                    $this->error("Error processing test: " . $e->getMessage());
                    Log::error('Scheduled test reminder error', [
                        'test' => $test,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                // Keep for future processing
                $remainingTests[] = $test;
            }
        }
        
        // Update the scheduled tests list
        if (count($processedTests) > 0) {
            SiteSetting::set('scheduled_test_reminders', json_encode($remainingTests));
            $this->info('Processed ' . count($processedTests) . ' scheduled tests');
        } else {
            $this->info('No scheduled tests due for processing');
        }
        
        return Command::SUCCESS;
    }
}
