<?php

namespace App\Http\Controllers\Frontend;

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
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Spatie\PdfToImage\Pdf;
use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Storage;

use ZipArchive;

class ContractController extends Controller
{
    use MediaUploadingTrait, CsvImportTrait;

    public function index()
    {
        $currentDate = Carbon::now();

        abort_if(Gate::denies('contract_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contracts = Contract::with(['programs', 'funder', 'media'])
        ->whereNull('archived') // or where('archived', '=', null)
        ->get();

        $programs = Program::get();

        $funders = Funder::get();

        return view('frontend.contracts.index', compact('contracts', 'funders', 'programs'));
    }

    public function create()
    {
        abort_if(Gate::denies('contract_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $programs = Program::pluck('program_name', 'id');

        $funders = Funder::pluck('funder_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('frontend.contracts.create', compact('funders', 'programs'));
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

        // Generate the contract content and save as a .txt file
            //   $this->saveContractAsTxt($contract);

        return redirect()->route('frontend.contracts.index');
    }

    public function edit(Contract $contract)
    {
        abort_if(Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $programs = Program::pluck('program_name', 'id');

        $funders = Funder::pluck('funder_name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $contract->load('programs', 'funder');

        return view('frontend.contracts.edit', compact('contract', 'funders', 'programs'));
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
   // Generate the contract content and save as a .txt file
 
//    $this->saveContractAsTxt($contract);

        return redirect()->route('frontend.contracts.index');
    }

    public function show(Contract $contract)
    {
        abort_if(Gate::denies('contract_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contract->load('programs', 'funder', 'contractReportDueDates', 'contractReports');

        return view('frontend.contracts.show', compact('contract'));
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
    
    public function monitor()
    {
    abort_if(Gate::denies('contract_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

    $today = Carbon::now()->format('Y-m-d');

    // Subquery to get the minimum future due date for each contract
    $dueDatesSubquery = DB::table('report_due_dates')
        ->select('contract_id', DB::raw('MIN(due_date) as min_due_date'))
        ->where('due_date', '>=', $today)
        ->groupBy('contract_id');

    // Main query
    $contracts = Contract::joinSub($dueDatesSubquery, 'due_dates', function ($join) {
        $join->on('contracts.id', '=', 'due_dates.contract_id');
    })
    ->with(['contractReportDueDates' => function ($query) use ($today) {
        $query->where('due_date', '>=', $today)
              ->orderBy('due_date', 'asc');
    }])
    ->orderBy('due_dates.min_due_date', 'asc')
    ->select('contracts.*') // Ensure only contract fields are selected
    ->get()
    ->map(function ($contract) {
        $contract->contractReportDueDates = $contract->contractReportDueDates->take(1);
        return $contract;
    });

    $programs = Program::get();
    $funders = Funder::get();

    return view('frontend.contracts.monitor', compact('contracts', 'programs', 'funders'));
    }

    public function archivedContracts()
    {
    $archivedContracts = Contract::where('archived', 'yes')->get(); // Adjust based on your archived field and value
    $programs = Program::all(); // Assuming you have a Program model
    $funders = Funder::all(); // Assuming you have a Funder model

    return view('frontend.contracts.archived_contracts', compact('archivedContracts', 'funders', 'programs'));


    }

    /**
 * Save contract content as a .txt file in the storage/app/contracts directory.
 *
 * @param  \App\Models\Contract  $contract
 */
private function saveContractAsTxt($contract)
{
    $content = $this->generateContractContent($contract);

    // Define the storage path and filename
    $fileName = "contract_{$contract->id}.txt";
    $filePath = "contracts/{$fileName}";

    // Store the file in the 'contracts' directory
    Storage::disk('local')->put($filePath, $content);
}


public function exportContractsToText(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(480);

        $contracts = Contract::with(['programs', 'funder', 'media'])
        ->when($request->start_date, fn ($query) => $query->where('start_date', '>=', $request->start_date))
        ->when($request->end_date, fn ($query) => $query->where('end_date', '<=', $request->end_date))
        ->when($request->program, fn ($query) => $query->whereHas('programs', fn ($q) => $q->where('id', $request->program)))
        ->when($request->funder, fn ($query) => $query->where('funder_id', $request->funder))
        ->get();

    if ($contracts->isEmpty()) {
        return back()->with('error', 'No contracts found for the selected filters.');
    }

    // Create a new Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Contracts Export');

    // Set the header row
    $sheet->setCellValue('A1', 'Contract ID')
          ->setCellValue('B1', 'Title')
          ->setCellValue('C1', 'Funder')
          ->setCellValue('D1', 'Programs')
          ->setCellValue('E1', 'Amount')
          ->setCellValue('F1', 'Start Date')
          ->setCellValue('G1', 'End Date')
          ->setCellValue('H1', 'Grant Code')
          ->setCellValue('I1', 'Notes');

    // Fill the table with contract data
    $row = 2;  // Start at the second row
    foreach ($contracts as $contract) {
        $sheet->setCellValue("A{$row}", $contract->id)
              ->setCellValue("B{$row}", $contract->title)
              ->setCellValue("C{$row}", $contract->funder?->funder_name ?? 'No Funder Assigned')
              ->setCellValue("D{$row}", $contract->programs->pluck('program_name')->join(', ') ?? 'No Program Assigned')
              ->setCellValue("E{$row}", $contract->amount)
              ->setCellValue("F{$row}", $contract->start_date)
              ->setCellValue("G{$row}", $contract->end_date)
              ->setCellValue("H{$row}", $contract->grant_code)
              ->setCellValue("I{$row}", $contract->notes);
        $row++;
    }

    // Write the Excel file to the response
    $fileName = 'contracts_export_' . date('Y_m_d_H_i_s') . '.xlsx';
    $tempFile = storage_path("app/{$fileName}");

    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    // public function exportContractsToText(Request $request)
    // {
    //     ini_set('memory_limit', '512M');
    //     set_time_limit(480);

    //     $directory = $request->input('directory', storage_path('exports'));  // Default directory if not provided
    
    //     if (!file_exists($directory)) {
    //         mkdir($directory, 0777, true);  // Create directory if it doesn't exist
    //     }
    
    //     $contracts = Contract::with(['programs', 'funder', 'media'])
    //     ->when($request->start_date, function ($query) use ($request) {
    //         $query->where('start_date', '>=', $request->start_date);
    //     })
    //     ->when($request->end_date, function ($query) use ($request) {
    //         $query->where('end_date', '<=', $request->end_date);
    //     })
    //     ->when($request->program, function ($query) use ($request) {
    //         $query->whereHas('programs', function ($q) use ($request) {
    //             $q->where('id', $request->program);
    //         });
    //     })
    //     ->when($request->funder, function ($query) use ($request) {
    //         $query->where('funder_id', $request->funder);
    //     })
    //     ->get();
    
    //     foreach ($contracts as $contract) {
    //         $filename = $this->generateFileName($contract, $directory);
    //         $fileContent = $this->generateContractContent($contract);
    
    //         file_put_contents($filename, $fileContent);  // Save each contract as a separate text file
    //     }
    
    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Contracts exported successfully.',
    //         'saved_to' => $directory,
    //     ]);
    // }
    
    private function generateFileName($contract, $directory)
    {
        $safeTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', $contract->title);
        return "{$directory}/contract_{$contract->id}_{$safeTitle}.txt";
    }
    
    private function generateContractContent($contract)
    {
        $content = "Title: {$contract->title}\n";
        $content = "Purpose: {$contract->purpose}\n";
        $content .= "Funder: " . ($contract->funder?->funder_name ?? 'No Funder Assigned') . "\n";
        $content .= "Program: " . ($contract->programs->pluck('program_name')->join(', ') ?? 'No Program Assigned') . "\n";
        $content .= "Amount: {$contract->amount}\n";
        $content .= "Start Date: {$contract->start_date}\n";
        $content .= "End Date: {$contract->end_date}\n\n";
        $content .= "Notes: {$contract->notes}\n\n";
        $content .= "Fund Number: {$contract->grant_code}\n\n";
    
        foreach ($contract->getMedia('documents') as $media) {
            $content .= "Attached Document: {$media->file_name}\n";
    
            if ($media->mime_type === 'application/pdf') {
                $content .= $this->extractTextFromPdf($media->getPath()) . "\n";
            } elseif ($media->mime_type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                $content .= $this->extractTextFromWord($media->getPath()) . "\n";
            } elseif ($media->mime_type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
                $content .= $this->extractTextFromExcel($media->getPath()) . "\n";
            } else {
                $content .= "Unsupported file format: {$media->mime_type}\n";
            }
    
            $content .= "--------------------\n";
        }
    
        return $content;
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
                    $phpWord = WordIOFactory::load($filePath);
                    $text = '';
            
                    foreach ($phpWord->getSections() as $section) {
                        $elements = $section->getElements();
                        foreach ($elements as $element) {
                            if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                                $text .= $element->getText() . "\n";
                            } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                                $text .= $this->extractTextFromTextRun($element) . "\n";
                            }
                        }
                    }
            
                    return $text ?: 'No readable text found in the Word document.';
                } catch (\Throwable $e) {  // Catch all exceptions and errors
                    return "Failed to extract text from Word document: " . $e->getMessage();
                }
            }
            
            private function extractTextFromTextRun(\PhpOffice\PhpWord\Element\TextRun $textRun)
            {
                $text = '';
                foreach ($textRun->getElements() as $element) {
                    if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
                        $text .= $element->getText();  // Append only plain text elements
                    }
                }
                return $text;
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


           public function showExportForm()
           {
               $programs = Program::all();
               $funders = Funder::all();
               
               return view('frontend.contracts.export_form', compact('programs', 'funders'));
           }


}
