<?php

use App\Notifications\BudgetModifiedNotification;
use App\Notifications\RequirementAssignedNotification;
use App\Notifications\RequirementCompletedNotification;
use App\Notifications\RequirementUpstreamUploadedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Phase 3B Step 1 invariant: every Phase 3-domain notification MUST
 * implement ShouldQueue. The deploy story (deploy/systemd/cc_caclv_data-queue.service)
 * depends on this — without ShouldQueue, dispatches block the user-facing
 * request on Office 365 SMTP latency.
 *
 * If a future change removes ShouldQueue from any of these classes,
 * this test fails before the regression suite reaches anything else
 * and the change is rejected.
 */
it('marks all phase 3 notification classes as ShouldQueue', function (string $class) {
    expect(is_subclass_of($class, ShouldQueue::class))
        ->toBeTrue("{$class} must implement ShouldQueue so dispatches drain through the queue worker rather than blocking on synchronous SMTP.");
})
    ->with([
        RequirementAssignedNotification::class,
        RequirementCompletedNotification::class,
        RequirementUpstreamUploadedNotification::class,
        BudgetModifiedNotification::class,
    ])
    ->group('regression');
