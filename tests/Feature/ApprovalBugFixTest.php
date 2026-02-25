<?php

use App\Models\HousingJob;
use App\Models\PendingApproval;
use App\Models\User;
use App\Notifications\ApprovalActionedNotification;
use App\Observers\PendingApprovalObserver;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('priority is included in PendingApproval fillable', function () {
    $user = User::factory()->create();

    $pa = PendingApproval::withoutEvents(function () use ($user) {
        return PendingApproval::create([
            'approvable_type' => 'App\\Models\\ApprovalSubmission',
            'approvable_id' => 1,
            'submitted_by' => $user->id,
            'item_type' => 'general',
            'item_name' => 'Test Item',
            'status' => 'pending',
            'submitted_at' => now(),
            'priority' => 1,
        ]);
    });

    expect($pa->priority)->toBe(1);
    expect($pa->exists)->toBeTrue();

    $this->assertDatabaseHas('pending_approvals', [
        'id' => $pa->id,
        'priority' => 1,
    ]);
});

test('scopeForJob filters by context morph', function () {
    $user = User::factory()->create();

    // Create a pending approval with context_type and context_id pointing to a HousingJob
    $pa = PendingApproval::withoutEvents(function () use ($user) {
        return PendingApproval::create([
            'approvable_type' => 'App\\Models\\ApprovalSubmission',
            'approvable_id' => 1,
            'submitted_by' => $user->id,
            'item_type' => 'property_document',
            'item_name' => 'Test Document',
            'status' => 'pending',
            'submitted_at' => now(),
            'context_type' => HousingJob::class,
            'context_id' => 999,
        ]);
    });

    // Create another PA without the context to make sure the scope filters correctly
    PendingApproval::withoutEvents(function () use ($user) {
        return PendingApproval::create([
            'approvable_type' => 'App\\Models\\ApprovalSubmission',
            'approvable_id' => 2,
            'submitted_by' => $user->id,
            'item_type' => 'general',
            'item_name' => 'Other Item',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    });

    $results = PendingApproval::forJob(999)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($pa->id);
    expect($results->first()->context_type)->toBe(HousingJob::class);
    expect($results->first()->context_id)->toBe(999);
});

test('ApprovalActionedNotification uses double quotes for interpolation', function () {
    $user = User::factory()->create();
    $approver = User::factory()->create();

    $pa = PendingApproval::withoutEvents(function () use ($user, $approver) {
        return PendingApproval::create([
            'approvable_type' => 'App\\Models\\ApprovalSubmission',
            'approvable_id' => 1,
            'submitted_by' => $user->id,
            'approver_id' => $approver->id,
            'item_type' => 'property_document',
            'item_name' => 'Test Document',
            'status' => 'rejected',
            'submitted_at' => now(),
            'actioned_at' => now(),
            'rejection_reason' => 'Needs revision',
        ]);
    });

    $notification = new ApprovalActionedNotification($pa, 'rejected');

    // Build the mail message - this would fail if interpolation used single quotes
    $mailMessage = $notification->toMail($user);

    // The subject line should contain the actual item_type value, not a literal variable reference
    expect($mailMessage->subject)->toContain('property_document');
    expect($mailMessage->subject)->not->toContain('{$itemType}');
    expect($mailMessage->subject)->not->toContain('$itemType');
});

test('PendingApprovalObserver uses correct permission name approvals.approve_reject', function () {
    // Use reflection to read the source code of the observer's getApprovers method
    $reflection = new ReflectionClass(PendingApprovalObserver::class);
    $method = $reflection->getMethod('getApprovers');

    $filename = $method->getFileName();
    $startLine = $method->getStartLine();
    $endLine = $method->getEndLine();
    $source = file($filename);
    $methodSource = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));

    // Verify the observer uses the correct permission name
    expect($methodSource)->toContain('approvals.approve_reject');
    // Ensure it does NOT use the old incorrect permission name
    expect($methodSource)->not->toMatch('/approvals\.approve[^_]/');
});

test('PendingApproval fillable includes all context morph fields', function () {
    $pa = new PendingApproval;
    $fillable = $pa->getFillable();

    expect($fillable)->toContain('context_type');
    expect($fillable)->toContain('context_id');
    expect($fillable)->toContain('approval_type_id');
    expect($fillable)->toContain('current_chain_step');
    expect($fillable)->toContain('priority');
});
