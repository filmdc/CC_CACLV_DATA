<?php

namespace App\Services;
use App\Models\ReportDueDate;
use Illuminate\Support\Facades\Log;
use Exception;
use DateTime;



class GsMicrosoftGraphCalendarService
{
    private $clientId = '565e8809-7a50-4849-836b-505fef96edd0';
    private $clientSecret = 'yq98Q~lGxUBEz-W3GLf_14~BvS2crluZOlsdZaOp';
    private $tenantId = 'e6a62f88-5eeb-4675-9e8b-a7bfb2d304d4';
    private const GRAPH_API_ENDPOINT = 'https://graph.microsoft.com/v1.0';

    public function getAccessToken()
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
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            Log::error("cURL Error in getAccessToken: $err");
            return null;
        }

        $responseData = json_decode($response, true);
        return $responseData['access_token'] ?? null;
    }

    private function prepareEventData($dueDate)
{
    try {
        $dueDateTime = new \DateTime($dueDate->due_date);
    } catch (Exception $e) {
        Log::error('Invalid date format: ' . $e->getMessage());
        return null;
    }

    $programNames = $dueDate->contract->programs->pluck('program_name')->join(', ');
    $subject = $programNames . ' - ' . $dueDate->contract->title;
    $description = "Program: {$programNames}<br><br>"
                   . "Purpose: {$dueDate->contract->purpose}<br>"
                   . "Notes: {$dueDate->contract->notes}<br>"
                   . "Contract Link: <a href=\"http://contracts.caclv.org/contracts/{$dueDate->contract->id}\">http://contracts.caclv.org/contracts/{$dueDate->contract->id}</a>";

    return [
        'subject' => $subject,
        'body' => [
            'contentType' => 'HTML',
            'content' => $description
        ],
        'start' => [
            'dateTime' => $dueDateTime->format('Y-m-d'), // All-day event start
            'timeZone' => 'UTC'
        ],
        'end' => [
            'dateTime' => $dueDateTime->format('Y-m-d'), // All-day event end (same day)
            'timeZone' => 'UTC'
        ],
        'isAllDay' => true
    ];
}


    public function createOrUpdateEvent($dueDate, $accessToken)
    {
        if (!$accessToken) {
            Log::error('Access token is null in createOrUpdateEvent');
            return false;
        }

        $eventData = $this->prepareEventData($dueDate);
        $url = self::GRAPH_API_ENDPOINT . '/users/scheduling@communityactionlv.org/events';

        if (!empty($dueDate->event_id)) { // Check if it's an update
            $url .= '/' . $dueDate->event_id;
            $method = 'PATCH';
        } else {
            $method = 'POST';
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($eventData));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            Log::error("cURL Error in createOrUpdateEvent: $err");
            return false;
        }

        if ($httpCode === 201 || $httpCode === 200) {
            Log::info('Event successfully created or updated');
            return true;
        } else {
            Log::error("Failed to create or update event: $response");
            return false;
        }
    }

    public function deleteEvent($eventId, $accessToken)
    {
        if (!$accessToken) {
            Log::error('Access token is null in deleteEvent');
            return false;
        }

        $curl = curl_init("https://graph.microsoft.com/v1.0/users/scheduling@communityactionlv.org/events/$eventId");
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($err) {
            Log::error("cURL Error in deleteEvent: $err");
            return false;
        }

        if ($httpCode === 204) {
            Log::info('Event successfully deleted');
            return true;
        } else {
            Log::error("Failed to delete event: $response");
            return false;
        }
    }

    
}