<?php

namespace App\Services;

use App\Models\ReportDueDate;
use Exception;
use DateTime;

class MicrosoftGraphService
{
    private $clientId;
    private $clientSecret;
    private $tenantId;

    public function __construct()
    {
        $this->clientId = '565e8809-7a50-4849-836b-505fef96edd0';
        $this->clientSecret = 'yq98Q~lGxUBEz-W3GLf_14~BvS2crluZOlsdZaOp';
        $this->tenantId = 'e6a62f88-5eeb-4675-9e8b-a7bfb2d304d4';
    }

    public function sendCalendarInvites($limit = null)
    {
        $recordLimit = $limit ?? ReportDueDate::count();
        // rest of the logic to send invites
    }

    public function getAccessToken()
    {
        // logic to get access token
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

    public function createGraphEvent($dueDate, $accessToken)
    {
        // logic to create graph event
        if (!$accessToken) {
            echo 'Access token is null';
            return;
        }
    
        // Convert the due date string to a DateTime object
        try {
            $dueDateTime = new \DateTime($dueDate->due_date);
        } catch (Exception $e) {
            echo 'Invalid date format: ' . $e->getMessage();
            return;
        }
    
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
                'dateTime' => $dueDateTime->format('c'), // ISO 8601 format
                'timeZone' => 'UTC' // Adjust timezone as needed
            ],
            'end' => [
                // Assuming the event ends on the same day, you can adjust this as needed
                'dateTime' => $dueDateTime->format('c'),
                'timeZone' => 'UTC'
            ]
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

    // Additional methods if needed
}
