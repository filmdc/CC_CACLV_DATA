<?php

use App\Livewire\Homes\CallsIntakes;
use App\Livewire\Homes\Login;
use App\Livewire\Homes\QuickLog;
use App\Livewire\Homes\Today;
use App\Models\CommunicationLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Homes\DashboardWidgets;
use App\Services\Homes\HousingPortalData;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/**
 * Creates a user holding exactly the given permissions under a non-admin role,
 * so nav gating and per-action gates on the housing portal can be exercised.
 */
function homesUser(array $permissionNames = []): User
{
    $role = Role::firstOrCreate(['name' => 'homes_portal_test'], ['display_name' => 'Homes Portal Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

it('redirects guests hitting the portal to the housing login', function () {
    $this->get(route('homes.today'))
        ->assertRedirect(route('homes.login'));
});

it('logs a user in and sends them to Today', function () {
    User::factory()->create([
        'email' => 'kaitlyn@caclv.org',
        'password' => Hash::make('secret-password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'kaitlyn@caclv.org')
        ->set('password', 'secret-password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('homes.today'));
});

it('rejects bad credentials on the housing login', function () {
    User::factory()->create([
        'email' => 'kaitlyn@caclv.org',
        'password' => Hash::make('secret-password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'kaitlyn@caclv.org')
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors('email');
});

it('shows due follow-ups on the Today home', function () {
    // The Follow-ups card is opt-in as of the dashboard-widget settings
    // (Settings -> Dashboard widgets); switch it on to exercise the queue.
    app(DashboardWidgets::class)->save([
        'pinned' => true, 'follow_ups' => true, 'waiting' => true, 'ready' => true, 'glance' => true,
    ]);

    $this->actingAs(homesUser(['communications_log.view']));

    CommunicationLog::factory()->create([
        'contact_first_name' => 'Evelyn',
        'contact_last_name' => 'Lapenna',
        'follow_up_date' => now()->subDays(2)->toDateString(),
        'follow_up_action' => 'Call to check in',
        'follow_up_completed' => false,
    ]);
    // Completed follow-ups must not appear.
    CommunicationLog::factory()->create([
        'follow_up_date' => now()->subDay()->toDateString(),
        'follow_up_completed' => true,
    ]);

    Livewire::test(Today::class)
        ->assertOk()
        ->assertViewHas('followUps', fn ($rows) => $rows->count() === 1)
        ->assertSee('Evelyn Lapenna');
});

it('gates the Calls & Intakes screen on communications_log.view', function () {
    $this->actingAs(homesUser([]));
    $this->get(route('homes.calls'))->assertForbidden();

    $this->actingAs(homesUser(['communications_log.view']));
    $this->get(route('homes.calls'))->assertOk();
});

it('filters the Calls list by the "needs something" pill', function () {
    $this->actingAs(homesUser(['communications_log.view']));

    CommunicationLog::factory()->create([
        'contact_first_name' => 'Needs',
        'contact_last_name' => 'Attention',
        'follow_up_date' => now()->addDay()->toDateString(),
        'follow_up_completed' => false,
    ]);
    CommunicationLog::factory()->create([
        'contact_first_name' => 'Quiet',
        'contact_last_name' => 'Record',
        'follow_up_date' => null,
    ]);

    Livewire::test(CallsIntakes::class)
        ->set('pill', 'needs')
        ->assertSee('Needs Attention')
        ->assertDontSee('Quiet Record')
        ->set('pill', 'all')
        ->assertSee('Quiet Record');
});

it('filters the Calls list by city', function () {
    $this->actingAs(homesUser(['communications_log.view']));

    CommunicationLog::factory()->create([
        'contact_first_name' => 'Alma',
        'contact_last_name' => 'Allentowner',
        'contact_city' => 'Allentown',
    ]);
    CommunicationLog::factory()->create([
        'contact_first_name' => 'Wes',
        'contact_last_name' => 'Whitehaller',
        // Legacy rows were saved with stray whitespace — the filter must still catch them.
        'contact_city' => 'Whitehall ',
    ]);

    Livewire::test(CallsIntakes::class)
        ->set('city', 'Allentown')
        ->assertSee('Alma Allentowner')
        ->assertDontSee('Wes Whitehaller')
        ->set('city', 'Whitehall')
        ->assertSee('Wes Whitehaller')
        ->assertDontSee('Alma Allentowner')
        ->set('city', '')
        ->assertSee('Alma Allentowner')
        ->assertSee('Wes Whitehaller');
});

it('remembers the city filter in a saved view', function () {
    $this->actingAs(homesUser(['communications_log.view']));

    CommunicationLog::factory()->create([
        'contact_first_name' => 'Alma',
        'contact_last_name' => 'Allentowner',
        'contact_city' => 'Allentown',
    ]);

    Livewire::test(CallsIntakes::class)
        ->set('city', 'Allentown')
        ->call('openCreateView')
        ->set('viewName', 'Allentown intakes')
        ->call('saveView')
        ->assertHasNoErrors()
        ->set('city', '')
        ->call('applyView', \App\Models\HomesSavedView::where('name', 'Allentown intakes')->value('id'))
        ->assertSet('city', 'Allentown');
});

it('persists a real CommunicationLog from the quick-log modal', function () {
    $user = homesUser(['communications_log.create']);
    $this->actingAs($user);

    Livewire::test(QuickLog::class)
        ->call('open')
        ->set('who', 'Carol Starkweather')
        ->set('what', 'Roof leaking throughout the house.')
        ->set('followUp', 'Call back')
        ->set('followUpWhen', '1 week')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('homes-call-logged')
        ->assertSet('show', false);

    $log = CommunicationLog::where('subject', 'Call with Carol Starkweather')->first();

    expect($log)->not->toBeNull();
    expect($log->contact_first_name)->toBe('Carol');
    expect($log->contact_last_name)->toBe('Starkweather');
    expect($log->summary)->toBe('Roof leaking throughout the house.');
    expect($log->follow_up_action)->toBe('Call back');
    expect($log->follow_up_date->toDateString())->toBe(now()->addDays(7)->toDateString());
    expect($log->created_by)->toBe($user->id);
});

it('saves the address in separate parts from the quick-log modal', function () {
    $this->actingAs(homesUser(['communications_log.create']));

    Livewire::test(QuickLog::class)
        ->call('open')
        ->set('who', 'Pat Splitfield')
        ->set('showDetails', true)
        ->set('address', ' 123 Main St ')
        ->set('city', ' Allentown ')
        ->set('state', 'pa')
        ->set('zip', '18101')
        ->call('save')
        ->assertHasNoErrors();

    $log = CommunicationLog::where('subject', 'Call with Pat Splitfield')->first();

    expect($log->contact_address)->toBe('123 Main St');
    expect($log->contact_city)->toBe('Allentown');
    expect($log->contact_state)->toBe('PA');
    expect($log->contact_zip)->toBe('18101');
});

it('requires a name in the quick-log modal', function () {
    $this->actingAs(homesUser(['communications_log.create']));

    Livewire::test(QuickLog::class)
        ->call('open')
        ->set('who', '')
        ->call('save')
        ->assertHasErrors('who');
});

it('computes weekly glance counts from real data', function () {
    homesUser([]); // ensure roles table seeded harmlessly
    $this->actingAs(homesUser(['communications_log.view']));

    CommunicationLog::factory()->count(2)->create([
        'communication_date' => now(),
    ]);

    $glance = app(HousingPortalData::class)->weekGlance();

    expect($glance['calls_logged'])->toBeGreaterThanOrEqual(2);
    expect($glance)->toHaveKeys(['calls_logged', 'new_intakes', 'projects_active', 'fund_expiring']);
});

it('labels a requirement-less log as "just a call"', function () {
    $log = CommunicationLog::factory()->create();

    $stage = app(HousingPortalData::class)->stage($log->load('intakeRequirements'));

    expect($stage['key'])->toBe('call');
});

it('suggests but does not link a participant on a partial quick-log name match', function () {
    $this->actingAs(homesUser(['communications_log.create']));

    $existing = \App\Models\Participant::factory()->create(['first_name' => 'Zebulon', 'last_name' => 'Quickmatch']);

    Livewire::test(QuickLog::class)
        ->call('open')
        ->set('who', 'Zebulon')
        ->assertSet('suggestedParticipantId', $existing->id)
        ->assertSet('matchedParticipantId', null)
        ->call('save')
        ->assertHasNoErrors();

    $log = CommunicationLog::where('subject', 'Call with Zebulon')->first();

    expect($log->participant_id)->toBeNull();
    expect($log->contact_first_name)->toBe('Zebulon');
    expect($log->contact_last_name)->toBeNull();
});

it('links a quick-logged call to a participant once the worker confirms the match', function () {
    $this->actingAs(homesUser(['communications_log.create']));

    $existing = \App\Models\Participant::factory()->create(['first_name' => 'Zebulon', 'last_name' => 'Quickmatch']);

    Livewire::test(QuickLog::class)
        ->call('open')
        ->set('who', 'Zebulon Quickmatch')
        ->call('linkSuggestedParticipant')
        ->assertSet('matchedParticipantId', $existing->id)
        ->call('save')
        ->assertHasNoErrors();

    $log = CommunicationLog::where('subject', 'Call with Zebulon Quickmatch')->first();

    expect($log->participant_id)->toBe($existing->id);
});

it('clears a quick-log follow-up when the selected option is clicked again', function () {
    $this->actingAs(homesUser(['communications_log.create']));

    Livewire::test(QuickLog::class)
        ->call('open')
        ->call('setFollowUp', 'Call back')
        ->assertSet('followUp', 'Call back')
        ->call('setFollowUp', 'Call back')
        ->assertSet('followUp', 'No follow-up');
});
