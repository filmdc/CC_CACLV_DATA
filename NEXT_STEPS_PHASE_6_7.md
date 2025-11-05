# Next Steps for Phase 6 & 7 Implementation

## Immediate Actions Required

### 1. Database Migration Cleanup and Execution

**Priority: HIGH**

```bash
# Review duplicate migration files
ls -la database/migrations/ | grep "2025_11_03"

# Compare and consolidate:
# - Keep: 2025_11_03_143221_add_approval_tracking_to_documents_and_tasks.php
# - Keep: 2025_11_03_143224_create_pending_approvals_table.php
# - Keep: 2025_11_03_143225_create_approval_history_table.php
# - Remove: Any duplicates from earlier today

# After cleanup, run migrations
php artisan migrate

# Verify tables created
php artisan db:show
```

**Checklist:**
- [ ] Review all migration files from today
- [ ] Remove duplicates/obsolete migrations
- [ ] Run migrations in development
- [ ] Verify all tables and columns created
- [ ] Check foreign key constraints
- [ ] Seed test data if needed

### 2. Testing the Observer and Workflow

**Priority: HIGH**

Create a test script to verify the document observer works:

```php
// tests/Feature/DocumentApprovalWorkflowTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\PendingApproval;
use App\Models\ApprovalHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_document_requiring_approval_creates_pending_approval()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();

        $this->actingAs($user);

        $document = PropertyDocument::create([
            'property_id' => $property->id,
            'document_name' => 'Test Document',
            'requires_approval' => true,
            'file_path' => 'test/path.pdf',
            'uploaded_by' => $user->id,
        ]);

        $this->assertDatabaseHas('pending_approvals', [
            'approvable_type' => PropertyDocument::class,
            'approvable_id' => $document->id,
            'status' => 'pending',
        ]);
    }

    public function test_reuploading_rejected_document_increments_version_and_resets_status()
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();
        $property = Property::factory()->create();

        $this->actingAs($user);

        $document = PropertyDocument::create([
            'property_id' => $property->id,
            'document_name' => 'Test Document',
            'requires_approval' => true,
            'file_path' => 'test/original.pdf',
            'uploaded_by' => $user->id,
            'approval_status' => 'rejected',
            'approved_by' => $approver->id,
            'rejection_reason' => 'Missing signature',
            'document_version' => 1,
        ]);

        // Reupload (change file_path)
        $document->update([
            'file_path' => 'test/updated.pdf',
        ]);

        $document->refresh();

        $this->assertEquals('pending', $document->approval_status);
        $this->assertEquals(2, $document->document_version);
        $this->assertNull($document->rejection_reason);

        // Check resubmission logged
        $this->assertDatabaseHas('approval_history', [
            'approvable_type' => PropertyDocument::class,
            'approvable_id' => $document->id,
            'action' => 'resubmitted',
            'version' => 2,
        ]);
    }

    public function test_job_cannot_complete_with_pending_approvals()
    {
        $job = HousingJob::factory()->create();
        $document = JobDocumentChecklist::create([
            'housing_job_id' => $job->id,
            'document_name' => 'Required Doc',
            'requires_approval' => true,
            'approval_status' => 'pending',
        ]);

        $this->assertFalse($job->canComplete());
        $this->assertNotNull($job->getCompletionBlockerMessage());
    }

    public function test_job_can_complete_when_all_approved()
    {
        $job = HousingJob::factory()->create();
        $document = JobDocumentChecklist::create([
            'housing_job_id' => $job->id,
            'document_name' => 'Required Doc',
            'requires_approval' => true,
            'approval_status' => 'approved',
        ]);

        $this->assertTrue($job->canComplete());
        $this->assertNull($job->getCompletionBlockerMessage());
    }
}
```

**Checklist:**
- [ ] Create feature tests
- [ ] Test document upload with requires_approval flag
- [ ] Test approval workflow
- [ ] Test rejection workflow
- [ ] Test resubmission workflow
- [ ] Test job completion gating
- [ ] Test approval progress calculation

