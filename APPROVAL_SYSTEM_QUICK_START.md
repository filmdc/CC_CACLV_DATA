# Approval System - Quick Start Guide

## For Developers: How to Add Approval to Your Feature

### Step 1: Create Approval Request

When a user submits something that needs approval:

```php
use App\Models\PendingApproval;

// Create the approval request
$approval = PendingApproval::create([
    'approvable_type' => get_class($yourModel),  // e.g., JobDocumentChecklist::class
    'approvable_id' => $yourModel->id,
    'submitted_by' => auth()->id(),
    'program_id' => $yourModel->program_id ?? null,  // Optional
    'item_type' => 'Document',  // Human-readable type
    'item_name' => $yourModel->name,
    'item_description' => $yourModel->description ?? null,
    'status' => 'pending',
    'submitted_at' => now(),
]);

// Notifications are sent automatically by the observer!
```

### Step 2: Handle Approval Status

Check if something is approved before allowing action:

```php
$approval = PendingApproval::where('approvable_type', get_class($document))
    ->where('approvable_id', $document->id)
    ->latest()
    ->first();

if ($approval && $approval->isApproved()) {
    // Allow the action
} elseif ($approval && $approval->isPending()) {
    // Show "waiting for approval" message
} elseif ($approval && $approval->isRejected()) {
    // Show rejection reason: $approval->rejection_reason
}
```

### Step 3: Handle Resubmission

When user uploads a new version after rejection:

```php
// Find the rejected approval
$approval = PendingApproval::where('approvable_type', get_class($document))
    ->where('approvable_id', $document->id)
    ->where('status', 'rejected')
    ->first();

if ($approval) {
    // Reset to pending
    $approval->update([
        'status' => 'pending',
        'rejection_reason' => null,
        'actioned_at' => null,
        'approver_id' => null,
        'submitted_at' => now(),
    ]);

    // Resubmission notification sent automatically!
}
```

## Common Patterns

### Pattern 1: Document Upload with Approval

```php
public function uploadDocument($file) {
    // Store the document
    $document = JobDocumentChecklist::create([
        'document_name' => $file->getClientOriginalName(),
        'file_path' => $file->store('documents'),
        'status' => 'uploaded',
        // ... other fields
    ]);

    // Create approval request
    PendingApproval::create([
        'approvable_type' => JobDocumentChecklist::class,
        'approvable_id' => $document->id,
        'submitted_by' => auth()->id(),
        'program_id' => $this->job->program_id,
        'item_type' => 'Job Document',
        'item_name' => $document->document_name,
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    session()->flash('message', 'Document uploaded and sent for approval.');
}
```

### Pattern 2: Task Completion with Approval

```php
public function markTaskComplete() {
    $task->update(['status' => 'completed']);

    // Create approval request
    PendingApproval::create([
        'approvable_type' => WorkPlanGoal::class,
        'approvable_id' => $task->id,
        'submitted_by' => auth()->id(),
        'item_type' => 'Task',
        'item_name' => $task->title,
        'item_description' => "Task completed by {$user->name}",
        'status' => 'pending',
        'submitted_at' => now(),
    ]);
}
```

### Pattern 3: Assign Specific Approver

```php
// Assign to a specific program manager
PendingApproval::create([
    'approvable_type' => JobDocumentChecklist::class,
    'approvable_id' => $document->id,
    'submitted_by' => auth()->id(),
    'approver_id' => $programManager->id,  // Specific approver
    'program_id' => $job->program_id,
    'item_type' => 'Job Document',
    'item_name' => $document->document_name,
    'status' => 'pending',
    'submitted_at' => now(),
]);
```

## Email Preview

### Approval Request Email
```
Subject: Approval Required: Job Document ABC.pdf

Hello John Doe,

Jane Smith submitted Job Document for your approval.

Item: ABC.pdf
Description: Contractor invoice for roof repair
Program: Weatherization Assistance Program
Submitted on: November 3, 2025 2:30 PM

[Review & Approve] [Reject]

Please review and take action on this approval request.
```

### Approved Email
```
Subject: Your Job Document was approved

Hello Jane Smith,

Good news! Your Job Document has been approved.

Item: ABC.pdf
Approved by: John Doe
Approved on: November 3, 2025 3:45 PM

You can now proceed with the next steps.
```

### Rejected Email
```
Subject: Your Job Document needs revision

Hello Jane Smith,

Your Job Document requires revision before approval.

Item: ABC.pdf
Rejected by: John Doe
Rejected on: November 3, 2025 3:45 PM
Reason: The invoice is missing the contractor's license number. Please upload a corrected version.

Please review the feedback and resubmit your Job Document.

[Upload New Version]
```

