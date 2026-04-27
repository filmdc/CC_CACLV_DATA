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
