<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use setasign\Fpdi\Fpdi;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContractPdfExportService
{
    protected array $temporaryFiles = [];

    protected ?Carbon $generationTimestamp = null;

    /**
     * Generate the PDF export for a contract.
     */
    public function generate(Contract $contract): string
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $this->generationTimestamp = now();

        $contract->loadMissing([
            'funder',
            'program',
            'programs',
            'reportDueDates.contractReports.media',
            'media',
            'createdBy',
            'updatedBy',
        ]);

        $segments = [];

        // Summary page (contract details)
        $segments[] = $this->renderViewToPdf('exports.contract-pdf-summary', [
            'generatedAt' => $this->generationTimestamp,
            'contract' => $contract,
            'summary' => $this->buildContractSummary($contract),
        ]);

        // Contract documents
        $segments = array_merge($segments, $this->prepareContractDocuments($contract));

        // Due date overview
        $dueDateSummaries = $this->buildDueDateSummaries($contract);
        if ($dueDateSummaries->isNotEmpty()) {
            $segments[] = $this->renderViewToPdf('exports.contract-pdf-due-dates', [
                'generatedAt' => $this->generationTimestamp,
                'contract' => $contract,
                'dueDateSummaries' => $dueDateSummaries,
            ]);
        }

        // Report submissions
        $segments = array_merge($segments, $this->prepareReportSubmissionDocuments($contract));

        $output = $this->mergePdfSegments($segments);

        $this->cleanupTemporaryFiles();

        return $output;
    }

    /**
     * Build a filename for the generated export.
     */
    public function generateFilename(Contract $contract): string
    {
        $slug = Str::slug($contract->title ?: 'contract');
        $timestamp = $this->generationTimestamp?->format('Ymd-His') ?? now()->format('Ymd-His');

        return "{$slug}-export-{$timestamp}.pdf";
    }

    /**
     * Build the contract summary data for the first page.
     */
    protected function buildContractSummary(Contract $contract): array
    {
        $programNames = $contract->getAllPrograms()->pluck('name')->implode(', ');

        return [
            'title' => $contract->title,
            'contract_number' => $contract->contract_number ?: 'Not specified',
            'status' => $contract->status,
            'grant_code' => $contract->grant_code ?: 'Not specified',
            'cfda_number' => $contract->cfda_number ?: 'Not specified',
            'amount' => $contract->amount ? number_format($contract->amount, 2) : null,
            'purpose' => $contract->purpose ?: 'No purpose provided.',
            'funder' => $contract->funder?->name ?? 'Not assigned',
            'programs' => $programNames ?: 'Not assigned',
            'start_date' => $this->formatDate($contract->start_date),
            'end_date' => $this->formatDate($contract->end_date),
            'date_of_award' => $this->formatDate($contract->date_of_award),
            'archived' => $contract->archived,
            'date_archived' => $this->formatDate($contract->date_archived),
            'created_at' => $this->formatDate($contract->created_at),
            'updated_at' => $this->formatDate($contract->updated_at),
            'created_by' => $contract->createdBy?->name,
            'updated_by' => $contract->updatedBy?->name,
            'documents_count' => $contract->documents->count(),
            'report_due_dates_count' => $contract->reportDueDates->count(),
        ];
    }

    /**
     * Prepare contract documents for inclusion in the PDF.
     *
     * @return array<int, string> Paths to PDF segments
     */
    protected function prepareContractDocuments(Contract $contract): array
    {
        $segments = [];

        $documents = $contract->documents;
        if ($documents->isEmpty()) {
            return $segments;
        }

        foreach ($documents as $index => $media) {
            $label = 'Contract Document ' . ($index + 1);

            $conversion = $this->convertMediaContentToPdf($media, $label);

            if (! empty($conversion['paths'])) {
                $segments[] = $this->renderDocumentCoverPage($label, $media, [
                    'Storage Disk' => $media->disk,
                    'Uploaded' => $this->formatDate($media->created_at),
                ]);
                $segments = array_merge($segments, $conversion['paths']);
            } else {
                $segments[] = $this->renderPlaceholderPage(
                    $label,
                    $media,
                    $conversion['placeholder'] ?? 'This file cannot be embedded in the PDF export.'
                );
            }
        }

        return $segments;
    }

    /**
     * Prepare report submission documents for inclusion.
     *
     * @return array<int, string>
     */
    protected function prepareReportSubmissionDocuments(Contract $contract): array
    {
        $segments = [];
        $counter = 1;

        $contract->reportDueDates
            ->sortBy('due_date')
            ->each(function ($dueDate) use (&$segments, &$counter) {
                $dueDateLabel = sprintf(
                    '%s (%s)',
                    $dueDate->report_type ? Str::headline($dueDate->report_type) : 'Report',
                    $dueDate->due_date ? $dueDate->due_date->format('M j, Y') : 'No due date'
                );

                $dueDate->contractReports->each(function (ContractReport $report) use (&$segments, &$counter, $dueDate, $dueDateLabel) {
                    $report->getMedia('submissions')->each(function (Media $media) use (&$segments, &$counter, $report, $dueDate, $dueDateLabel) {
                        $label = sprintf('Report Submission %02d', $counter++);

                        $details = [
                            'Due Date' => $dueDate->due_date ? $dueDate->due_date->format('M j, Y') : 'N/A',
                            'Report Type' => $dueDateLabel,
                            'Submission Title' => $report->title ?: 'Submission',
                            'Status' => Str::headline($report->status ?? 'Unknown'),
                            'Submitted On' => $report->submitted_date ? $report->submitted_date->format('M j, Y') : 'N/A',
                        ];

                        $conversion = $this->convertMediaContentToPdf($media, $label);

                        if (! empty($conversion['paths'])) {
                            $segments[] = $this->renderDocumentCoverPage($label, $media, $details);
                            $segments = array_merge($segments, $conversion['paths']);
                        } else {
                            $segments[] = $this->renderPlaceholderPage(
                                $label,
                                $media,
                                $conversion['placeholder'] ?? 'This submission cannot be embedded in the PDF export.',
                                $details
                            );
                        }
                    });
                });
            });

        return $segments;
    }

    /**
     * Convert a media item into PDF segments.
     *
     * @return array{paths: array<int, string>, placeholder: ?string}
     */
    protected function convertMediaContentToPdf(Media $media, string $label): array
    {
        $path = $media->getPath();
        $extension = strtolower($media->extension ?? pathinfo($media->file_name, PATHINFO_EXTENSION));

        if (! file_exists($path)) {
            return [
                'paths' => [],
                'placeholder' => "The original file for '{$media->file_name}' is missing from storage.",
            ];
        }

        try {
            switch ($extension) {
                case 'pdf':
                    return [
                        'paths' => [$path],
                        'placeholder' => null,
                    ];

                case 'png':
                case 'jpg':
                case 'jpeg':
                case 'gif':
                case 'bmp':
                    return [
                        'paths' => [$this->convertImageToPdf($path)],
                        'placeholder' => null,
                    ];

                case 'doc':
                case 'docx':
                case 'rtf':
                case 'odt':
                    $html = $this->convertWordToHtml($path);
                    if (trim(strip_tags($html)) === '') {
                        return [
                            'paths' => [],
                            'placeholder' => 'No readable text content was detected in this document.',
                        ];
                    }

                    return [
                        'paths' => [
                            $this->renderHtmlContentPage($label, $media->file_name, $html),
                        ],
                        'placeholder' => null,
                    ];

                case 'txt':
                    return [
                        'paths' => [
                            $this->renderHtmlContentPage($label, $media->file_name, $this->convertTextToHtml($path)),
                        ],
                        'placeholder' => null,
                    ];

                case 'eml':
                    return [
                        'paths' => [
                            $this->renderHtmlContentPage($label, $media->file_name, $this->convertEmlToHtml($path)),
                        ],
                        'placeholder' => null,
                    ];

                case 'msg':
                    return [
                        'paths' => [],
                        'placeholder' => "The Outlook message '{$media->file_name}' cannot be embedded. Download the original message from the contract record.",
                    ];

                case 'xls':
                case 'xlsx':
                case 'csv':
                    return [
                        'paths' => [],
                        'placeholder' => "Spreadsheet files such as '{$media->file_name}' should be downloaded separately to view the full workbook.",
                    ];

                default:
                    return [
                        'paths' => [],
                        'placeholder' => "Files of type " . strtoupper($extension ?: 'unknown') . " are not supported in the PDF export.",
                    ];
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to convert media for contract PDF export', [
                'media_id' => $media->id,
                'file_name' => $media->file_name,
                'label' => $label,
                'error' => $e->getMessage(),
            ]);

            return [
                'paths' => [],
                'placeholder' => "An error occurred while preparing '{$media->file_name}'. Download the original file to review it.",
            ];
        }
    }

    /**
     * Build the due date summaries for the overview section.
     */
    protected function buildDueDateSummaries(Contract $contract): Collection
    {
        return $contract->reportDueDates
            ->sortBy('due_date')
            ->map(function ($dueDate) {
                $submittedReports = $dueDate->contractReports->whereNotNull('submitted_date');

                return [
                    'type' => $dueDate->report_type ? Str::headline($dueDate->report_type) : 'Other',
                    'due_date' => $dueDate->due_date ? $dueDate->due_date->format('M j, Y') : 'N/A',
                    'status' => Str::headline($dueDate->status ?? 'Unknown'),
                    'description' => $dueDate->description ?? '',
                    'active' => (bool) $dueDate->active,
                    'submitted_count' => $submittedReports->count(),
                    'last_submitted_date' => $submittedReports->isNotEmpty()
                        ? optional($submittedReports->sortByDesc('submitted_date')->first()->submitted_date)->format('M j, Y')
                        : null,
                ];
            });
    }

    /**
     * Render a generic document cover page with metadata.
     */
    protected function renderDocumentCoverPage(string $label, Media $media, array $additionalDetails = []): string
    {
        $details = array_filter(array_merge([
            'File Name' => $media->file_name,
            'Display Name' => $media->name,
            'Type' => strtoupper($media->extension ?? 'N/A'),
            'File Size' => $media->human_readable_size ?? null,
        ], $additionalDetails));

        $bodyHtml = '<p>This page marks the start of the document. The following pages contain the document content as stored in the contract record.</p>';

        return $this->renderDocumentPage($label, 'Document Overview', $details, $bodyHtml);
    }

    /**
     * Render a placeholder page when the original file cannot be embedded.
     */
    protected function renderPlaceholderPage(string $label, Media $media, string $message, array $additionalDetails = []): string
    {
        $details = array_filter(array_merge([
            'File Name' => $media->file_name,
            'Display Name' => $media->name,
            'Type' => strtoupper($media->extension ?? 'N/A'),
            'File Size' => $media->human_readable_size ?? null,
        ], $additionalDetails));

        $bodyHtml = '<p>' . e($message) . '</p>';

        return $this->renderDocumentPage($label, 'Document Placeholder', $details, $bodyHtml);
    }

    /**
     * Render HTML content extracted from a document into a PDF segment.
     */
    protected function renderHtmlContentPage(string $label, string $filename, string $html): string
    {
        $details = array_filter([
            'Source File' => $filename,
        ]);

        return $this->renderDocumentPage($label, 'Document Content', $details, $html);
    }

    /**
     * Render a document page using the shared blade template.
     */
    protected function renderDocumentPage(string $title, ?string $subtitle, array $details, string $bodyHtml): string
    {
        return $this->renderViewToPdf('exports.contract-pdf-document-page', [
            'generatedAt' => $this->generationTimestamp,
            'title' => $title,
            'subtitle' => $subtitle,
            'details' => $details,
            'bodyHtml' => $bodyHtml,
        ]);
    }

    /**
     * Render a blade view to a PDF file and return its path.
     */
    protected function renderViewToPdf(string $view, array $data): string
    {
        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('letter');

        $path = $this->trackTemporary(tempnam(sys_get_temp_dir(), 'contract_pdf_') . '.pdf');
        file_put_contents($path, $pdf->output());

        return $path;
    }

    /**
     * Merge multiple PDF segments together.
     *
     * @param  array<int, string>  $paths
     */
    protected function mergePdfSegments(array $paths): string
    {
        $pdf = new Fpdi();

        foreach ($paths as $path) {
            if (! file_exists($path)) {
                continue;
            }

            $pageCount = $pdf->setSourceFile($path);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }

        return $pdf->Output('S');
    }

    /**
     * Convert an image file to a single-page PDF and track the temporary file.
     */
    protected function convertImageToPdf(string $path): string
    {
        $imagick = new \Imagick($path);
        $imagick->setImageFormat('pdf');
        $imagick->setResolution(150, 150);

        $tempPath = $this->trackTemporary(tempnam(sys_get_temp_dir(), 'contract_img_pdf_') . '.pdf');
        $imagick->writeImage($tempPath);

        $imagick->clear();
        $imagick->destroy();

        return $tempPath;
    }

    /**
     * Convert a Word document to simple HTML content.
     */
    protected function convertWordToHtml(string $path): string
    {
        try {
            $phpWord = WordIOFactory::load($path);
            $paragraphs = [];

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if ($element instanceof Text) {
                        $paragraphs[] = trim($element->getText());
                    } elseif ($element instanceof TextRun) {
                        $paragraphs[] = trim($this->extractTextFromTextRun($element));
                    } elseif ($element instanceof TextBreak) {
                        $paragraphs[] = '';
                    }
                }
            }

            $paragraphs = array_filter($paragraphs, fn ($line) => $line !== '');

            if (empty($paragraphs)) {
                return '';
            }

            return collect($paragraphs)
                ->map(fn ($line) => '<p>' . e($line) . '</p>')
                ->implode("\n");
        } catch (\Throwable $e) {
            Log::warning('Failed to extract text from Word document', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Extract text from a TextRun element.
     */
    protected function extractTextFromTextRun(TextRun $textRun): string
    {
        $text = '';
        foreach ($textRun->getElements() as $element) {
            if ($element instanceof Text) {
                $text .= $element->getText();
            }
        }

        return $text;
    }

    /**
     * Convert a plain text file to HTML.
     */
    protected function convertTextToHtml(string $path): string
    {
        $contents = file_get_contents($path) ?: '';
        return '<pre class="whitespace-pre-wrap text-sm">' . e($contents) . '</pre>';
    }

    /**
     * Convert an .eml file to readable HTML.
     */
    protected function convertEmlToHtml(string $path): string
    {
        $raw = file_get_contents($path) ?: '';

        [$rawHeaders, $rawBody] = preg_split("/\R\R/", $raw, 2) + ['', ''];

        $headerLines = collect(explode("\n", $rawHeaders))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->map(function ($line) {
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    return '<strong>' . e(trim($name)) . ':</strong> ' . e(trim($value));
                }

                return e($line);
            })
            ->implode('<br>');

        $body = e($rawBody);

        return <<<HTML
            <div class="mail-print">
                <h4>Email Headers</h4>
                <div class="mail-headers">{$headerLines}</div>
                <hr>
                <h4>Email Body</h4>
                <pre class="mail-body whitespace-pre-wrap text-sm">{$body}</pre>
            </div>
        HTML;
    }

    /**
     * Track temporary files for cleanup.
     */
    protected function trackTemporary(string $path): string
    {
        $this->temporaryFiles[] = $path;
        return $path;
    }

    /**
     * Delete any temporary files created during generation.
     */
    protected function cleanupTemporaryFiles(): void
    {
        foreach ($this->temporaryFiles as $path) {
            @unlink($path);
        }

        $this->temporaryFiles = [];
    }

    /**
     * Format a date value for display.
     */
    protected function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('M j, Y');
        }

        try {
            return Carbon::parse($value)->format('M j, Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
