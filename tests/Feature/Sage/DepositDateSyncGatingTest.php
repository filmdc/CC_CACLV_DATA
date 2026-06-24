<?php

use App\Models\DonorPerfectSetting;
use App\Models\SageIntacctSetting;
use App\Models\SageIntacctSyncItem;
use App\Services\DepositDateSyncService;
use App\Services\DonorPerfectApiService;
use App\Services\SageIntacctApiService;
use Illuminate\Support\Facades\Schema;

/**
 * Guards the deposit-date sync gate: the system must sync the REAL bank deposit
 * date (DEPOSIT.BATCHDATE, surfaced by the API layer as receipt['depositdate'] with
 * deposited=true) and must SKIP — never mark synced — receipts still sitting in
 * undeposited funds, so a later run can pick up the real date once it lands.
 */

/**
 * Build a DepositDateSyncService with its API collaborators swapped for mocks.
 * The service constructs them internally from settings, so we inject via reflection.
 */
function makeDepositSyncService(SageIntacctApiService $sage, DonorPerfectApiService $dp): DepositDateSyncService
{
    $dpSetting = Mockery::mock(DonorPerfectSetting::class);
    $dpSetting->shouldReceive('isConfigured')->andReturn(true);

    $service = new DepositDateSyncService;
    $ref = new ReflectionObject($service);

    foreach ([
        'sageApiService' => $sage,
        'dpApiService' => $dp,
        'sageSetting' => new SageIntacctSetting,
        'dpSetting' => $dpSetting,
        'environment' => 'production',
    ] as $prop => $value) {
        $p = $ref->getProperty($prop);
        $p->setAccessible(true);
        $p->setValue($service, $value);
    }

    return $service;
}

function makePendingSyncItem(string $recordNo): SageIntacctSyncItem
{
    Schema::disableForeignKeyConstraints();
    $item = SageIntacctSyncItem::create([
        'sync_history_id' => 1,
        'environment' => 'production',
        'gift_id' => '203734',
        'status' => SageIntacctSyncItem::STATUS_SUCCESS,
        'intacct_record_id' => $recordNo,
        'synced_at' => now(),
    ]);
    Schema::enableForeignKeyConstraints();

    return $item;
}

it('skips a receipt still in undeposited funds and leaves it pending', function () {
    $item = makePendingSyncItem('49182');

    $sage = Mockery::mock(SageIntacctApiService::class);
    $sage->shouldReceive('queryOtherReceiptsByRecordNos')->once()->andReturn([
        'success' => true,
        'receipts' => [[
            'recordno' => '49182',
            'deposited' => false,
            'depositdate' => null,
            'projectid' => 'F8000',
            'departmentid' => 'S16999',
            'state' => 'Recorded',
        ]],
    ]);

    $dp = Mockery::mock(DonorPerfectApiService::class);
    $dp->shouldReceive('updateGiftUdf')->never();

    $result = makeDepositSyncService($sage, $dp)->sync();

    expect($result['skipped_not_deposited'])->toBe(1);
    expect($result['dp_updated'])->toBe(0);
    expect($item->fresh()->deposit_date_synced_at)->toBeNull();
});

it('keeps a skipped (not yet deposited) receipt in the pending pool for the next run', function () {
    $item = makePendingSyncItem('49182');

    $sage = Mockery::mock(SageIntacctApiService::class);
    $sage->shouldReceive('queryOtherReceiptsByRecordNos')->andReturn([
        'success' => true,
        'receipts' => [[
            'recordno' => '49182',
            'deposited' => false,
            'depositdate' => null,
            'state' => 'Recorded',
        ]],
    ]);
    $dp = Mockery::mock(DonorPerfectApiService::class);

    $service = makeDepositSyncService($sage, $dp);
    $service->sync();

    // Still unmarked → still returned by the pending query (a future run re-checks it).
    expect($item->fresh()->deposit_date_synced_at)->toBeNull();
    expect($service->getPendingItems()->pluck('id'))->toContain($item->id);
});

it('retires a reversed receipt so it leaves the pending pool without writing a date', function () {
    $item = makePendingSyncItem('45405');

    $sage = Mockery::mock(SageIntacctApiService::class);
    $sage->shouldReceive('queryOtherReceiptsByRecordNos')->once()->andReturn([
        'success' => true,
        'receipts' => [[
            'recordno' => '45405',
            'deposited' => false,
            'depositdate' => null,
            'state' => 'Reversed',
        ]],
    ]);
    $dp = Mockery::mock(DonorPerfectApiService::class);
    $dp->shouldReceive('updateGiftUdf')->never();

    $service = makeDepositSyncService($sage, $dp);
    $result = $service->sync();

    expect($result['skipped_terminal'])->toBe(1);
    expect($result['skipped_not_deposited'])->toBe(0);
    // Marked done-without-writing → drops out of the pending pool, not polled forever.
    expect($item->fresh()->deposit_date_synced_at)->not->toBeNull();
    expect($item->fresh()->sage_deposit_date)->toBeNull();
    expect($service->getPendingItems()->pluck('id'))->not->toContain($item->id);
});

it('syncs the real deposit date and marks synced once deposited', function () {
    $item = makePendingSyncItem('45896');

    $sage = Mockery::mock(SageIntacctApiService::class);
    $sage->shouldReceive('queryOtherReceiptsByRecordNos')->once()->andReturn([
        'success' => true,
        'receipts' => [[
            'recordno' => '45896',
            'deposited' => true,
            // Entry date was 02-17; the REAL deposit slip date is 02-18.
            'depositdate' => '2026-02-18',
            'entrydepositdate' => '2026-02-17',
            'projectid' => 'F8000',
            'departmentid' => 'S16999',
            'state' => 'Deposited',
        ]],
    ]);

    $dp = Mockery::mock(DonorPerfectApiService::class);
    $dp->shouldReceive('updateGiftUdf')
        ->with(203734, 'DEPOSIT_DATE', '2026-02-18')
        ->once()
        ->andReturn(['success' => true]);
    $dp->shouldReceive('updateGiftUdf')->andReturn(['success' => true]); // fund/service codes

    $result = makeDepositSyncService($sage, $dp)->sync();

    expect($result['dp_updated'])->toBe(1);
    expect($result['skipped_not_deposited'])->toBe(0);

    $fresh = $item->fresh();
    expect($fresh->deposit_date_synced_at)->not->toBeNull();
    expect($fresh->sage_deposit_date->format('Y-m-d'))->toBe('2026-02-18');
});
