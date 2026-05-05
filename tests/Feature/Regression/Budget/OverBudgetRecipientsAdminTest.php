<?php

use App\Livewire\OverBudgetRecipientsManagement;
use App\Models\OverBudgetEmailRecipient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RegressionTestSeeder;
use Livewire\Livewire;
use Tests\Support\RegressionHelpers;
use Tests\Support\SnapshotHelper;

beforeEach(function () {
    $this->seed(RegressionTestSeeder::class);
});

/*
|----------------------------------------------------------------------
| Phase 3B Step 7 — admin recipient list management.
|
| Permission: over_budget_recipients.manage. Not auto-attached. The
| admin user from RegressionHelpers gets all permissions; for the
| permission-gate test we attach explicitly to a non-admin role.
|----------------------------------------------------------------------
*/

function makeRecipientManager(): User
{
    $perm = Permission::firstOrCreate(
        ['name' => 'over_budget_recipients.manage'],
        ['display_name' => 'Manage Over-Budget Recipients', 'description' => '']
    );
    $role = Role::firstOrCreate(['name' => 'budget_recipient_manager'], ['display_name' => 'Budget Recipient Manager']);
    $role->permissions()->syncWithoutDetaching([$perm->id]);

    $user = User::factory()->create();
    $user->roles()->attach($role->id);

    return $user;
}

it('renders the recipients admin page for an authed user with the manage permission', function () {
    $manager = makeRecipientManager();

    $this->actingAs($manager)
        ->get('/admin/over-budget-recipients')
        ->assertOk();
})->group('regression');

it('forbids users without over_budget_recipients.manage permission', function () {
    $unprivileged = User::factory()->create();

    $this->actingAs($unprivileged)
        ->get('/admin/over-budget-recipients')
        ->assertForbidden();
})->group('regression');

it('adds an active user to the recipient list', function () {
    $manager = makeRecipientManager();
    $candidate = User::factory()->create(['name' => 'Kaitlyn', 'active' => true]);

    Livewire::actingAs($manager)
        ->test(OverBudgetRecipientsManagement::class)
        ->set('userIdToAdd', $candidate->id)
        ->call('addRecipient');

    expect(OverBudgetEmailRecipient::where('user_id', $candidate->id)->exists())->toBeTrue();
    expect(OverBudgetEmailRecipient::find(
        OverBudgetEmailRecipient::where('user_id', $candidate->id)->value('id')
    )->created_by)->toBe($manager->id);
})->group('regression');

it('rejects an inactive user from being added to the recipient list', function () {
    $manager = makeRecipientManager();
    $candidate = User::factory()->create(['name' => 'Inactive Sam', 'active' => false]);

    Livewire::actingAs($manager)
        ->test(OverBudgetRecipientsManagement::class)
        ->set('userIdToAdd', $candidate->id)
        ->call('addRecipient');

    expect(OverBudgetEmailRecipient::where('user_id', $candidate->id)->exists())->toBeFalse();
})->group('regression');

it('dedupes a double-add via firstOrCreate (no DB unique exception bubbles up)', function () {
    $manager = makeRecipientManager();
    $candidate = User::factory()->create(['name' => 'Twice', 'active' => true]);

    Livewire::actingAs($manager)
        ->test(OverBudgetRecipientsManagement::class)
        ->set('userIdToAdd', $candidate->id)
        ->call('addRecipient')
        ->set('userIdToAdd', $candidate->id)
        ->call('addRecipient');

    expect(OverBudgetEmailRecipient::where('user_id', $candidate->id)->count())->toBe(1);
})->group('regression');

it('removes a recipient by id', function () {
    $manager = makeRecipientManager();
    $candidate = User::factory()->create(['active' => true]);

    $recipient = OverBudgetEmailRecipient::create([
        'user_id' => $candidate->id,
        'created_by' => $manager->id,
    ]);

    Livewire::actingAs($manager)
        ->test(OverBudgetRecipientsManagement::class)
        ->call('removeRecipient', $recipient->id);

    expect(OverBudgetEmailRecipient::where('id', $recipient->id)->exists())->toBeFalse();
})->group('regression');

it('snapshot pins the admin recipient page markup', function () {
    $manager = makeRecipientManager();
    $manager->update(['name' => 'Snapshot Manager', 'email' => 'manager@example.com']);

    $alice = User::factory()->create(['name' => 'Alice Recipient', 'email' => 'alice@example.com', 'active' => true]);
    OverBudgetEmailRecipient::create(['user_id' => $alice->id, 'created_by' => $manager->id]);

    // A second active user not yet on the list, to populate the
    // candidate dropdown deterministically.
    User::factory()->create(['name' => 'Bob Candidate', 'email' => 'bob@example.com', 'active' => true]);

    $html = (string) $this->actingAs($manager)
        ->get('/admin/over-budget-recipients')
        ->getContent();

    SnapshotHelper::assertHtmlMatchesSnapshot('over_budget_recipients_admin', $html);
})->group('regression');
