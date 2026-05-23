<?php

/*
|----------------------------------------------------------------------
| P-39a: per-parent canViewMedia() rule coverage.
|
| Unit-level tests of the HasMediaAuthorization implementations on
| every production HasMedia model. Exercises the rule by direct method
| call — no HTTP, no real Media files, no factories required. This is
| the broadest coverage layer: every parent type, every branch of its
| rule.
|
| MediaPolicy + MediaController integration is covered separately in
| MediaControllerAuthorizationTest.
|----------------------------------------------------------------------
*/

use App\Contracts\HasMediaAuthorization;
use App\Models\ApprovalSubmission;
use App\Models\Contract;
use App\Models\ContractReport;
use App\Models\ContractReportDueDate;
use App\Models\Feedback;
use App\Models\MilestoneCommunication;
use App\Models\MilestoneDocument;
use App\Models\Permission;
use App\Models\ProjectMilestone;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RegressionTestSeeder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

function userWithPermissionsForRule(array $permissionNames): User
{
    $role = Role::firstOrCreate(
        ['name' => 'rule_test_'.md5(implode(',', $permissionNames))],
        ['display_name' => 'Rule test role', 'description' => 'P-39a rule test fixture role.']
    );

    // RegressionTestSeeder only seeds housing/comm-log/fund-code permissions.
    // For contracts.*, due_dates.*, feedback.*, approvals.* etc. we
    // firstOrCreate so the test row exists regardless of which seeders ran.
    $permissionIds = [];
    foreach ($permissionNames as $name) {
        $perm = Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => $name, 'description' => 'P-39a fixture permission.']
        );
        $permissionIds[] = $perm->id;
    }
    $role->permissions()->syncWithoutDetaching($permissionIds);

    $user = User::factory()->create();
    $user->roles()->attach($role->id);

    return $user;
}

/**
 * The canViewMedia method is pure given the parent instance + user;
 * no Media attribute is read inside any production rule. A bare
 * unsaved Media model is sufficient.
 */
function bareMedia(): Media
{
    return new Media;
}

// -----------------------------------------------------------------
// Requirement (rides on projects.view)
// -----------------------------------------------------------------

it('grants Requirement media to users with projects.view', function () {
    $req = new Requirement;
    $user = userWithPermissionsForRule(['projects.view']);

    expect($req->canViewMedia($user, bareMedia()))->toBeTrue();
})->group('regression');

it('denies Requirement media to users without projects.view', function () {
    $req = new Requirement;
    $user = userWithPermissionsForRule([]);

    expect($req->canViewMedia($user, bareMedia()))->toBeFalse();
})->group('regression');

// -----------------------------------------------------------------
// MilestoneDocument (rides on projects.view)
// -----------------------------------------------------------------

it('grants MilestoneDocument media to users with projects.view', function () {
    $doc = new MilestoneDocument;
    $user = userWithPermissionsForRule(['projects.view']);

    expect($doc->canViewMedia($user, bareMedia()))->toBeTrue();
})->group('regression');

it('denies MilestoneDocument media to users without projects.view', function () {
    $doc = new MilestoneDocument;
    $user = userWithPermissionsForRule([]);

    expect($doc->canViewMedia($user, bareMedia()))->toBeFalse();
})->group('regression');

// -----------------------------------------------------------------
// MilestoneCommunication (rides on projects.view)
// -----------------------------------------------------------------

it('grants MilestoneCommunication media to users with projects.view', function () {
    $comm = new MilestoneCommunication;
    $user = userWithPermissionsForRule(['projects.view']);

    expect($comm->canViewMedia($user, bareMedia()))->toBeTrue();
})->group('regression');

it('denies MilestoneCommunication media to users without projects.view', function () {
    $comm = new MilestoneCommunication;
    $user = userWithPermissionsForRule([]);

    expect($comm->canViewMedia($user, bareMedia()))->toBeFalse();
})->group('regression');

// -----------------------------------------------------------------
// ProjectMilestone (rides on projects.view)
// -----------------------------------------------------------------

it('grants ProjectMilestone media to users with projects.view', function () {
    $milestone = new ProjectMilestone;
    $user = userWithPermissionsForRule(['projects.view']);

    expect($milestone->canViewMedia($user, bareMedia()))->toBeTrue();
})->group('regression');

it('denies ProjectMilestone media to users without projects.view', function () {
    $milestone = new ProjectMilestone;
    $user = userWithPermissionsForRule([]);

    expect($milestone->canViewMedia($user, bareMedia()))->toBeFalse();
})->group('regression');

// -----------------------------------------------------------------
// Contract (contracts.view)
// -----------------------------------------------------------------

it('grants Contract media to users with contracts.view', function () {
    $contract = new Contract;
    $user = userWithPermissionsForRule(['contracts.view']);

    expect($contract->canViewMedia($user, bareMedia()))->toBeTrue();
})->group('regression');

it('denies Contract media to users without contracts.view', function () {
    $contract = new Contract;
    $user = userWithPermissionsForRule([]);

    expect($contract->canViewMedia($user, bareMedia()))->toBeFalse();
})->group('regression');

// -----------------------------------------------------------------
// ContractReport (contract_reports.view)
// -----------------------------------------------------------------

