# Phase 6 & 7: Milestone Gating and Resubmission Workflow - Implementation Summary

## Overview
Successfully implemented milestone-based approval gating and document resubmission workflow for the housing module. This system prevents job completion until all required documents and tasks are approved, and automatically handles document resubmission after rejection.

## Components Implemented

### 1. Database Migrations

#### `/database/migrations/2025_11_03_143221_add_approval_tracking_to_documents_and_tasks.php`
Adds approval tracking fields to all document and task tables:

**Tables Modified:**
- `property_documents`
- `contractor_documents`
- `job_document_checklists`
- `work_plan_goals`

**Fields Added:**
- `requires_approval` (boolean) - Flag to indicate if item needs approval
- `approval_status` (string) - Current status: pending, approved, rejected
- `approved_by` (foreign key) - User who approved/rejected
- `approved_at` (timestamp) - When approval action occurred
- `rejection_reason` (text) - Reason for rejection
- `document_version` (integer) - Version number (increments on reupload)
- `completed_at` (timestamp) - For work plan goals

#### `/database/migrations/2025_11_03_143224_create_pending_approvals_table.php`
Creates centralized approval queue table with:
- Polymorphic relationship to any approvable item
- Link to housing job for context
- Priority levels (normal, high, urgent)
- Status tracking (pending, approved, rejected, cancelled)
- Approver assignment
- Action notes and timestamps

#### `/database/migrations/2025_11_03_143225_create_approval_history_table.php`
Creates audit trail for all approval actions:
- Tracks every approval, rejection, and resubmission
- Stores version numbers
- Maintains metadata and notes
- Supports reporting and compliance requirements

### 2. Models

#### `app/Models/PendingApproval.php`
**Purpose:** Manages the approval queue

**Key Methods:**
- `approve(User $approver, ?string $notes)` - Approve an item
- `reject(User $approver, string $reason, ?string $notes)` - Reject an item
- `cancel(?string $reason)` - Cancel an approval request
- `isPending()`, `isApproved()`, `isRejected()`, `isCancelled()` - Status checks

**Scopes:**
- `pending()`, `approved()`, `rejected()`, `cancelled()` - Filter by status
- `forJob(int $jobId)` - Filter by housing job
- `highPriority()` - Get high-priority items

**Attributes:**
- `status_color` - Color coding for UI display
- `priority_label` - Human-readable priority (Normal/High/Urgent)

#### `app/Models/ApprovalHistory.php`
**Purpose:** Maintains audit trail of all approval actions

**Key Static Methods:**
- `logApproval(Model $document, User $user, ...)` - Log approval
- `logRejection(Model $document, User $user, string $reason, ...)` - Log rejection
- `logResubmission(Model $document, User $user, ...)` - Log resubmission

**Scopes:**
- `forDocument(string $type, int $id)` - Get history for specific document
- `forJob(int $jobId)` - Get all history for a job
- `approvals()`, `rejections()`, `resubmissions()` - Filter by action type
- `latest()` - Order by most recent

### 3. Service Layer

#### `app/Services/MilestoneApprovalService.php`
**Purpose:** Business logic for approval gating and progress tracking

**Key Methods:**

**Gating Logic:**
- `canCompleteJob(HousingJob $job): bool`
  - Validates all required approvals are satisfied
  - Ensures at least one required document exists
  - Returns true only if job can be marked complete

**Progress Tracking:**
- `getApprovalProgress(HousingJob $job): array`
  - Returns detailed statistics:
    - `documents_total`, `documents_approved`, `documents_pending`
    - `documents_progress_percent`
    - `tasks_total`, `tasks_approved`, `tasks_pending`
    - `tasks_progress_percent`
    - `overall_total`, `overall_approved`, `overall_pending`
    - `overall_progress_percent`

**Approval Management:**
- `getPendingApprovalsForJob(HousingJob $job): Collection`
  - Returns all pending approval items with relationships loaded

- `getPendingItemsBreakdown(HousingJob $job): array`
  - Returns categorized breakdown of pending items:
    - `property_documents[]`
    - `contractor_documents[]`
    - `job_documents[]`
    - `work_plan_goals[]`
  - Each item includes: id, type, name, status, version

