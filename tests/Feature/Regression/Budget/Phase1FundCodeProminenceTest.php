<?php

use App\Livewire\ProjectManagement;
use App\Models\Contract;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\ProjectBudget;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('renders the contract grant_code as a prominent badge when present (P-22)', function () {
    $contract = Contract::factory()->create([
        'title' => 'Roofing Subcontract',
        'grant_code' => 'GRANT-PROM-001',
    ]);
    $job = HousingJob::factory()->create();
    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'contract_id' => $contract->id,
        'category' => 'Roofing',
        'budgeted_amount' => 5000,
    ]);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('activeTab', 'budget')
        ->html();

    expect($html)->toContain('GRANT-PROM-001');
    // Prominence — the badge uses larger text + bolder weight than the
    // pre-Phase-1 markup (which was text-xs font-medium).
    expect($html)->toContain('text-sm font-semibold');
})->group('regression');

it('falls back to project-assigned fund codes when budget has no contract (P-22 / P-08)', function () {
    // Job with a budget but no contract; project has two fund codes
    // attached directly via the fund_code_housing_job pivot (Phase 2 / P-08
    // representation — replaces the Phase 1 comm-log-derived fallback).
    $job = HousingJob::factory()->create();
    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'contract_id' => null,
        'category' => 'Misc',
        'budgeted_amount' => 1000,
    ]);

    $codeA = FundCode::factory()->create(['code' => 'FC-PROM-A']);
    $codeB = FundCode::factory()->create(['code' => 'FC-PROM-B']);
    $job->fundCodes()->attach([$codeA->id, $codeB->id]);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('activeTab', 'budget')
        ->html();

    expect($html)->toContain('FC-PROM-A');
    expect($html)->toContain('FC-PROM-B');
    // No provenance label — codes render flat, as the project's fund codes.
    expect($html)->not->toContain('from comm log');
    expect($html)->not->toContain('(from comm logs)');
})->group('regression');

it('renders an em-dash when budget has neither contract nor project fund codes (P-22)', function () {
    $job = HousingJob::factory()->create();
    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'contract_id' => null,
        'category' => 'Standalone',
        'budgeted_amount' => 100,
    ]);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('activeTab', 'budget')
        ->html();

    // Budget row should show em-dash, not "-" (the older placeholder).
    // We can't grep for "—" alone (would clash with optional UI labels),
    // so look for it inside the budget table tbody specifically.
    preg_match('#<tbody[^>]*>(.*?)</tbody>#s', $html, $matches);
    $tbody = $matches[1] ?? '';
    expect($tbody)->toContain('—');
})->group('regression');

it('eager-loads budgets.fundCode so a freshly-saved budget shows its code without refresh (P-22 / P-2.5)', function () {
    // Phase 2.5 swapped the form's Contract picker for a Fund Code picker.
    // The eager-load assertion that lived here for Phase 1 P-22 still
    // matters in spirit: refreshJob() must hydrate the relation backing
    // the row's Fund Code cell so the cell renders the just-saved code
    // without the user needing to manually refresh the page.
    $job = HousingJob::where('job_number', 'JOB-FIX-00001')->firstOrFail();
    $code = FundCode::factory()->create(['code' => 'FC-EAGER-001']);
    $job->fundCodes()->attach($code->id);

    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('activeTab', 'budget')
        ->call('showCreateBudget')
        ->set('budget_category', 'Eager-loaded category')
        ->set('budget_amount', 1234)
        ->set('budget_fund_code_id', $code->id)
        ->call('saveBudget')
        ->assertHasNoErrors();

    $html = $component->html();
    expect($html)->toContain('FC-EAGER-001');
})->group('regression');

it('preserves contract grant_code rendering for legacy contract-bearing rows (P-22 / P-2.5 fallback)', function () {
    // The form no longer offers a Contract picker, but contract-bearing
    // rows that pre-date Phase 2.5 still render their grant_code via the
    // row cell's level-2 fallback. Programmatic creation here mirrors what
    // a legacy row looks like on disk.
    $contract = Contract::factory()->create([
        'title' => 'Legacy Contract Probe',
        'grant_code' => 'GRANT-LEGACY-001',
    ]);
    $job = HousingJob::factory()->create();
    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'contract_id' => $contract->id,
        'fund_code_id' => null,
        'category' => 'Legacy Category',
        'budgeted_amount' => 5000,
    ]);

    $html = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('activeTab', 'budget')
        ->html();

    expect($html)->toContain('GRANT-LEGACY-001');
})->group('regression');
