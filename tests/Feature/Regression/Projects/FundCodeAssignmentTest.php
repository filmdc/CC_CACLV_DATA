<?php

use App\Livewire\ProjectManagement;
use App\Models\FundCode;
use App\Models\HousingJob;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('attachFundCode adds a fund code to the project pivot (P-08)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create(['code' => 'ATTACH-001']);

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('newFundCodeId', $code->id)
        ->call('attachFundCode')
        ->assertHasNoErrors();

    expect($job->fundCodes()->count())->toBe(1);
    expect($job->fundCodes()->first()->id)->toBe($code->id);
})->group('regression');

it('attachFundCode is idempotent — repeat attach does not double the row (P-08)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create();

    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id]);

    $component->set('newFundCodeId', $code->id)->call('attachFundCode');
    $component->set('newFundCodeId', $code->id)->call('attachFundCode');

    expect($job->fundCodes()->count())->toBe(1);
})->group('regression');

it('detachFundCode removes the pivot row (P-08)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create();
    $job->fundCodes()->attach($code->id);

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('detachFundCode', $code->id);

    expect($job->fundCodes()->count())->toBe(0);
})->group('regression');

it('setPrimaryFundCode marks primary_fund_code_id and ensures pivot membership (P-08)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create();

    Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->call('setPrimaryFundCode', $code->id);

    $job->refresh();
    expect($job->primary_fund_code_id)->toBe($code->id);
    expect($job->fundCodes()->where('fund_codes.id', $code->id)->exists())->toBeTrue();
})->group('regression');

it('switching primary updates primary_fund_code_id and leaves both pivot rows in place (P-08)', function () {
    $job = HousingJob::factory()->create();
    $codeA = FundCode::factory()->create(['code' => 'PRIM-A']);
    $codeB = FundCode::factory()->create(['code' => 'PRIM-B']);

    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id]);

    $component->call('setPrimaryFundCode', $codeA->id);
    $job->refresh();
    expect($job->primary_fund_code_id)->toBe($codeA->id);

    $component->call('setPrimaryFundCode', $codeB->id);
    $job->refresh();
    expect($job->primary_fund_code_id)->toBe($codeB->id);
    expect($job->fundCodes()->count())->toBe(2);
})->group('regression');

it('detaching the primary code auto-clears primary_fund_code_id (P-08)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create();

    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id]);

    $component->call('setPrimaryFundCode', $code->id);
    $job->refresh();
    expect($job->primary_fund_code_id)->toBe($code->id);

    $component->call('detachFundCode', $code->id);
    $job->refresh();
    expect($job->primary_fund_code_id)->toBeNull();
    expect($job->fundCodes()->count())->toBe(0);
})->group('regression');

it('detaching a non-primary code leaves primary_fund_code_id alone (P-08)', function () {
    $job = HousingJob::factory()->create();
    $codeA = FundCode::factory()->create();
    $codeB = FundCode::factory()->create();

    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id]);

    $component->call('setPrimaryFundCode', $codeA->id);
    $component->set('newFundCodeId', $codeB->id)->call('attachFundCode');

    $component->call('detachFundCode', $codeB->id);

    $job->refresh();
    expect($job->primary_fund_code_id)->toBe($codeA->id);
    expect($job->fundCodes()->count())->toBe(1);
})->group('regression');

it('renders attached fund codes with primary indicator on the project view (P-08)', function () {
    // The seeder attaches FC-FIX-003 to HJ-1 with primary=FC-FIX-003
    // (via $hj1->setPrimaryFundCode in the seeder). The view must surface
    // both the code AND the primary indicator.
    $hj1 = HousingJob::where('job_number', 'JOB-FIX-00001')->firstOrFail();

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $hj1->id])
        ->html();

    expect($html)->toContain('FC-FIX-003');
    expect($html)->toContain('Primary');
})->group('regression');

it('availableFundCodes excludes already-attached codes from the dropdown (P-08)', function () {
    $job = HousingJob::factory()->create();
    // Codes named so neither contains the other as a substring — otherwise
    // the option-tag regex below would false-positive.
    $attached = FundCode::factory()->create(['code' => 'IN-PIVOT-Q']);
    $unattached = FundCode::factory()->create(['code' => 'OUT-PIVOT-Q']);
    $job->fundCodes()->attach($attached->id);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->html();

    // The unattached code is offered in the picker (option in the select).
    expect($html)->toContain('OUT-PIVOT-Q');
    // The attached code is not duplicated in the picker — it appears only
    // as one of the assigned-codes badges. Confirm no <option> tag holds
    // the attached code.
    expect($html)->not->toMatch('/<option[^>]*>[^<]*IN-PIVOT-Q/');
    // And the attached code IS visible in the assigned-codes section
    // (rendered inside <span> badges, not inside an <option>).
    expect($html)->toContain('IN-PIVOT-Q');
})->group('regression');