## Display Approval Status in UI

### In Livewire Component

```php
public function getApprovalStatusProperty()
{
    return PendingApproval::where('approvable_type', get_class($this->document))
        ->where('approvable_id', $this->document->id)
        ->latest()
        ->first();
}
```

### In Blade View

```blade
@if($this->approvalStatus)
    <div class="rounded-md p-4 mb-4
        @if($this->approvalStatus->isPending()) bg-yellow-50 dark:bg-yellow-900
        @elseif($this->approvalStatus->isApproved()) bg-green-50 dark:bg-green-900
        @else bg-red-50 dark:bg-red-900 @endif">

        @if($this->approvalStatus->isPending())
            <p class="text-yellow-800 dark:text-yellow-200">
                Waiting for approval from {{ $this->approvalStatus->approver->name ?? 'program manager' }}
            </p>
        @elseif($this->approvalStatus->isApproved())
            <p class="text-green-800 dark:text-green-200">
                Approved by {{ $this->approvalStatus->approver->name }}
                on {{ $this->approvalStatus->actioned_at->format('M j, Y') }}
            </p>
        @else
            <p class="text-red-800 dark:text-red-200">
                Rejected: {{ $this->approvalStatus->rejection_reason }}
            </p>
            <button wire:click="reupload" class="mt-2 text-sm underline">
                Upload New Version
            </button>
        @endif
    </div>
@endif
```

## Query Examples

```php
// Get all pending approvals for current user
$pending = PendingApproval::pending()
    ->where('approver_id', auth()->id())
    ->orWhereNull('approver_id')
    ->with(['submittedBy', 'program'])
    ->get();

// Get approval history for a document
$history = PendingApproval::where('approvable_type', JobDocumentChecklist::class)
    ->where('approvable_id', $documentId)
    ->orderBy('created_at', 'desc')
    ->get();

// Get user's submission history
$submitted = PendingApproval::where('submitted_by', auth()->id())
    ->with('approver')
    ->orderBy('created_at', 'desc')
    ->paginate(20);

// Get program approval statistics
$stats = PendingApproval::where('program_id', $programId)
    ->selectRaw('status, count(*) as count')
    ->groupBy('status')
    ->get();
```

## Troubleshooting

### Notifications Not Sending

1. Check queue is running: `php artisan queue:work`
2. Check mail configuration in `.env`
3. Check logs: `tail -f storage/logs/laravel.log`
4. Test mail: `php artisan tinker` then `Mail::raw('Test', function($m) { $m->to('test@example.com')->subject('Test'); });`

### Signed URLs Not Working

1. Ensure APP_KEY is set in `.env`
2. Check URL expiration (default: 30 minutes)
3. Verify 'signed' middleware is on the route
4. Clear config cache: `php artisan config:clear`

### Permissions Issues

1. Verify permission exists: `\App\Models\Permission::where('name', 'approvals.approve')->first()`
2. Check user has permission: `auth()->user()->hasPermission('approvals.approve')`
3. Verify role has permission: `$role->permissions()->where('name', 'approvals.approve')->exists()`

## API Reference

### PendingApproval Model Methods

- `approve(User $approver)` - Mark as approved
- `reject(User $approver, string $reason)` - Mark as rejected with reason
- `isPending()` - Check if status is pending
- `isApproved()` - Check if status is approved
- `isRejected()` - Check if status is rejected

### Query Scopes

- `pending()` - Filter by pending status
- `approved()` - Filter by approved status
- `rejected()` - Filter by rejected status

### Relationships

- `approvable()` - Polymorphic relation to the item being approved
- `submittedBy()` - User who submitted
- `approver()` - User who approved/rejected
- `program()` - Associated program (if applicable)

## Configuration Options

In your model that needs approval, you can add a method:

```php
public function requiresApproval(): bool
{
    // Define your logic for when approval is needed
    return $this->is_critical || $this->amount > 5000;
}
```

Then use it:

```php
if ($document->requiresApproval()) {
    PendingApproval::create([/* ... */]);
}
```

## Integration Checklist

- [ ] Create PendingApproval record when submission occurs
- [ ] Display approval status in UI
- [ ] Handle resubmission after rejection
- [ ] Test email notifications
- [ ] Add permission checks where needed
- [ ] Update documentation for your feature
- [ ] Test signed URL expiration
- [ ] Verify queue worker is processing notifications

## Support

For questions or issues with the approval system:
1. Check this guide and the main implementation document
2. Review the observer code for notification trigger logic
3. Check Laravel logs for errors
4. Verify queue is processing jobs
5. Test with manual PendingApproval creation in tinker
