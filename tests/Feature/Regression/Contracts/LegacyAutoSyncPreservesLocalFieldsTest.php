<?php

use App\Models\Contract;
use App\Models\ContractReportDueDate;
use App\Services\LegacyContractSyncService;

/**
 * Database-level companion to tests/Unit/LegacyContractSyncOwnershipTest.php.
 *
 * The unit test proves the payload filter drops the right keys. These tests
 * prove that applying the filtered payload through Eloquent really does leave
 * the locally-owned columns standing — casts, mutators and observers included.
 */
uses()->group('regression');

/**
 * Apply a legacy payload the way LegacyContractSyncService does on update.
 */
function applyLegacyUpdate($model, array $payload, string $entity)
{
    $service = app(LegacyContractSyncService::class);
    $method = new ReflectionMethod($service, 'stripLocallyOwned');
    $method->setAccessible(true);

    $model->update($method->invoke($service, $payload, $entity, $model));

    return $model->fresh();
}

it('does not unarchive a contract that was archived in CAP TRACK', function () {
    $contract = Contract::factory()->archived()->create([
        'legacy_id' => 4242,
        'title' => 'Stale Title',
        'amount' => 1.00,
    ]);

    $fresh = applyLegacyUpdate($contract, [
        'title' => 'Updated Title From Legacy',
        'amount' => 123456.78,
        'purpose' => '<p>Updated purpose</p>',
        'archived' => false,        // what legacy would push
        'date_archived' => null,
        'last_synced_at' => now(),
    ], 'contract');

    // Legacy owns the content...
    expect($fresh->title)->toBe('Updated Title From Legacy')
        ->and((float) $fresh->amount)->toBe(123456.78)
        ->and($fresh->purpose)->toBe('<p>Updated purpose</p>');

    // ...CAP TRACK keeps its archive state.
    expect($fresh->archived)->toBeTrue()
        ->and($fresh->date_archived)->not->toBeNull();
});

it('does not reset a due date status or reminder intervals set in CAP TRACK', function () {
    $contract = Contract::factory()->create(['legacy_id' => 4242]);

    $dueDate = ContractReportDueDate::create([
        'contract_id' => $contract->id,
        'legacy_id' => 7777,
        'report_name' => 'Renamed Locally',
        'due_date' => '2026-06-30',
        'status' => 'overdue',                  // derived here; legacy has no such value
        'reminder_intervals' => [45, 10],       // customised here
        'active' => true,
    ]);

    $fresh = applyLegacyUpdate($dueDate, [
        'report_name' => 'Legacy Description',
        'due_date' => '2026-07-15',
        'description' => 'Refreshed from legacy',
        'reminder_intervals' => [30, 15, 7, 3, 1],
        'status' => 'pending',
        'active' => true,
        'last_synced_at' => now(),
    ], 'due_date');

    // Legacy owns the date and description.
    expect($fresh->due_date->format('Y-m-d'))->toBe('2026-07-15')
        ->and($fresh->description)->toBe('Refreshed from legacy');

    // CAP TRACK owns the workflow state.
    expect($fresh->status)->toBe('overdue')
        ->and($fresh->reminder_intervals)->toBe([45, 10])
        ->and($fresh->report_name)->toBe('Renamed Locally');
});

it('records a sync run that can be summarised for the status panel', function () {
    $run = App\Models\LegacyContractSyncRun::create([
        'status' => 'success',
        'triggered_by' => 'scheduled',
        'started_at' => now(),
        'completed_at' => now(),
        'detected_new' => 6,
        'detected_updated' => 34,
        'detected_deleted' => 36,
        'created_count' => 6,
        'updated_count' => 34,
        'media_synced' => 12,
        'deleted_legacy_ids' => [101, 102],
    ]);

    expect($run->madeChanges())->toBeTrue()
        ->and($run->hasProblems())->toBeFalse()
        ->and($run->summaryLine())->toBe('6 imported, 34 updated, 12 attachments')
        ->and($run->deleted_legacy_ids)->toBe([101, 102])
        ->and(App\Models\LegacyContractSyncRun::latestRun()->id)->toBe($run->id);

    // A quiet night reads as such rather than as a failure.
    $quiet = App\Models\LegacyContractSyncRun::create([
        'status' => 'success',
        'triggered_by' => 'scheduled',
        'started_at' => now(),
    ]);

    expect($quiet->madeChanges())->toBeFalse()
        ->and($quiet->summaryLine())->toBe('No changes — legacy system is in step.');
});
