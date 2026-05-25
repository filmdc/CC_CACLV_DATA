<?php

/*
| Phase 9 schema tripwires for the HousingService catalog and its
| four pivots (fund codes, template sets, definitions, comm logs).
*/

use App\Models\CommunicationLog;
use App\Models\FundCode;
use App\Models\HousingService;
use App\Models\RequirementDefinition;
use App\Models\RequirementTemplateSet;
use Illuminate\Support\Facades\Schema;

it('housing_services + 4 pivots exist with expected columns', function () {
    expect(Schema::hasTable('housing_services'))->toBeTrue();
    expect(Schema::hasColumns('housing_services', [
        'id', 'name', 'description', 'is_active',
        'created_by_user_id', 'updated_by_user_id',
        'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();

    foreach ([
        'housing_service_fund_codes' => ['housing_service_id', 'fund_code_id'],
        'housing_service_template_sets' => ['housing_service_id', 'template_set_id'],
        'housing_service_definitions' => ['housing_service_id', 'requirement_definition_id'],
        'communication_log_housing_services' => ['communication_log_id', 'housing_service_id'],
    ] as $tbl => $cols) {
        expect(Schema::hasTable($tbl))->toBeTrue();
        expect(Schema::hasColumns($tbl, $cols))->toBeTrue();
    }
})->group('regression');

it('HousingService ↔ FundCode many-to-many works', function () {
    $svc = HousingService::factory()->create();
    $fund = FundCode::factory()->create();

    $svc->fundCodes()->attach($fund->id, ['created_at' => now()]);

    expect($svc->fresh()->fundCodes->pluck('id')->all())->toContain($fund->id);
})->group('regression');

it('HousingService ↔ RequirementTemplateSet many-to-many works', function () {
    $svc = HousingService::factory()->create();
    $bundle = RequirementTemplateSet::factory()->create();

    $svc->templateSets()->attach($bundle->id, ['created_at' => now()]);

    expect($svc->fresh()->templateSets->pluck('id')->all())->toContain($bundle->id);
})->group('regression');

it('HousingService ↔ RequirementDefinition many-to-many works', function () {
    $svc = HousingService::factory()->create();
    $atom = RequirementDefinition::factory()->create();

    $svc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    expect($svc->fresh()->requirementDefinitions->pluck('id')->all())->toContain($atom->id);
})->group('regression');

it('CommunicationLog ↔ HousingService many-to-many works (inverse relation)', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();

    $log->housingServices()->attach($svc->id, ['created_at' => now()]);

    expect($log->fresh()->housingServices->pluck('id')->all())->toContain($svc->id);
})->group('regression');

it('HousingService soft-deletes preserve historical comm-log references', function () {
    $log = CommunicationLog::factory()->create();
    $svc = HousingService::factory()->create();
    $log->housingServices()->attach($svc->id, ['created_at' => now()]);

    $svc->delete();

    // Historical pivot row stays (cascade is on housing_services hard-delete,
    // but SoftDeletes prevents the hard delete here).
    expect($log->fresh()->housingServices()->withTrashed()->count())->toBe(1);
})->group('regression');
