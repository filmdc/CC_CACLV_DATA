<?php

use App\Livewire\FundCodeManagement;
use App\Models\FundCode;
use App\Models\FundCodeRequirement;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('adds a requirement to a fund code via the management UI (P-06)', function () {
    $code = FundCode::factory()->create(['code' => 'REQ-ADD-001']);

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('manageRequirements', $code->id)
        ->call('openRequirementForm')
        ->set('requirement_label', 'Environmental review required')
        ->set('requirement_description', 'EPA Phase 1 study before construction milestone.')
        ->set('requirement_is_hard', true)
        ->call('saveRequirement')
        ->assertHasNoErrors();

    $req = $code->refresh()->requirements()->firstOrFail();
    expect($req->label)->toBe('Environmental review required');
    expect($req->description)->toBe('EPA Phase 1 study before construction milestone.');
    expect($req->is_hard_requirement)->toBeTrue();
    expect($req->sort_order)->toBeGreaterThan(0);
})->group('regression');

it('edits an existing requirement and persists the change (P-06)', function () {
    $code = FundCode::factory()->create();
    $req = FundCodeRequirement::factory()->create([
        'fund_code_id' => $code->id,
        'label' => 'Initial label',
        'is_hard_requirement' => false,
    ]);

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('manageRequirements', $code->id)
        ->call('editRequirement', $req->id)
        ->set('requirement_label', 'Edited label')
        ->set('requirement_is_hard', true)
        ->call('saveRequirement')
        ->assertHasNoErrors();

    $req->refresh();
    expect($req->label)->toBe('Edited label');
    expect($req->is_hard_requirement)->toBeTrue();
})->group('regression');

it('deletes a requirement via the management UI (P-06)', function () {
    $code = FundCode::factory()->create();
    $req = FundCodeRequirement::factory()->create(['fund_code_id' => $code->id]);

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('manageRequirements', $code->id)
        ->call('deleteRequirement', $req->id);

    expect(FundCodeRequirement::find($req->id))->toBeNull();
})->group('regression');

it('cascade-deletes requirements when the fund code itself is deleted (P-06)', function () {
    $code = FundCode::factory()->create();
    $r1 = FundCodeRequirement::factory()->create(['fund_code_id' => $code->id]);
    $r2 = FundCodeRequirement::factory()->create(['fund_code_id' => $code->id]);

    expect(FundCodeRequirement::whereIn('id', [$r1->id, $r2->id])->count())->toBe(2);
    $code->delete();
    expect(FundCodeRequirement::whereIn('id', [$r1->id, $r2->id])->count())->toBe(0);
})->group('regression');

it('reorders requirements via moveRequirementUp / moveRequirementDown (P-06)', function () {
    $code = FundCode::factory()->create();
    $r1 = FundCodeRequirement::factory()->create([
        'fund_code_id' => $code->id, 'sort_order' => 1, 'label' => 'First',
    ]);
    $r2 = FundCodeRequirement::factory()->create([
        'fund_code_id' => $code->id, 'sort_order' => 2, 'label' => 'Second',
    ]);

    // Move r1 down past r2.
    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('manageRequirements', $code->id)
        ->call('moveRequirementDown', $r1->id);

    $r1->refresh();
    $r2->refresh();
    expect($r1->sort_order)->toBeGreaterThan($r2->sort_order);

    // Move r1 back up.
    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('manageRequirements', $code->id)
        ->call('moveRequirementUp', $r1->id);

    $r1->refresh();
    $r2->refresh();
    expect($r1->sort_order)->toBeLessThan($r2->sort_order);
})->group('regression');

it('renders hard and informational requirements with distinct visual treatment (P-06)', function () {
    $code = FundCode::factory()->create(['code' => 'REQ-VIS-001']);
    FundCodeRequirement::factory()->hard()->create([
        'fund_code_id' => $code->id,
        'label' => 'Hard Req Label',
        'sort_order' => 1,
    ]);
    FundCodeRequirement::factory()->create([
        'fund_code_id' => $code->id,
        'label' => 'Soft Req Label',
        'sort_order' => 2,
    ]);

    $html = Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('manageRequirements', $code->id)
        ->html();

    expect($html)->toContain('Hard Req Label');
    expect($html)->toContain('Soft Req Label');
    // Distinct badges — text content + class hints differ.
    expect($html)->toContain('Required');
    expect($html)->toContain('Informational');
    // The Required badge uses the red palette; Informational uses blue.
    expect($html)->toMatch('/Required<\/span>|bg-red-100[^"]*">\s*Required/');
})->group('regression');

it('does not allow saving a requirement with an empty label (P-06)', function () {
    $code = FundCode::factory()->create();

    Livewire::actingAs($this->user)
        ->test(FundCodeManagement::class)
        ->call('manageRequirements', $code->id)
        ->call('openRequirementForm')
        ->set('requirement_label', '')
        ->call('saveRequirement')
        ->assertHasErrors(['requirement_label']);
})->group('regression');
