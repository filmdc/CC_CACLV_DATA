# Phase 5: Email Notification System Implementation Summary

## Overview
This document summarizes the implementation of the email notification system with signed URLs for approval workflows. The system allows approvers to receive email notifications with direct links to approve or reject items, and notifies submitters when their items are approved or rejected.

## Components Created

### 1. Database Layer

#### Migration: `2025_11_03_143225_create_pending_approvals_table.php`
**Location:** `/var/www/CC_CAP_TRACK/database/migrations/2025_11_03_143225_create_pending_approvals_table.php`

**Status:** Created (needs to be run with `php artisan migrate`)

**Schema:**
- `id` - Primary key
- `approvable_type` - Polymorphic type (e.g., JobDocumentChecklist, PropertyDocument)
- `approvable_id` - Polymorphic ID
- `submitted_by` - Foreign key to users table
- `approver_id` - Foreign key to users table (nullable)
- `program_id` - Foreign key to programs table (nullable)
- `item_type` - String describing the type of item
- `item_name` - String name of the item
- `item_description` - Text description (nullable)
- `status` - Enum: 'pending', 'approved', 'rejected'
- `rejection_reason` - Text field (nullable)
- `submitted_at` - Timestamp
- `actioned_at` - Timestamp (nullable)
- Indexes on: approvable_type/id, status, submitted_by, approver_id, program_id

#### Model: `PendingApproval.php`
**Location:** `/var/www/CC_CAP_TRACK/app/Models/PendingApproval.php`

**Key Methods:**
- `approvable()` - Polymorphic relationship to any approvable model
- `submittedBy()` - Relationship to User who submitted
- `approver()` - Relationship to User who approved/rejected
- `program()` - Relationship to Program (if applicable)
- `approve(User $approver)` - Marks item as approved
- `reject(User $approver, string $reason)` - Marks item as rejected
- `isPending()`, `isApproved()`, `isRejected()` - Status check methods
- `scopePending()`, `scopeApproved()`, `scopeRejected()` - Query scopes

### 2. Notification Classes

#### ApprovalRequestedNotification
**Location:** `/var/www/CC_CAP_TRACK/app/Notifications/ApprovalRequestedNotification.php`

**Purpose:** Sent to approvers when a new item is submitted for approval

**Email Content:**
- Subject: "Approval Required: {item name}"
- Greeting: "Hello {recipient name}"
- Details: Submitter name, item type, item name, description, program, submission date
- Actions: Two buttons with signed URLs:
  - "Review & Approve" - Direct approval link
  - "Reject" - Link to rejection form

**Features:**
- Queued for background processing (implements `ShouldQueue`)
- Uses signed URLs for security
- Conditional display of program and description fields

#### ApprovalActionedNotification
**Location:** `/var/www/CC_CAP_TRACK/app/Notifications/ApprovalActionedNotification.php`

**Purpose:** Sent to submitters when their item is approved or rejected

**For Approved Items:**
- Subject: "Your {item type} was approved"
- Green success message style
- Details: Item name, approver name, approval date
- Encouraging message to proceed with next steps

**For Rejected Items:**
- Subject: "Your {item type} needs revision"
- Yellow warning message style
- Details: Item name, rejecter name, rejection date, reason
- Action button: "Upload New Version" (links to relevant page based on item type)

**Smart Upload URL Generation:**
- JobDocumentChecklist → housing-jobs.show
- PropertyDocument → properties.show
- ContractorDocument → contractors.show

#### DocumentResubmittedNotification
**Location:** `/var/www/CC_CAP_TRACK/app/Notifications/DocumentResubmittedNotification.php`

**Purpose:** Sent to approvers when a previously rejected item is resubmitted

**Email Content:**
- Subject: "{item type} has been resubmitted for approval"
- Notifies that submitter addressed previous feedback
- Details: Item name, resubmission date
- Action: "Review Resubmission" button with signed URL

### 3. Controller Layer

#### ApprovalController
**Location:** `/var/www/CC_CAP_TRACK/app/Http/Controllers/ApprovalController.php`

**Methods:**

1. **`approveFromEmail($id)`**
   - Validates signed URL
   - Checks user permissions
   - Verifies item is still pending
   - Approves the item
   - Sends notification to submitter
   - Redirects to dashboard with success message

2. **`rejectFromEmail($id)`**
   - Validates signed URL
   - Checks user permissions
   - Verifies item is still pending
   - Shows rejection form view

3. **`submitRejection(Request $request, $id)`**
   - Validates rejection reason (minimum 10 characters)
   - Checks user permissions
   - Verifies item is still pending
   - Rejects the item with reason
   - Sends notification to submitter
   - Redirects to dashboard with success message

4. **`canApprove(PendingApproval $pendingApproval)`** (protected)
   - Admins can approve anything
   - Checks if user has 'approvals.approve' permission
   - If program-specific, checks program access

### 4. View Layer

#### Rejection Form View
**Location:** `/var/www/CC_CAP_TRACK/resources/views/approvals/reject.blade.php`

