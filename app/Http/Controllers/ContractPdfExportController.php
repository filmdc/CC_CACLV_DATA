<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\ContractPdfExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractPdfExportController extends Controller
{
    public function __invoke(Request $request, Contract $contract, ContractPdfExportService $exportService): StreamedResponse
    {
        abort_unless($request->user()?->can('contracts.view'), 403, 'Unauthorized.');

        $pdfBinary = $exportService->generate($contract);
        $filename = $exportService->generateFilename($contract);

        return response()->streamDownload(
            function () use ($pdfBinary) {
                echo $pdfBinary;
            },
            $filename,
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }
}
