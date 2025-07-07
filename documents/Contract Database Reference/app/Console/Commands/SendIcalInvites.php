<?php

namespace App\Console\Commands;
use App\Models\ReportDueDate;
use Illuminate\Console\Command;
use Illuminate\Broadcasting\Channel;
use DB;
use Carbon\Carbon;
use Throwable;

class SendIcalInvites extends Command
{


       
    protected $signature = 'send:SendIcalInvites {limit?}';
    protected $clientId = '565e8809-7a50-4849-836b-505fef96edd0';
    protected $clientSecret = 'yq98Q~lGxUBEz-W3GLf_14~BvS2crluZOlsdZaOp';
    protected $tenantId = 'e6a62f88-5eeb-4675-9e8b-a7bfb2d304d4';


        public function handle()
        {
            $recordLimit = $this->argument('limit') ?? ReportDueDate::count();
            DB::beginTransaction();
            try {
                $accessToken = $this->getAccessToken();
                if (!$accessToken) {
                    echo "Access token is null\n";
                    return;
                }
    
                $dueDates = ReportDueDate::with('contract')
                   ->where('invite_sent', false)
                   ->take($recordLimit) // Limit the number of records
                   ->get();
    
                foreach ($dueDates as $dueDate) {
                    $this->createGraphEvent($dueDate, $accessToken);
                    $dueDate->invite_sent = true;
                    $dueDate->save();
                    
                    echo "Event created for contract {$dueDate->contract_id}\n";
                    
                    
                }
    
                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                echo "An error occurred: " . $e->getMessage() . "\n";
            }
        }

    private function getAccessToken()
    {
        $url = "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token";
        $data = [
            'client_id' => $this->clientId,
            'scope' => 'https://graph.microsoft.com/.default',
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials'
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        $responseData = json_decode($response, true);
        return $responseData['access_token'] ?? null;
    }
   
    private function createGraphEvent($dueDate, $accessToken) {
        if (!$accessToken) {
            echo 'Access token is null';
            return;
        }
    
        // Convert the due date string to a DateTime object for the start date
        try {
            $dueDateTime = new \DateTime($dueDate->due_date);
        } catch (Exception $e) {
            echo 'Invalid date format: ' . $e->getMessage();
            return;
        }
    
        // Format the end date as the day after the due date
        $endDateTime = (clone $dueDateTime)->modify('+1 day');
    
        $programNames = $dueDate->contract->programs->pluck('program_name')->join(', ');
        $subject = $programNames . ' - ' . $dueDate->contract->title;
        $description = "Program: {$programNames}<br><br>"
                       . "Purpose: {$dueDate->contract->purpose}<br>"
                       . "Notes: {$dueDate->contract->notes}<br>"
                       . "Contract Link: <a href=\"http://contracts.caclv.org/contracts/{$dueDate->contract->id}\">http://contracts.caclv.org/contracts/{$dueDate->contract->id}</a>";
    
        $eventData = [
            'subject' => $subject,
            'body' => [
                'contentType' => 'HTML',
                'content' => $description
            ],
            'start' => [
                'dateTime' => $dueDateTime->format('Y-m-d'), // Date only for all-day events
                'timeZone' => 'America/New_York'
            ],
            'end' => [
                'dateTime' => $endDateTime->format('Y-m-d'), // Date only, and end date is the next day
                'timeZone' => 'America/New_York'
            ],
            'isAllDay' => true, // Mark as all-day event
        ];

    $curl = curl_init('https://graph.microsoft.com/v1.0/users/scheduling@communityactionlv.org/events');
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($eventData));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode === 201) {
        echo 'Event successfully created';
        
    } else {
        echo "Failed to create event: $response";
    }

}

}


// To run the command
// $command = new SendIcalInvites();
// $command->handle();