### 3. UI Implementation - Approval Dashboard

**Priority: MEDIUM**

Create a Livewire component for the approval dashboard:

```bash
php artisan make:livewire ApprovalDashboard
```

**File:** `app/Livewire/ApprovalDashboard.php`

```php
<?php

namespace App\Livewire;

use App\Models\PendingApproval;
use Livewire\Component;
use Livewire\WithPagination;

class ApprovalDashboard extends Component
{
    use WithPagination;

    public $filter = 'pending'; // pending, approved, rejected, all
    public $search = '';

    public function approve($approvalId)
    {
        $approval = PendingApproval::findOrFail($approvalId);

        // Check permissions
        // $this->authorize('approve', $approval);

        $approval->approve(auth()->user());

        // Also approve the underlying document
        if ($approval->approvable) {
            $approval->approvable->approve(auth()->user());
        }

        session()->flash('message', 'Item approved successfully.');
    }

    public function reject($approvalId, $reason)
    {
        $approval = PendingApproval::findOrFail($approvalId);

        // Check permissions
        // $this->authorize('approve', $approval);

        $approval->reject(auth()->user(), $reason);

        // Also reject the underlying document
        if ($approval->approvable) {
            $approval->approvable->reject(auth()->user(), $reason);
        }

        session()->flash('message', 'Item rejected.');
    }

    public function render()
    {
        $query = PendingApproval::with(['submittedBy', 'approvable', 'housingJob'])
            ->orderBy('priority', 'desc')
            ->orderBy('submitted_at', 'desc');

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        if ($this->search) {
            $query->where('item_name', 'like', '%' . $this->search . '%');
        }

        $approvals = $query->paginate(20);

        return view('livewire.approval-dashboard', [
            'approvals' => $approvals,
        ]);
    }
}
```

**Checklist:**
- [ ] Create ApprovalDashboard Livewire component
- [ ] Create blade view for dashboard
- [ ] Add route: `Route::get('/approvals', ApprovalDashboard::class)`
- [ ] Add navigation menu item
- [ ] Implement approve/reject actions
- [ ] Add permission checks
- [ ] Style with Tailwind CSS

### 4. UI Implementation - Job Detail Approval Panel

**Priority: MEDIUM**

Update the existing HousingJobManagement component to show approval progress.

**Add to blade view:** `resources/views/livewire/housing-job-management.blade.php`

