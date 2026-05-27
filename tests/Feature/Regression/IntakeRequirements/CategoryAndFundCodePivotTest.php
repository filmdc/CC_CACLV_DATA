<?php

/*
| Phase 10.1: schema tripwires for the category column on
| requirement_definitions and the new fund_code_requirement_definitions
| M:M pivot.
*/

use App\Models\FundCode;
use App\Models\RequirementDefinition;
use Illuminate\Support\Facades\Schema;

it('requirement_definitions.category column exists with the 3 enum values', function () {
    expect(Schema::hasColumn('requirement_definitions', 'category'))->toBeTrue();

    foreach (['eligibility', 'compliance', 'service_delivery_compliance'] as $cat) {
        $atom = RequirementDefinition::factory()->create(['category' => $cat]);
        expect($atom->fresh()->category)->toBe($cat);
    }
})->group('regression');

it('fund_code_requirement_definitions pivot links fund codes to atoms', function () {
    expect(Schema::hasTable('fund_code_requirement_definitions'))->toBeTrue();

    $fund = FundCode::factory()->create();
    $atom = RequirementDefinition::factory()->create();

    $fund->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    expect($fund->fresh()->requirementDefinitions->pluck('id')->all())->toBe([$atom->id]);
    expect($atom->fresh()->fundCodes->pluck('id')->all())->toBe([$fund->id]);
})->group('regression');

it('category constants and PHASE_*_CATEGORIES are wired correctly', function () {
    expect(RequirementDefinition::PHASE_1_CATEGORIES)->toBe(['eligibility', 'compliance']);
    expect(RequirementDefinition::PHASE_2_CATEGORIES)->toBe(['service_delivery_compliance']);
    expect(RequirementDefinition::CATEGORIES)->toHaveCount(3);
})->group('regression');