**User Feedback:**
- `getCompletionBlockerMessage(HousingJob $job): ?string`
  - Returns human-readable message explaining what's blocking completion
  - Example: "Cannot complete job - 3 document(s) and 2 task(s) require approval."
  - Returns null if no blockers exist

### 4. Model Enhancements

#### `app/Models/HousingJob.php`
**New Relationships:**
- `pendingApprovals()` - MorphMany relationship to PendingApproval
- `approvalHistory()` - HasMany relationship to ApprovalHistory

**New Methods:**
- `hasPendingApprovals(): bool` - Check if any approvals are pending
- `canComplete(): bool` - Check if job can be completed (uses service)
- `getApprovalProgressAttribute(): array` - Get approval progress stats
- `getCompletionBlockerMessage(): ?string` - Get blocker message
- `getPendingItemsBreakdown(): array` - Get detailed pending items

**New Scope:**
- `withApprovalCounts()` - Eager load approval-related counts

#### Document Models Updated
All three document models (`PropertyDocument`, `ContractorDocument`, `JobDocumentChecklist`) now include:

**New Fields:**
- `requires_approval`, `approval_status`, `approved_by`, `approved_at`
- `rejection_reason`, `document_version`

**New Methods:**
- `isPending()`, `isApproved()`, `isRejected()` - Status checks
- `approve(User $approver, ?string $notes)` - Approve document
- `reject(User $approver, string $reason, ?string $notes)` - Reject document

**New Attribute:**
- `approval_status_color` - Color for UI display

### 5. Observer Pattern Implementation

#### `app/Observers/DocumentObserver.php`
**Purpose:** Automatically handle approval workflow events

**Events Handled:**

**created()**
- Creates PendingApproval record when document requiring approval is uploaded
- Links to housing job if available
- Sets initial status to 'pending'

**updating()** (before save)
- Detects when file is reuploaded
- If previous status was 'rejected':
  - Resets approval_status to 'pending'
  - Clears approved_by, approved_at, rejection_reason
  - Increments document_version

**updated()** (after save)
- Logs resubmission to ApprovalHistory
- Cancels old PendingApproval record
- Creates new high-priority PendingApproval
- Sends notification to original approver

**Helper Methods:**
- `getHousingJob(Model $document)` - Finds associated housing job
- `createPendingApproval(Model $document)` - Creates approval request
- `updatePendingApproval(Model $document, array $original)` - Handles resubmission
- `notifyOriginalApprover(Model $document, array $original)` - Sends notification

### 6. Model Relationship Updates

#### `app/Models/Property.php`
- Added: `housingJobs(): HasMany` relationship

#### `app/Models/Contractor.php`
- Added: `housingJobs(): HasMany` relationship

These relationships allow the DocumentObserver to find the housing job context for property and contractor documents.

### 7. Service Provider Registration

#### `app/Providers/AppServiceProvider.php`
Registered DocumentObserver for all three document models:
```php
PropertyDocument::observe(DocumentObserver::class);
ContractorDocument::observe(DocumentObserver::class);
JobDocumentChecklist::observe(DocumentObserver::class);
```

## Workflow Diagrams

### Document Upload & Approval Flow
```
1. User uploads document with requires_approval=true
   └─> DocumentObserver::created() fires
       └─> Creates PendingApproval record
           └─> Status: 'pending'
           └─> Notification sent to approver

2. Approver reviews document
   └─> Approves:
       └─> Update approval_status='approved'
       └─> Log to ApprovalHistory
       └─> Update PendingApproval status='approved'
   └─> Rejects:
       └─> Update approval_status='rejected'
       └─> Store rejection_reason
       └─> Log to ApprovalHistory
       └─> Update PendingApproval status='rejected'
```

