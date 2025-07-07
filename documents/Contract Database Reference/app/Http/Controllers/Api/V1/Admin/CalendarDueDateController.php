<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller; // Import the base Controller
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
use App\Services\MicrosoftGraphService;

class CalendarDueDateController extends Controller
{
    
    public function __construct(MicrosoftGraphService $microsoftGraphService)
    {
        $this->microsoftGraphService = $microsoftGraphService;
    }


        public function addToCalendar(Request $request)
    {
        // Validate the request data
        $request->validate([
            'due_date_id' => 'required|exists:report_due_dates,id', // Assuming 'due_date_id' is passed in the request
            // Include other necessary validations
        ]);

        try {
            // Retrieve the due date from the database
            $dueDate = ReportDueDate::findOrFail($request->due_date_id);

            // Get the access token
            $accessToken = $this->microsoftGraphService->getAccessToken();

            if (!$accessToken) {
                return response()->json(['message' => 'Failed to retrieve access token'], 500);
            }

            // Create the event in the Microsoft Calendar
            $this->microsoftGraphService->createGraphEvent($dueDate, $accessToken);

            // Update the ReportDueDate model if necessary
            // For example, you might want to mark it as processed
            $dueDate->invite_sent = true; // Assuming there's a flag to indicate this
            $dueDate->save();

            return response()->json(['message' => 'Due date added to calendar successfully']);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
    



}
