<?php

namespace App\Livewire;

use App\Models\HousingJob;
use App\Models\MilestoneDocument;
use App\Models\MilestoneTask;
use App\Models\ProjectBudget;
use App\Models\ProjectExpense;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Services\MediaValidationService;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProjectManagement extends Component
{
    use WithFileUploads, WithPermissionChecks;

    public $jobId;

    public $job;

    public $activeTab = 'milestones';

    public $selectedMilestone = null;

    // Milestone Form
    public $showMilestoneForm = false;

    public $editingMilestone = null;

    public $milestone_name = '';

    public $milestone_description = '';

    public $milestone_target_date = '';

    public $milestone_icon = 'Circle';

    public $milestone_color = '#6366f1';

    // Task Form
    public $showTaskForm = false;

    public $editingTask = null;

    public $task_name = '';

    public $task_description = '';

    public $task_assigned_to = '';

    public $task_deadline = '';

    public $task_requires_approval = false;

    // Document Form
    public $showDocumentForm = false;

    public $editingDocument = null;

    public $document_type = '';

    public $document_description = '';

    public $document_assigned_to = '';

    public $document_requires_approval = false;

    public $document_pdf_template_id = '';

    public $documentFiles = [];

    // Document Upload Form
    public $showDocumentUploadForm = false;

    public $uploadingDocumentId = null;

    public $uploadFiles = [];

    // Document Viewer/Review Form
    public $showDocumentViewerModal = false;

    public $viewingDocumentId = null;

    public $rejection_reason = '';

    // Budget Form
    public $showBudgetForm = false;

    public $editingBudget = null;

    public $budget_category = '';

    public $budget_amount = '';

    public $budget_contract_id = '';

    // Expense Form
    public $showExpenseForm = false;

    public $editingExpense = null;

    public $expense_category = '';

    public $expense_amount = '';

    public $expense_date = '';

    public $expense_description = '';

    public $expense_document_id = '';

    public $expense_contract_id = '';

    // Template Form
    public $showSaveAsTemplateForm = false;

    public $template_name = '';

    public $template_description = '';

    // Edit Project Form
    public $showEditProjectForm = false;

    public $edit_job_title = '';

    public $edit_job_description = '';

    public $edit_status = '';

    public $edit_priority = '';

    public $edit_request_date = '';

    public $edit_start_date = '';

    public $edit_target_completion_date = '';

    public $edit_estimated_cost = '';

    public $edit_contractor_id = '';

    public $edit_participant_id = '';

    public $edit_internal_notes = '';

    // Quick Participant Form
    public $showQuickParticipantForm = false;

    public $quick_participant_first_name = '';

    public $quick_participant_last_name = '';

    public $quick_participant_phone = '';

    public $quick_participant_email = '';

    public $quick_participant_dob = '';

    public $quick_participant_address = '';

    public $quick_participant_city = '';

    public $quick_participant_state = '';

    public $quick_participant_zip = '';

    protected $listeners = [
        'milestoneUpdated' => '$refresh',
        'item-selected-edit_participant' => 'onParticipantSelected',
    ];

    public function mount($id)
    {
        $this->jobId = $id;
        $this->job = HousingJob::with([
            'program',
            'participant',
            'property',
            'contractor',
            'milestones.tasks.assignedTo',
            'milestones.documents.media',
            'milestones.documents.assignedTo',
            'milestones.documents.approvedBy',
            'budgets',
            'expenses',
        ])->findOrFail($id);

        $this->authorizeAction('projects.view');

        // Fix any inconsistent document states on load
        foreach ($this->job->milestones as $milestone) {
            foreach ($milestone->documents as $document) {
                $hasFiles = $document->getMedia('documents')->count() > 0;

                // If status indicates files should exist but they don't, reset to 'not_uploaded'
                if (! $hasFiles && in_array($document->status, ['pending_review', 'uploaded'])) {
                    $document->status = 'not_uploaded';
                    $document->uploaded_date = null;
                    $document->save();

                    // Clean up any orphaned media records (files that don't exist on disk)
                    $document->clearMediaCollection('documents');
                }

                $this->cleanupDocumentMedia($document);
            }
        }

        // Select first milestone by default
        if ($this->job->milestones->count() > 0 && ! $this->selectedMilestone) {
            $this->selectedMilestone = $this->job->milestones->first()->id;
        }
    }

    public function selectMilestone($milestoneId)
    {
        $this->selectedMilestone = $milestoneId;
        $this->resetForms();
    }

    // Milestone Methods
    public function showCreateMilestone()
    {
        $this->resetMilestoneForm();
        $this->showMilestoneForm = true;
    }

    public function showEditMilestone($id)
    {
        $milestone = ProjectMilestone::findOrFail($id);
        $this->editingMilestone = $id;
        $this->milestone_name = $milestone->name;
        $this->milestone_description = $milestone->description;
        $this->milestone_target_date = $milestone->target_date?->format('Y-m-d');
        $this->milestone_icon = $milestone->icon;
        $this->milestone_color = $milestone->color;
        $this->showMilestoneForm = true;
    }

    public function saveMilestone()
    {
        $validated = $this->validate([
            'milestone_name' => 'required|string|max:255',
            'milestone_description' => 'nullable|string',
            'milestone_target_date' => 'nullable|date',
            'milestone_icon' => 'required|string',
            'milestone_color' => 'required|string',
        ]);

        if ($this->editingMilestone) {
            $milestone = ProjectMilestone::findOrFail($this->editingMilestone);
            $milestone->update([
                'name' => $this->milestone_name,
                'description' => $this->milestone_description !== '' ? $this->milestone_description : null,
                'target_date' => $this->milestone_target_date !== '' ? $this->milestone_target_date : null,
                'icon' => $this->milestone_icon,
                'color' => $this->milestone_color,
                'updated_by' => Auth::id(),
            ]);
            session()->flash('success', 'Milestone updated successfully');
        } else {
            $sequenceOrder = $this->job->milestones()->max('sequence_order') + 1;
            $milestone = ProjectMilestone::create([
                'housing_job_id' => $this->jobId,
                'name' => $this->milestone_name,
                'description' => $this->milestone_description !== '' ? $this->milestone_description : null,
                'target_date' => $this->milestone_target_date !== '' ? $this->milestone_target_date : null,
                'icon' => $this->milestone_icon,
                'color' => $this->milestone_color,
                'sequence_order' => $sequenceOrder,
                'status' => $sequenceOrder == 1 ? 'in_progress' : 'not_started',
                'created_by' => Auth::id(),
            ]);
            $this->selectedMilestone = $milestone->id;
            session()->flash('success', 'Milestone created successfully');
        }

        $this->showMilestoneForm = false;
        $this->resetMilestoneForm();
        $this->refreshJob();
    }

    public function deleteMilestone($id)
    {
        $milestone = ProjectMilestone::findOrFail($id);
        $milestone->delete();

        if ($this->selectedMilestone == $id) {
            $this->selectedMilestone = $this->job->milestones()->first()?->id;
        }

        session()->flash('success', 'Milestone deleted successfully');
        $this->refreshJob();
    }

    public function updateMilestoneStatus($id, $status)
    {
        $milestone = ProjectMilestone::findOrFail($id);

        if ($status === 'completed' && ! $milestone->canBeCompleted()) {
            session()->flash('error', 'Cannot complete milestone. All tasks and documents must be completed first.');

            return;
        }

        if ($status === 'completed') {
            $milestone->markAsCompleted();
        } else {
            $milestone->status = $status;
            $milestone->save();
        }

        session()->flash('success', 'Milestone status updated');
        $this->refreshJob();
    }

    // Task Methods
    public function showCreateTask()
    {
        if (! $this->selectedMilestone) {
            session()->flash('error', 'Please select a milestone first');

            return;
        }
        $this->resetTaskForm();
        $this->showTaskForm = true;
    }

    public function showEditTask($id)
    {
        $task = MilestoneTask::findOrFail($id);
        $this->editingTask = $id;
        $this->task_name = $task->name;
        $this->task_description = $task->description;
        $this->task_assigned_to = $task->assigned_to;
        $this->task_deadline = $task->deadline?->format('Y-m-d');
        $this->task_requires_approval = $task->requires_approval;
        $this->showTaskForm = true;
    }

    public function saveTask()
    {
        $validated = $this->validate([
            'task_name' => 'required|string|max:255',
            'task_description' => 'nullable|string',
            'task_assigned_to' => 'nullable|exists:users,id',
            'task_deadline' => 'nullable|date',
            'task_requires_approval' => 'boolean',
        ]);

        if ($this->editingTask) {
            $task = MilestoneTask::findOrFail($this->editingTask);
            $previousAssignee = $task->assigned_to;

            $task->update([
                'name' => $this->task_name,
                'description' => $this->task_description !== '' ? $this->task_description : null,
                'assigned_to' => $this->task_assigned_to !== '' ? $this->task_assigned_to : null,
                'deadline' => $this->task_deadline !== '' ? $this->task_deadline : null,
                'requires_approval' => $this->task_requires_approval,
                'updated_by' => Auth::id(),
            ]);

            // Send notification if assigned to a new user or re-assigned
            if ($this->task_assigned_to && $this->task_assigned_to !== $previousAssignee) {
                $assignee = User::find($this->task_assigned_to);
                $assignee->notify(new \App\Notifications\TaskAssignedNotification($task, $this->job));
            }

            session()->flash('success', 'Task updated successfully');
        } else {
            $task = MilestoneTask::create([
                'milestone_id' => $this->selectedMilestone,
                'name' => $this->task_name,
                'description' => $this->task_description !== '' ? $this->task_description : null,
                'assigned_to' => $this->task_assigned_to !== '' ? $this->task_assigned_to : null,
                'deadline' => $this->task_deadline !== '' ? $this->task_deadline : null,
                'requires_approval' => $this->task_requires_approval,
                'created_by' => Auth::id(),
            ]);

            // Send notification if assigned to a user
            if ($this->task_assigned_to) {
                $assignee = User::find($this->task_assigned_to);
                $assignee->notify(new \App\Notifications\TaskAssignedNotification($task, $this->job));
            }

            session()->flash('success', 'Task created successfully');
        }

        $this->showTaskForm = false;
        $this->resetTaskForm();
        $this->refreshJob();
    }

    public function deleteTask($id)
    {
        MilestoneTask::findOrFail($id)->delete();
        session()->flash('success', 'Task deleted successfully');
        $this->refreshJob();
    }

    public function updateTaskStatus($id, $status)
    {
        $task = MilestoneTask::findOrFail($id);

        if ($status === 'completed') {
            $task->markAsCompleted();
        } else {
            $task->status = $status;
            $task->save();
        }

        session()->flash('success', 'Task status updated');
        $this->refreshJob();
    }

    // Document Methods
    public function showCreateDocument()
    {
        if (! $this->selectedMilestone) {
            session()->flash('error', 'Please select a milestone first');

            return;
        }
        $this->resetDocumentForm();
        $this->showDocumentForm = true;
    }

    public function saveDocument()
    {
        $validated = $this->validate([
            'document_type' => 'required|string|max:255',
            'document_description' => 'nullable|string',
            'document_assigned_to' => 'nullable|exists:users,id',
            'document_requires_approval' => 'boolean',
            'document_pdf_template_id' => 'nullable|exists:pdf_form_templates,id',
            'documentFiles.*' => 'nullable|file|max:51200',
        ]);

        DB::beginTransaction();

        try {
            $document = MilestoneDocument::create([
                'milestone_id' => $this->selectedMilestone,
                'pdf_template_id' => $this->document_pdf_template_id !== '' ? $this->document_pdf_template_id : null,
                'document_type' => $this->document_type,
                'description' => $this->document_description !== '' ? $this->document_description : null,
                'assigned_to' => $this->document_assigned_to !== '' ? $this->document_assigned_to : null,
                'requires_approval' => $this->document_requires_approval,
                'created_by' => Auth::id(),
            ]);

            $uploadSummary = ['uploaded' => 0, 'failed' => []];

            if (! empty($this->documentFiles)) {
                $uploadSummary = $this->processDocumentUploads($document, $this->documentFiles);

                if ($uploadSummary['uploaded'] === 0) {
                    throw new \RuntimeException('All file uploads failed validation or storage.');
                }
            }

            // Auto-generate PDF if template is selected
            if ($document->pdf_template_id) {
                $template = \App\Models\PdfFormTemplate::find($document->pdf_template_id);
                if ($template) {
                    \App\Services\PdfFormService::generateAndAttach(
                        $template,
                        $document,
                        $this->job,
                        [
                            'flatten' => false,
                            'additionalContext' => [
                                'Participant' => $this->job->participant,
                                'Property' => $this->job->property,
                                'Contractor' => $this->job->contractor,
                                'Program' => $this->job->program,
                                'Service' => $this->job->service,
                            ],
                        ]
                    );
                }
            }

            DB::commit();

            // Send notification if assigned to a user
            if ($this->document_assigned_to) {
                $assignee = User::find($this->document_assigned_to);
                $assignee->notify(new \App\Notifications\DocumentAssignedNotification($document, $this->job));
            }

            $successMessage = 'Document requirement created successfully';

            if (! empty($this->documentFiles)) {
                $successMessage .= ' – '.$uploadSummary['uploaded'].' file(s) uploaded';
            }

            if ($document->pdf_template_id) {
                $successMessage .= ' – PDF form auto-generated and filled';
            }

            session()->flash('success', $successMessage);

            if (! empty($uploadSummary['failed'])) {
                $failedNames = collect($uploadSummary['failed'])->pluck('name')->filter()->implode(', ');
                session()->flash(
                    'error',
                    'Some files could not be uploaded: '.($failedNames ?: 'please verify the file type and size.')
                );
            }

            $this->showDocumentForm = false;
            $this->resetDocumentForm();
            $this->refreshJob();
        } catch (\Throwable $e) {
            DB::rollBack();

            if (isset($document) && $document->exists) {
                $document->delete();
            }

            \Log::error('Failed to save milestone document', [
                'milestone_id' => $this->selectedMilestone,
                'document_type' => $this->document_type,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Document requirement could not be saved: '.$e->getMessage());
        }
    }

    public function showUploadDocument($id)
    {
        $this->uploadingDocumentId = $id;
        $this->uploadFiles = [];
        $this->showDocumentUploadForm = true;
    }

    public function uploadDocumentFiles()
    {
        $this->validate([
            'uploadFiles.*' => 'required|file|max:51200',
        ]);

        $document = MilestoneDocument::findOrFail($this->uploadingDocumentId);

        $uploadSummary = $this->processDocumentUploads($document, $this->uploadFiles ?? []);

        if ($uploadSummary['uploaded'] > 0) {
            $message = $uploadSummary['uploaded'].' file(s) uploaded successfully';

            session()->flash('success', $message);

            if (! empty($uploadSummary['failed'])) {
                $failedNames = collect($uploadSummary['failed'])->pluck('name')->filter()->implode(', ');
                session()->flash(
                    'error',
                    'Some files could not be uploaded: '.($failedNames ?: 'please verify the file type and size.')
                );
            }
        } else {
            $failedNames = collect($uploadSummary['failed'])->pluck('name')->filter()->implode(', ');
            session()->flash(
                'error',
                'File upload failed: '.($failedNames ?: 'please verify the file type and size.')
            );
        }

        $this->showDocumentUploadForm = false;
        $this->uploadingDocumentId = null;
        $this->uploadFiles = [];
        $this->refreshJob();
    }

    public function showDocumentViewer($id)
    {
        $this->viewingDocumentId = $id;
        $this->rejection_reason = '';
        $this->showDocumentViewerModal = true;
    }

    public function approveDocumentFromViewer()
    {
        $document = MilestoneDocument::findOrFail($this->viewingDocumentId);
        $document->approve(Auth::user());

        session()->flash('success', 'Document approved successfully');
        $this->showDocumentViewerModal = false;
        $this->viewingDocumentId = null;
        $this->refreshJob();
    }

    public function rejectDocumentFromViewer()
    {
        $this->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $document = MilestoneDocument::findOrFail($this->viewingDocumentId);
        $document->reject(Auth::user(), $this->rejection_reason);

        session()->flash('success', 'Document rejected');
        $this->showDocumentViewerModal = false;
        $this->viewingDocumentId = null;
        $this->rejection_reason = '';
        $this->refreshJob();
    }

    public function approveTask($taskId)
    {
        $this->authorizeAction('projects.manage');

        try {
            $task = MilestoneTask::findOrFail($taskId);
            $task->approve(Auth::user());

            $this->job->load('milestones.tasks');
            session()->flash('success', 'Task approved successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Error approving task: '.$e->getMessage());
        }
    }

    public function rejectTask()
    {
        $this->authorizeAction('projects.manage');

        $this->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        try {
            if (! $this->editingTask) {
                throw new \Exception('No task selected for rejection');
            }

            $task = MilestoneTask::findOrFail($this->editingTask);
            $task->reject(Auth::user(), $this->rejection_reason);

            $this->job->load('milestones.tasks');
            $this->resetTaskForm();
            session()->flash('success', 'Task rejected. User will be notified.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error rejecting task: '.$e->getMessage());
        }
    }

    public function toggleDocumentApproval($documentId)
    {
        $this->authorizeAction('projects.manage');

        try {
            $document = MilestoneDocument::findOrFail($documentId);
            $document->requires_approval = ! $document->requires_approval;
            $document->save();

            $this->job->load('milestones.documents');
            session()->flash('success', 'Document approval requirement updated');
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating document: '.$e->getMessage());
        }
    }

    public function deleteDocumentFile($documentId, $mediaId)
    {
        $document = MilestoneDocument::findOrFail($documentId);
        $media = $document->getMedia('documents')->firstWhere('id', $mediaId);

        if ($media) {
            $media->delete();

            // Refresh the document to get updated media count
            $document->refresh();

            // Update status if no files remain
            if (! $document->has_files) {
                $document->status = 'not_uploaded';
                $document->uploaded_date = null;
                $document->save();
            }

            session()->flash('success', 'File deleted successfully');
            $this->refreshJob();
        }
    }

    protected function processDocumentUploads(MilestoneDocument $document, array $files): array
    {
        $summary = ['uploaded' => 0, 'failed' => []];

        foreach ($files as $file) {
            try {
                MediaValidationService::validate($file, MediaValidationService::DOCUMENT_MIME_TYPES, MediaValidationService::SIZE_100MB);

                $realPath = $file->getRealPath();

                if ($realPath === false) {
                    $realPath = method_exists($file, 'getPathname') ? $file->getPathname() : null;
                }

                if (! $realPath || ! file_exists($realPath)) {
                    throw new \RuntimeException('Uploaded file could not be located on disk.');
                }

                $document->addMedia($realPath)
                    ->usingName($file->getClientOriginalName())
                    ->usingFileName(MediaValidationService::sanitizeFileName(
                        $file->getClientOriginalName()
                    ))
                    ->withCustomProperties([
                        'uploaded_by' => Auth::id(),
                        'uploaded_at' => now()->toISOString(),
                    ])
                    ->toMediaCollection('documents');

                $summary['uploaded']++;
            } catch (\Throwable $e) {
                $summary['failed'][] = [
                    'name' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : null,
                    'error' => $e->getMessage(),
                ];

                \Log::error('Milestone document upload failed', [
                    'document_id' => $document->id,
                    'filename' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($summary['uploaded'] > 0) {
            $document->markAsUploaded();
        }

        $document->refresh();
        $this->cleanupDocumentMedia($document);

        return $summary;
    }

    protected function cleanupDocumentMedia(MilestoneDocument $document): void
    {
        $missingMedia = $document->missing_document_media;

        if ($missingMedia->isEmpty()) {
            return;
        }

        foreach ($missingMedia as $media) {
            try {
                $media->delete();
            } catch (\Throwable $e) {
                \Log::warning('Failed to delete missing media record', [
                    'media_id' => $media->id,
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $document->refresh();

        if (! $document->has_files && $document->status !== 'not_uploaded') {
            $document->status = 'not_uploaded';
            $document->uploaded_date = null;
            $document->save();
        }
    }

    public function deleteDocument($id)
    {
        MilestoneDocument::findOrFail($id)->delete();
        session()->flash('success', 'Document deleted successfully');
        $this->refreshJob();
    }

    public function regeneratePdf($documentId)
    {
        try {
            $document = MilestoneDocument::with('pdfTemplate')->findOrFail($documentId);

            if (! $document->hasPdfTemplate()) {
                session()->flash('error', 'No PDF template assigned to this document');

                return;
            }

            // Clear existing generated PDFs
            $document->pdfGenerations()->where('generation_status', 'success')->delete();

            // Regenerate the PDF and attach
            \App\Services\PdfFormService::generateAndAttach(
                $document->pdfTemplate,
                $document,
                $this->job,
                [
                    'flatten' => false,
                    'additionalContext' => [
                        'Participant' => $this->job->participant,
                        'Property' => $this->job->property,
                        'Contractor' => $this->job->contractor,
                        'Program' => $this->job->program,
                        'Service' => $this->job->service,
                    ],
                ]
            );

            session()->flash('success', 'PDF regenerated successfully with updated project data!');
            $this->refreshJob();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to regenerate PDF: '.$e->getMessage());
            \Log::error('PDF regeneration failed', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updateDocumentStatus($id, $status)
    {
        $document = MilestoneDocument::findOrFail($id);

        if ($status === 'approved') {
            $document->approve(Auth::user());
        } elseif ($status === 'rejected') {
            $document->reject(Auth::user(), 'Rejected by user');
        } else {
            $document->status = $status;
            $document->save();
        }

        session()->flash('success', 'Document status updated');
        $this->refreshJob();
    }

    // Budget Methods
    public function showCreateBudget()
    {
        $this->resetBudgetForm();
        $this->showBudgetForm = true;
    }

    public function saveBudget()
    {
        $validated = $this->validate([
            'budget_category' => 'required|string|max:255',
            'budget_amount' => 'required|numeric|min:0',
            'budget_contract_id' => 'nullable|exists:contracts,id',
        ]);

        ProjectBudget::create([
            'housing_job_id' => $this->jobId,
            'contract_id' => $this->budget_contract_id !== '' ? $this->budget_contract_id : null,
            'category' => $this->budget_category,
            'budgeted_amount' => $this->budget_amount,
            'created_by' => Auth::id(),
        ]);

        session()->flash('success', 'Budget category created successfully');
        $this->showBudgetForm = false;
        $this->resetBudgetForm();
        $this->refreshJob();
    }

    public function deleteBudget($id)
    {
        ProjectBudget::findOrFail($id)->delete();
        session()->flash('success', 'Budget category deleted successfully');
        $this->refreshJob();
    }

    // Expense Methods
    public function showCreateExpense()
    {
        $this->resetExpenseForm();
        $this->expense_date = now()->format('Y-m-d');
        $this->showExpenseForm = true;
    }

    public function saveExpense()
    {
        $validated = $this->validate([
            'expense_category' => 'required|string',
            'expense_amount' => 'required|numeric',
            'expense_date' => 'required|date',
            'expense_description' => 'nullable|string',
            'expense_document_id' => 'nullable|exists:milestone_documents,id',
            'expense_contract_id' => 'nullable|exists:contracts,id',
        ]);

        ProjectExpense::create([
            'housing_job_id' => $this->jobId,
            'contract_id' => $this->expense_contract_id !== '' ? $this->expense_contract_id : null,
            'category' => $this->expense_category,
            'amount' => $this->expense_amount,
            'expense_date' => $this->expense_date,
            'description' => $this->expense_description !== '' ? $this->expense_description : null,
            'document_id' => $this->expense_document_id !== '' ? $this->expense_document_id : null,
            'created_by' => Auth::id(),
        ]);

        session()->flash('success', 'Expense recorded successfully');
        $this->showExpenseForm = false;
        $this->resetExpenseForm();
        $this->refreshJob();
    }

    public function deleteExpense($id)
    {
        ProjectExpense::findOrFail($id)->delete();
        session()->flash('success', 'Expense deleted successfully');
        $this->refreshJob();
    }

    // Template Methods
    public function showSaveAsTemplate()
    {
        if ($this->job->milestones()->count() == 0) {
            session()->flash('error', 'Cannot save as template. Project must have at least one milestone.');

            return;
        }

        $this->showSaveAsTemplateForm = true;
    }

    public function saveAsTemplate()
    {
        try {
            $validated = $this->validate([
                'template_name' => 'required|string|max:255',
                'template_description' => 'nullable|string',
            ]);

            \Log::info('Creating template', [
                'job_id' => $this->job->id,
                'template_name' => $this->template_name,
            ]);

            $template = $this->job->saveAsTemplate($this->template_name, $this->template_description);

            \Log::info('Template created successfully', ['template_id' => $template->id]);

            $this->showSaveAsTemplateForm = false;
            $this->template_name = '';
            $this->template_description = '';

            session()->flash('success', 'Template "'.$template->template_name.'" created successfully with '.$template->milestones()->count().' milestones!');

            $this->refreshJob();
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Validation failed: '.implode(', ', $e->validator->errors()->all()));
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to create template', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to create template: '.$e->getMessage());
        }
    }

    // Edit Project Methods
    public function showEditProject()
    {
        $this->edit_job_title = $this->job->job_title;
        $this->edit_job_description = $this->job->job_description ?? '';
        $this->edit_status = $this->job->status;
        $this->edit_priority = $this->job->priority;
        $this->edit_request_date = $this->job->request_date?->format('Y-m-d') ?? '';
        $this->edit_start_date = $this->job->start_date?->format('Y-m-d') ?? '';
        $this->edit_target_completion_date = $this->job->target_completion_date?->format('Y-m-d') ?? '';
        $this->edit_estimated_cost = $this->job->estimated_cost ?? '';
        $this->edit_contractor_id = $this->job->contractor_id ?? '';
        $this->edit_participant_id = $this->job->participant_id ?? '';
        $this->edit_internal_notes = $this->job->internal_notes ?? '';
        $this->showEditProjectForm = true;
    }

    public function updateProjectInfo()
    {
        $validated = $this->validate([
            'edit_job_title' => 'required|string|max:255',
            'edit_job_description' => 'nullable|string',
            'edit_status' => 'required|in:pending,approved,in_progress,on_hold,completed,cancelled',
            'edit_priority' => 'required|in:low,medium,high,emergency',
            'edit_request_date' => 'nullable|date',
            'edit_start_date' => 'nullable|date',
            'edit_target_completion_date' => 'nullable|date',
            'edit_estimated_cost' => 'nullable|numeric|min:0',
            'edit_contractor_id' => 'nullable|exists:contractors,id',
            'edit_participant_id' => 'required|exists:participants,id',
            'edit_internal_notes' => 'nullable|string',
        ]);

        // Get participant to derive household_id
        $participant = \App\Models\Participant::findOrFail($this->edit_participant_id);

        // Verify participant is head of household
        if (! $participant->is_head_of_household) {
            session()->flash('error', 'Selected participant must be head of household');

            return;
        }

        $this->job->update([
            'job_title' => $this->edit_job_title,
            'job_description' => $this->edit_job_description !== '' ? $this->edit_job_description : null,
            'status' => $this->edit_status,
            'priority' => $this->edit_priority,
            'request_date' => $this->edit_request_date !== '' ? $this->edit_request_date : null,
            'start_date' => $this->edit_start_date !== '' ? $this->edit_start_date : null,
            'target_completion_date' => $this->edit_target_completion_date !== '' ? $this->edit_target_completion_date : null,
            'estimated_cost' => $this->edit_estimated_cost !== '' ? $this->edit_estimated_cost : null,
            'contractor_id' => $this->edit_contractor_id !== '' ? $this->edit_contractor_id : null,
            'participant_id' => $this->edit_participant_id,
            'household_id' => $participant->household_id,
            'internal_notes' => $this->edit_internal_notes !== '' ? $this->edit_internal_notes : null,
            'updated_by' => Auth::id(),
        ]);

        $this->showEditProjectForm = false;
        session()->flash('success', 'Project information updated successfully');
        $this->refreshJob();
    }

    public function onParticipantSelected($participantId)
    {
        $this->edit_participant_id = $participantId;
    }

    public function showQuickParticipantModal()
    {
        $this->resetQuickParticipantForm();
        $this->showQuickParticipantForm = true;
    }

    public function saveQuickParticipant()
    {
        $validated = $this->validate([
            'quick_participant_first_name' => 'required|string|max:255',
            'quick_participant_last_name' => 'required|string|max:255',
            'quick_participant_phone' => 'nullable|string',
            'quick_participant_email' => 'nullable|email',
            'quick_participant_dob' => 'nullable|date',
            'quick_participant_address' => 'required|string|max:255',
            'quick_participant_city' => 'required|string|max:100',
            'quick_participant_state' => 'required|string|size:2',
            'quick_participant_zip' => 'required|string|max:10',
        ]);

        // Auto-create household first
        $household = \App\Models\Household::create([
            'household_type' => 'Single Person',
            'household_size' => 1,
            'housing_status' => 'Unknown/not reported',
        ]);

        // Create household address
        $household->addresses()->create([
            'address' => $this->quick_participant_address,
            'city' => $this->quick_participant_city,
            'state' => strtoupper($this->quick_participant_state),
            'zip_code' => $this->quick_participant_zip,
            'is_current' => true,
        ]);

        // Create participant as head of household
        $participant = \App\Models\Participant::create([
            'household_id' => $household->id,
            'first_name' => $this->quick_participant_first_name,
            'last_name' => $this->quick_participant_last_name,
            'telephone' => $this->quick_participant_phone !== '' ? $this->quick_participant_phone : null,
            'email' => $this->quick_participant_email !== '' ? $this->quick_participant_email : null,
            'date_of_birth' => $this->quick_participant_dob !== '' ? $this->quick_participant_dob : null,
            'relationship_to_head' => 'self',
            'is_head_of_household' => true,
            'created_by' => Auth::id(),
        ]);

        $this->edit_participant_id = $participant->id;
        $this->showQuickParticipantForm = false;

        session()->flash('success', 'Participant and household created: '.
                         $participant->first_name.' '.$participant->last_name.
                         ' (Household: '.$household->household_code.' at '.$this->quick_participant_address.')');
    }

    protected function resetQuickParticipantForm()
    {
        $this->quick_participant_first_name = '';
        $this->quick_participant_last_name = '';
        $this->quick_participant_phone = '';
        $this->quick_participant_email = '';
        $this->quick_participant_dob = '';
        $this->quick_participant_address = '';
        $this->quick_participant_city = '';
        $this->quick_participant_state = '';
        $this->quick_participant_zip = '';
    }

    // Helper Methods
    protected function refreshJob()
    {
        $this->job = HousingJob::with([
            'program',
            'participant',
            'property',
            'contractor',
            'milestones.tasks.assignedTo',
            'milestones.documents.media',
            'milestones.documents.assignedTo',
            'milestones.documents.approvedBy',
            'budgets',
            'expenses.document',
        ])->findOrFail($this->jobId);

        // Fix any inconsistent document states (e.g., status is 'pending_review' but no files)
        foreach ($this->job->milestones as $milestone) {
            foreach ($milestone->documents as $document) {
                $hasFiles = $document->getMedia('documents')->count() > 0;

                // If status indicates files should exist but they don't, reset to 'not_uploaded'
                if (! $hasFiles && in_array($document->status, ['pending_review', 'uploaded'])) {
                    $document->status = 'not_uploaded';
                    $document->uploaded_date = null;
                    $document->save();

                    // Clean up any orphaned media records (files that don't exist on disk)
                    $document->clearMediaCollection('documents');
                }

                $this->cleanupDocumentMedia($document);
            }
        }
    }

    protected function resetForms()
    {
        $this->resetMilestoneForm();
        $this->resetTaskForm();
        $this->resetDocumentForm();
        $this->resetBudgetForm();
        $this->resetExpenseForm();
    }

    protected function resetMilestoneForm()
    {
        $this->showMilestoneForm = false;
        $this->editingMilestone = null;
        $this->milestone_name = '';
        $this->milestone_description = '';
        $this->milestone_target_date = '';
        $this->milestone_icon = 'Circle';
        $this->milestone_color = '#6366f1';
    }

    protected function resetTaskForm()
    {
        $this->showTaskForm = false;
        $this->editingTask = null;
        $this->task_name = '';
        $this->task_description = '';
        $this->task_assigned_to = '';
        $this->task_deadline = '';
        $this->task_requires_approval = false;
    }

    protected function resetDocumentForm()
    {
        $this->showDocumentForm = false;
        $this->editingDocument = null;
        $this->document_type = '';
        $this->document_description = '';
        $this->document_assigned_to = '';
        $this->document_requires_approval = false;
        $this->document_pdf_template_id = '';
        $this->documentFiles = [];
    }

    protected function resetBudgetForm()
    {
        $this->showBudgetForm = false;
        $this->editingBudget = null;
        $this->budget_category = '';
        $this->budget_amount = '';
        $this->budget_contract_id = '';
    }

    protected function resetExpenseForm()
    {
        $this->showExpenseForm = false;
        $this->editingExpense = null;
        $this->expense_category = '';
        $this->expense_amount = '';
        $this->expense_date = '';
        $this->expense_description = '';
        $this->expense_document_id = '';
        $this->expense_contract_id = '';
    }

    public function render()
    {
        // Get only users who have access to the CAH program
        $cahProgram = \App\Models\Program::where('name', 'LIKE', '%CAH%')->first();

        $users = User::where('active', true)
            ->where(function ($query) use ($cahProgram) {
                // Include admins and Contract Managers (have access to all programs)
                $query->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['Admin', 'Contract Manager']);
                })
                // Or users specifically assigned to the CAH program
                    ->when($cahProgram, function ($q) use ($cahProgram) {
                        $q->orWhereHas('programs', function ($pq) use ($cahProgram) {
                            $pq->where('program_id', $cahProgram->id);
                        });
                    });
            })
            ->orderBy('name')
            ->get();

        $currentMilestone = $this->selectedMilestone
            ? $this->job->milestones->firstWhere('id', $this->selectedMilestone)
            : null;

        // Get active PDF templates
        $pdfTemplates = \App\Models\PdfFormTemplate::active()->orderBy('name')->get();

        // Get contractors for the edit form
        $contractors = \App\Models\Contractor::orderBy('company_name')->get();

        // Get active contracts for budget/expense assignment
        $contracts = \App\Models\Contract::active()
            ->with('funder')
            ->orderBy('title')
            ->get();

        // Get participants (heads of household only) for the searchable select
        $participantsSearchable = \App\Models\Participant::where('is_head_of_household', true)
            ->with('household')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($p) {
                $secondaryInfo = [];
                if ($p->household && $p->household->household_code) {
                    $secondaryInfo[] = 'HH: '.$p->household->household_code;
                }
                if ($p->email) {
                    $secondaryInfo[] = $p->email;
                } elseif ($p->telephone) {
                    $secondaryInfo[] = $p->telephone;
                }

                return [
                    'id' => $p->id,
                    'display' => $p->first_name.' '.$p->last_name,
                    'secondary' => implode(' - ', $secondaryInfo),
                ];
            })->toArray();

        return view('livewire.project-management', [
            'users' => $users,
            'currentMilestone' => $currentMilestone,
            'participants' => $participantsSearchable,
            'pdfTemplates' => $pdfTemplates,
            'contractors' => $contractors,
            'contracts' => $contracts,
        ]);
    }
}
