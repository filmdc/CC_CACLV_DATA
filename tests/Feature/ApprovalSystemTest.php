<?php

use App\Models\ApprovalChain;
use App\Models\ApprovalSubmission;
use App\Models\ApprovalType;
use App\Models\PendingApproval;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('can create approval types', function () {
    $type = ApprovalType::factory()->create([
        'name' => 'expense_report',
        'display_name' => 'Expense Report',
        'category' => 'Finance',
        'module' => 'finance',
    ]);

    expect($type->exists)->toBeTrue();
    expect($type->name)->toBe('expense_report');
    expect($type->display_name)->toBe('Expense Report');
    expect($type->category)->toBe('Finance');
    expect($type->is_active)->toBeTrue();
    expect($type->requires_document)->toBeFalse();

    $this->assertDatabaseHas('approval_types', [
        'id' => $type->id,
        'name' => 'expense_report',
        'display_name' => 'Expense Report',
    ]);
});

test('can create approval chain with steps', function () {
    $type = ApprovalType::factory()->create();

    $step1 = ApprovalChain::factory()->create([
        'approval_type_id' => $type->id,
        'step_number' => 1,
        'step_name' => 'Manager Review',
    ]);

    $step2 = ApprovalChain::factory()->create([
        'approval_type_id' => $type->id,
        'step_number' => 2,
        'step_name' => 'Director Approval',
    ]);

    $type->refresh();

    expect($type->approvalChains)->toHaveCount(2);
    expect($type->approvalChains->first()->step_name)->toBe('Manager Review');
    expect($type->approvalChains->last()->step_name)->toBe('Director Approval');
    expect($step1->approvalType->id)->toBe($type->id);
    expect($step2->approvalType->id)->toBe($type->id);
});

test('approval submission can be created and submitted', function () {
    $user = User::factory()->create();
    $type = ApprovalType::factory()->create();

    // Acting as user so Auditable trait can set created_by/updated_by
    $this->actingAs($user);

    $submission = ApprovalSubmission::factory()->create([
        'approval_type_id' => $type->id,
        'title' => 'My Expense Report',
        'status' => 'draft',
        'submitted_by' => $user->id,
    ]);

    expect($submission->status)->toBe('draft');
    expect($submission->isDraft())->toBeTrue();

    $submission->submit();
    $submission->refresh();

    expect($submission->status)->toBe('pending');
    expect($submission->isPending())->toBeTrue();
    expect($submission->submitted_at)->not->toBeNull();
});

