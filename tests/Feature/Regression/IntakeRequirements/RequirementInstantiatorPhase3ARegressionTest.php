<?php

/*
|----------------------------------------------------------------------
| P-39b: Phase 3A behavior-preservation tests for the one-line filter
| added to RequirementInstantiator::forFundCode().
|
| Without these tests, the project-phase requirement flow that Kaitlyn
| depends on in production could regress silently behind 39b's
| migration. With them, any future change to the phase filter must
| either preserve project-phase behavior or update these assertions
| explicitly.
|----------------------------------------------------------------------
*/

use App\Models\FundCode;
use App\Models\FundCodeRequirement;
use App\Models\HousingJob;
use App\Models\Requirement;
use App\Services\RequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->actor = RegressionHelpers::adminUser();
    $this->service = app(RequirementInstantiator::class);
});

it('forFundCode_returns_only_project_phase_templates', function () {
    $job = HousingJob::factory()->create();
    $fc = FundCode::factory()->create();

    $proj = FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Project doc', 'type' => 'document', 'phase' => 'project',
    ]);
    FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Eligibility doc', 'type' => 'document', 'phase' => 'eligibility',
    ]);

    $created = $this->service->forFundCode($job, $fc, $this->actor);

    expect($created)->toHaveCount(1)
        ->and($created->first()->source_template_id)->toBe($proj->id);

    $projectTemplateCount = FundCodeRequirement::where('fund_code_id', $fc->id)
        ->where('phase', 'project')
        ->count();
    expect($created->count())->toBe($projectTemplateCount);
})->group('regression');

it('forFundCode_excludes_eligibility_phase_templates', function () {
    $job = HousingJob::factory()->create();
    $fc = FundCode::factory()->create();

    $elig = FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Eligibility-only', 'type' => 'document', 'phase' => 'eligibility',
    ]);
    FundCodeRequirement::create([
        'fund_code_id' => $fc->id, 'label' => 'Project-only', 'type' => 'document', 'phase' => 'project',
    ]);

    $created = $this->service->forFundCode($job, $fc, $this->actor);

    $createdTemplateIds = $created->pluck('source_template_id')->all();
    expect($createdTemplateIds)->not->toContain($elig->id);

    expect(Requirement::where('housing_job_id', $job->id)
        ->where('source_template_id', $elig->id)
        ->exists())->toBeFalse();
})->group('regression');

it('forFundCode_unchanged_when_all_templates_are_project_phase', function () {
    // Mirrors the legacy fleet state: every existing FundCodeRequirement
    // was authored before the phase split and ended up as 'project'
    // after the backfill migration. Behavior here must match the
    // pre-migration baseline: every template instantiates as a
    // Requirement, in order, with copy-on-instantiate labels.
    $job = HousingJob::factory()->create();
    $fc = FundCode::factory()->create();

    $templates = collect(['A', 'B', 'C'])->map(fn ($label, $i) => FundCodeRequirement::create([
        'fund_code_id' => $fc->id,
        'label' => 'Project '.$label,
        'type' => 'document',
        'phase' => 'project',
        'sort_order' => $i,
    ]));

    $created = $this->service->forFundCode($job, $fc, $this->actor);

    expect($created)->toHaveCount($templates->count());

    foreach ($templates as $t) {
        $row = $created->firstWhere('source_template_id', $t->id);
        expect($row)->not->toBeNull()
            ->and($row->source_label_snapshot)->toBe($t->label);
    }
})->group('regression');
