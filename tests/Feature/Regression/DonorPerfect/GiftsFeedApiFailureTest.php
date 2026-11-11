<?php

use App\Livewire\DonorPerfectGiftsFeed;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\DonorPerfectGiftService;
use Livewire\Livewire;

function donorGiftsViewer(): User
{
    $role = Role::firstOrCreate(['name' => 'test-donor-gifts-viewer'], ['display_name' => 'Test Donor Gifts Viewer']);
    $permission = Permission::firstOrCreate(['name' => 'donor_gifts.view'], ['display_name' => 'View donor gifts']);
    $role->permissions()->syncWithoutDetaching([$permission->id]);
    $user = User::factory()->create();
    $user->roles()->attach($role->id);

    return $user;
}

/*
 * When the DonorPerfect API fails (e.g. its gateway returns 502), the feed must
 * render an error banner — not a 500 from a filter variable the error branch
 * forgot to pass to the view.
 */
it('renders the feed when the DonorPerfect API returns an error', function () {
    $service = Mockery::mock(DonorPerfectGiftService::class)->makePartial();
    $service->shouldReceive('isConfigured')->andReturn(true);
    $service->shouldReceive('getGiftsWithMetadata')->andReturn([
        'success' => false,
        'error' => 'API request failed with status: 502',
        'auth_error' => false,
    ]);
    app()->instance(DonorPerfectGiftService::class, $service);

    $this->actingAs(donorGiftsViewer());

    Livewire::test(DonorPerfectGiftsFeed::class)
        ->assertOk()
        ->assertSee('API request failed with status: 502');
})->group('regression');

it('renders the feed when the DonorPerfect API is not configured', function () {
    $service = Mockery::mock(DonorPerfectGiftService::class)->makePartial();
    $service->shouldReceive('isConfigured')->andReturn(false);
    app()->instance(DonorPerfectGiftService::class, $service);

    $this->actingAs(donorGiftsViewer());

    Livewire::test(DonorPerfectGiftsFeed::class)->assertOk();
})->group('regression');

it('shows the outage message with a clickable status page link', function () {
    $service = Mockery::mock(DonorPerfectGiftService::class)->makePartial();
    $service->shouldReceive('isConfigured')->andReturn(true);
    $service->shouldReceive('getGiftsWithMetadata')->andReturn([
        'success' => false,
        'error' => \App\Services\DonorPerfectApiService::UNAVAILABLE_MESSAGE,
        'auth_error' => false,
    ]);
    app()->instance(DonorPerfectGiftService::class, $service);

    $this->actingAs(donorGiftsViewer());

    Livewire::test(DonorPerfectGiftsFeed::class)
        ->assertOk()
        ->assertSeeHtml('<a href="https://status.donorperfect.com/"');
})->group('regression');