### Resubmission Workflow
```
1. User reuploads rejected document (file_path changes)
   └─> DocumentObserver::updating() fires
       └─> Detects: previous status was 'rejected'
       └─> Resets: approval_status='pending'
       └─> Increments: document_version (e.g., 1 → 2)
       └─> Clears: approved_by, approved_at, rejection_reason

2. DocumentObserver::updated() fires (after save)
   └─> Logs resubmission to ApprovalHistory
   └─> Cancels old PendingApproval
   └─> Creates new PendingApproval with:
       └─> Priority: 1 (high)
       └─> Description: "Resubmitted (v2)"
       └─> Assigned to: original approver
   └─> Sends DocumentResubmittedNotification
```

### Job Completion Gating
```
1. User attempts to mark job as 'completed'
   └─> System calls: $job->canComplete()
       └─> MilestoneApprovalService::canCompleteJob()
           └─> Counts required documents/tasks
           └─> Counts approved documents/tasks
           └─> Returns: true if all approved, false otherwise

2. If canComplete() returns false:
   └─> System blocks completion
   └─> Displays: $job->getCompletionBlockerMessage()
       └─> Example: "Cannot complete job - 3 document(s) and 1 task(s) require approval."

3. If canComplete() returns true:
   └─> System allows status change to 'completed'
```

## UI Integration Points

### Job Detail View
**Approval Progress Panel** should display:
- Overall progress: X/Y items approved (Z% complete)
- Documents: A/B approved
  - Progress bar (green/yellow/red)
- Tasks: C/D approved
  - Progress bar (green/yellow/red)
- List of pending items with "Approve" links
- Color coding:
  - Red: Not started (0% approved)
  - Yellow: Partial (1-99% approved)
  - Green: Complete (100% approved)

### Job Completion Button
```blade
@if($job->hasPendingApprovals())
    <button disabled class="opacity-50 cursor-not-allowed">
        Complete Job
    </button>
    <p class="text-red-600 text-sm mt-1">
        {{ $job->getCompletionBlockerMessage() }}
    </p>
    <a href="#approvals" class="text-blue-600 underline">
        View Pending Approvals
    </a>
@else
    <button wire:click="completeJob" class="bg-green-600 text-white">
        Complete Job
    </button>
@endif
```

### Document Upload Forms
When uploading documents, include:
```blade
<label>
    <input type="checkbox" wire:model="requires_approval">
    Requires approval before job completion
</label>
```

### Approval Dashboard
Should show:
- All pending approvals grouped by job
- Priority indicators (urgent, high, normal)
- Resubmission badges (e.g., "Resubmitted v2")
- Quick approve/reject actions
- Link to job detail

## Database Schema Summary

### pending_approvals
```sql
- id
- approvable_type, approvable_id (polymorphic)
- housing_job_id (nullable, foreign key)
- item_type (document/task/milestone)
- item_name
- item_description
- submitted_by (foreign key to users)
- submitted_at
- approver_id (nullable, foreign key to users)
- status (pending/approved/rejected/cancelled)
- actioned_by (nullable, foreign key to users)
- actioned_at
- action_notes
- priority (0=normal, 1=high, 2=urgent)
- timestamps
```

### approval_history
```sql
- id
- approvable_type, approvable_id (polymorphic)
- housing_job_id (nullable, foreign key)
- document_type (class name)
- document_id
- version
- action (approved/rejected/resubmitted)
- user_id (foreign key)
- reason (nullable)
- notes (nullable)
- metadata (json, nullable)
- timestamps
```

## Testing Recommendations

### Unit Tests
1. `MilestoneApprovalService::canCompleteJob()`
   - Test with all approved
   - Test with pending approvals
   - Test with no required approvals

2. `DocumentObserver` events
   - Test document creation triggers PendingApproval
   - Test reupload after rejection increments version
   - Test notification sent on resubmission

3. Model methods
   - `HousingJob::canComplete()`
   - `PropertyDocument::approve()` and `reject()`

### Feature Tests
1. Complete workflow: upload → reject → reupload → approve
2. Job completion blocking when approvals pending
3. Approval progress calculation accuracy
4. Resubmission notification delivery

### Manual Testing Checklist
- [ ] Upload document with requires_approval=true
- [ ] Verify PendingApproval created
- [ ] Approve document, check status updates
- [ ] Reject document with reason
- [ ] Reupload rejected document
- [ ] Verify version incremented
- [ ] Verify notification sent
- [ ] Attempt to complete job with pending approvals
- [ ] Verify completion blocked with proper message
- [ ] Approve all items
- [ ] Verify job can be completed
- [ ] Check ApprovalHistory records accurate

