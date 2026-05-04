<?php

use App\Livewire\ProjectManagement;
use App\Models\FundCode;
use App\Models\FundCodeRequirement;
use App\Models\HousingJob;
use App\Models\Requirement;
use App\Models\User;
use App\Notifications\RequirementAssignedNotification;
use App\Services\RequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
    $this->admin = RegressionHelpers::adminUser();
});

it('instantiates one requirement per fund-code-requirement template when a fund code attaches', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $code = FundCode::factory()->create(['code' => 'INSTANTIATE-001']);

    FundCodeRequirement::factory()->create(['fund_code_id' => $code->id, 'label' => 'First template', 'sort_order' => 1]);
    FundCodeRequirement::factory()->document()->create(['fund_code_id' => $code->id, 'label' => 'Second template', 'sort_order' => 2]);

    Livewire::actingAs($this->admin)
        ->test(ProjectManagement::class, ['id' => $job->id])
        ->set('newFundCodeId', $code->id)
        ->call('attachFundCode')
        ->assertHasNoErrors();

    $reqs = Requirement::where('housing_job_id', $job->id)->get();
    expect($reqs)->toHaveCount(2);
    expect($reqs->pluck('source_type')->unique()->all())->toBe(['fund_code']);
    expect($reqs->pluck('source_id')->unique()->all())->toBe([$code->id]);
    expect($reqs->pluck('owner_user_id')->unique()->all())->toBe([$manager->id]);
    expect($reqs->where('title', 'Second template')->first()->type)->toBe('document');
    expect($reqs->where('title', 'First template')->first()->type)->toBe('task');
});

it('is idempotent — re-attaching the same fund code does not duplicate requirement rows', function () {
    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $code = FundCode::factory()->create();
    FundCodeRequirement::factory()->count(3)->create(['fund_code_id' => $code->id]);

    $component = Livewire::actingAs($this->admin)->test(ProjectManagement::class, ['id' => $job->id]);
    $component->set('newFundCodeId', $code->id)->call('attachFundCode');
    $component->set('newFundCodeId', $code->id)->call('attachFundCode');

    expect(Requirement::where('housing_job_id', $job->id)->count())->toBe(3);
});

it('fires RequirementAssignedNotification to the project manager when actor is not the manager', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $assistant = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $code = FundCode::factory()->create();
    FundCodeRequirement::factory()->count(2)->create(['fund_code_id' => $code->id]);

    app(RequirementInstantiator::class)->forFundCode($job->fresh(), $code, $assistant);

    Notification::assertSentTo($manager, RequirementAssignedNotification::class, function ($n) use ($job) {
        return $n->requirement->housing_job_id === $job->id;
    });
    Notification::assertSentToTimes($manager, RequirementAssignedNotification::class, 2);
});

it('suppresses self-notification when the actor is the project manager', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $code = FundCode::factory()->create();
    FundCodeRequirement::factory()->count(2)->create(['fund_code_id' => $code->id]);

    app(RequirementInstantiator::class)->forFundCode($job->fresh(), $code, $manager);

    Notification::assertNothingSent();
});