```blade
<!-- Insert this before job completion section -->

@if($job->hasPendingApprovals())
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-yellow-900 dark:text-yellow-100 mb-4">
            ⚠️ Approval Progress
        </h3>

        @php
            $progress = $job->approval_progress;
        @endphp

        <!-- Overall Progress Bar -->
        <div class="mb-4">
            <div class="flex justify-between mb-2">
                <span class="text-sm font-medium dark:text-gray-300">Overall Progress</span>
                <span class="text-sm font-medium dark:text-gray-300">
                    {{ $progress['overall_approved'] }}/{{ $progress['overall_total'] }}
                    ({{ number_format($progress['overall_progress_percent'], 1) }}%)
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="bg-blue-600 h-3 rounded-full transition-all duration-300"
                     style="width: {{ $progress['overall_progress_percent'] }}%"></div>
            </div>
        </div>

        <!-- Documents and Tasks Progress -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <!-- Documents -->
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm font-medium dark:text-gray-300">Documents</span>
                    <span class="text-sm dark:text-gray-400">
                        {{ $progress['documents_approved'] }}/{{ $progress['documents_total'] }}
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    @php
                        $docColor = $progress['documents_progress_percent'] == 100 ? 'green' :
                                   ($progress['documents_progress_percent'] > 0 ? 'yellow' : 'red');
                    @endphp
                    <div class="bg-{{ $docColor }}-600 h-2 rounded-full"
                         style="width: {{ $progress['documents_progress_percent'] }}%"></div>
                </div>
            </div>

            <!-- Tasks -->
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-sm font-medium dark:text-gray-300">Tasks</span>
                    <span class="text-sm dark:text-gray-400">
                        {{ $progress['tasks_approved'] }}/{{ $progress['tasks_total'] }}
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    @php
                        $taskColor = $progress['tasks_progress_percent'] == 100 ? 'green' :
                                    ($progress['tasks_progress_percent'] > 0 ? 'yellow' : 'red');
                    @endphp
                    <div class="bg-{{ $taskColor }}-600 h-2 rounded-full"
                         style="width: {{ $progress['tasks_progress_percent'] }}%"></div>
                </div>
            </div>
        </div>

        <!-- Pending Items -->
        @php
            $pendingItems = $job->getPendingItemsBreakdown();
            $totalPending = count($pendingItems['property_documents']) +
                           count($pendingItems['contractor_documents']) +
                           count($pendingItems['job_documents']) +
                           count($pendingItems['work_plan_goals']);
        @endphp

        @if($totalPending > 0)
            <div class="mt-4 pt-4 border-t border-yellow-200 dark:border-yellow-700">
                <h4 class="font-medium text-sm mb-2 dark:text-gray-200">
                    Pending Approvals ({{ $totalPending }}):
                </h4>
                <ul class="space-y-1 text-sm max-h-40 overflow-y-auto">
                    @foreach($pendingItems['property_documents'] as $item)
                        <li class="flex items-center text-gray-700 dark:text-gray-300">
                            <span class="inline-block w-2 h-2 bg-yellow-400 rounded-full mr-2"></span>
                            <span>Property: {{ $item['name'] }}</span>
                            <span class="ml-auto text-xs text-gray-500">(v{{ $item['version'] }})</span>
                        </li>
                    @endforeach

                    @foreach($pendingItems['contractor_documents'] as $item)
                        <li class="flex items-center text-gray-700 dark:text-gray-300">
                            <span class="inline-block w-2 h-2 bg-yellow-400 rounded-full mr-2"></span>
                            <span>Contractor: {{ $item['name'] }}</span>
                            <span class="ml-auto text-xs text-gray-500">(v{{ $item['version'] }})</span>
                        </li>
                    @endforeach

                    @foreach($pendingItems['job_documents'] as $item)
                        <li class="flex items-center text-gray-700 dark:text-gray-300">
                            <span class="inline-block w-2 h-2 bg-yellow-400 rounded-full mr-2"></span>
                            <span>Job: {{ $item['name'] }}</span>
                            <span class="ml-auto text-xs text-gray-500">(v{{ $item['version'] }})</span>
                        </li>
                    @endforeach

                    @foreach($pendingItems['work_plan_goals'] as $item)
                        <li class="flex items-center text-gray-700 dark:text-gray-300">
                            <span class="inline-block w-2 h-2 bg-yellow-400 rounded-full mr-2"></span>
                            <span>Task: {{ $item['name'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-4">
            <a href="{{ route('approvals.dashboard') }}"
               class="text-blue-600 dark:text-blue-400 hover:underline text-sm font-medium">
                View Approval Dashboard →
            </a>
        </div>
    </div>
@endif

<!-- Update Complete Job Button -->
<div class="flex items-center gap-4">
    @if($job->hasPendingApprovals())
        <button disabled
                class="bg-gray-400 dark:bg-gray-600 text-white px-6 py-2 rounded-lg opacity-50 cursor-not-allowed">
            Complete Job
        </button>
        <div class="text-red-600 dark:text-red-400 text-sm">
            {{ $job->getCompletionBlockerMessage() }}
        </div>
    @else
        <button wire:click="completeJob"
                wire:confirm="Mark this job as completed?"
                class="bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white px-6 py-2 rounded-lg">
            Complete Job
        </button>
    @endif
</div>
```

