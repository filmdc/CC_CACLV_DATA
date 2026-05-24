<?php

/*
|----------------------------------------------------------------------
| P-39b: schema-level invariants for intake-phase tables.
|
| Cascade-delete behavior, unique constraint on the anticipated pivot,
| template-snapshot survives template deletion (no FK on
| source_template_id), legacy tag pivot is gone.
|----------------------------------------------------------------------
*/

use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\FundCodeRequirement;
use App\Models\IntakeRequirement;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

it('cascade-deletes intake_requirements when the parent CommunicationLog is deleted', function () {
    $log = CommunicationLog::factory()->create();
    $req = IntakeRequirement::factory()->create(['communication_log_id' => $log->id]);

    $log->delete();

    expect(IntakeRequirement::find($req->id))->toBeNull();
})->group('regression');

it('cascade-deletes anticipated-fund-codes pivot rows when the parent CommunicationLog is deleted', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();
    $log->anticipatedFundCodes()->attach($fc->id, ['created_at' => now()]);

    expect(DB::table('communication_log_anticipated_fund_codes')->where('communication_log_id', $log->id)->count())->toBe(1);

    $log->delete();

    expect(DB::table('communication_log_anticipated_fund_codes')->where('communication_log_id', $log->id)->count())->toBe(0);
})->group('regression');

it('intake_requirements.source_template_id has no FK — snapshot survives template deletion', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();
    $template = FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Snapshot survival', 'type' => 'document', 'phase' => 'eligibility',
    ]);

    $req = IntakeRequirement::factory()->fundCode($template->id, $template->label)->create([
        'communication_log_id' => $log->id,
    ]);

    $template->delete();

    $fresh = $req->fresh();
    expect($fresh)->not->toBeNull()
        ->and($fresh->source_label_snapshot)->toBe('Snapshot survival')
        ->and($fresh->source_template_id)->toBe($template->id);
})->group('regression');

it('unique constraint on (communication_log_id, fund_code_id) rejects duplicate anticipates', function () {
    $log = CommunicationLog::factory()->create();
    $fc = FundCode::factory()->create();

    DB::table('communication_log_anticipated_fund_codes')->insert([
        'communication_log_id' => $log->id, 'fund_code_id' => $fc->id, 'created_at' => now(),
    ]);

    expect(fn () => DB::table('communication_log_anticipated_fund_codes')->insert([
        'communication_log_id' => $log->id, 'fund_code_id' => $fc->id, 'created_at' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
})->group('regression');

it('the legacy communication_log_fund_code table no longer exists', function () {
    expect(Schema::hasTable('communication_log_fund_code'))->toBeFalse();
})->group('regression');

it('fund_code_requirements.phase column exists with default project', function () {
    expect(Schema::hasColumn('fund_code_requirements', 'phase'))->toBeTrue();

    // Existing rows should all be 'project' (backfill at migration time).
    $nonProjectCount = DB::table('fund_code_requirements')->where('phase', '!=', 'project')->count();
    expect($nonProjectCount)->toBe(0);
})->group('regression');
