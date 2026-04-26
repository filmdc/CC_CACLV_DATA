<?php

use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\Tag;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\DB;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();

    // Pull the migration's instance for direct up() / down() invocation.
    // RefreshDatabase has already run the migration once on this test DB
    // before this beforeEach; tests that exercise up() are validating
    // re-entrant / idempotent behavior.
    $this->migration = require base_path(
        'database/migrations/2026_04_26_000005_migrate_communication_log_fund_code_tags_to_structured_attachments.php'
    );
});

it('attaches fund codes to comm logs whose tag matches an existing FundCode.code (P-27)', function () {
    $code = FundCode::factory()->create(['code' => 'P27-MATCH-001']);
    $log = CommunicationLog::factory()->create();
    $log->attachTagByName('P27-MATCH-001');

    // Sanity: not yet in the pivot.
    expect($log->fundCodes()->where('fund_codes.id', $code->id)->exists())->toBeFalse();

    $this->migration->up();

    expect($log->refresh()->fundCodes()->where('fund_codes.id', $code->id)->exists())->toBeTrue();
})->group('regression');

it('matches tag names case-insensitively (P-27)', function () {
    $code = FundCode::factory()->create(['code' => 'P27-CASE-001']);
    $log = CommunicationLog::factory()->create();
    // Lowercase tag, uppercase code.
    $log->attachTagByName('p27-case-001');

    $this->migration->up();

    expect($log->refresh()->fundCodes()->where('fund_codes.id', $code->id)->exists())->toBeTrue();
})->group('regression');

it('is idempotent — re-running up() does not duplicate the pivot row (P-27)', function () {
    $code = FundCode::factory()->create(['code' => 'P27-IDEMP-001']);
    $log = CommunicationLog::factory()->create();
    $log->attachTagByName('P27-IDEMP-001');

    $this->migration->up();
    $countAfterFirst = DB::table('communication_log_fund_code')
        ->where('communication_log_id', $log->id)
        ->where('fund_code_id', $code->id)
        ->count();

    $this->migration->up();
    $countAfterSecond = DB::table('communication_log_fund_code')
        ->where('communication_log_id', $log->id)
        ->where('fund_code_id', $code->id)
        ->count();

    expect($countAfterFirst)->toBe(1);
    expect($countAfterSecond)->toBe(1);
})->group('regression');

it('leaves the original tag rows untouched (P-27)', function () {
    $code = FundCode::factory()->create(['code' => 'P27-PRESERVE-001']);
    $log = CommunicationLog::factory()->create();
    $log->attachTagByName('P27-PRESERVE-001');

    $tagsBefore = Tag::count();
    $taggablesBefore = DB::table('taggables')->count();

    $this->migration->up();

    expect(Tag::count())->toBe($tagsBefore);
    expect(DB::table('taggables')->count())->toBe($taggablesBefore);
    expect($log->fresh()->tags->pluck('name')->all())->toContain('P27-PRESERVE-001');
})->group('regression');

it('down() detaches only the migration-created pivot rows, leaves tags alone (P-27)', function () {
    $code = FundCode::factory()->create(['code' => 'P27-REVERSE-001']);
    $log = CommunicationLog::factory()->create();
    $log->attachTagByName('P27-REVERSE-001');

    $this->migration->up();
    expect($log->fresh()->fundCodes()->where('fund_codes.id', $code->id)->exists())->toBeTrue();

    $this->migration->down();

    expect($log->fresh()->fundCodes()->where('fund_codes.id', $code->id)->exists())->toBeFalse();
    // Tag still exists, taggable still exists.
    expect(Tag::where('name', 'P27-REVERSE-001')->exists())->toBeTrue();
    expect($log->fresh()->tags->pluck('name')->all())->toContain('P27-REVERSE-001');
})->group('regression');

it('down() does not remove pivot rows that pre-dated this migration (P-27)', function () {
    // A pivot row that's there for a different reason (not derived from a
    // tag) must survive down().
    $code = FundCode::factory()->create(['code' => 'P27-PREDATED-001']);
    $log = CommunicationLog::factory()->create();
    $log->fundCodes()->attach($code->id);
    // Note: NO tag is attached, so down() should not touch this pivot row.

    expect($log->fresh()->fundCodes()->where('fund_codes.id', $code->id)->exists())->toBeTrue();

    $this->migration->down();

    expect($log->fresh()->fundCodes()->where('fund_codes.id', $code->id)->exists())->toBeTrue();
})->group('regression');

it('skips tags that do not match any fund code (P-27)', function () {
    FundCode::factory()->create(['code' => 'P27-EXISTS-001']);
    $log = CommunicationLog::factory()->create();
    $log->attachTagByName('NOT-A-FUND-CODE-AT-ALL');
    $log->attachTagByName('also-random');

    $countBefore = DB::table('communication_log_fund_code')
        ->where('communication_log_id', $log->id)
        ->count();

    $this->migration->up();

    $countAfter = DB::table('communication_log_fund_code')
        ->where('communication_log_id', $log->id)
        ->count();

    expect($countAfter)->toBe($countBefore);
})->group('regression');
