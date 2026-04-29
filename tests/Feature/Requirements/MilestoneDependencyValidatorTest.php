<?php

use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Services\MilestoneDependencyValidator;
use Database\Seeders\RegressionTestSeeder;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->validator = new MilestoneDependencyValidator;
});

it('rejects a self-reference', function () {
    $job = HousingJob::factory()->create();
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $job->id]);

    expect(fn () => $this->validator->validate($a, [$a->id]))
        ->toThrow(\DomainException::class);
});

it('rejects a direct two-cycle: existing A→B, propose B→A', function () {
    $job = HousingJob::factory()->create();
    [$a, $b] = ProjectMilestone::factory()->count(2)->create(['housing_job_id' => $job->id])->all();

    // Existing edge: A depends on B.
    \DB::table('milestone_dependencies')->insert([
        'milestone_id' => $a->id,
        'depends_on_milestone_id' => $b->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Propose: B depends on A → would create cycle.
    expect(fn () => $this->validator->validate($b, [$a->id]))
        ->toThrow(\DomainException::class);
});

it('rejects a transitive three-cycle: A→B, B→C, propose C→A', function () {
    $job = HousingJob::factory()->create();
    [$a, $b, $c] = ProjectMilestone::factory()->count(3)->create(['housing_job_id' => $job->id])->all();

    \DB::table('milestone_dependencies')->insert([
        ['milestone_id' => $a->id, 'depends_on_milestone_id' => $b->id, 'created_at' => now(), 'updated_at' => now()],
        ['milestone_id' => $b->id, 'depends_on_milestone_id' => $c->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    expect(fn () => $this->validator->validate($c, [$a->id]))
        ->toThrow(\DomainException::class);
});

it('rejects a cross-project edge', function () {
    $jobA = HousingJob::factory()->create();
    $jobB = HousingJob::factory()->create();
    $a = ProjectMilestone::factory()->create(['housing_job_id' => $jobA->id]);
    $bOnOtherProject = ProjectMilestone::factory()->create(['housing_job_id' => $jobB->id]);

    expect(fn () => $this->validator->validate($a, [$bOnOtherProject->id]))
        ->toThrow(\DomainException::class);
});

it('allows a diamond DAG: A→B, A→C, B→D, C→D — multiple paths, no cycle', function () {
    $job = HousingJob::factory()->create();
    [$a, $b, $c, $d] = ProjectMilestone::factory()->count(4)->create(['housing_job_id' => $job->id])->all();

    // Set existing edges step by step using validator (verifies each
    // doesn't throw); use direct inserts for setup though.
    \DB::table('milestone_dependencies')->insert([
        ['milestone_id' => $a->id, 'depends_on_milestone_id' => $b->id, 'created_at' => now(), 'updated_at' => now()],
        ['milestone_id' => $a->id, 'depends_on_milestone_id' => $c->id, 'created_at' => now(), 'updated_at' => now()],
        ['milestone_id' => $b->id, 'depends_on_milestone_id' => $d->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Propose: C depends on D — completes the diamond.
    $this->validator->validate($c, [$d->id]);

    expect(true)->toBeTrue(); // no exception
});

it('allows a wholesale-replace edge case: A currently depends on B; editing A to depend on C is fine even if B previously depended on A', function () {
    $job = HousingJob::factory()->create();
    [$a, $b, $c] = ProjectMilestone::factory()->count(3)->create(['housing_job_id' => $job->id])->all();

    // Existing graph has A→B and B→A. (For test purposes only — the
    // validator wouldn't have allowed building this graph in the first
    // place via its own API. We're modelling stale state here.) Then
    // we propose to REPLACE A's edges with [C], removing A→B in the
    // process. The validator must compute against A's edges being
    // removed, so the otherwise-cyclic B→A is no longer reachable.
    \DB::table('milestone_dependencies')->insert([
        ['milestone_id' => $a->id, 'depends_on_milestone_id' => $b->id, 'created_at' => now(), 'updated_at' => now()],
        ['milestone_id' => $b->id, 'depends_on_milestone_id' => $a->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Replace A's deps with [C]. The validator excludes A's outbound
    // edges from the graph during the cycle check, so B→A is not
    // reachable from C. No cycle.
    $this->validator->validate($a, [$c->id]);
    expect(true)->toBeTrue();
});

it('rejects only one of multiple proposed edges if just that one closes a cycle', function () {
    $job = HousingJob::factory()->create();
    [$a, $b, $c] = ProjectMilestone::factory()->count(3)->create(['housing_job_id' => $job->id])->all();

    // Existing: B→A.
    \DB::table('milestone_dependencies')->insert([
        ['milestone_id' => $b->id, 'depends_on_milestone_id' => $a->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Propose A depends on [C, B]. C is fine; B→A→B closes a cycle.
    expect(fn () => $this->validator->validate($a, [$c->id, $b->id]))
        ->toThrow(\DomainException::class);
});
