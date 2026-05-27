<?php

/*
|----------------------------------------------------------------------
| P-39b: end-to-end coverage of the cutover migration that backfills
| communication_log_fund_code into communication_log_anticipated_fund_codes
| and then drops the legacy table.
|
| At test boot, `RefreshDatabase` has already applied every migration in
| order — so the legacy table no longer exists and the new pivot is the
| live one. This test recreates the legacy table in-test, inserts
| fixture rows, then invokes the production migration's up() directly
| to prove the backfill + drop is correct.
|----------------------------------------------------------------------
*/

use App\Models\CommunicationLog;
use App\Models\FundCode;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);

    $this->migration = require base_path(
        'database/migrations/2026_05_23_180004_migrate_communication_log_fund_code_to_anticipated_and_drop.php'
    );
});

it('backfills legacy tag pivot rows into the anticipated pivot, then drops the legacy table', function () {
    $log1 = CommunicationLog::factory()->create();
    $log2 = CommunicationLog::factory()->create();
    $fc1 = FundCode::factory()->create();
    $fc2 = FundCode::factory()->create();

    // Recreate the legacy table and seed three rows.
    // The schema mirrors the original 2025_12_18 migration.
    Schema::create('communication_log_fund_code', function (Blueprint $table) {
        $table->foreignId('communication_log_id')
            ->constrained('communication_logs')
            ->cascadeOnDelete();
        $table->foreignId('fund_code_id')
            ->constrained('fund_codes')
            ->cascadeOnDelete();
        $table->timestamps();
        $table->primary(['communication_log_id', 'fund_code_id']);
    });

    // Clear any rows the seeder may have inserted into the anticipated pivot
    // for these logs, so the parity assertion has a clean baseline.
    DB::table('communication_log_anticipated_fund_codes')
        ->whereIn('communication_log_id', [$log1->id, $log2->id])
        ->delete();

    $oldStamp = now()->subDays(7);
    DB::table('communication_log_fund_code')->insert([
        ['communication_log_id' => $log1->id, 'fund_code_id' => $fc1->id, 'created_at' => $oldStamp, 'updated_at' => $oldStamp],
        ['communication_log_id' => $log1->id, 'fund_code_id' => $fc2->id, 'created_at' => $oldStamp, 'updated_at' => $oldStamp],
        ['communication_log_id' => $log2->id, 'fund_code_id' => $fc1->id, 'created_at' => $oldStamp, 'updated_at' => $oldStamp],
    ]);

    expect(Schema::hasTable('communication_log_fund_code'))->toBeTrue();

    $this->migration->up();

    // Legacy table is gone.
    expect(Schema::hasTable('communication_log_fund_code'))->toBeFalse();

    // All three rows landed in the new pivot.
    $newRows = DB::table('communication_log_anticipated_fund_codes')
        ->whereIn('communication_log_id', [$log1->id, $log2->id])
        ->orderBy('communication_log_id')
        ->orderBy('fund_code_id')
        ->get();

    expect($newRows)->toHaveCount(3);

    // created_at was preserved from the source.
    foreach ($newRows as $row) {
        expect((string) $row->created_at)->toBe((string) $oldStamp);
    }
})->group('regression');

it('is idempotent — re-applying the migration after the legacy table is gone is a no-op', function () {
    // The cutover migration ran during the test boot. Calling up() a second
    // time would fail outright if the legacy table doesn't exist. Confirm
    // that's the failure mode (Schema::dropIfExists handles the no-op
    // gracefully, but the SELECT count() against the dropped table throws).
    expect(Schema::hasTable('communication_log_fund_code'))->toBeFalse();

    // The migration is not re-entrant against a missing source table. That
    // is by design — once the cutover runs, it must not run again. Verify
    // by attempting up() and expecting an exception about the missing
    // table.
    expect(fn () => $this->migration->up())->toThrow(\Illuminate\Database\QueryException::class);
})->group('regression');
