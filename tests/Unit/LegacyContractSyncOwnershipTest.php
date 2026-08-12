<?php

use App\Models\ContractReportDueDate;
use App\Services\LegacyContractSyncService;

/**
 * The automatic legacy contract sync rewrites existing contracts whenever the
 * upstream record changes. A set of columns is owned by CAP TRACK and has no
 * legacy equivalent — if the sync writes over them, live operational state is
 * destroyed silently and at scale. The first version of this code reset
 * `archived` on every contract it touched and flattened every derived report
 * status back to the legacy incomplete/completed pair.
 *
 * These tests cover the filter itself and need no database.
 */
uses(Tests\TestCase::class);

/**
 * Reach the protected payload filter directly — it is the single seam every
 * update path funnels through.
 */
function stripLocal(array $payload, string $entity, $existing = null): array
{
    $service = app(LegacyContractSyncService::class);
    $method = new ReflectionMethod($service, 'stripLocallyOwned');
    $method->setAccessible(true);

    return $method->invoke($service, $payload, $entity, $existing);
}

function legacyContractPayload(array $overrides = []): array
{
    return array_merge([
        'legacy_id' => 4242,
        'title' => 'Updated Title From Legacy',
        'contract_number' => 'CT-99999',
        'purpose' => '<p>Updated purpose</p>',
        'program_id' => 5,
        'amount' => 123456.78,
        'archived' => false,          // legacy has no archiving concept
        'date_archived' => null,
        'service_code' => null,       // CAP TRACK / Intacct concept
        'last_synced_at' => '2026-08-12 04:00:00',
    ], $overrides);
}

it('strips the archived flag when updating an existing contract', function () {
    $existing = (object) ['archived' => true, 'program_id' => 8, 'date_archived' => '2026-01-01'];

    $payload = stripLocal(legacyContractPayload(), 'contract', $existing);

    expect($payload)->not->toHaveKey('archived')
        ->and($payload)->not->toHaveKey('date_archived')
        ->and($payload)->not->toHaveKey('service_code');
});

it('still passes legacy-owned fields through on update', function () {
    $existing = (object) ['archived' => true, 'program_id' => 8];

    $payload = stripLocal(legacyContractPayload(), 'contract', $existing);

    expect($payload)->toHaveKey('title')
        ->and($payload['title'])->toBe('Updated Title From Legacy')
        ->and($payload['amount'])->toBe(123456.78)
        ->and($payload['purpose'])->toBe('<p>Updated purpose</p>')
        ->and($payload)->toHaveKey('last_synced_at');
});

it('applies every field on first import', function () {
    // Nothing is stripped when there is no existing local record.
    $payload = stripLocal(legacyContractPayload(), 'contract', null);

    expect($payload)->toHaveKey('archived')
        ->and($payload['archived'])->toBeFalse()
        ->and($payload)->toHaveKey('program_id');
});

it('preserves a hand-corrected program but backfills an empty one', function () {
    // Corrected by hand here — fuzzy legacy name matching must not overwrite it.
    $corrected = (object) ['archived' => false, 'program_id' => 8];
    expect(stripLocal(legacyContractPayload(), 'contract', $corrected))
        ->not->toHaveKey('program_id');

    // Never resolved on first import — fill_if_empty allows a later backfill.
    $unmapped = (object) ['archived' => false, 'program_id' => null];
    $payload = stripLocal(legacyContractPayload(), 'contract', $unmapped);

    expect($payload)->toHaveKey('program_id')
        ->and($payload['program_id'])->toBe(5);
});

it('protects due date workflow state owned by CAP TRACK', function () {
    $existing = (object) [
        'status' => 'overdue',
        'reminder_intervals' => [45, 10],
        'report_name' => 'Renamed Locally',
    ];

    $payload = stripLocal([
        'legacy_id' => 7777,
        'report_name' => 'Legacy Description',
        'due_date' => '2026-07-15',
        'description' => 'Refreshed from legacy',
        'reminder_intervals' => [30, 15, 7, 3, 1],
        'status' => 'pending',
        'active' => true,
        'last_synced_at' => '2026-08-12 04:00:00',
    ], 'due_date', $existing);

    // CAP TRACK owns the workflow state...
    expect($payload)->not->toHaveKey('status')
        ->and($payload)->not->toHaveKey('reminder_intervals')
        ->and($payload)->not->toHaveKey('report_name')
        ->and($payload)->not->toHaveKey('active');

    // ...legacy still owns the date and description.
    expect($payload['due_date'])->toBe('2026-07-15')
        ->and($payload['description'])->toBe('Refreshed from legacy');
});

it('translates legacy due date statuses into the CAP TRACK vocabulary', function () {
    $service = app(LegacyContractSyncService::class);
    $method = new ReflectionMethod($service, 'mapDueDateStatus');
    $method->setAccessible(true);

    // Legacy "incomplete" is not a valid CAP TRACK status.
    expect($method->invoke($service, 'incomplete'))->toBe('pending')
        ->and($method->invoke($service, 'completed'))->toBe('completed')
        ->and($method->invoke($service, 'cancelled'))->toBe('cancelled')
        ->and($method->invoke($service, null))->toBe('pending');

    expect(array_keys(ContractReportDueDate::STATUS_OPTIONS))
        ->toContain($method->invoke($service, 'incomplete'));
});

/**
 * A contract with no funder is legitimate — letters of support and pre-award
 * records often have none, and `contracts.funder_id` is nullable. Throwing on a
 * null funder blocked every such contract from importing at all.
 */
it('treats a missing legacy funder as no funder rather than an error', function () {
    $service = app(LegacyContractSyncService::class);
    $method = new ReflectionMethod($service, 'mapFunder');
    $method->setAccessible(true);

    expect($method->invoke($service, null))->toBeNull()
        ->and($method->invoke($service, 0))->toBeNull();
});

/**
 * Tripwire: the ownership lists are the whole safety mechanism. Removing an
 * entry silently re-enables the data loss, so name the critical ones here.
 */
it('keeps the locally-owned field lists intact', function () {
    expect(config('legacy-import.auto_sync.locally_owned.contract'))
        ->toContain('archived')
        ->toContain('date_archived')
        ->toContain('program_id');

    expect(config('legacy-import.auto_sync.locally_owned.due_date'))
        ->toContain('status')
        ->toContain('reminder_intervals');

    // Deleting upstream must never destroy local records by default.
    expect(config('legacy-import.auto_sync.deleted_in_legacy'))->toBe('report');
});
