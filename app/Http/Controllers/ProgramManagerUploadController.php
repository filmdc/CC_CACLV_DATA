<?php

namespace App\Http\Controllers;

use App\Models\ProgramManagerUpload;
use App\Services\ImportExportService;
use App\Services\HMISPennsylvaniaImportService;
use App\Services\RXOfficeHousingImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProgramManagerUploadController extends Controller
{
    public function __construct(
        protected ImportExportService $importExportService,
        protected HMISPennsylvaniaImportService $hmisImportService,
        protected RXOfficeHousingImportService $rxOfficeImportService
    ) {
    }

    /**
     * Display the public upload landing page.
     */
    public function create(): View
    {
        return view('public.program-manager-upload');
    }

    /**
     * Handle a program manager upload submission.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'submission_type' => 'required|in:general,hmis,rx_office',
            'program_manager_name' => 'required|string|max:255',
            'program_manager_email' => 'required|email|max:255',
            'organization' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:5000',
            'upload_file' => 'required|file|max:102400|mimes:csv,txt,xlsx,xls,pdf,zip,doc,docx,json',
        ]);

        $file = $request->file('upload_file');
        $type = $validated['submission_type'];
        $storageDirectory = "program-manager-uploads/{$type}";

        $filename = now()->format('Ymd_His') . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs($storageDirectory, $filename);

        ProgramManagerUpload::create([
            'type' => $type,
            'program_manager_name' => $validated['program_manager_name'],
            'program_manager_email' => $validated['program_manager_email'],
            'organization' => $validated['organization'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'file_path' => $storedPath,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'ip_address' => $request->ip(),
            'submitted_at' => now(),
        ]);

        Log::info('Program manager upload submitted', [
            'type' => $type,
            'email' => $validated['program_manager_email'],
            'path' => $storedPath,
        ]);

        return back()->with('success', 'Your file has been submitted successfully. We will review it shortly.');
    }

    /**
     * Provide import templates for public program managers.
     */
    public function downloadTemplate(string $type)
    {
        switch ($type) {
            case 'general':
                $result = $this->importExportService->generateImportTemplate();

                if (!($result['success'] ?? false)) {
                    return redirect()
                        ->route('program-manager-upload.create')
                        ->with('error', 'Unable to generate the general upload template. Please try again later.');
                }

                $path = Storage::disk('local')->path($result['file_path']);

                return response()->download($path, $result['filename'])->deleteFileAfterSend(true);

            case 'hmis':
                $content = $this->hmisImportService->generateTemplate();
                $fileName = 'hmis_import_template_' . now()->format('Y-m-d_H-i-s') . '.csv';

                return response()->streamDownload(function () use ($content) {
                    echo $content;
                }, $fileName, ['Content-Type' => 'text/csv']);

            case 'rx_office':
                $content = $this->rxOfficeImportService->generateTemplate('detailed_case');
                $fileName = 'rx_office_import_template_' . now()->format('Y-m-d_H-i-s') . '.csv';

                return response()->streamDownload(function () use ($content) {
                    echo $content;
                }, $fileName, ['Content-Type' => 'text/csv']);

            default:
                abort(404);
        }
    }
}
