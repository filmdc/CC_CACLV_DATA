<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyReportDueDateRequest;
use App\Http\Requests\StoreReportDueDateRequest;
use App\Http\Requests\UpdateReportDueDateRequest;
use App\Models\Contract;
use App\Models\DueDateEmail;
use App\Models\ReportDueDate;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;


class ReportDueDateController extends Controller
{
    use MediaUploadingTrait;

    protected $clientId = '565e8809-7a50-4849-836b-505fef96edd0';
    protected $clientSecret = 'yq98Q~lGxUBEz-W3GLf_14~BvS2crluZOlsdZaOp';
    protected $tenantId = 'e6a62f88-5eeb-4675-9e8b-a7bfb2d304d4';

    public function index()
    {
        abort_if(Gate::denies('report_due_date_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $reportDueDates = ReportDueDate::with(['emails', 'contract', 'media'])->get();

        $due_date_emails = DueDateEmail::get();

        $contracts = Contract::get();

        return view('frontend.reportDueDates.index', compact('contracts', 'due_date_emails', 'reportDueDates'));
    }

    public function create()
    {
        abort_if(Gate::denies('report_due_date_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $emails = DueDateEmail::pluck('email', 'id');

        $contracts = Contract::pluck('title', 'id')->prepend(trans('global.pleaseSelect'), '');



        return view('frontend.reportDueDates.create', compact('contracts', 'emails'));


    }

    public function store(StoreReportDueDateRequest $request)
{
    $reportDueDate = ReportDueDate::create($request->all());

    // Sync emails
    $reportDueDate->emails()->sync($request->input('emails', []));

    // Handle documents upload
    foreach ($request->input('documents', []) as $file) {
        $reportDueDate->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
    }

    // Update CKEditor media
    if ($media = $request->input('ck-media', false)) {
        Media::whereIn('id', $media)->update(['model_id' => $reportDueDate->id]);
    }

    $accessToken = $this->getAccessToken();
       
        if (!$accessToken) {
            echo "Access token is null\n";
            return;
        }
     // Now, send the due date to Microsoft Calendar
     $this->sendEventToMicrosoftCalendar($reportDueDate, $accessToken);

     
     return redirect()->to('contracts/'.$request->input('contract2_id'));
    //  return redirect()->route('frontend.report-due-dates.index');
 }

 // You'll need to implement getAccessToken based on your existing console command logic
 protected function getAccessToken()
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
 protected function sendEventToMicrosoftCalendar($reportDueDate, $accessToken)
{
    if (!$accessToken) {
        echo 'Access token is null';
        return;
    }

    // Convert the due date string to a DateTime object for the start date
    try {
        $dueDateTime = new \DateTime($reportDueDate->due_date);
    } catch (Exception $e) {
        echo 'Invalid date format: ' . $e->getMessage();
        return;
    }

    // Format the end date as the day after the due date
    $endDateTime = (clone $dueDateTime)->modify('+1 day');

    $programNames = $reportDueDate->contract->programs->pluck('program_name')->join(', ');
    $subject = $programNames . ' - ' . $reportDueDate->contract->title;
    $description = "Program: {$programNames}<br><br>"
                   . "Purpose: {$reportDueDate->contract->purpose}<br>"
                   . "Notes: {$reportDueDate->contract->notes}<br>"
                   . "Contract Link: <a href=\"http://contracts.caclv.org/contracts/{$reportDueDate->contract->id}\">http://contracts.caclv.org/contracts/{$reportDueDate->contract->id}</a>";

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
    // Parse the JSON response to an associative array
    $responseData = json_decode($response, true);

    // Check if the 'id' is set in the response and use it
    if (isset($responseData['id'])) {
        $eventID = $responseData['id'];
        $reportDueDate->microsoft_event_id = $eventID;
        $reportDueDate->save();
        Log::info('Event successfully created with ID: ' . $eventID);
    } else {
        Log::error("Event created but ID not found in response: $response");
    }
} else {
    Log::error("Failed to create event: HTTP $httpCode, Response: $response");
}


}
    
    
    // $accessToken = $this->getAccessToken();
    // if (!$accessToken) {
    //     Log::error('Failed to get access token for Microsoft Graph');
    //     return false;
    // }

    // $graph = new Graph();
    // $graph->setAccessToken($accessToken);

    // $dueDateTime = new \DateTime($reportDueDate->due_date);
    // $endDateTime = (clone $dueDateTime)->modify('+1 day');

    // $eventData = [
    //     'subject' => 'Due Date: ' . $reportDueDate->description,
    //     'body' => [
    //         'contentType' => 'HTML',
    //         'content' => 'Here are the details of the due date...'
    //     ],
    //     'start' => [
    //         'dateTime' => $dueDateTime->format('Y-m-d'),
    //         'timeZone' => 'American/New_York'
    //     ],
    //     'end' => [
    //         'dateTime' => $endDateTime->format('Y-m-d'),
    //         'timeZone' => 'American/New_York'
    //     ],
    //     'isAllDay' => true,
    // ];

    // try {
    //     // Adjust the endpoint to target the specific calendar
    //     $response = $graph->createRequest("POST", "/users/scheduling@communityactionlv.org/events")
    //                       ->attachBody($eventData)
    //                       ->setReturnType(Model\Event::class)
    //                       ->execute();

    //     Log::info('Event successfully created. ID: ' . $response->getId());
    //     return true;
    // } catch (\Exception $e) {
    //     Log::error('Failed to create event in Microsoft Calendar: ' . $e->getMessage());
    //     return false;
    // }

 
 
    public function edit(ReportDueDate $reportDueDate)
    {
        abort_if(Gate::denies('report_due_date_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $emails = DueDateEmail::pluck('email', 'id');

        $contracts = Contract::pluck('title', 'id')->prepend(trans('global.pleaseSelect'), '');

        $reportDueDate->load('emails', 'contract');

        return view('frontend.reportDueDates.edit', compact('contracts', 'emails', 'reportDueDate'));
    }

    public function update(UpdateReportDueDateRequest $request, ReportDueDate $reportDueDate)
    {
        $reportDueDate->update($request->all());
        $reportDueDate->emails()->sync($request->input('emails', []));
        if (count($reportDueDate->documents) > 0) {
            foreach ($reportDueDate->documents as $media) {
                if (!in_array($media->file_name, $request->input('documents', []))) {
                    $media->delete();
                }
            }
        }
        $media = $reportDueDate->documents->pluck('file_name')->toArray();
        foreach ($request->input('documents', []) as $file) {
            if (count($media) === 0 || !in_array($file, $media)) {
                $reportDueDate->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
            }
        }
        $accessToken = $this->getAccessToken();
        if ($accessToken) {
            $this->updateMicrosoftCalendarEvent($reportDueDate, $accessToken);
        } else {
            Log::error('Unable to obtain access token for updating Microsoft Calendar event.');
        }
        // return redirect()->route('frontend.report-due-dates.index');
        return view('frontend.reportDueDates.show', compact('reportDueDate'));
    }


    protected function updateMicrosoftCalendarEvent($reportDueDate, $accessToken)
{
    if (!$accessToken || !$reportDueDate->microsoft_event_id) {
        Log::error('Missing access token or Microsoft Event ID. Cannot update calendar event.');
        return;
    }

    // Ensure related data is loaded. You may remove this line if it's guaranteed to be loaded already.
   // $reportDueDate->load('contract.programs');

    $dueDateTime = new \DateTime($reportDueDate->due_date);
    $endDateTime = (clone $dueDateTime)->modify('+1 day');

    $programNames = $reportDueDate->contract->programs->pluck('program_name')->join(', ');
    $subject = $programNames . ' - ' . $reportDueDate->contract->title;
    $description = "Program: {$programNames}<br><br>"
                   . "Purpose: {$reportDueDate->contract->purpose}<br>"
                   . "Notes: {$reportDueDate->contract->notes}<br>"
                   . "Contract Link: <a href=\"http://contracts.caclv.org/contracts/{$reportDueDate->contract->id}\">http://contracts.caclv.org/contracts/{$reportDueDate->contract->id}</a>";

    $eventData = [
        'subject' => $subject,
        'body' => [
            'contentType' => 'HTML',
            'content' => $description
        ],
        'start' => [
            'dateTime' => $dueDateTime->format('Y-m-d'),
            'timeZone' => 'America/New_York'
        ],
        'end' => [
            'dateTime' => $endDateTime->format('Y-m-d'),
            'timeZone' => 'America/New_York'
        ],
        'isAllDay' => true,
    ];

    $curl = curl_init("https://graph.microsoft.com/v1.0/users/scheduling@communityactionlv.org/events/{$reportDueDate->microsoft_event_id}");
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json"
    ]);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($eventData));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode == 200) {
        Log::info("Successfully updated calendar event: {$reportDueDate->microsoft_event_id}");
    } else {
        Log::error("Failed to update calendar event: {$response}");
    }
}

    public function show(ReportDueDate $reportDueDate)
    {
        abort_if(Gate::denies('report_due_date_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $reportDueDate->load('emails', 'contract');

        return view('frontend.reportDueDates.show', compact('reportDueDate'));
    }

    public function destroy(ReportDueDate $reportDueDate)
    {
        abort_if(Gate::denies('report_due_date_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');


        if ($reportDueDate->microsoft_event_id) {
            $this->removeEventFromMicrosoftCalendar($reportDueDate->microsoft_event_id);
        }
    

        $reportDueDate->delete();

        return back();
    }
    protected function removeEventFromMicrosoftCalendar($eventId)
{
    $accessToken = $this->getAccessToken();
    if (!$accessToken) {
        Log::error('Failed to get access token for Microsoft Graph. Cannot delete calendar event.');
        return;
    }

    $curl = curl_init("https://graph.microsoft.com/v1.0/users/scheduling@communityactionlv.org/events/{$eventId}");
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json"
    ]);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode == 204) { // 204 No Content indicates success
        Log::info("Successfully deleted calendar event: {$eventId}");
    } else {
        Log::error("Failed to delete calendar event: {$eventId}. Response: {$response}");
    }
}

    public function massDestroy(MassDestroyReportDueDateRequest $request)
    {
        ReportDueDate::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('report_due_date_create') && Gate::denies('report_due_date_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new ReportDueDate();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }

   

}