## Future Enhancements

### Notification Preferences (Bonus Feature)
Not implemented in this phase, but recommended:

**Table:** `user_notification_preferences`
```sql
- user_id
- notification_type (approval_request, resubmission, etc.)
- delivery_method (email_immediate, email_digest, none)
- digest_frequency (daily, weekly)
```

**Usage:**
```php
if ($user->notificationPreference('approval_request') !== 'none') {
    $user->notify(new DocumentResubmittedNotification(...));
}
```

### Additional Features to Consider
1. **Bulk Approval**: Allow approving multiple items at once
2. **Delegation**: Allow approvers to delegate to others
3. **Approval Templates**: Pre-define approval workflows for job types
4. **SLA Tracking**: Alert when approvals are overdue
5. **Approval Comments**: Discussion thread for each approval item
6. **Version Comparison**: Show diff between document versions
7. **Conditional Approvals**: Different approval rules based on job value/type

## Migration Notes

### Running Migrations
```bash
php artisan migrate
```

### Rollback (if needed)
```bash
php artisan migrate:rollback --step=3
```

### Cleaning Up Duplicate Migrations
There appear to be some duplicate migration files from previous implementations. Before running migrations, review and consolidate:

```bash
ls -la database/migrations/ | grep "2025_11_03"
```

Consider keeping only:
- `2025_11_03_143221_add_approval_tracking_to_documents_and_tasks.php`
- `2025_11_03_143224_create_pending_approvals_table.php`
- `2025_11_03_143225_create_approval_history_table.php`

Remove any duplicates or older versions.

## Summary of Files Created/Modified

### New Files
- `app/Services/MilestoneApprovalService.php`
- `app/Observers/DocumentObserver.php`
- `app/Models/ApprovalHistory.php`
- `database/migrations/2025_11_03_143221_add_approval_tracking_to_documents_and_tasks.php`
- `database/migrations/2025_11_03_143224_create_pending_approvals_table.php`
- `database/migrations/2025_11_03_143225_create_approval_history_table.php`

### Modified Files
- `app/Models/PendingApproval.php` (enhanced existing)
- `app/Models/HousingJob.php` (added approval methods and relationships)
- `app/Models/PropertyDocument.php` (already had approval fields from previous work)
- `app/Models/ContractorDocument.php` (already had approval fields from previous work)
- `app/Models/JobDocumentChecklist.php` (already had approval fields from previous work)
- `app/Models/Property.php` (added housingJobs relationship)
- `app/Models/Contractor.php` (added housingJobs relationship)
- `app/Providers/AppServiceProvider.php` (registered DocumentObserver)

## Implementation Status

✅ **Completed:**
1. MilestoneApprovalService with gating logic
2. Approval tracking migrations
3. PendingApproval and ApprovalHistory models
4. HousingJob approval methods and relationships
5. DocumentObserver with resubmission workflow
6. Observer registration in AppServiceProvider
7. Model relationship updates (Property, Contractor)

⚠️ **Pending:**
1. UI implementation for approval dashboard
2. UI implementation for approval progress indicators
3. UI implementation for job completion button with gating
4. Migration execution and testing
5. Notification template customization

## Next Steps

1. **Clean up duplicate migrations** before running migrations
2. **Run migrations** in development environment
3. **Test observer behavior** with actual document uploads
4. **Implement UI components** as outlined above
5. **Create approval dashboard** route and Livewire component
6. **Add approval progress panel** to job detail view
7. **Update job completion logic** in HousingJobManagement component
8. **Write comprehensive tests**
9. **Document user workflows** in user guide
10. **Train staff** on approval process

## Conclusion

Phase 6 & 7 implementation provides a robust, auditable approval workflow with automatic resubmission handling and milestone-based gating. The system prevents premature job completion while providing clear feedback to users about what's blocking completion. The version tracking and approval history ensure full compliance and auditability for all housing rehab jobs.