**Checklist:**
- [ ] Add approval progress panel to job detail view
- [ ] Update job completion button with gating logic
- [ ] Test with jobs that have pending approvals
- [ ] Test with jobs that have all approvals
- [ ] Ensure dark mode styling works

### 5. Update Document Upload Components

**Priority: MEDIUM**

Add "requires approval" checkbox to all document upload forms.

**Example for PropertyDocument upload:**

```blade
<!-- In document upload form -->
<div class="mt-4">
    <label class="inline-flex items-center">
        <input type="checkbox"
               wire:model="requires_approval"
               class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
            Requires supervisor approval
        </span>
    </label>
    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-6">
        If checked, this document must be approved before the job can be completed.
    </p>
</div>
```

**Checklist:**
- [ ] Add checkbox to PropertyDocument upload
- [ ] Add checkbox to ContractorDocument upload
- [ ] Add checkbox to JobDocumentChecklist creation
- [ ] Add checkbox to WorkPlanGoal creation
- [ ] Update Livewire component properties
- [ ] Save requires_approval value

### 6. Permission System Integration

**Priority: LOW (but recommended)**

Create permissions for approval actions:

```bash
php artisan tinker
```

```php
use App\Models\Permission;

// Create approval permissions
Permission::create([
    'name' => 'documents.approve',
    'display_name' => 'Approve Documents',
    'description' => 'Can approve or reject document submissions'
]);

Permission::create([
    'name' => 'tasks.approve',
    'display_name' => 'Approve Tasks',
    'description' => 'Can approve or reject task completions'
]);

// Assign to supervisor role
$supervisorRole = \App\Models\Role::where('name', 'supervisor')->first();
$supervisorRole->permissions()->attach([
    Permission::where('name', 'documents.approve')->first()->id,
    Permission::where('name', 'tasks.approve')->first()->id,
]);
```

**Update components to check permissions:**

```php
public function approve($approvalId)
{
    $this->authorize('documents.approve');

    // ... approval logic
}
```

**Checklist:**
- [ ] Create approval permissions
- [ ] Assign to appropriate roles
- [ ] Add permission checks in components
- [ ] Update UI to hide approve buttons from unauthorized users

### 7. Notification Templates

**Priority: LOW**

Customize the DocumentResubmittedNotification template:

**File:** `app/Notifications/DocumentResubmittedNotification.php`

Ensure it includes:
- Document name and version
- Job context
- Link to approval dashboard
- Rejection reason from previous review

**Checklist:**
- [ ] Review notification template
- [ ] Test notification delivery
- [ ] Customize email template if needed
- [ ] Add notification preferences (optional)

## Optional Enhancements

### 8. Approval Analytics

Create a dashboard showing:
- Average approval time
- Rejection rate by document type
- Backlog of pending approvals
- Top approvers by volume

### 9. SLA Tracking

Add alerts for overdue approvals:
- Configure SLA timeframes (e.g., 48 hours)
- Send reminder notifications
- Show overdue items in dashboard

### 10. Bulk Actions

Allow approvers to:
- Select multiple items
- Approve all selected
- Reject all selected with common reason

## Summary

**Must Complete Before Production:**
1. ✅ Database migrations
2. ✅ Testing
3. ⚠️ UI implementation (approval dashboard)
4. ⚠️ UI implementation (job detail panel)
5. ⚠️ Document upload checkboxes

**Should Complete:**
6. Permission system integration
7. Notification templates

**Nice to Have:**
8. Approval analytics
9. SLA tracking
10. Bulk actions

## Timeline Estimate

- Immediate (Today): Migrations, basic testing (2-3 hours)
- Short-term (This week): UI implementation (4-6 hours)
- Medium-term (Next week): Permissions, notifications (2-3 hours)
- Optional (As needed): Analytics, SLA, bulk actions (6-8 hours)

**Total Core Implementation:** ~8-12 hours
**Total with Optional Features:** ~14-20 hours
