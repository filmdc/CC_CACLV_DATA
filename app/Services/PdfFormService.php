<?php

namespace App\Services;

use App\Models\PdfFormTemplate;
use App\Models\PdfFormFieldMapping;
use App\Models\PdfFormGeneration;
use App\Models\MilestoneDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PdfFormService
{
    /**
     * Upload and create a new PDF form template
     *
     * @param UploadedFile $file PDF file
     * @param array $data Template metadata (name, description, category)
     * @return PdfFormTemplate Created template
     * @throws \Exception if upload or field extraction fails
     */
    public static function createTemplate(UploadedFile $file, array $data): PdfFormTemplate
    {
        // Validate file is PDF
        if ($file->getMimeType() !== 'application/pdf') {
            throw new \Exception('File must be a PDF');
        }

        DB::beginTransaction();

        try {
            // Ensure directory exists
            $directory = storage_path('app/pdf_templates');
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Store the file
            $filename = uniqid() . '_' . time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('pdf_templates', $filename);

            // Verify file was uploaded
            $fullPath = storage_path('app/' . $path);
            \Log::info('PDF template uploaded', [
                'path' => $path,
                'full_path' => $fullPath,
                'file_exists' => file_exists($fullPath),
            ]);

            if (!file_exists($fullPath)) {
                throw new \Exception("File upload succeeded but file not found at: {$fullPath}");
            }

            // Create template record
            $template = PdfFormTemplate::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'] ?? null,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => 'application/pdf',
                'file_size' => $file->getSize(),
                'field_count' => 0,
                'created_by' => Auth::id(),
            ]);

            // Extract form fields
            self::scanTemplateFields($template);

            DB::commit();

            return $template;
        } catch (\Exception $e) {
            DB::rollBack();

            // Clean up file if it was uploaded
            if (isset($path) && Storage::exists($path)) {
                Storage::delete($path);
            }

            throw $e;
        }
    }

    /**
     * Scan a template's PDF file and create field mappings
     *
     * @param PdfFormTemplate $template Template to scan
     * @return int Number of fields found
     * @throws \Exception if scanning fails
     */
    public static function scanTemplateFields(PdfFormTemplate $template): int
    {
        try {
            // Extract fields from PDF
            $fields = PdfFormFieldExtractor::scan($template->full_path);

            // Delete existing field mappings
            $template->fieldMappings()->delete();

            // Create field mapping records
            $displayOrder = 0;
            foreach ($fields as $field) {
                PdfFormFieldMapping::create([
                    'template_id' => $template->id,
                    'field_name' => $field['name'],
                    'field_type' => $field['type'],
                    'field_label' => $field['label'],
                    'mapping_type' => 'unmapped',
                    'field_flags' => $field['flags'] ?? [],
                    'field_options' => $field['options'] ?? null,
                    'display_order' => $displayOrder++,
                ]);
            }

            // Update template metadata
            $template->update([
                'field_count' => count($fields),
                'last_field_scan' => now(),
            ]);

            return count($fields);
        } catch (\Exception $e) {
            \Log::error('Template field scan failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate a filled PDF from a template
     *
     * @param PdfFormTemplate $template PDF template
     * @param Model $primaryModel Primary model (e.g., HousingJob)
     * @param array $options Generation options
     * @return PdfFormGeneration Generation record
     * @throws \Exception if generation fails
     */
    public static function generatePdf(
        PdfFormTemplate $template,
        Model $primaryModel,
        array $options = []
    ): PdfFormGeneration {
        $options = array_merge([
            'flatten' => false,
            'additionalContext' => [],
            'milestone_document_id' => null,
        ], $options);

        DB::beginTransaction();

        try {
            // Resolve field data
            $fieldData = PdfFormDataMapper::resolveData(
                $template,
                $primaryModel,
                $options['additionalContext']
            );

            // Fill the PDF form
            $filledPdfPath = PdfFormFiller::fill(
                $template->full_path,
                $fieldData,
                [
                    'flatten' => $options['flatten'],
                    'needAppearances' => true,
                ]
            );

            // Move to permanent storage
            $permanentPath = self::moveToStorage($filledPdfPath, $template);

            // Create generation record
            $generation = PdfFormGeneration::create([
                'template_id' => $template->id,
                'generatable_type' => get_class($primaryModel),
                'generatable_id' => $primaryModel->id,
                'file_path' => $permanentPath,
                'original_filename' => $template->original_filename,
                'file_size' => filesize(storage_path('app/' . $permanentPath)),
                'filled_field_count' => count(array_filter($fieldData)),
                'total_field_count' => $template->field_count,
                'generation_status' => 'success',
                'field_data' => $fieldData,
                'milestone_document_id' => $options['milestone_document_id'],
                'is_flattened' => $options['flatten'],
                'generated_by' => Auth::id(),
                'generated_at' => now(),
            ]);

            // Increment template usage counter
            $template->incrementUsage();

            DB::commit();

            return $generation;
        } catch (\Exception $e) {
            DB::rollBack();

            // Create failed generation record for audit trail
            PdfFormGeneration::create([
                'template_id' => $template->id,
                'generatable_type' => get_class($primaryModel),
                'generatable_id' => $primaryModel->id,
                'file_path' => '',
                'original_filename' => $template->original_filename,
                'generation_status' => 'failed',
                'error_message' => $e->getMessage(),
                'generated_by' => Auth::id(),
                'generated_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate a PDF and attach to MilestoneDocument
     *
     * @param PdfFormTemplate $template PDF template
     * @param MilestoneDocument $document Milestone document
     * @param Model $primaryModel Primary model (usually HousingJob)
     * @param array $options Generation options
     * @return void
     * @throws \Exception if generation or attachment fails
     */
    public static function generateAndAttach(
        PdfFormTemplate $template,
        MilestoneDocument $document,
        Model $primaryModel,
        array $options = []
    ): void {
        // Generate PDF with milestone document reference
        $generation = self::generatePdf($template, $primaryModel, array_merge($options, [
            'milestone_document_id' => $document->id,
        ]));

        // Attach to milestone document using Spatie Media Library
        $document->addMedia($generation->full_path)
            ->usingName($generation->original_filename)
            ->usingFileName(MediaValidationService::sanitizeFileName($generation->original_filename))
            ->withCustomProperties([
                'generated_by' => Auth::id(),
                'generated_at' => now()->toISOString(),
                'pdf_generation_id' => $generation->id,
                'template_id' => $template->id,
            ])
            ->toMediaCollection('documents');

        // Update document status
        $document->markAsUploaded();
    }

    /**
     * Move temporary filled PDF to permanent storage
     *
     * @param string $tempPath Temporary file path
     * @param PdfFormTemplate $template Template used
     * @return string Storage path
     */
    protected static function moveToStorage(string $tempPath, PdfFormTemplate $template): string
    {
        $filename = 'filled_' . $template->id . '_' . uniqid() . '_' . time() . '.pdf';
        $storagePath = 'pdf_generated/' . $filename;

        // Ensure directory exists
        $directory = storage_path('app/pdf_generated');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Move file
        $destinationPath = storage_path('app/' . $storagePath);
        if (!rename($tempPath, $destinationPath)) {
            throw new \Exception('Failed to move filled PDF to storage');
        }

        return $storagePath;
    }

    /**
     * Delete a template and all associated data
     *
     * @param PdfFormTemplate $template Template to delete
     * @return bool Success status
     */
    public static function deleteTemplate(PdfFormTemplate $template): bool
    {
        try {
            // Model's boot method will handle cascading deletes
            return $template->delete();
        } catch (\Exception $e) {
            \Log::error('Template deletion failed', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get available models for field mapping
     *
     * @return array Array of model names
     */
    public static function getAvailableModels(): array
    {
        return [
            'Participant' => 'App\Models\Participant',
            'Household' => 'App\Models\Household',
            'Property' => 'App\Models\Property',
            'HousingJob' => 'App\Models\HousingJob',
            'Contractor' => 'App\Models\Contractor',
            'Contract' => 'App\Models\Contract',
            'Program' => 'App\Models\Program',
            'Service' => 'App\Models\Service',
        ];
    }

    /**
     * Get available computed methods
     *
     * @return array Array of method names and descriptions
     */
    public static function getComputedMethods(): array
    {
        return [
            'todayDate' => 'Today\'s Date',
            'currentUserName' => 'Current User Name',
            'calculateAge' => 'Calculate Age from DOB',
            'formatPhoneNumber' => 'Format Phone Number',
            'fullAddress' => 'Full Address (Single Line)',
            'formatCurrency' => 'Format as Currency',
            'yesNo' => 'Yes/No from Boolean',
        ];
    }
}
