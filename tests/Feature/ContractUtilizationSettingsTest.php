<?php

namespace Tests\Feature;

use App\Livewire\ContractUtilizationSettings;
use App\Models\Contract;
use App\Models\ContractUtilizationNotification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\ContractUtilizationThresholdNotification;
use App\Services\ContractUtilizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ContractUtilizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(string $permission): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'util-admin-'.uniqid(), 'display_name' => 'Util Admin']);
        $perm = Permission::firstOrCreate(
            ['name' => $permission],
            ['display_name' => $permission, 'category' => 'contracts']
        );
        $role->permissions()->attach($perm->id);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function contractWithRecipient(): array
    {
        $contract = Contract::factory()->create(['amount' => 10000]);
        $manager = User::factory()->create();
        $contract->managers()->attach($manager->id, ['receive_notifications' => true]);

        return [$contract, $manager];
    }

    public function test_page_requires_contracts_manage_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/contract-utilization-settings')
            ->assertStatus(403);
    }

    public function test_page_loads_for_authorized_user(): void
    {
        $this->actingAs($this->userWith('contracts.manage'))
            ->get('/admin/contract-utilization-settings')
            ->assertStatus(200)
            ->assertSee('Utilization Alert Settings');
    }

    public function test_master_toggle_and_test_email_are_persisted(): void
    {
        Livewire::actingAs($this->userWith('contracts.manage'))
            ->test(ContractUtilizationSettings::class)
            ->set('notificationsEnabled', false)
            ->set('testMode', true)
            ->set('testEmail', 'tester@caclv.org')
            ->call('save')
            ->assertHasNoErrors();

        SiteSetting::clearCache();

        $this->assertFalse(ContractUtilizationService::notificationsEnabled());
        $this->assertTrue(ContractUtilizationService::testModeEnabled());
        $this->assertSame('tester@caclv.org', ContractUtilizationService::testEmail());
    }

    public function test_test_mode_requires_an_email_address(): void
    {
        Livewire::actingAs($this->userWith('contracts.manage'))
            ->test(ContractUtilizationSettings::class)
            ->set('testMode', true)
            ->set('testEmail', '')
            ->call('save')
            ->assertHasErrors(['testEmail']);
    }

    public function test_thresholds_are_saved_unique_and_descending(): void
    {
        Livewire::actingAs($this->userWith('contracts.manage'))
            ->test(ContractUtilizationSettings::class)
            ->set('thresholds', [0, 50, 50, 10])
            ->set('testMode', false)
            ->call('save')
            ->assertHasNoErrors();

        SiteSetting::clearCache();

        $this->assertSame([50, 10, 0], ContractUtilizationService::getThresholds());
    }

    public function test_disabled_master_switch_records_crossing_without_sending(): void
    {
        Notification::fake();

        SiteSetting::set('utilization_notification_enabled', '0', 'boolean');
        SiteSetting::set('utilization_test_mode', '0', 'boolean');
        SiteSetting::clearCache();

        [$contract, $manager] = $this->contractWithRecipient();

        $sent = ContractUtilizationService::sendThresholdNotification($contract, 0);

        $this->assertSame(0, $sent);
        Notification::assertNothingSent();

        $this->assertDatabaseHas('contract_utilization_notifications', [
            'contract_id' => $contract->id,
            'threshold_percentage' => 0,
            'notification_type' => 'suppressed',
        ]);
    }

    public function test_suppressed_crossings_are_not_replayed_when_re_enabled(): void
    {
        Notification::fake();

        SiteSetting::set('utilization_notification_enabled', '0', 'boolean');
        SiteSetting::clearCache();

        [$contract] = $this->contractWithRecipient();

        ContractUtilizationService::sendThresholdNotification($contract, 0);

        SiteSetting::set('utilization_notification_enabled', '1', 'boolean');
        SiteSetting::clearCache();

        // The recorded crossing means this threshold is no longer "outstanding",
        // so turning alerts back on cannot fire it a second time.
        $crossed = ContractUtilizationNotification::getAllCrossedThresholds(
            $contract->id,
            0.0,
            [75, 50, 25, 10, 5, 0]
        );

        $this->assertNotContains(0, $crossed);
    }

    public function test_test_mode_delivers_to_the_test_address_even_while_disabled(): void
    {
        Notification::fake();

        SiteSetting::set('utilization_notification_enabled', '0', 'boolean');
        SiteSetting::set('utilization_test_mode', '1', 'boolean');
        SiteSetting::set('utilization_test_email', 'tester@caclv.org', 'text');
        SiteSetting::clearCache();

        [$contract, $manager] = $this->contractWithRecipient();

        $sent = ContractUtilizationService::sendThresholdNotification($contract, 0);

        $this->assertSame(1, $sent);

        // Goes to the test address, never to the real recipient.
        Notification::assertSentTo(
            new AnonymousNotifiable,
            ContractUtilizationThresholdNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'tester@caclv.org'
        );
        Notification::assertNotSentTo($manager, ContractUtilizationThresholdNotification::class);
    }

    public function test_enabled_alerts_reach_the_real_recipient(): void
    {
        Notification::fake();

        SiteSetting::set('utilization_notification_enabled', '1', 'boolean');
        SiteSetting::set('utilization_test_mode', '0', 'boolean');
        SiteSetting::clearCache();

        [$contract, $manager] = $this->contractWithRecipient();

        $sent = ContractUtilizationService::sendThresholdNotification($contract, 0);

        $this->assertSame(1, $sent);
        Notification::assertSentTo($manager, ContractUtilizationThresholdNotification::class);
    }

    public function test_a_test_run_does_not_consume_the_real_threshold(): void
    {
        Notification::fake();

        SiteSetting::set('utilization_notification_enabled', '1', 'boolean');
        SiteSetting::set('utilization_test_mode', '1', 'boolean');
        SiteSetting::set('utilization_test_email', 'tester@caclv.org', 'text');
        SiteSetting::clearCache();

        [$contract] = $this->contractWithRecipient();

        ContractUtilizationService::sendThresholdNotification($contract, 0);

        // Nothing recorded, so the genuine alert still owes the real recipients.
        $this->assertDatabaseMissing('contract_utilization_notifications', [
            'contract_id' => $contract->id,
            'threshold_percentage' => 0,
        ]);

        $this->assertContains(
            0,
            ContractUtilizationNotification::getAllCrossedThresholds($contract->id, 0.0, [0])
        );
    }

    public function test_dashboard_warns_when_alerts_are_switched_off(): void
    {
        SiteSetting::set('utilization_notification_enabled', '0', 'boolean');
        SiteSetting::set('utilization_test_mode', '0', 'boolean');
        SiteSetting::clearCache();

        $this->actingAs($this->userWith('contracts.view'))
            ->get('/contract-utilization')
            ->assertStatus(200)
            ->assertSee('Overspend alert emails are turned off.');
    }

    public function test_dashboard_is_quiet_when_alerts_are_on(): void
    {
        SiteSetting::set('utilization_notification_enabled', '1', 'boolean');
        SiteSetting::set('utilization_test_mode', '0', 'boolean');
        SiteSetting::clearCache();

        $this->actingAs($this->userWith('contracts.view'))
            ->get('/contract-utilization')
            ->assertStatus(200)
            ->assertDontSee('Overspend alert emails are turned off.');
    }

    public function test_command_still_evaluates_while_alerts_are_disabled(): void
    {
        Notification::fake();

        SiteSetting::set('utilization_notification_enabled', '0', 'boolean');
        SiteSetting::set('utilization_test_mode', '0', 'boolean');
        SiteSetting::clearCache();

        $this->artisan('contract:check-utilization')
            ->expectsOutputToContain('DISABLED')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }
}
