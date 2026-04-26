<?php

use App\Livewire\ProjectManagement;
use App\Models\CommunicationLog;
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

it('falls back to derived comm-log fund codes when budget has no contract (P-22)', function () {
    // Job with a budget but no contract; comm log brings two fund codes.
    $job = HousingJob::factory()->create();
    ProjectBudget::factory()->create([
        'housing_job_id' => $job->id,
        'contract_id' => null,
        'category' => 'Misc',
        'budgeted_amount' => 1000,
    ]);

    $codeA = FundCode::factory()->create(['code' => 'FC-PROM-A']);
    $codeB = FundCode::factory()->create(['code' => 'FC-PROM-B']);

    $log = CommunicationLog::factory()->create(['housing_job_id' => $job->id]);
    $log->fundCodes()->attach([$codeA->id, $codeB->id]);

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

it('renders an em-dash when budget has neither contract nor comm-log fund codes (P-22)', function () {
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

it('eager-loads budgets.contract so a freshly-saved budget shows its grant_code without refresh (P-22)', function () {
    // The pre-fix behavior: saveBudget() then refreshJob() did not load the
    // contract relation, so the badge cell was empty until the next page
    // load. After Phase 1 refreshJob() eager-loads budgets.contract.
    $contract = Contract::factory()->create([
        'title' => 'Eager Load Probe',
        'grant_code' => 'GRANT-EAGER-001',
    ]);
    $job = HousingJob::where('job_number', 'JOB-FIX-00001')->firstOrFail();

    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('activeTab', 'budget')
        ->call('showCreateBudget')
        ->set('budget_category', 'Eager-loaded category')
        ->set('budget_amount', 1234)
        ->set('budget_contract_id', $contract->id)
        ->call('saveBudget')
        ->assertHasNoErrors();

    $html = $component->html();
    expect($html)->toContain('GRANT-EAGER-001');
})->group('regression');
