<?php

/*
|----------------------------------------------------------------------
| Phase-filter behavior-preservation tests for
| RequirementInstantiator::forFundCode().
|
| Comm Log v2: the project instantiator now draws from the unified
| Requirements Library (requirement_definitions, via the
| fund_code_requirement_definitions pivot) instead of the legacy
| fund_code_requirements table. It instantiates only PHASE_2 items
| (category=service_delivery_compliance); PHASE_1 items
| (eligibility / compliance) are intake-only and flow through
| IntakeRequirementInstantiator.
|
| Without these tests, the project-phase requirement flow that Kaitlyn
| depends on in production could regress silently. With them, any future
| change to the phase filter must either preserve project-phase behavior
| or update these assertions explicitly.
|----------------------------------------------------------------------
*/

use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\Requirement;
use App\Models\RequirementDefinition;
use App\Services\RequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->actor = RegressionHelpers::adminUser();
    $this->service = app(RequirementInstantiator::class);
});

/** Link a library item of the given category to a fund code. */
function linkAtom(FundCode $fc, string $label, string $category): RequirementDefinition
{
    $atom = RequirementDefinition::factory()->category($category)->kind('doc')->create(['label' => $label]);
    $fc->requirementDefinitions()->attach($atom->id, ['created_at' => now()]);

    return $atom;
}

it('forFundCode_returns_only_project_phase_atoms', function () {
    $job = HousingJob::factory()->create();
    $fc = FundCode::factory()->create();

    $proj = linkAtom($fc, 'Project doc', RequirementDefinition::CATEGORY_SERVICE_DELIVERY);
    linkAtom($fc, 'Eligibility doc', RequirementDefinition::CATEGORY_ELIGIBILITY);

    $created = $this->service->forFundCode($job, $fc, $this->actor);

    expect($created)->toHaveCount(1)
        ->and($created->first()->source_template_id)->toBe($proj->id);

    $projectAtomCount = $fc->requirementDefinitions()
        ->whereIn('category', RequirementDefinition::PHASE_2_CATEGORIES)
        ->count();
    expect($created->count())->toBe($projectAtomCount);
})->group('regression');

it('forFundCode_excludes_phase_1_atoms', function () {
    $job = HousingJob::factory()->create();
    $fc = FundCode::factory()->create();

    $elig = linkAtom($fc, 'Eligibility-only', RequirementDefinition::CATEGORY_ELIGIBILITY);
    linkAtom($fc, 'Project-only', RequirementDefinition::CATEGORY_SERVICE_DELIVERY);

    $created = $this->service->forFundCode($job, $fc, $this->actor);

    $createdTemplateIds = $created->pluck('source_template_id')->all();
    expect($createdTemplateIds)->not->toContain($elig->id);

    expect(Requirement::where('housing_job_id', $job->id)
        ->where('source_template_id', $elig->id)
        ->exists())->toBeFalse();
})->group('regression');

it('forFundCode_instantiates_every_project_phase_atom_in_order', function () {
    $job = HousingJob::factory()->create();
    $fc = FundCode::factory()->create();

    $atoms = collect(['A', 'B', 'C'])->map(fn ($label) => linkAtom(
        $fc, 'Project '.$label, RequirementDefinition::CATEGORY_SERVICE_DELIVERY
    ));

    $created = $this->service->forFundCode($job, $fc, $this->actor);

    expect($created)->toHaveCount($atoms->count());

    foreach ($atoms as $atom) {
        $row = $created->firstWhere('source_template_id', $atom->id);
        expect($row)->not->toBeNull()
            ->and($row->source_label_snapshot)->toBe($atom->label);
    }
})->group('regression');
