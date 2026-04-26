<?php

use App\Livewire\ProjectManagement;
use App\Models\FundCode;
use App\Models\FundCodeRequirement;
use App\Models\HousingJob;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;
use Tests\Support\SnapshotHelper;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

/**
 * Helper — slice the panel section out of the full view HTML using the
 * snapshot-region marker comments embedded in the partial. Returns just
 * the wrapper-and-content so the snapshot is stable against unrelated
 * changes to the rest of project-management.blade.php.
 */
function extractPanelRegion(string $html): string
{
    $start = strpos($html, '<!-- snapshot-region: fund-code-requirements-panel-start -->');
    $end = strpos($html, '<!-- snapshot-region: fund-code-requirements-panel-end -->');
    if ($start === false || $end === false) {
        return '';
    }

    return substr($html, $start, $end - $start);
}

it('renders the panel when at least one attached code has requirements (P-07)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create(['code' => 'PANEL-001']);
    FundCodeRequirement::factory()->hard()->create([
        'fund_code_id' => $code->id,
        'label' => 'Hard requirement label',
        'sort_order' => 1,
    ]);
    FundCodeRequirement::factory()->create([
        'fund_code_id' => $code->id,
        'label' => 'Informational requirement label',
        'sort_order' => 2,
    ]);
    $job->fundCodes()->attach($code->id);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->html();

    $panel = extractPanelRegion($html);
    expect($panel)->toContain('Fund Code Requirements');
    expect($panel)->toContain('PANEL-001');
    expect($panel)->toContain('Hard requirement label');
    expect($panel)->toContain('Informational requirement label');
    expect($panel)->toContain('Required');
    expect($panel)->toContain('Informational');
})->group('regression');

it('does not render the panel when no attached code has requirements (P-07)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create(['code' => 'NOREQ-001']);
    $job->fundCodes()->attach($code->id);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->html();

    $panel = extractPanelRegion($html);
    // The wrapping markers exist but the conditional content is absent.
    expect($panel)->not->toContain('Fund Code Requirements');
})->group('regression');

it('groups requirements by fund code with the primary indicator on the primary code (P-07)', function () {
    $job = HousingJob::factory()->create();
    $primary = FundCode::factory()->create(['code' => 'PRIM-001']);
    $secondary = FundCode::factory()->create(['code' => 'SEC-001']);

    FundCodeRequirement::factory()->hard()->create([
        'fund_code_id' => $primary->id,
        'label' => 'Primary code hard req',
        'sort_order' => 1,
    ]);
    FundCodeRequirement::factory()->create([
        'fund_code_id' => $secondary->id,
        'label' => 'Secondary code soft req',
        'sort_order' => 1,
    ]);

    $job->fundCodes()->attach([$primary->id, $secondary->id]);
    $job->setPrimaryFundCode($primary);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->html();

    $panel = extractPanelRegion($html);

    // Both groups present.
    expect($panel)->toContain('PRIM-001');
    expect($panel)->toContain('SEC-001');

    // Primary indicator appears in the panel.
    expect($panel)->toContain('Primary');

    // The Primary indicator sits inside the PRIM-001 code group, not the
    // SEC-001 group. Carve out the substring between PRIM-001 and SEC-001
    // and confirm the badge lives there.
    $primStart = strpos($panel, 'PRIM-001');
    $secStart = strpos($panel, 'SEC-001');
    expect($primStart)->not->toBeFalse();
    expect($secStart)->not->toBeFalse();
    $primGroup = substr($panel, $primStart, $secStart - $primStart);
    expect($primGroup)->toContain('Primary');
})->group('regression');

it('updates reactively when a new fund code with requirements is attached (P-07)', function () {
    $job = HousingJob::factory()->create();
    $code = FundCode::factory()->create(['code' => 'REACT-001']);
    FundCodeRequirement::factory()->create([
        'fund_code_id' => $code->id,
        'label' => 'Reactive req',
        'sort_order' => 1,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id]);

    // Before: no panel content.
    $before = extractPanelRegion($component->html());
    expect($before)->not->toContain('Fund Code Requirements');

    // Attach via the assignment UI.
    $component->set('newFundCodeId', $code->id)->call('attachFundCode');

    // After: panel surfaces the requirement label.
    $after = extractPanelRegion($component->html());
    expect($after)->toContain('Fund Code Requirements');
    expect($after)->toContain('Reactive req');
})->group('regression');

it('snapshot baseline of the panel for a deterministic mixed-requirements fixture (P-07)', function () {
    $job = HousingJob::where('job_number', 'JOB-FIX-00001')->firstOrFail();

    // Build a deterministic fixture WITHIN this test so the baseline is
    // self-contained. Two fund codes — one already attached (the seeder
    // attaches FC-FIX-003 to HJ-1 with primary=FC-FIX-003 in commit 6),
    // the other added here. Each gets a hard + informational req.
    $fcStandalone = FundCode::where('code', 'FC-FIX-003')->firstOrFail();
    FundCodeRequirement::factory()->hard()->create([
        'fund_code_id' => $fcStandalone->id,
        'label' => 'Submit environmental review form before construction',
        'description' => 'EPA Phase 1 study required, hand-off via the project documents tab.',
        'sort_order' => 1,
    ]);
    FundCodeRequirement::factory()->create([
        'fund_code_id' => $fcStandalone->id,
        'label' => 'Notify program officer 30 days before completion',
        'sort_order' => 2,
    ]);

    $extra = FundCode::factory()->create([
        'code' => 'FC-PANEL-EXTRA',
        'description' => 'Extra fixture fund code for the panel snapshot',
    ]);
    FundCodeRequirement::factory()->hard()->create([
        'fund_code_id' => $extra->id,
        'label' => 'Quarterly draw report due to funder',
        'sort_order' => 1,
    ]);

    $job->fundCodes()->attach($extra->id);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->html();

    $panel = extractPanelRegion($html);
    SnapshotHelper::assertHtmlMatchesSnapshot('project_detail_requirements_panel', $panel);
})->group('regression');
