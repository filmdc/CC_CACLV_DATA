<?php

/**
 * Project Detail redesign rev 1 — fund-code requirements are now surfaced
 * as gating requirement rows under their owning milestone (via the
 * MilestoneDetail right pane, tagged "via fund code"). The legacy
 * standalone "Fund Code Requirements" panel partial was retired; this
 * test was rewritten to pin the new surfacing contract.
 *
 * The contract:
 *   - When a fund code with a project-phase RequirementDefinition is
 *     attached to a project, the corresponding Requirement row materialises
 *     on the project (per Phase 3A RequirementInstantiator behavior).
 *   - That Requirement is visible on the page in the Work tab, with a
 *     "via fund code" attribution near the label.
 */

use App\Livewire\ProjectManagement;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\Requirement;
use App\Models\RequirementDefinition;
use App\Services\RequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('a fund-code-attached RequirementDefinition surfaces as a gating requirement on the project', function () {
    $job = HousingJob::factory()->create(['project_manager_id' => $this->user->id]);

    $code = FundCode::factory()->create(['code' => 'PANEL-001']);
    $defs = RegressionHelpers::linkProjectRequirements($code, 1);

    // Attach the fund code AND instantiate its requirements onto the
    // project. The mock attachFundCode wire:click flow does this in
    // ProjectManagement, but here we set up via the service directly
    // so the test stays focused on the surfacing contract.
    $job->fundCodes()->attach($code->id);
    app(RequirementInstantiator::class)->forFundCode($job, $code);

    // For the requirement to render, the milestone it belongs to has to
    // be selected. We set selectedMilestone explicitly below — but
    // fund-code-source requirements show up under the milestone the
    // fund code is pinned to (or the first milestone if not pinned).
    // The simplest assertion: the requirement title appears anywhere
    // on the page (Work tab) when the milestone is selected.
    $milestone = $job->milestones()->create([
        'name' => 'Fund-Code Anchor',
        'sequence_order' => 1,
        'status' => 'in_progress',
    ]);
    $job->fundCodes()->updateExistingPivot($code->id, ['milestone_id' => $milestone->id]);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('selectedMilestone', $milestone->id)
        ->html();

    // The requirement title (from the atom's label) is on the page.
    expect($html)->toContain($defs->first()->label);
    // It's marked as fund-code-sourced.
    expect($html)->toContain('via fund code');
})->group('regression');

it('does not show fund-code requirements when no fund code is attached', function () {
    $job = HousingJob::factory()->create();

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->html();

    // Nothing claims fund-code attribution when there are no fund codes.
    expect($html)->not->toContain('via fund code');
})->group('regression');
