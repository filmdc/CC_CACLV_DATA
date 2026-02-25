# CAP TRACK Approval System Guide

## Overview

The CAP TRACK Approval System is a standalone, configurable module that supports approval workflows for any type of item in the platform. It replaces the original housing-document-only approval process with a generalized system supporting:

- **9 configurable approval types** across Housing, Programs, Contracts, and General categories
- **Multi-step approval chains** with role-based, permission-based, or user-pinned approver routing
- **Email-based approve/reject** actions via signed URLs
- **Audit trail** with IP address and browser signature capture
- **Excel export** of the full audit report
- **File attachments** via Spatie MediaLibrary on submissions that require documents

Any model in the application can participate in the approval system by using the `HasApprovals` trait.

---

## Table of Contents

1. [Approval Types](#1-approval-types)
2. [Permissions & Roles](#2-permissions--roles)
3. [Navigation](#3-navigation)
4. [Step-by-Step: Submitting an Approval Request](#4-step-by-step-submitting-an-approval-request)
5. [Step-by-Step: Reviewing & Approving Items](#5-step-by-step-reviewing--approving-items)
6. [Step-by-Step: Rejecting Items](#6-step-by-step-rejecting-items)
7. [Step-by-Step: Approving from Email](#7-step-by-step-approving-from-email)
8. [Tracking Your Submissions](#8-tracking-your-submissions)
9. [Audit Report & Export](#9-audit-report--export)
10. [Multi-Step Approval Chains](#10-multi-step-approval-chains)
11. [Use Cases & Examples](#11-use-cases--examples)
12. [Technical Reference](#12-technical-reference)

---

## 1. Approval Types

The system ships with 9 pre-configured approval types, organized into 4 categories. Each type can optionally require a file attachment.

### Housing

| Type | Description | Requires Attachment |
|------|-------------|:-------------------:|
| **Housing Document** | Property and housing-related document approvals | Yes |
| **Contractor Document** | Contractor compliance and certification documents | Yes |
| **Job Document** | Project job checklist document approvals | Yes |

### Programs

| Type | Description | Requires Attachment |
|------|-------------|:-------------------:|
| **Work Plan Goal** | Work plan goal completion approvals | No |

### Contracts

| Type | Description | Requires Attachment |
|------|-------------|:-------------------:|
| **Performance Report** | Performance report review and approval | Yes |

### General

| Type | Description | Requires Attachment |
|------|-------------|:-------------------:|
| **General Document** | General document approval requests | Yes |
| **Policy Acknowledgment** | Policy and procedure acknowledgment confirmations | No |
| **Budget Request** | Budget allocation and expense approval requests | Yes |
| **Digital Confirmation** | Digital confirmation and sign-off requests | No |

Administrators can add, modify, or deactivate approval types through the `approval_types` database table.

---

## 2. Permissions & Roles

### Permission Reference

| Permission | What It Grants |
|-----------|---------------|
| `approvals.submit` | Access to submit new approval requests |
| `approvals.view_dashboard` | Access to the Pending Approvals dashboard and Audit Report |
| `approvals.approve_reject` | Ability to approve or reject items from the dashboard or email links |
| `approvals.bulk_approve` | Ability to bulk-approve multiple items at once |
| `approvals.view_audit_log` | Access to the Audit Report (alternative to `view_dashboard`) |

### Default Role Assignments

| Role | Permissions Granted |
|------|-------------------|
| **Administrator** | All 5 approval permissions |
| **Project Manager** | All 5 approval permissions |
| **Housing Manager** | All 5 approval permissions |
| **Project Team Member** | None (must be granted individually) |
| **Housing Coordinator** | None (must be granted individually) |

To grant a user the ability to submit approvals, ensure their role includes the `approvals.submit` permission. To make a user an approver, their role needs `approvals.approve_reject`.

---

## 3. Navigation

The Approvals section appears in the sidebar for any user who has either `approvals.view_dashboard` or `approvals.submit`.

| Menu Item | Permission Required | Description |
|-----------|-------------------|-------------|
| **Pending Approvals** | `approvals.view_dashboard` | Dashboard showing all items awaiting review. Displays a live badge count of pending items. |
| **Submit Approval** | `approvals.submit` | Form to submit a new approval request |
| **My Submissions** | *(any authenticated user)* | View your own submission history and status |
| **Audit Report** | `approvals.view_dashboard` or `approvals.view_audit_log` | Searchable, filterable audit trail with Excel export |

---

## 4. Step-by-Step: Submitting an Approval Request

This is the workflow for any staff member who needs to submit an item for approval.

### Prerequisites
- You must have the `approvals.submit` permission assigned to your role.
- You must be logged in.

### Steps

1. **Navigate to Submit Approval**
   Click **Submit Approval** in the sidebar under the Approvals section, or go to `/approvals/submit`.

2. **Select an Approval Type**
   Choose the type that best matches your request from the dropdown. Types are grouped by category (Housing, Programs, Contracts, General). After selecting a type, you will see its description and whether an attachment is required.

3. **Enter a Title** *(required)*
   Provide a short, descriptive title for your submission (max 255 characters). Example: *"Q4 2025 Performance Report - Head Start"*

4. **Enter a Description** *(optional)*
   Add any additional context or details about what you are submitting (max 5,000 characters).

5. **Add Notes for the Reviewer** *(optional)*
   Include any specific notes you want the approver to see (max 2,000 characters). Example: *"Please review the updated budget figures on page 3."*

6. **Select a Program** *(optional)*
   If this submission is associated with a specific program, select it from the dropdown. This helps route the request to the correct approvers and organizes it for reporting.

7. **Upload Attachments** *(required for some types)*
   - If the approval type requires a document (indicated by a warning message), you must upload at least one file.
   - If the type does not require a document, the upload area still appears but is optional.
   - You can upload multiple files. Each file can be up to 100 MB.
   - After uploading, you will see the filename and file size listed.

8. **Click Submit**
   Your submission is created and all qualified approvers are automatically notified via email. You are redirected to **My Submissions** where you can track its status.

### What Happens After Submission

- An email notification is sent to all qualified approvers for this type.
- The item appears in the **Pending Approvals** dashboard for approvers.
- You can track the status from **My Submissions** at any time.
- If multi-step approval chains are configured for the type, each step's approvers are notified sequentially.

---

## 5. Step-by-Step: Reviewing & Approving Items

This is the workflow for users who have approval authority.

### Prerequisites
- You must have the `approvals.view_dashboard` and `approvals.approve_reject` permissions.

### Approving a Single Item

1. **Navigate to Pending Approvals**
   Click **Pending Approvals** in the sidebar. The badge shows how many items are waiting.

2. **Review the Queue**
   The dashboard shows all pending items by default. Use the filters to narrow by:
   - **Type**: Documents, Tasks, or Submissions
   - **Status**: Pending, Approved, Rejected, or All
   - **Program**: Filter to a specific program
   - **Search**: Search by submitter name, email, item name, or description

3. **Review the Item**
   Each row shows the item type, name, who submitted it, when, and which program it belongs to. Click **View Details** to see additional context.

4. **Click Approve**
   Click the green **Approve** button on the item's row. The item is immediately approved, the submitter is notified via email, and the approval is logged in the audit trail.

   - If the item has a multi-step approval chain, clicking Approve advances it to the next step. You will see a message like *"Step 1 approved. Advanced to step 2."* The next step's approvers are then notified.

### Bulk Approving Multiple Items

1. **Select Items**
   Check the boxes next to each pending item you want to approve.

2. **Click "Approve All Selected"**
   A bulk action bar appears at the top of the table showing how many items are selected. Click the button to approve all selected items at once.

3. **Confirmation**
   You will see a success message showing how many items were approved. Each submitter is notified individually.

---

## 6. Step-by-Step: Rejecting Items

### From the Dashboard

1. **Click Reject**
   Click the red **Reject** button on the item's row. A modal window appears.

2. **Enter a Rejection Reason** *(required, minimum 10 characters)*
   Explain why the item is being rejected and what the submitter should do to resubmit.

3. **Confirm Rejection**
   Click **Reject** in the modal. The submitter is notified via email with the rejection reason. The item's status changes to "Rejected."

### What the Submitter Sees

- The submitter receives an email with the rejection reason and a link to resubmit.
- In **My Submissions**, rejected items show the rejection reason in a highlighted box and include a **Resubmit** link that takes them back to the submission form.

---

## 7. Step-by-Step: Approving from Email

Approvers can act on items directly from their email notification without logging into the dashboard.

### Approve via Email

1. **Open the Notification Email**
   The email shows the item name, type, description, who submitted it, when, and which program.

2. **Click "Review & Approve"**
   This opens a signed URL in your browser. If you are logged in, the item is approved immediately. The submitter is notified, and the action is logged with your IP address and browser signature.

3. **For Multi-Step Items**
   If the item requires multiple approval steps, clicking Approve advances it to the next step. The next approver is notified automatically.

### Reject via Email

1. **Click "Reject" in the Email**
   This opens a rejection form in your browser.

2. **Enter a Rejection Reason** *(minimum 10 characters)*
   Explain why the item is being rejected.

3. **Submit**
   The submitter is notified with the reason. The action is logged in the audit trail.

### Security Notes

- Email links use signed URLs that cannot be forged or reused.
- Each action records the approver's IP address and browser user agent in the audit trail.
- If an item has already been processed (approved or rejected), the link shows a message: *"This approval has already been processed."*

---

## 8. Tracking Your Submissions

Any authenticated user can track their own submissions.

### Viewing My Submissions

1. **Navigate to My Submissions**
   Click **My Submissions** in the sidebar, or go to `/approvals/my-submissions`.

2. **Review Your Submissions**
   The table shows all submissions you have created, with columns for:
   - **Title** - Your submission title
   - **Type** - The approval type
   - **Status** - Color-coded badge:
     - Gray = Draft
     - Yellow = Pending (awaiting review)
     - Green = Approved
     - Red = Rejected
     - Gray = Cancelled
   - **Submitted Date**
   - **Approver** - Who approved or rejected the item

3. **Filter Your Submissions**
   Use the search bar, status dropdown, or type dropdown to find specific submissions.

4. **View Details**
   Click on a row to expand it and see the full description, notes, program, and rejection reason (if applicable).

### After a Rejection

If your submission is rejected:
- The rejection reason appears in a red highlighted box in the detail view.
- A **Resubmit** link appears that takes you to the submission form to create a new request.

---

## 9. Audit Report & Export

The Audit Report provides a complete trail of every approval action taken in the system.

### Accessing the Report

Navigate to **Audit Report** in the sidebar, or go to `/approvals/audit-report`. Requires `approvals.view_dashboard` or `approvals.view_audit_log` permission.

### Summary Statistics

At the top of the report, three cards show filtered totals:
- **Approved** (green) - Total items approved in the date range
- **Rejected** (red) - Total items rejected in the date range
- **Resubmitted** (yellow) - Total items resubmitted after rejection

### Filtering the Report

| Filter | Description |
|--------|-------------|
| **Date From / Date To** | Defaults to the last 30 days. Adjustable to any range. |
| **Status** | Filter by action type: Approved, Rejected, or Resubmitted |
| **Approval Type** | Filter by any of the 9 approval types |
| **Program** | Filter by associated program |
| **Actioned By** | Filter by the user who performed the action |
| **Search** | Search across approver name, email, reason, and notes |

### Report Columns

| Column | Description |
|--------|-------------|
| **Date** | When the action was taken (date and time) |
| **Action** | Approved, Rejected, or Resubmitted (color-coded) |
| **Item** | The type and name of the item |
| **Category** | Housing, Programs, Contracts, or General |
| **Actioned By** | Name and email of the person who took action |
| **Chain Step** | Which step in a multi-step chain (if applicable) |
| **IP Address** | The IP address of the person who took action |
| **Notes / Reason** | The rejection reason or any notes recorded |

### Exporting to Excel

1. Apply your desired filters.
2. Click **Export to Excel**.
3. An `.xlsx` file is generated and downloaded automatically.
4. The export includes:
   - Title row with the date range
   - Summary counts (total records, approved, rejected, resubmitted)
   - All filtered data with column headers
   - Professional formatting with alternating row colors

---

## 10. Multi-Step Approval Chains

For items that require multiple levels of review, the system supports sequential approval chains.

### How It Works

1. **An administrator creates chain steps** for an approval type in the `approval_chains` table. Each step has:
   - A **step number** (1, 2, 3, ...)
   - A **step name** (e.g., "Supervisor Review," "Director Approval," "Finance Sign-Off")
   - An **approver requirement**: a specific user, a role, or a permission

2. **When an item is submitted**, the system creates a `PendingApproval` record at `current_chain_step = 1` and notifies only the Step 1 approvers.

3. **When Step 1 is approved**, the system:
   - Logs the intermediate approval to the audit trail
   - Advances `current_chain_step` to 2
   - Notifies the Step 2 approvers
   - The item remains in "Pending" status

4. **When the final step is approved**, the item transitions to "Approved" status and the submitter is notified.

5. **If any step rejects**, the entire item is rejected and the submitter is notified with the reason.

### Approver Resolution Priority

At each step, the system resolves who can approve using this priority:

1. **Pinned User** - If a specific user is assigned to the step, only that user can approve
2. **Required Role** - If a role is specified, all users with that role can approve
3. **Required Permission** - If a permission is specified, all users whose roles grant that permission can approve
4. **Fallback** - If none of the above are set, all users with the `approvals.approve_reject` permission can approve

### Email Notifications for Chain Steps

Each approval notification email for a multi-step chain includes:
- The current step name
- The step number (e.g., "Step 2 of 3")
- All standard item details

### Example: 3-Step Budget Approval

| Step | Name | Approver |
|------|------|----------|
| 1 | Department Head Review | Role: `project_manager` |
| 2 | Finance Review | Permission: `budget.edit` |
| 3 | Executive Director Approval | Pinned user: Jane Smith |

When a budget request is submitted:
- Step 1: All project managers are notified
- After Step 1 approval: Users with `budget.edit` permission are notified
- After Step 2 approval: Only Jane Smith is notified
- After Step 3 approval: The submitter receives the final approval notification

---

## 11. Use Cases & Examples

### Routine Document Approval

A housing coordinator uploads a contractor's updated insurance certificate:
1. Go to **Submit Approval** and select **Contractor Document**
2. Title: *"ABC Contractors - Updated Insurance Certificate 2026"*
3. Attach the PDF
4. Select the relevant program
5. Submit

The housing manager receives an email, clicks **Review & Approve**, and the coordinator sees the green "Approved" status in **My Submissions**.

### Budget Request with Justification

A program manager needs approval for an unbudgeted expense:
1. Go to **Submit Approval** and select **Budget Request**
2. Title: *"Emergency HVAC Repair - 123 Main Street"*
3. Description: *"HVAC system failed during cold snap. Immediate repair needed for occupied unit."*
4. Notes: *"Vendor quote attached. Requesting expedited approval due to tenant safety."*
5. Attach the vendor quote
6. Submit

### Policy Acknowledgment

HR needs staff to acknowledge a new workplace policy:
1. Go to **Submit Approval** and select **Policy Acknowledgment**
2. Title: *"2026 Remote Work Policy Acknowledgment"*
3. Description: *"I have read and understand the updated remote work policy effective March 1, 2026."*
4. No attachment needed
5. Submit

### Performance Report Review

A contract manager submits a quarterly report for review:
1. Go to **Submit Approval** and select **Performance Report**
2. Title: *"Q1 2026 Head Start Performance Report"*
3. Attach the report document
4. Select the Head Start program
5. Add notes: *"Enrollment figures on page 2 reflect the February intake cohort."*
6. Submit

### Digital Confirmation / Sign-Off

A project manager needs a stakeholder to confirm project completion:
1. Go to **Submit Approval** and select **Digital Confirmation**
2. Title: *"Project #2026-0042 Final Walkthrough Confirmation"*
3. Description: *"Confirming that the final walkthrough of 456 Oak Avenue has been completed and all items on the punch list are resolved."*
4. No attachment needed
5. Submit

---

## 12. Technical Reference

### Models

| Model | Table | Purpose |
|-------|-------|---------|
| `ApprovalType` | `approval_types` | Defines the 9 configurable approval categories |
| `ApprovalChain` | `approval_chains` | Defines multi-step chain sequences per type |
| `ApprovalSubmission` | `approval_submissions` | User-submitted approval requests |
| `PendingApproval` | `pending_approvals` | The approval queue (polymorphic to any approvable model) |
| `ApprovalHistory` | `approval_history` | Immutable audit trail of all actions |
| `ApprovalNotification` | `approval_notifications` | Notification delivery tracking |

### Key Relationships

```
ApprovalType ---< ApprovalChain          (one type has many chain steps)
ApprovalType ---< PendingApproval        (one type has many pending items)
ApprovalSubmission ---> ApprovalType     (each submission has one type)
ApprovalSubmission ---> Program          (optional program association)
PendingApproval ---morphTo---> any model (polymorphic approvable)
PendingApproval ---morphTo---> any model (polymorphic context)
ApprovalHistory ---morphTo---> any model (polymorphic approvable)
ApprovalHistory ---morphTo---> any model (polymorphic context)
```

### HasApprovals Trait

Any Eloquent model can participate in the approval system by adding the `HasApprovals` trait:

```php
use App\Traits\HasApprovals;

class MyModel extends Model
{
    use HasApprovals;

    // Override these methods for custom behavior:
    public function getApprovalTypeLabel(): string
    {
        return 'My Custom Type';
    }

    public function getApprovalCategory(): string
    {
        return 'My Category';
    }

    public function getApprovalDetailUrl(): ?string
    {
        return route('my-model.show', $this->id);
    }
}
```

The trait provides:
- `pendingApprovals()` - MorphMany relationship to PendingApproval
- `approvalHistory()` - MorphMany relationship to ApprovalHistory
- `getActivePendingApproval()` - Fetches the current pending approval record
- `isApproved()`, `isRejected()`, `isPendingApproval()` - Status checks
- `requiresApproval()` - Whether this model needs approval (checks `requires_approval` attribute)

### Routes

| Method | URI | Name | Permission |
|--------|-----|------|-----------|
| GET | `/approvals/pending` | `approvals.pending` | `approvals.view_dashboard` |
| GET | `/approvals/submit/{type?}` | `approvals.submit` | `approvals.submit` |
| GET | `/approvals/my-submissions` | `approvals.my-submissions` | *(auth only)* |
| GET | `/approvals/audit-report` | `approvals.audit-report` | `approvals.view_dashboard` |
| GET | `/approvals/audit-report-download` | `approvals.audit-report-download` | `approvals.view_dashboard` |
| GET | `/approvals/{id}/approve` | `approval.approve` | Signed URL |
| GET | `/approvals/{id}/reject` | `approval.reject` | Signed URL |
| POST | `/approvals/{id}/reject` | `approval.reject.submit` | Signed URL |

### Artisan Commands

```bash
# Seed approval types (run after fresh migration)
php artisan db:seed --class=ApprovalTypesSeeder --force

# Seed permissions (includes approval permissions)
php artisan db:seed --class=ProjectHousingPermissionsSeeder --force
```

### Database Migrations

| Migration | Purpose |
|-----------|---------|
| `2026_02_20_100000` | Creates `approval_types` table |
| `2026_02_20_100001` | Creates `approval_chains` table |
| `2026_02_20_100002` | Creates `approval_submissions` table |
| `2026_02_20_100003` | Generalizes `pending_approvals` (adds context morph, removes `housing_job_id`) |
| `2026_02_20_100004` | Generalizes `approval_history` (adds context morph, signature fields, removes legacy columns) |