test('pending approval approve method works', function () {
    $submitter = User::factory()->create();
    $approver = User::factory()->create();

    $pa = PendingApproval::withoutEvents(function () use ($submitter) {
        return PendingApproval::create([
            'approvable_type' => 'App\\Models\\ApprovalSubmission',
            'approvable_id' => 1,
            'submitted_by' => $submitter->id,
            'item_type' => 'general',
            'item_name' => 'Test Approval',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    });

    expect($pa->isPending())->toBeTrue();

    PendingApproval::withoutEvents(function () use ($pa, $approver) {
        $pa->approve($approver);
    });

    $pa->refresh();

    expect($pa->status)->toBe('approved');
    expect($pa->isApproved())->toBeTrue();
    expect($pa->approver_id)->toBe($approver->id);
    expect($pa->approved_at)->not->toBeNull();
    expect($pa->actioned_at)->not->toBeNull();
});

test('pending approval reject method works', function () {
    $submitter = User::factory()->create();
    $approver = User::factory()->create();

    $pa = PendingApproval::withoutEvents(function () use ($submitter) {
        return PendingApproval::create([
            'approvable_type' => 'App\\Models\\ApprovalSubmission',
            'approvable_id' => 1,
            'submitted_by' => $submitter->id,
            'item_type' => 'general',
            'item_name' => 'Test Approval',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    });

    PendingApproval::withoutEvents(function () use ($pa, $approver) {
        $pa->reject($approver, 'Insufficient documentation');
    });

    $pa->refresh();

    expect($pa->status)->toBe('rejected');
    expect($pa->isRejected())->toBeTrue();
    expect($pa->approver_id)->toBe($approver->id);
    expect($pa->rejection_reason)->toBe('Insufficient documentation');
    expect($pa->actioned_at)->not->toBeNull();
});

test('multi-step chain advances on approve', function () {
    $submitter = User::factory()->create();
    $approver = User::factory()->create();

    // Act as submitter so Auditable trait works on ApprovalSubmission
    $this->actingAs($submitter);

    $type = ApprovalType::factory()->create();

    // Create 2 active chain steps
    ApprovalChain::factory()->create([
        'approval_type_id' => $type->id,
        'step_number' => 1,
        'step_name' => 'Step 1',
        'is_active' => true,
    ]);

    ApprovalChain::factory()->create([
        'approval_type_id' => $type->id,
        'step_number' => 2,
        'step_name' => 'Step 2',
        'is_active' => true,
    ]);

    // Create a real ApprovalSubmission so approvable morph resolves
    $submission = ApprovalSubmission::factory()->create([
        'approval_type_id' => $type->id,
        'submitted_by' => $submitter->id,
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $pa = PendingApproval::withoutEvents(function () use ($submitter, $type, $submission) {
        return PendingApproval::create([
            'approvable_type' => ApprovalSubmission::class,
            'approvable_id' => $submission->id,
            'submitted_by' => $submitter->id,
            'item_type' => 'general',
            'item_name' => 'Multi-step Approval',
            'status' => 'pending',
            'submitted_at' => now(),
            'approval_type_id' => $type->id,
            'current_chain_step' => 1,
        ]);
    });

    // Approve step 1 - should advance to step 2, not fully approve
    PendingApproval::withoutEvents(function () use ($pa, $approver) {
        $pa->approve($approver);
    });

    $pa->refresh();

    // Should still be pending but on step 2
    expect($pa->status)->toBe('pending');
    expect($pa->current_chain_step)->toBe(2);
    expect($pa->approver_id)->toBe($approver->id);

    // Approve step 2 - should fully approve now
    $secondApprover = User::factory()->create();

    PendingApproval::withoutEvents(function () use ($pa, $secondApprover) {
        $pa->approve($secondApprover);
    });

    $pa->refresh();

    expect($pa->status)->toBe('approved');
    expect($pa->isApproved())->toBeTrue();
    expect($pa->approved_at)->not->toBeNull();
});

test('HasApprovals trait provides pendingApprovals relationship', function () {
    $user = User::factory()->create();
    $type = ApprovalType::factory()->create();

    // Act as user so Auditable trait works
    $this->actingAs($user);

    $submission = ApprovalSubmission::factory()->create([
        'approval_type_id' => $type->id,
        'submitted_by' => $user->id,
    ]);

    // Create a PendingApproval linked to this submission via morph
    PendingApproval::withoutEvents(function () use ($submission, $user) {
        return PendingApproval::create([
            'approvable_type' => ApprovalSubmission::class,
            'approvable_id' => $submission->id,
            'submitted_by' => $user->id,
            'item_type' => 'general',
            'item_name' => 'Test',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    });

    $submission->refresh();

    // The HasApprovals trait should provide a morphMany relationship
    expect($submission->pendingApprovals)->toHaveCount(1);
    expect($submission->pendingApprovals->first())->toBeInstanceOf(PendingApproval::class);
    expect($submission->pendingApprovals->first()->approvable_type)->toBe(ApprovalSubmission::class);
    expect($submission->pendingApprovals->first()->approvable_id)->toBe($submission->id);
});

test('approval types scope active works', function () {
    $activeType = ApprovalType::factory()->create([
        'is_active' => true,
        'name' => 'active_type',
    ]);

    $inactiveType = ApprovalType::factory()->create([
        'is_active' => false,
        'name' => 'inactive_type',
    ]);

    $activeTypes = ApprovalType::active()->get();

    expect($activeTypes)->toHaveCount(1);
    expect($activeTypes->first()->id)->toBe($activeType->id);
    expect($activeTypes->contains('id', $inactiveType->id))->toBeFalse();
});

test('PendingApproval getTypeLabel delegates to approvable', function () {
    $user = User::factory()->create();
    $type = ApprovalType::factory()->create([
        'display_name' => 'Expense Report Approval',
    ]);

    // Act as user so Auditable trait works
    $this->actingAs($user);

    $submission = ApprovalSubmission::factory()->create([
        'approval_type_id' => $type->id,
        'submitted_by' => $user->id,
    ]);

    $pa = PendingApproval::withoutEvents(function () use ($submission, $user, $type) {
        return PendingApproval::create([
            'approvable_type' => ApprovalSubmission::class,
            'approvable_id' => $submission->id,
            'submitted_by' => $user->id,
            'item_type' => 'general',
            'item_name' => 'My Expense',
            'status' => 'pending',
            'submitted_at' => now(),
            'approval_type_id' => $type->id,
        ]);
    });

    // getTypeLabel should delegate to the approvable's getApprovalTypeLabel(),
    // which on ApprovalSubmission returns the approval type's display_name
    expect($pa->getTypeLabel())->toBe('Expense Report Approval');
});
