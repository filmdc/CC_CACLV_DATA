<?php

use App\Models\HousingJob;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\User;
use App\Services\RequirementInstantiator;
use Database\Seeders\RegressionTestSeeder;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

it('clones milestone-source requirements from a template into the new project, owned by the instantiating user', function () {
    $templateOwner = User::factory()->create();
    $instantiator = User::factory()->create();

    Auth::login($templateOwner);
    $template = HousingJob::factory()->create([
        'is_template' => true,
        'template_name' => 'Test template',
        'project_manager_id' => $templateOwner->id,
        'created_by' => $templateOwner->id,
    ]);
    $tplMilestone = ProjectMilestone::factory()->create([
        'housing_job_id' => $template->id,
        'name' => 'Get Permit',
        'sequence_order' => 1,
    ]);
    app(RequirementInstantiator::class)->forMilestone(
        $tplMilestone,
        Requirement::TYPE_TASK,
        'Submit application',
        $templateOwner,
    );
    app(RequirementInstantiator::class)->forMilestone(
        $tplMilestone,
        Requirement::TYPE_DOCUMENT,
        'Upload signed contract',
        $templateOwner,
    );

    Auth::logout();
    Auth::login($instantiator);

    $newJob = HousingJob::createFromTemplate($template, [
        'job_title' => 'Cloned project',
        'program_id' => $template->program_id,
        'status' => 'pending',
        'request_date' => now(),
    ]);

    expect($newJob->is_template)->toBeFalse();
    expect($newJob->project_manager_id)->toBe($instantiator->id);

    $clonedReqs = Requirement::where('housing_job_id', $newJob->id)
        ->where('source_type', Requirement::SOURCE_MILESTONE)
        ->orderBy('id')
        ->get();

    expect($clonedReqs)->toHaveCount(2);

    foreach ($clonedReqs as $req) {
        expect($req->owner_user_id)->toBe($instantiator->id);
        expect($req->status)->toBe(Requirement::STATUS_OUTSTANDING);
        expect($req->completed_at)->toBeNull();
        expect($req->completed_by_user_id)->toBeNull();
        expect($req->override_reason)->toBeNull();
        expect($req->source_template_id)->toBeNull();
        expect($req->created_by)->toBe($instantiator->id);
        // source_id must point at one of the new project's milestones,
        // never the template's milestone.
        expect($req->source_id)->not->toBe($tplMilestone->id);
        expect($newJob->milestones->pluck('id')->all())->toContain($req->source_id);
    }

    expect($clonedReqs->pluck('title')->all())
        ->toContain('Submit application')
        ->toContain('Upload signed contract');
});

it('does not carry completion or override state from a template requirement into the cloned project', function () {
    $owner = User::factory()->create();
    $instantiator = User::factory()->create();

    Auth::login($owner);
    $template = HousingJob::factory()->create(['is_template' => true, 'project_manager_id' => $owner->id, 'created_by' => $owner->id]);
    $tplMilestone = ProjectMilestone::factory()->create(['housing_job_id' => $template->id]);
    $tplReq = app(RequirementInstantiator::class)->forMilestone(
        $tplMilestone,
        Requirement::TYPE_DOCUMENT,
        'Already-completed-on-the-template requirement',
        $owner,
    );
    $tplReq->markCompleteWithOverride($owner, 'Hard copy on file in template archive');
    expect($tplReq->fresh()->isComplete())->toBeTrue();

    Auth::logout();
    Auth::login($instantiator);

    $newJob = HousingJob::createFromTemplate($template, [
        'job_title' => 'Fresh project',
        'program_id' => $template->program_id,
        'status' => 'pending',
        'request_date' => now(),
    ]);

    $cloned = Requirement::where('housing_job_id', $newJob->id)
        ->where('source_type', Requirement::SOURCE_MILESTONE)
        ->firstOrFail();

    expect($cloned->status)->toBe(Requirement::STATUS_OUTSTANDING);
    expect($cloned->completed_at)->toBeNull();
    expect($cloned->override_reason)->toBeNull();
    expect($cloned->overridden_by_user_id)->toBeNull();
});

it('preserves completion history and stamps a reopened-by line when reopen() is called', function () {
    $manager = User::factory()->create(['name' => 'Kaitlyn']);
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->task()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $manager->id,
    ]);
    $req->markComplete($manager, 'Done at 4pm Tuesday.');

    $req->reopen($manager);

    $req->refresh();
    expect($req->status)->toBe(Requirement::STATUS_OUTSTANDING);
    expect($req->completed_at)->toBeNull();
    expect($req->completed_by_user_id)->toBeNull();
    expect($req->completion_note)->toContain('Done at 4pm Tuesday.');
    expect($req->completion_note)->toContain('[REOPENED by Kaitlyn');
});

it('rolls override_reason text into completion_note as history when reopened', function () {
    $manager = User::factory()->create(['name' => 'Kaitlyn']);
    $job = HousingJob::factory()->create(['project_manager_id' => $manager->id]);
    $req = Requirement::factory()->document()->create([
        'housing_job_id' => $job->id,
        'owner_user_id' => $manager->id,
    ]);
    $req->markCompleteWithOverride($manager, 'Hard copy on file');

    $req->reopen($manager);

    $req->refresh();
    expect($req->status)->toBe(Requirement::STATUS_OUTSTANDING);
    expect($req->override_reason)->toBeNull();
    expect($req->overridden_by_user_id)->toBeNull();
    expect($req->completion_note)->toContain('Hard copy on file');
    expect($req->completion_note)->toContain('[REOPENED by Kaitlyn');
});
