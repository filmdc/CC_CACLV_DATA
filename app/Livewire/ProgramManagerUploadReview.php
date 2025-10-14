<?php

namespace App\Livewire;

use App\Models\ProgramManagerUpload;
use App\Services\ImportExportService;
use App\Services\HMISPennsylvaniaImportService;
use App\Services\RXOfficeHousingImportService;
use App\Traits\WithPermissionChecks;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class ProgramManagerUploadReview extends Component
{
    use WithPagination;
    use WithPermissionChecks;

    public string $status = 'pending';
    public string $type = 'all';
    public string $search = '';
    public int $perPage = 15;

    public bool $showRejectModal = false;
    public ?int $selectedUploadId = null;
    public string $rejectNotes = '';

    protected $queryString = [
        'status' => ['except' => 'pending'],
        'type' => ['except' => 'all'],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->authorizeAction('program-manager-uploads.manage', 'You do not have permission to access this area.');
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = ProgramManagerUpload::query();

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->type !== 'all') {
            $query->where('type', $this->type);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $term = '%' . $this->search . '%';
                $q->where('program_manager_name', 'like', $term)
                    ->orWhere('program_manager_email', 'like', $term)
                    ->orWhere('organization', 'like', $term)
                    ->orWhere('original_filename', 'like', $term);
            });
        }

        $uploads = $query->orderByDesc('submitted_at')->paginate($this->perPage);

        return view('livewire.program-manager-upload-review', [
            'uploads' => $uploads,
        ]);
    }

    public function openRejectModal(int $uploadId): void
    {
        $this->authorizeAction('program-manager-uploads.manage');
        $this->selectedUploadId = $uploadId;
        $this->rejectNotes = '';
        $this->showRejectModal = true;
    }

    public function cancelRejection(): void
    {
        $this->showRejectModal = false;
        $this->selectedUploadId = null;
        $this->rejectNotes = '';
    }

    public function rejectUpload(): void
    {
        $this->authorizeAction('program-manager-uploads.manage');

        if (!$this->selectedUploadId) {
            session()->flash('error', 'No upload selected.');
            return;
        }

        $upload = ProgramManagerUpload::find($this->selectedUploadId);

        if (!$upload) {
            session()->flash('error', 'Upload not found.');
            return;
        }

        $upload->update([
            'status' => ProgramManagerUpload::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $this->rejectNotes,
        ]);

        $this->showRejectModal = false;
        $this->selectedUploadId = null;
        $this->rejectNotes = '';

        session()->flash('success', 'Upload has been marked as rejected.');
    }

    public function processUpload(
        int $uploadId,
        ImportExportService $importExportService,
        HMISPennsylvaniaImportService $hmisImportService,
        RXOfficeHousingImportService $rxOfficeImportService
    ): void {
        $this->authorizeAction('program-manager-uploads.manage');

        $upload = ProgramManagerUpload::find($uploadId);

        if (!$upload) {
            session()->flash('error', 'Upload not found.');
            return;
        }

        if ($upload->status === ProgramManagerUpload::STATUS_PROCESSING) {
            session()->flash('error', 'This upload is already being processed.');
            return;
        }

        if ($upload->status === ProgramManagerUpload::STATUS_COMPLETED) {
            session()->flash('error', 'This upload has already been processed successfully.');
            return;
        }

        if ($upload->status === ProgramManagerUpload::STATUS_REJECTED) {
            session()->flash('error', 'This upload has been rejected and cannot be processed.');
            return;
        }

        if (!Storage::exists($upload->file_path)) {
            $upload->update([
                'status' => ProgramManagerUpload::STATUS_FAILED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'processing_result' => ['success' => false, 'error' => 'File could not be found on the server.'],
            ]);

            session()->flash('error', 'Upload failed: original file could not be found.');
            return;
        }

        $upload->update([
            'status' => ProgramManagerUpload::STATUS_PROCESSING,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        try {
            $result = [];
            $filePath = Storage::path($upload->file_path);

            switch ($upload->type) {
                case 'general':
                    $extension = strtolower(pathinfo($upload->original_filename ?? '', PATHINFO_EXTENSION));

                    if (!in_array($extension, ['csv', 'txt'])) {
                        throw new \RuntimeException('General uploads must be CSV or TXT files before processing.');
                    }

                    $uploadedFile = new UploadedFile(
                        $filePath,
                        $upload->original_filename ?: basename($filePath),
                        null,
                        null,
                        true
                    );

                    $validation = $importExportService->validateImportFile($uploadedFile);

                    if (!($validation['success'] ?? false)) {
                        $messages = [];
                        if (!empty($validation['errors']) && is_array($validation['errors'])) {
                            $messages = $validation['errors'];
                        }

                        if (!empty($validation['error'])) {
                            $messages[] = $validation['error'];
                        }

                        $message = !empty($messages) ? implode(' ', $messages) : 'Validation failed for the uploaded file.';
                        throw new \RuntimeException($message);
                    }

                    $result = $importExportService->processImport($validation['temp_file_path']);
                    break;

                case 'hmis':
                    $result = $hmisImportService->processImport($filePath, []);
                    break;

                case 'rx_office':
                    $result = $rxOfficeImportService->importFromCsv($filePath);
                    break;

                default:
                    throw new \RuntimeException('Unsupported upload type: ' . $upload->type);
            }

            $upload->update([
                'status' => ($result['success'] ?? false) ? ProgramManagerUpload::STATUS_COMPLETED : ProgramManagerUpload::STATUS_FAILED,
                'processing_result' => $result,
                'reviewed_at' => now(),
            ]);

            if ($result['success'] ?? false) {
                session()->flash('success', 'Upload processed successfully.');
            } else {
                session()->flash('error', 'Upload processing completed with errors.');
            }
        } catch (\Throwable $e) {
            $upload->update([
                'status' => ProgramManagerUpload::STATUS_FAILED,
                'processing_result' => [
                    'success' => false,
                    'error' => $e->getMessage(),
                ],
                'reviewed_at' => now(),
            ]);

            session()->flash('error', 'Upload processing failed: ' . $e->getMessage());
        }
    }
}
