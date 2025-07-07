<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\CsvImportTrait;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyContractRequest;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Contract;
use App\Models\Funder;
use App\Models\Program;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Spatie\PdfToImage\Pdf;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ContractController extends Controller
{
    use MediaUploadingTrait, CsvImportTrait;

    public function index()
    {
        abort_if(Gate::denies('contract_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contracts = Contract::with(['programs', 'funder', 'media'])->get();

        $programs = Program::get();

        $funders = Funder::get();

        return view('admin.contracts.index', compact('contracts', 'funders', 'programs'));
    }

    public function create()
    {
        abort_if(Gate::denies('contract_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $programs = Program::pluck('program_name', 'id');

        $funders = Funder::pluck('funder_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.contracts.create', compact('funders', 'programs'));
    }

    public function store(StoreContractRequest $request)
    {
        $contract = Contract::create($request->all());
        $contract->programs()->sync($request->input('programs', []));
        foreach ($request->input('documents', []) as $file) {
            $contract->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $contract->id]);
        }

        return redirect()->route('admin.contracts.index');
    }

    public function edit(Contract $contract)
    {
        abort_if(Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $programs = Program::pluck('program_name', 'id');

        $funders = Funder::pluck('funder_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $contract->load('programs', 'funder');

        return view('admin.contracts.edit', compact('contract', 'funders', 'programs'));
    }

    public function update(UpdateContractRequest $request, Contract $contract)
    {
        $contract->update($request->all());
        $contract->programs()->sync($request->input('programs', []));
        if (count($contract->documents) > 0) {
            foreach ($contract->documents as $media) {
                if (! in_array($media->file_name, $request->input('documents', []))) {
                    $media->delete();
                }
            }
        }
        $media = $contract->documents->pluck('file_name')->toArray();
        foreach ($request->input('documents', []) as $file) {
            if (count($media) === 0 || ! in_array($file, $media)) {
                $contract->addMedia(storage_path('tmp/uploads/' . basename($file)))->toMediaCollection('documents');
            }
        }

        return redirect()->route('admin.contracts.index');
    }

    public function show(Contract $contract)
    {
        abort_if(Gate::denies('contract_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contract->load('programs', 'funder', 'contractReportDueDates', 'contractReports');

        return view('admin.contracts.show', compact('contract'));
    }

    public function destroy(Contract $contract)
    {
        abort_if(Gate::denies('contract_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contract->delete();

        return back();
    }

    public function massDestroy(MassDestroyContractRequest $request)
    {
        $contracts = Contract::find(request('ids'));

        foreach ($contracts as $contract) {
            $contract->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('contract_create') && Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new Contract();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
    public function toggleArchive(Request $request, $id)
    {
    $contract = Contract::findOrFail($id);
    $contract->archived = $request->input('archived');
    if ($request->input('archived') == 'yes') {
        $contract->date_archived = now(); // Set the current date as the archived date
    } else {
        $contract->date_archived = null; // Clear the archived date
    }
    $contract->save();

    return response()->json(['message' => 'Contract archive status updated successfully.']);
    }
    
    
    
    
    public function exportContractsToText()
{
    $contracts = Contract::with(['programs', 'funder', 'media'])->get();
    $fileContent = '';

    foreach ($contracts as $contract) {
        $fileContent .= "Title: {$contract->title}\n";
        $fileContent .= "Funder: {$contract->funder->funder_name}\n";
        $fileContent .= "Program: " . implode(', ', $contract->programs->pluck('program_name')->toArray()) . "\n";
        $fileContent .= "Amount: {$contract->amount}\n";
        $fileContent .= "Start Date: {$contract->start_date}\n";
        $fileContent .= "End Date: {$contract->end_date}\n\n";

        foreach ($contract->getMedia('documents') as $media) {
            $fileContent .= "Attached Document: {$media->file_name}\n";
            
            if ($media->mime_type === 'application/pdf') {
                $fileContent .= $this->extractTextFromPdf($media->getPath()) . "\n";
            } elseif ($media->mime_type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                $fileContent .= $this->extractTextFromWord($media->getPath()) . "\n";
            } elseif ($media->mime_type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                $fileContent .= $this->extractTextFromExcel($media->getPath()) . "\n";
            } else {
                $fileContent .= "Unsupported file format: {$media->mime_type}\n";
            }

            $fileContent .= "--------------------\n";
        }

        $fileContent .= "====================\n\n";
    }

    $filename = "contracts_export_" . date('Y-m-d_H-i-s') . ".txt";
    return response($fileContent)
        ->header('Content-Type', 'text/plain')
        ->header('Content-Disposition', "attachment; filename={$filename}");
}


private function isScannedPdf($filePath)
        {
          try {
              $parser = new Parser();
              $pdf = $parser->parseFile($filePath);
              $text = $pdf->getText();

              // If less than 50 characters of text, assume it’s scanned
              return strlen($text) < 50;
          } catch (\Exception $e) {
              return true; // Assume scanned if parsing fails
          }
        }



        private function extractTextFromPdf($filePath)
        {
            if ($this->isScannedPdf($filePath)) {
                return $this->extractTextFromScannedPdf($filePath);
            }
        
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();
                return $text ?: 'No readable text found in the PDF.';
            } catch (\Exception $e) {
                return "Failed to extract text from PDF: " . $e->getMessage();
            }
        }

        private function convertPdfToImages($filePath)
        {
            $images = [];
            try {
                $pdf = new Pdf($filePath);
                $pdf->setOutputFormat('png'); // Convert to PNG for better OCR accuracy
            
                $totalPages = $pdf->getNumberOfPages();
                for ($i = 1; $i <= $totalPages; $i++) {
                    $imagePath = storage_path("tmp/pdf_page_{$i}.png");
                    $pdf->setPage($i)->saveImage($imagePath);
                    $images[] = $imagePath;
                }
            } catch (\Exception $e) {
                return ["Error converting PDF to images: " . $e->getMessage()];
            }
        
            return $images;
        }

        private function extractTextFromImages(array $imagePaths)
        {
            $text = '';
            try {
                foreach ($imagePaths as $imagePath) {
                    $ocr = new TesseractOCR($imagePath);
                    $ocr->lang('eng'); // Set language (adjust based on your needs)
                    $text .= $ocr->run() . "\n\n";
                }
            } catch (\Exception $e) {
                return "Failed to extract text with OCR: " . $e->getMessage();
            }
        
            return $text ?: 'No text detected in the images.';
        }


        private function extractTextFromScannedPdf($filePath)
        {
            $imagePaths = $this->convertPdfToImages($filePath);

            if (empty($imagePaths)) {
                return "Failed to convert PDF to images.";
            }
        
            $text = $this->extractTextFromImages($imagePaths);
        
            // Clean up temporary image files
            foreach ($imagePaths as $imagePath) {
                @unlink($imagePath);
            }
        
            return $text;
        }




        private function extractTextFromWord($filePath)
        {
        try {
        $phpWord = IOFactory::load($filePath);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            $elements = $section->getElements();
            foreach ($elements as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                }
            }
        }

        return $text ?: 'No readable text found in the Word document.';
           } catch (\Exception $e) {
             return "Failed to extract text from Word document: " . $e->getMessage();
           }
       }

    

       private function extractTextFromExcel($filePath)
       {
           try {
               $spreadsheet = SpreadsheetIOFactory::load($filePath);
               $sheetData = $spreadsheet->getActiveSheet()->toArray();
               $text = '';
   
               foreach ($sheetData as $row) {
                   $text .= implode("\t", $row) . "\n";
               }
   
               return $text ?: 'No readable content found in the Excel file.';
           } catch (\Exception $e) {
               return "Failed to extract text from Excel file: " . $e->getMessage();
           }
       }

}
