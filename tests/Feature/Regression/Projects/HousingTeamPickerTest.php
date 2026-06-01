<?php

/**
 * The owner / assigned-to picker on the Project Detail page is gated by
 * the explicit `users.is_housing_team` flag ("Community Action Homes
 * designation"). Replaces the brittle pre-2026-06-01 query that did
 *   Program::where('name', 'LIKE', '%CAH%')->first()
 * and fell to empty whenever the program was renamed.
 *
 * Contract:
 *   - User with is_housing_team=true AND active=true → in picker.
 *   - User with is_housing_team=false → NOT in picker (even if admin).
 *   - User with is_housing_team=true AND active=false → NOT in picker.
 */

use App\Livewire\ProjectManagement;
use App\Models\HousingJob;
use App\Models\User;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->user = RegressionHelpers::adminUser();
});

it('shows users flagged is_housing_team in the project owner picker', function () {
    $job = HousingJob::factory()->create();

    $included = User::factory()->create([
        'name' => 'Picker Included User',
        'active' => true,
        'is_housing_team' => true,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id]);

    $users = $component->viewData('users');
    expect($users->pluck('id')->all())->toContain($included->id);
})->group('regression');

it('excludes users without the housing-team flag — even admins', function () {
    $job = HousingJob::factory()->create();

    // Non-housing admin: full system privileges but no Housing designation.
    $nonHousingAdmin = User::factory()->create([
        'name' => 'Non Housing Admin',
        'active' => true,
        'is_housing_team' => false,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id]);

    $userIds = $component->viewData('users')->pluck('id')->all();
    expect($userIds)->not->toContain($nonHousingAdmin->id);
})->group('regression');

it('excludes inactive users even when flagged is_housing_team', function () {
    $job = HousingJob::factory()->create();

    $inactiveHousingMember = User::factory()->create([
        'name' => 'Inactive Housing Member',
        'active' => false,
        'is_housing_team' => true,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ProjectManagement::class, ['id' => $job->id]);

    $userIds = $component->viewData('users')->pluck('id')->all();
    expect($userIds)->not->toContain($inactiveHousingMember->id);
})->group('regression');