it('grants ContractReport media to users with contract_reports.view', function () {
    $report = new ContractReport;
    $user = userWithPermissionsForRule(['contract_reports.view']);

    expect($report->canViewMedia($user, bareMedia()))->toBeTrue();
})->group('regression');

it('denies ContractReport media to users without contract_reports.view', function () {
    $report = new ContractReport;
    $user = userWithPermissionsForRule([]);

    expect($report->canViewMedia($user, bareMedia()))->toBeFalse();
})->group('regression');

// -----------------------------------------------------------------
// ContractReportDueDate (due_dates.view)
// -----------------------------------------------------------------

it('grants ContractReportDueDate media to users with due_dates.view', function () {
    $due = new ContractReportDueDate;
    $user = userWithPermissionsForRule(['due_dates.view']);

    expect($due->canViewMedia($user, bareMedia()))->toBeTrue();
})->group('regression');

it('denies ContractReportDueDate media to users without due_dates.view', function () {
    $due = new ContractReportDueDate;
    $user = userWithPermissionsForRule([]);

    expect($due->canViewMedia($user, bareMedia()))->toBeFalse();
})->group('regression');

// -----------------------------------------------------------------
// ApprovalSubmission (submitter OR approvals.view_dashboard)
// -----------------------------------------------------------------

it('grants ApprovalSubmission media to the submitter', function () {
    $submitter = User::factory()->create();
    $submission = new ApprovalSubmission(['submitted_by' => $submitter->id]);
    $submission->submitted_by = $submitter->id;

    expect($submission->canViewMedia($submitter, bareMedia()))->toBeTrue();
})->group('regression');

it('grants ApprovalSubmission media to users with approvals.view_dashboard', function () {
    $submitter = User::factory()->create();
    $submission = new ApprovalSubmission(['submitted_by' => $submitter->id]);
    $submission->submitted_by = $submitter->id;

    $approver = userWithPermissionsForRule(['approvals.view_dashboard']);

    expect($submission->canViewMedia($approver, bareMedia()))->toBeTrue();
})->group('regression');

it('denies ApprovalSubmission media to non-submitter without approvals.view_dashboard', function () {
    $submitter = User::factory()->create();
    $submission = new ApprovalSubmission(['submitted_by' => $submitter->id]);
    $submission->submitted_by = $submitter->id;

    $other = userWithPermissionsForRule([]);

    expect($submission->canViewMedia($other, bareMedia()))->toBeFalse();
})->group('regression');

// -----------------------------------------------------------------
// Feedback (submitter OR feedback.view_all OR feedback.manage)
// -----------------------------------------------------------------

it('grants Feedback media to the submitter', function () {
    $submitter = User::factory()->create();
    $feedback = new Feedback(['user_id' => $submitter->id]);
    $feedback->user_id = $submitter->id;

    expect($feedback->canViewMedia($submitter, bareMedia()))->toBeTrue();
})->group('regression');

it('grants Feedback media to users with feedback.view_all', function () {
    $submitter = User::factory()->create();
    $feedback = new Feedback(['user_id' => $submitter->id]);
    $feedback->user_id = $submitter->id;

    $viewer = userWithPermissionsForRule(['feedback.view_all']);

    expect($feedback->canViewMedia($viewer, bareMedia()))->toBeTrue();
})->group('regression');

it('grants Feedback media to users with feedback.manage', function () {
    $submitter = User::factory()->create();
    $feedback = new Feedback(['user_id' => $submitter->id]);
    $feedback->user_id = $submitter->id;

    $manager = userWithPermissionsForRule(['feedback.manage']);

    expect($feedback->canViewMedia($manager, bareMedia()))->toBeTrue();
})->group('regression');

it('denies Feedback media to non-submitter without view_all or manage', function () {
    $submitter = User::factory()->create();
    $feedback = new Feedback(['user_id' => $submitter->id]);
    $feedback->user_id = $submitter->id;

    $other = userWithPermissionsForRule([]);

    expect($feedback->canViewMedia($other, bareMedia()))->toBeFalse();
})->group('regression');

// -----------------------------------------------------------------
// Contract enforcement: every HasMedia model in app/Models implements
// HasMediaAuthorization. This catches accidental regressions where a
// new media-bearing model is added without opting into the contract.
// -----------------------------------------------------------------

it('requires every HasMedia model in app/Models to implement HasMediaAuthorization', function () {
    $offenders = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path('Models'))) as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        if (! str_contains($contents, 'implements HasMedia')) {
            continue;
        }

        // Resolve FQCN from filename.
        $relative = str_replace(
            [app_path('Models').'/', '/', '.php'],
            ['', '\\', ''],
            $file->getPathname()
        );
        $class = 'App\\Models\\'.$relative;

        if (! class_exists($class)) {
            continue;
        }

        if (! is_subclass_of($class, HasMediaAuthorization::class)
            && ! in_array(HasMediaAuthorization::class, class_implements($class) ?: [])) {
            $offenders[] = $class;
        }
    }

    expect($offenders)->toBeEmpty(
        'Models implementing HasMedia without HasMediaAuthorization: '.implode(', ', $offenders)
    );
})->group('regression');
