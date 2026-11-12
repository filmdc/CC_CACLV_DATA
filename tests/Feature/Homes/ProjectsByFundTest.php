<?php

use App\Livewire\Homes\Today;
use App\Models\FundCode;
use App\Models\HousingJob;
use App\Models\Participant;
use App\Models\Permission;
use App\Models\ProjectMilestone;
use App\Models\Role;
use App\Models\User;
use App\Services\Homes\DashboardWidgets;
use App\Services\Homes\HousingPortalData;
use Livewire\Livewire;

/**
 * The "Projects by fund" dashboard card.
 *
 * Grouped off the fund↔project pivot, not off budget dollars — no budget
 * line in production carries a fund code yet, so grouping on money would
 * show an empty card while grouping on the assignment shows the real work.
 */
function fundCardUser(array $permissionNames = []): User
{
    $role = Role::firstOrCreate(['name' => 'fund_card_test'], ['display_name' => 'Fund Card Test']);
    $ids = collect($permissionNames)->map(fn ($n) => Permission::firstOrCreate(['name' => $n], ['display_name' => $n])->id);
    $role->permissions()->sync($ids->all());

    $user = User::factory()->create();
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

/** A project with a milestone that decides its plain status. */
function fundCardJob(string $title, string $milestoneStatus, ?string $target = null): HousingJob
{
    $job = HousingJob::factory()->create(['status' => 'in_progress', 'job_title' => $title]);

    ProjectMilestone::factory()->create([
        'housing_job_id' => $job->id,
        'status' => $milestoneStatus,
        'target_date' => $target,
    ]);

    return $job;
}

it('groups active projects under the funds paying for them', function () {
    $home = FundCode::factory()->create(['code' => 'HOME-2026']);
    $cdbg = FundCode::factory()->create(['code' => 'CDBG-2026']);

    $needsHand = fundCardJob('Overdue roof', 'in_progress', now()->subWeek()->toDateString());
    $running = fundCardJob('Calm siding', 'in_progress', now()->addMonth()->toDateString());
    $onCdbg = fundCardJob('CDBG porch', 'in_progress', now()->addMonth()->toDateString());

    $needsHand->fundCodes()->attach($home->id);
    $running->fundCodes()->attach($home->id);
    $onCdbg->fundCodes()->attach($cdbg->id);

    $groups = app(HousingPortalData::class)->projectsByFund();

    expect($groups)->toHaveCount(2);

    $homeGroup = $groups->firstWhere('code', 'HOME-2026');
    // One number per fund — the status split lives on the Projects list.
    expect($homeGroup['total'])->toBe(2);
    expect($homeGroup)->not->toHaveKeys(['attention', 'active', 'finished', 'open']);
    // Ordering inside a fund still leads with whatever needs a hand.
    expect($homeGroup['rows']->first()['status'])->toBe('attention');

    expect($groups->firstWhere('code', 'CDBG-2026')['total'])->toBe(1);
});

it('puts the busiest fund first, then sorts by code', function () {
    $big = FundCode::factory()->create(['code' => 'ZZZ-BIG']);
    $small = FundCode::factory()->create(['code' => 'AAA-SMALL']);
    $alsoSmall = FundCode::factory()->create(['code' => 'BBB-SMALL']);

    foreach (range(1, 3) as $i) {
        fundCardJob("Big {$i}", 'in_progress', now()->addMonth()->toDateString())
            ->fundCodes()->attach($big->id);
    }
    fundCardJob('Small one', 'in_progress', now()->addMonth()->toDateString())
        ->fundCodes()->attach($small->id);
    fundCardJob('Small two', 'in_progress', now()->addMonth()->toDateString())
        ->fundCodes()->attach($alsoSmall->id);

    // Sorted on the same number the row shows, so the order never looks
    // arbitrary; ties break alphabetically.
    expect(app(HousingPortalData::class)->projectsByFund()->pluck('code')->all())
        ->toBe(['ZZZ-BIG', 'AAA-SMALL', 'BBB-SMALL']);
});

it('shows a project on two funds under both, and says so per fund', function () {
    $a = FundCode::factory()->create(['code' => 'FUND-A']);
    $b = FundCode::factory()->create(['code' => 'FUND-B']);

    $job = fundCardJob('Shared job', 'in_progress', now()->addMonth()->toDateString());
    $job->fundCodes()->attach([$a->id, $b->id]);

    $groups = app(HousingPortalData::class)->projectsByFund();

    expect($groups->firstWhere('code', 'FUND-A')['total'])->toBe(1);
    expect($groups->firstWhere('code', 'FUND-B')['total'])->toBe(1);
});

it('leaves out templates and archived projects', function () {
    $fund = FundCode::factory()->create(['code' => 'FUND-X']);

    $template = HousingJob::factory()->create(['is_template' => true]);
    $archived = HousingJob::factory()->create(['is_archived' => true]);
    $template->fundCodes()->attach($fund->id);
    $archived->fundCodes()->attach($fund->id);

    expect(app(HousingPortalData::class)->projectsByFund())->toBeEmpty();
});

it('names the active funds carrying nothing', function () {
    $used = FundCode::factory()->create(['code' => 'IN-USE', 'is_active' => true]);
    FundCode::factory()->create(['code' => 'IDLE-FUND', 'is_active' => true]);
    FundCode::factory()->create(['code' => 'RETIRED-FUND', 'is_active' => false]);

    fundCardJob('A job', 'in_progress', now()->addMonth()->toDateString())
        ->fundCodes()->attach($used->id);

    $idle = app(HousingPortalData::class)->fundsWithoutProjects();

    expect($idle->all())->toBe(['IDLE-FUND']);
});

it('caps each fund group and says how many more there are', function () {
    $fund = FundCode::factory()->create(['code' => 'BIG-FUND']);

    foreach (range(1, 9) as $i) {
        fundCardJob("Job {$i}", 'in_progress', now()->addMonth()->toDateString())
            ->fundCodes()->attach($fund->id);
    }

    $group = app(HousingPortalData::class)->projectsByFund()->firstWhere('code', 'BIG-FUND');

    expect($group['total'])->toBe(9);
    expect($group['rows'])->toHaveCount(6);
    expect($group['more'])->toBe(3);
});

it('renders the card on Today and expands one fund at a time', function () {
    $this->actingAs(fundCardUser(['communications_log.view', 'projects.view']));

    $fund = FundCode::factory()->create(['code' => 'HOME-2026']);
    $participant = Participant::factory()->create(['first_name' => 'Ada', 'last_name' => 'Riveramonte']);
    $job = fundCardJob('Ada roof', 'in_progress', now()->addMonth()->toDateString());
    $job->update(['participant_id' => $participant->id]);
    $job->fundCodes()->attach($fund->id);

    Livewire::test(Today::class)
        ->assertSee('Projects by fund')
        ->assertSee('HOME-2026')
        ->assertSee('1 project')
        // Collapsed: the fund is listed, its projects are not.
        ->assertDontSee('Riveramonte')
        ->call('toggleFund', $fund->id)
        ->assertSet('openFund', $fund->id)
        ->assertSee('Ada Riveramonte')
        // Clicking the same fund again closes it.
        ->call('toggleFund', $fund->id)
        ->assertSet('openFund', null)
        ->assertDontSee('Riveramonte');
});

it('hides the card when the widget is switched off', function () {
    $this->actingAs(fundCardUser(['communications_log.view', 'projects.view']));

    $fund = FundCode::factory()->create(['code' => 'HOME-2026']);
    fundCardJob('A job', 'in_progress', now()->addMonth()->toDateString())
        ->fundCodes()->attach($fund->id);

    Livewire::test(Today::class)->assertSee('Projects by fund');

    app(DashboardWidgets::class)->save(['pinned' => true, 'glance' => true, 'funds' => false]);

    Livewire::test(Today::class)
        ->assertDontSee('Projects by fund')
        ->assertSet('openFund', null);
});

it('hides the card from someone who cannot see projects', function () {
    $this->actingAs(fundCardUser(['communications_log.view']));

    $fund = FundCode::factory()->create(['code' => 'HOME-2026']);
    fundCardJob('A job', 'in_progress', now()->addMonth()->toDateString())
        ->fundCodes()->attach($fund->id);

    Livewire::test(Today::class)->assertDontSee('Projects by fund');
});

it('gives a projects-only user a dashboard instead of a dead end', function () {
    $this->actingAs(fundCardUser(['projects.view']));

    $fund = FundCode::factory()->create(['code' => 'HOME-2026']);
    fundCardJob('A job', 'in_progress', now()->addMonth()->toDateString())
        ->fundCodes()->attach($fund->id);

    Livewire::test(Today::class)
        ->assertSee('Projects by fund')
        ->assertDontSee("You don't have access to the communications log");
});
