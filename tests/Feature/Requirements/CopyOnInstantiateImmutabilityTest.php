<?php

use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\Requirement;
use App\Models\RequirementDefinition;
use App\Models\User;
use App\Services\RequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

it('snapshots the template label at instantiation; later edits to the upstream template do not propagate', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $code = FundCode::factory()->create();
    $template = RequirementDefinition::factory()->category('service_delivery_compliance')->create([
        'label' => 'Original label',
        'hint' => 'Original description',
    ]);
    $code->requirementDefinitions()->attach($template->id, ['created_at' => now()]);

    app(RequirementInstantiator::class)->forFundCode($job, $code, $manager);

    $req = Requirement::where('source_template_id', $template->id)->firstOrFail();
    expect($req->title)->toBe('Original label');
    expect($req->source_label_snapshot)->toBe('Original label');
    expect($req->description)->toBe('Original description');

    $template->update(['label' => 'EDITED label', 'hint' => 'EDITED description']);

    $req->refresh();
    expect($req->title)->toBe('Original label');
    expect($req->source_label_snapshot)->toBe('Original label');
    expect($req->description)->toBe('Original description');
});

it('also preserves the original type on the requirement when the upstream template type changes', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create();
    $template = RequirementDefinition::factory()->category('service_delivery_compliance')->kind('verify')->create();
    $code->requirementDefinitions()->attach($template->id, ['created_at' => now()]);

    app(RequirementInstantiator::class)->forFundCode($job, $code, null);

    $req = Requirement::where('source_template_id', $template->id)->firstOrFail();
    expect($req->type)->toBe('task');

    $template->update(['kind' => 'doc']);
    $req->refresh();
    expect($req->type)->toBe('task');
});