**Features:**
- Standalone page (doesn't require authentication middleware, uses signed URL)
- Displays item details in a card:
  - Type, name, description
  - Submitter name and date
- Form with textarea for rejection reason
  - Validation: Required, minimum 10 characters
  - Helpful placeholder text
  - Character count guidance
- Cancel and Submit buttons
- Dark mode support throughout
- Responsive design

### 5. Routes

**Location:** `/var/www/CC_CAP_TRACK/routes/web.php`

**Added Routes:**
```php
// Approval Email Actions (with signed URLs)
Route::get('approvals/{id}/approve', [ApprovalController::class, 'approveFromEmail'])
    ->middleware(['signed'])
    ->name('approval.approve');

Route::get('approvals/{id}/reject', [ApprovalController::class, 'rejectFromEmail'])
    ->middleware(['signed'])
    ->name('approval.reject');

Route::post('approvals/{id}/reject', [ApprovalController::class, 'submitRejection'])
    ->middleware(['signed'])
    ->name('approval.reject.submit');
```

**Security Features:**
- All routes use `signed` middleware for URL signature verification
- Prevents tampering with approval IDs
- URLs expire based on Laravel's signed URL timeout settings

### 6. Observer Pattern

#### PendingApprovalObserver
**Location:** `/var/www/CC_CAP_TRACK/app/Observers/PendingApprovalObserver.php`

**Event Handlers:**

1. **`created(PendingApproval $pendingApproval)`**
   - Triggered when new PendingApproval is created
   - Gets list of approvers based on:
     - Specific approver if assigned
     - OR all users with 'approvals.approve' permission
     - Filtered by program access if applicable
   - Sends `ApprovalRequestedNotification` to all approvers

2. **`updated(PendingApproval $pendingApproval)`**
   - Triggered when PendingApproval is updated
   - **Status changed from pending to approved/rejected:**
     - Sends `ApprovalActionedNotification` to submitter
   - **Status changed from rejected to pending (resubmission):**
     - Sends `DocumentResubmittedNotification` to approvers

3. **`getApprovers(PendingApproval $pendingApproval)`** (protected)
   - Returns array of User models who should receive notifications
   - Logic:
     - If specific approver assigned, return only that user
     - Otherwise, find all users with 'approvals.approve' permission
     - Filter by program access if program_id is set

#### Observer Registration
**Location:** `/var/www/CC_CAP_TRACK/app/Providers/AppServiceProvider.php`

**Code Added:**
```php
use App\Models\PendingApproval;
use App\Observers\PendingApprovalObserver;

// In boot() method:
PendingApproval::observe(PendingApprovalObserver::class);
```

## Integration Points

### How to Create Approval Requests

When a document or task needs approval, create a PendingApproval record:

```php
use App\Models\PendingApproval;

// Example: Document upload requiring approval
$pendingApproval = PendingApproval::create([
    'approvable_type' => JobDocumentChecklist::class,
    'approvable_id' => $document->id,
    'submitted_by' => auth()->id(),
    'program_id' => $document->housingJob->program_id,
    'item_type' => 'Job Document',
    'item_name' => $document->document_name,
    'item_description' => $document->document_description,
    'status' => 'pending',
    'submitted_at' => now(),
]);

// The observer will automatically send notifications to approvers
```

### How to Handle Resubmissions

When a rejected document is reuploaded:

```php
// Find the existing rejected approval
$pendingApproval = PendingApproval::where('approvable_type', JobDocumentChecklist::class)
    ->where('approvable_id', $document->id)
    ->where('status', 'rejected')
    ->first();

if ($pendingApproval) {
    // Reset to pending status
    $pendingApproval->update([
        'status' => 'pending',
        'rejection_reason' => null,
        'actioned_at' => null,
        'approver_id' => null,
        'submitted_at' => now(),
    ]);

    // Observer will automatically send resubmission notifications
}
```

## Notification Workflow Diagrams

### Approval Request Flow
```
1. User submits document/task
2. PendingApproval record created
3. Observer: created() triggered
4. ApprovalRequestedNotification sent to approvers
5. Approver receives email with signed URLs
6. Approver clicks "Approve" or "Reject"
```

### Approval Flow
```
1. Approver clicks "Approve" link
2. ApprovalController->approveFromEmail()
3. Validates signed URL and permissions
4. Calls PendingApproval->approve()
5. Observer: updated() triggered
6. ApprovalActionedNotification sent to submitter
7. Redirect to dashboard with success message
```

### Rejection Flow
```
1. Approver clicks "Reject" link
2. ApprovalController->rejectFromEmail()
3. Shows rejection form
4. Approver submits reason
5. ApprovalController->submitRejection()
6. Calls PendingApproval->reject()
7. Observer: updated() triggered
8. ApprovalActionedNotification sent to submitter
9. Redirect to dashboard with success message
```

### Resubmission Flow
```
1. Submitter reuploads document
2. PendingApproval status changed to 'pending'
3. Observer: updated() triggered
4. DocumentResubmittedNotification sent to approvers
5. Approver receives notification
6. Process repeats from approval request flow
```

## Required Permissions

The system uses the following permission:
- `approvals.approve` - Allows user to approve/reject items

Add this permission to your permissions seeder:
```php
Permission::create(['name' => 'approvals.approve', 'description' => 'Can approve or reject pending approvals']);
```

Assign to relevant roles (e.g., Program Manager, Administrator):
```php
$programManager->permissions()->attach($approvePermission);
```

## Queue Configuration

All notifications implement `ShouldQueue`, meaning they are dispatched to the queue system. Ensure your queue worker is running:

```bash
php artisan queue:work
```

Or use the dev environment command:
```bash
composer dev  # Includes queue worker
```

## Email Template Customization

The notifications use Laravel's default markdown email templates. To customize:

```bash
php artisan vendor:publish --tag=laravel-mail
```

This will publish email templates to `resources/views/vendor/mail/`.

## Testing the System

### Manual Testing Steps

1. **Create a test approval:**
```php
php artisan tinker

$approval = \App\Models\PendingApproval::create([
    'approvable_type' => 'App\\Models\\JobDocumentChecklist',
    'approvable_id' => 1,
    'submitted_by' => 1,
    'item_type' => 'Test Document',
    'item_name' => 'Test Approval',
    'item_description' => 'This is a test',
    'status' => 'pending',
    'submitted_at' => now(),
]);
```

2. **Check mail logs or queue:**
```bash
php artisan queue:work --once
tail -f storage/logs/laravel.log
```

3. **Test approval from email:**
- Copy the signed URL from the email
- Visit the URL in browser
- Verify approval succeeds

4. **Test rejection from email:**
- Copy the reject URL from the email
- Fill out rejection form
- Verify rejection notification sent

## Security Considerations

1. **Signed URLs:** All approval actions use signed URLs to prevent tampering
2. **Permission Checks:** Every action verifies user has 'approvals.approve' permission
3. **Program Access:** Program-specific items check user has access to that program
4. **Status Validation:** Prevents re-approving already actioned items
5. **Middleware:** 'signed' middleware ensures URL signatures are valid

## Database Indexes

The pending_approvals table includes indexes on:
- `approvable_type` and `approvable_id` (composite) - Fast polymorphic lookups
- `status` - Fast pending/approved/rejected queries
- `submitted_by` - Fast user submission history
- `approver_id` - Fast approver history
- `program_id` - Fast program-specific queries

## Future Enhancements

1. **Dashboard Widget:** Show pending approvals count and list
2. **Batch Approvals:** Allow approving multiple items at once
3. **Approval Deadlines:** Add due dates with reminder notifications
4. **Approval History:** Track all actions taken on an item
5. **Comments/Notes:** Allow approvers to add comments without rejecting
6. **Approval Chain:** Support multi-level approval workflows
7. **Mobile App:** Push notifications for approvals
8. **Approval Analytics:** Reports on approval times, rejection rates, etc.

## Migration Instructions

To activate this system in production:

1. **Run the migration:**
```bash
php artisan migrate
```

2. **Add permission to database:**
```bash
php artisan tinker
\App\Models\Permission::create(['name' => 'approvals.approve', 'description' => 'Can approve or reject pending approvals']);
```

3. **Assign permission to roles:**
```bash
php artisan tinker
$role = \App\Models\Role::where('name', 'Program Manager')->first();
$permission = \App\Models\Permission::where('name', 'approvals.approve')->first();
$role->permissions()->attach($permission);
```

4. **Configure email settings** in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

5. **Ensure queue worker is running:**
```bash
# In production, use supervisor or similar
php artisan queue:work --daemon
```

## Files Created/Modified

### New Files Created:
1. `/var/www/CC_CAP_TRACK/database/migrations/2025_11_03_143225_create_pending_approvals_table.php`
2. `/var/www/CC_CAP_TRACK/app/Models/PendingApproval.php`
3. `/var/www/CC_CAP_TRACK/app/Notifications/ApprovalRequestedNotification.php`
4. `/var/www/CC_CAP_TRACK/app/Notifications/ApprovalActionedNotification.php`
5. `/var/www/CC_CAP_TRACK/app/Notifications/DocumentResubmittedNotification.php`
6. `/var/www/CC_CAP_TRACK/app/Http/Controllers/ApprovalController.php`
7. `/var/www/CC_CAP_TRACK/app/Observers/PendingApprovalObserver.php`
8. `/var/www/CC_CAP_TRACK/resources/views/approvals/reject.blade.php`

### Modified Files:
1. `/var/www/CC_CAP_TRACK/routes/web.php` - Added 3 approval routes
2. `/var/www/CC_CAP_TRACK/app/Providers/AppServiceProvider.php` - Registered observer

## Summary

The email notification system has been successfully implemented with:
- Complete database structure for tracking approvals
- Three notification types (request, actioned, resubmitted)
- Secure controller actions with signed URLs
- Automatic notification triggers via observer pattern
- Beautiful, responsive rejection form
- Program-level and role-based access control
- Queue support for performance
- Comprehensive security measures

All components follow Laravel 12 best practices and integrate seamlessly with the existing CAP TRACK system architecture.
