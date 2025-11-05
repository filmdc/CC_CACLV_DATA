# Housing Module Approval Workflow Guide

## Table of Contents
1. [Overview](#overview)
2. [Approval Requirements](#approval-requirements)
3. [Milestone Gating Logic](#milestone-gating-logic)
4. [Resubmission Workflow](#resubmission-workflow)
5. [Developer Integration Guide](#developer-integration-guide)
6. [User Guide](#user-guide)
7. [Troubleshooting](#troubleshooting)

## Overview

The approval workflow system ensures quality control and compliance for housing rehabilitation jobs by:
- Requiring approval for critical documents and tasks
- Preventing job completion until all approvals are satisfied
- Automatically handling document resubmissions after rejection
- Maintaining complete audit trail
- Providing clear progress indicators

## Approval Requirements

### What Can Require Approval?

1. **Property Documents**
   - Deed, Title, Insurance, Inspection Reports
   - Assessment documents, Permits, Contracts
   - Photos documenting conditions

2. **Contractor Documents**
   - Licenses, Insurance certificates
   - W-9 forms, Service agreements
   - Certifications, References

3. **Job Documents** (Checklist Items)
   - Scope of Work documents
   - Change orders
   - Completion certificates
   - Final inspection reports

4. **Work Plan Goals** (Tasks)
   - Major milestones requiring supervisor approval
   - Budget expenditures above threshold
   - Compliance checkpoints

### Setting Approval Requirements

When creating/uploading a document or defining a work plan goal:

```php
// For documents
$document = PropertyDocument::create([
    'property_id' => $propertyId,
    'document_name' => 'Property Inspection Report',
    'requires_approval' => true, // <-- KEY FLAG
    // ... other fields
]);

// For work plan goals
$goal = WorkPlanGoal::create([
    'work_plan_id' => $workPlanId,
    'goal_name' => 'Complete Foundation Repairs',
    'requires_approval' => true, // <-- KEY FLAG
    // ... other fields
]);
```

## Milestone Gating Logic

### How Job Completion is Gated

The system uses `MilestoneApprovalService` to determine if a job can be completed:

```php
use App\Services\MilestoneApprovalService;

$approvalService = app(MilestoneApprovalService::class);

// Check if job can be completed
if ($approvalService->canCompleteJob($job)) {
    $job->update(['status' => 'completed']);
} else {
    // Show error message
    $message = $approvalService->getCompletionBlockerMessage($job);
    // "Cannot complete job - 3 document(s) and 1 task(s) require approval."
}
```

### Completion Criteria

A job can be completed when:
1. **ALL** documents with `requires_approval=true` have `approval_status='approved'`
2. **ALL** work plan goals with `requires_approval=true` have `approval_status='approved'`
3. At least one required document/task exists (prevents accidental completion of empty jobs)

### Progress Calculation

The service provides detailed progress metrics:

```php
$progress = $approvalService->getApprovalProgress($job);

// Returns:
[
    'documents_total' => 5,
    'documents_approved' => 3,
    'documents_pending' => 2,
    'documents_progress_percent' => 60.0,

    'tasks_total' => 2,
    'tasks_approved' => 1,
    'tasks_pending' => 1,
    'tasks_progress_percent' => 50.0,

    'overall_total' => 7,
    'overall_approved' => 4,
    'overall_pending' => 3,
    'overall_progress_percent' => 57.1,
]
```

## Resubmission Workflow

### Automatic Resubmission Detection

When a document is reuploaded (file_path changes), the `DocumentObserver` automatically:

1. **Detects the change** in `updating()` event
2. **Checks previous status** - was it rejected?
3. **Resets approval state**:
   - `approval_status` → 'pending'
   - `approved_by` → null
   - `approved_at` → null
   - `rejection_reason` → null
   - `document_version` → incremented (e.g., 1 → 2)

4. **Logs the resubmission** in `updated()` event:
   - Creates ApprovalHistory record with action='resubmitted'
   - Stores previous version number in metadata

5. **Updates approval queue**:
   - Cancels old PendingApproval record
   - Creates new PendingApproval with:
     - Status: 'pending'
     - Priority: 1 (high)
     - Description: "Resubmitted (v2)"
     - Assigned to: original approver (if available)

6. **Sends notification**:
   - Notifies original approver about resubmission
   - Email includes document name, version, and job context

### Manual Resubmission Handling

If you need to manually handle resubmission (e.g., task completion):

```php
use App\Models\WorkPlanGoal;
use App\Models\ApprovalHistory;

$goal = WorkPlanGoal::find($goalId);

if ($goal->approval_status === 'rejected') {
    // Mark as completed again (e.g., user re-did the work)
    $goal->update([
        'completed_at' => now(),
        'approval_status' => 'pending',
        'approved_by' => null,
        'rejection_reason' => null,
    ]);

    // Log the resubmission
    ApprovalHistory::logResubmission(
        $goal,
        auth()->user(),
        $goal->workPlan->program->housingJobs()->latest()->first(),
        "Task re-completed after rejection"
    );

    // Create new pending approval
    PendingApproval::create([
        'approvable_type' => WorkPlanGoal::class,
        'approvable_id' => $goal->id,
        'item_type' => 'work_plan_goal',
        'item_name' => $goal->goal_name,
        'submitted_by' => auth()->id(),
        'submitted_at' => now(),
        'status' => 'pending',
        'priority' => 1,
    ]);
}
```

## Developer Integration Guide

### Livewire Component Example: Job Completion

```php
<?php

namespace App\Livewire;

use App\Models\HousingJob;
use App\Services\MilestoneApprovalService;
use Livewire\Component;

class HousingJobManagement extends Component
{
    public HousingJob $job;
    public $showApprovalProgress = false;

    public function mount($jobId)
    {
        $this->job = HousingJob::with([
            'documentChecklists',
            'property.documents',
            'contractor.documents'
        ])->findOrFail($jobId);
    }

    public function completeJob()
    {
        // Check if can complete
        if (!$this->job->canComplete()) {
            $this->addError('completion', $this->job->getCompletionBlockerMessage());
            $this->showApprovalProgress = true;
            return;
        }

        // All approvals satisfied - allow completion
        $this->job->update([
            'status' => 'completed',
            'actual_completion_date' => now(),
        ]);

        session()->flash('message', 'Job completed successfully!');
        return redirect()->route('housing.jobs.index');
    }

    public function render()
    {
        $approvalProgress = $this->job->approval_progress;
        $pendingItems = $this->job->getPendingItemsBreakdown();

        return view('livewire.housing-job-management', [
            'approvalProgress' => $approvalProgress,
            'pendingItems' => $pendingItems,
        ]);
    }
}
```

### Blade Template Example

```blade
<!-- Approval Progress Panel -->
@if($job->hasPendingApprovals())
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-yellow-900 mb-4">
            Approval Progress
        </h3>

        <!-- Overall Progress -->
        <div class="mb-4">
            <div class="flex justify-between mb-1">
                <span class="text-sm font-medium">Overall</span>
                <span class="text-sm font-medium">
                    {{ $approvalProgress['overall_approved'] }}/{{ $approvalProgress['overall_total'] }}
                    ({{ $approvalProgress['overall_progress_percent'] }}%)
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-600 h-2.5 rounded-full"
                     style="width: {{ $approvalProgress['overall_progress_percent'] }}%"></div>
            </div>
        </div>

        <!-- Documents Progress -->
        <div class="mb-4">
            <div class="flex justify-between mb-1">
                <span class="text-sm font-medium">Documents</span>
                <span class="text-sm font-medium">
                    {{ $approvalProgress['documents_approved'] }}/{{ $approvalProgress['documents_total'] }}
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                @php
                    $color = $approvalProgress['documents_progress_percent'] == 100 ? 'green' :
                            ($approvalProgress['documents_progress_percent'] > 0 ? 'yellow' : 'red');
                @endphp
                <div class="bg-{{ $color }}-600 h-2.5 rounded-full"
                     style="width: {{ $approvalProgress['documents_progress_percent'] }}%"></div>
            </div>
        </div>

        <!-- Tasks Progress -->
        <div class="mb-4">
            <div class="flex justify-between mb-1">
                <span class="text-sm font-medium">Tasks</span>
                <span class="text-sm font-medium">
                    {{ $approvalProgress['tasks_approved'] }}/{{ $approvalProgress['tasks_total'] }}
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                @php
                    $color = $approvalProgress['tasks_progress_percent'] == 100 ? 'green' :
                            ($approvalProgress['tasks_progress_percent'] > 0 ? 'yellow' : 'red');
                @endphp
                <div class="bg-{{ $color }}-600 h-2.5 rounded-full"
                     style="width: {{ $approvalProgress['tasks_progress_percent'] }}%"></div>
            </div>
        </div>

        <!-- Pending Items List -->
        <div class="mt-6">
            <h4 class="font-medium text-sm mb-2">Pending Approvals:</h4>
            <ul class="space-y-1 text-sm">
                @foreach($pendingItems['property_documents'] as $item)
                    <li class="flex items-center">
                        <span class="inline-block w-3 h-3 bg-yellow-400 rounded-full mr-2"></span>
                        <span>Property: {{ $item['name'] }}</span>
                        <span class="ml-auto text-gray-500">(v{{ $item['version'] }})</span>
                    </li>
                @endforeach

                @foreach($pendingItems['contractor_documents'] as $item)
                    <li class="flex items-center">
                        <span class="inline-block w-3 h-3 bg-yellow-400 rounded-full mr-2"></span>
                        <span>Contractor: {{ $item['name'] }}</span>
                        <span class="ml-auto text-gray-500">(v{{ $item['version'] }})</span>
                    </li>
                @endforeach

                @foreach($pendingItems['job_documents'] as $item)
                    <li class="flex items-center">
                        <span class="inline-block w-3 h-3 bg-yellow-400 rounded-full mr-2"></span>
                        <span>Job: {{ $item['name'] }}</span>
                        <span class="ml-auto text-gray-500">(v{{ $item['version'] }})</span>
                    </li>
                @endforeach

                @foreach($pendingItems['work_plan_goals'] as $item)
                    <li class="flex items-center">
                        <span class="inline-block w-3 h-3 bg-yellow-400 rounded-full mr-2"></span>
                        <span>Task: {{ $item['name'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-4">
            <a href="{{ route('approvals.dashboard') }}"
               class="text-blue-600 hover:underline text-sm">
                View Approval Dashboard →
            </a>
        </div>
    </div>
@endif

<!-- Job Completion Button -->
<div class="mt-6">
    @if($job->hasPendingApprovals())
        <button disabled
                class="bg-gray-400 text-white px-6 py-2 rounded-lg opacity-50 cursor-not-allowed">
            Complete Job
        </button>
        <p class="text-red-600 text-sm mt-2">
            {{ $job->getCompletionBlockerMessage() }}
        </p>
    @else
        <button wire:click="completeJob"
                wire:confirm="Mark this job as completed?"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
            Complete Job
        </button>
    @endif
</div>

@error('completion')
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mt-4">
        {{ $message }}
    </div>
@enderror
```

### Document Upload with Approval Flag

```blade
<form wire:submit.prevent="uploadDocument">
    <!-- Document fields... -->

    <div class="mt-4">
        <label class="flex items-center">
            <input type="checkbox"
                   wire:model="requires_approval"
                   class="rounded border-gray-300">
            <span class="ml-2 text-sm text-gray-700">
                Requires approval before job completion
            </span>
        </label>
        <p class="text-xs text-gray-500 mt-1 ml-6">
            If checked, this document must be reviewed and approved by a supervisor
            before the job can be marked as complete.
        </p>
    </div>

    <button type="submit" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">
        Upload Document
    </button>
</form>
```

## User Guide

### For Staff Members (Uploaders)

#### Uploading Documents Requiring Approval

1. Navigate to the job's document section
2. Click "Upload Document"
3. Fill in document details
4. **Check "Requires approval"** if document is critical
5. Upload file and submit
6. Document status shows as "Pending Approval"
7. You will receive notification when approved/rejected

#### If Document is Rejected

1. You'll receive notification with rejection reason
2. Review the feedback provided
3. Make necessary corrections
4. Simply reupload the file in the same document record
5. System automatically:
   - Resets status to "Pending"
   - Increments version number
   - Notifies original approver
6. Wait for re-review

### For Supervisors (Approvers)

#### Reviewing Pending Approvals

1. Navigate to Approval Dashboard
2. See list of all pending items
3. Click on item to view details
4. Review document/task completion
5. Choose action:
   - **Approve**: Item is accepted, job can progress
   - **Reject**: Provide detailed reason for rejection

#### Best Practices for Reviewers

- Be specific in rejection reasons
- Review documents promptly (check for SLA alerts)
- Prioritize resubmissions (marked with "Resubmitted vX")
- Use approval notes to communicate with staff

### For Project Managers

#### Monitoring Job Progress

1. View job detail page
2. Check "Approval Progress" panel
3. Review percentages:
   - Green: All items approved
   - Yellow: Partial approval
   - Red: No approvals yet
4. Click pending items to see details
5. Contact staff if items are delayed

#### Completing Jobs

1. Ensure all work is finished
2. Check approval progress panel
3. If all approvals satisfied:
   - "Complete Job" button is enabled
   - Click to mark job complete
4. If approvals pending:
   - Button is disabled
   - Message shows what's blocking completion
   - Review pending items
   - Follow up with staff/approvers

## Troubleshooting

### "Cannot complete job" Error

**Problem:** Trying to complete job but system blocks it.

**Solution:**
1. Check approval progress panel
2. Identify pending items
3. Options:
   - Wait for approvals
   - Remove `requires_approval` flag if not actually needed
   - Contact approver to expedite review

### Document Resubmission Not Working

**Problem:** Reuploaded document but status didn't reset.

**Checks:**
1. Did you actually change the file? (file_path must change)
2. Was previous status "rejected"? (Observer only resets rejected items)
3. Check ApprovalHistory - was resubmission logged?

**Debug:**
```php
// In Tinker
$doc = PropertyDocument::find(123);
$doc->approval_status; // Should be 'pending'
$doc->document_version; // Should be incremented
$doc->approvalHistory; // Check for 'resubmitted' entry
```

### Approver Not Receiving Notification

**Problem:** Resubmission happened but approver wasn't notified.

**Checks:**
1. Was there an `approved_by` on the original rejection?
2. Is that user account still active?
3. Check notification queue: `php artisan queue:work`
4. Check user's email settings

**Manual resend:**
```php
use App\Notifications\DocumentResubmittedNotification;

$document = PropertyDocument::find(123);
$approver = User::find($document->getRawOriginal()['approved_by']);
$approver->notify(new DocumentResubmittedNotification($document, auth()->user()));
```

### Progress Percentage Incorrect

**Problem:** Progress bar shows wrong percentage.

**Causes:**
1. Cached calculations
2. Orphaned approval records
3. Missing relationships

**Fix:**
```php
// Refresh job with all relationships
$job = HousingJob::withApprovalCounts()->find($jobId);

// Recalculate
$service = app(MilestoneApprovalService::class);
$progress = $service->getApprovalProgress($job);
```

### Version Number Not Incrementing

**Problem:** Reuploading document doesn't increment version.

**Check:**
1. Is `document_version` column present? (Check migration ran)
2. Is DocumentObserver registered? (Check AppServiceProvider)

**Manual increment:**
```php
$doc = PropertyDocument::find(123);
$doc->increment('document_version');
```

## API Reference

### MilestoneApprovalService

```php
canCompleteJob(HousingJob $job): bool
getPendingApprovalsForJob(HousingJob $job): Collection
getApprovalProgress(HousingJob $job): array
getPendingItemsBreakdown(HousingJob $job): array
getCompletionBlockerMessage(HousingJob $job): ?string
```

### HousingJob Methods

```php
$job->hasPendingApprovals(): bool
$job->canComplete(): bool
$job->getApprovalProgressAttribute(): array
$job->getCompletionBlockerMessage(): ?string
$job->getPendingItemsBreakdown(): array
$job->pendingApprovals() // Relationship
$job->approvalHistory() // Relationship
```

### Document Model Methods

```php
$document->isPending(): bool
$document->isApproved(): bool
$document->isRejected(): bool
$document->approve(User $approver, ?string $notes): void
$document->reject(User $approver, string $reason, ?string $notes): void
$document->approval_status_color // Attribute
```

### ApprovalHistory Static Methods

```php
ApprovalHistory::logApproval(Model $document, User $user, ?HousingJob $job, ?string $notes, ?array $metadata)
ApprovalHistory::logRejection(Model $document, User $user, string $reason, ?HousingJob $job, ?string $notes, ?array $metadata)
ApprovalHistory::logResubmission(Model $document, User $user, ?HousingJob $job, ?string $notes, ?array $metadata)
```

## Conclusion

This approval workflow provides a robust, auditable system for quality control in housing rehabilitation projects. By automatically handling resubmissions and preventing premature job completion, it ensures all work meets quality standards while maintaining a complete audit trail for compliance purposes.
