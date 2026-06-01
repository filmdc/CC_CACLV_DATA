<?php

namespace Tests\Feature;

use App\Livewire\UserBulkOperations;
use App\Livewire\UserCreate;
use App\Livewire\UserEdit;
use App\Livewire\UserManagement;
use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase {
        migrateFreshUsing as baseMigrateFreshUsing;
    }

    protected function migrateFreshUsing()
    {
        return array_merge(
            $this->baseMigrateFreshUsing(),
            [
                '--path' => [
                    'database/migrations/0001_01_01_000000_create_users_table.php',
                    'database/migrations/2025_05_30_221138_create_programs_table.php',
                    'database/migrations/2025_05_30_221633_create_roles_and_permissions_tables.php',
                    'database/migrations/2025_06_10_121944_add_housing_module_to_programs_table.php',
                    'database/migrations/2025_07_07_171250_add_active_column_to_users_table.php',
                    'database/migrations/2026_02_25_100000_add_approval_delegate_id_to_users_table.php',
                    'database/migrations/2026_06_01_081236_add_is_housing_team_to_users.php',
                ],
            ]
        );
    }

    // -------------------------------------------------------------------
    // UserManagement (list)
    // -------------------------------------------------------------------

    public function test_list_defaults_to_active_users_only(): void
    {
        $this->actingAsUserWithPermissions(['users.view', 'users.edit']);
        $active = User::factory()->create(['active' => true, 'name' => 'Alice Active']);
        $inactive = User::factory()->create(['active' => false, 'name' => 'Bob Inactive']);

        Livewire::test(UserManagement::class)
            ->assertSee('Alice Active')
            ->assertDontSee('Bob Inactive');
    }

    public function test_status_filter_can_show_inactive_users(): void
    {
        $this->actingAsUserWithPermissions(['users.view', 'users.edit']);
        User::factory()->create(['active' => true, 'name' => 'Alice Active']);
        User::factory()->create(['active' => false, 'name' => 'Bob Inactive']);

        Livewire::test(UserManagement::class)
            ->call('setStatusFilter', 'inactive')
            ->assertSee('Bob Inactive')
            ->assertDontSee('Alice Active');
    }

    public function test_user_cannot_deactivate_self(): void
    {
        $admin = $this->actingAsUserWithPermissions(['users.view', 'users.edit']);
        $admin->forceFill(['active' => true])->save();

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->call('toggleUserStatus', $admin->id);

        $this->assertTrue($admin->refresh()->active);
    }

    // -------------------------------------------------------------------
    // UserEdit
    // -------------------------------------------------------------------

    public function test_edit_rejects_duplicate_email(): void
    {
        $admin = $this->actingAsUserWithPermissions(['users.view', 'users.edit']);
        $target = User::factory()->create(['active' => true]);

        Livewire::actingAs($admin)
            ->test(UserEdit::class, ['id' => $target->id])
            ->set('email', $admin->email)
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_edit_saves_changes(): void
    {
        $admin = $this->actingAsUserWithPermissions(['users.view', 'users.edit']);
        $target = User::factory()->create(['active' => true, 'is_housing_team' => false]);

        Livewire::actingAs($admin)
            ->test(UserEdit::class, ['id' => $target->id])
            ->set('name', 'Renamed User')
            ->set('isHousingTeam', true)
            ->call('save');

        $fresh = $target->fresh();
        $this->assertSame('Renamed User', $fresh->name);
        $this->assertTrue((bool) $fresh->is_housing_team);
    }

    // -------------------------------------------------------------------
    // UserCreate
    // -------------------------------------------------------------------

    public function test_create_with_admin_set_password_creates_active_user(): void
    {
        $this->actingAsUserWithPermissions(['users.view', 'users.edit']);

        Livewire::test(UserCreate::class)
            ->set('name', 'New Person')
            ->set('email', 'new@example.com')
            ->set('passwordMethod', UserCreate::PW_METHOD_ADMIN_SET)
            ->set('password', 'secret-pass-123')
            ->set('password_confirmation', 'secret-pass-123')
            ->call('create');

        $user = User::where('email', 'new@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('New Person', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->active);
    }

    public function test_create_with_reset_link_sends_email(): void
    {
        Notification::fake();
        $this->actingAsUserWithPermissions(['users.view', 'users.edit']);

        Livewire::test(UserCreate::class)
            ->set('name', 'Reset Link User')
            ->set('email', 'reset@example.com')
            ->set('passwordMethod', UserCreate::PW_METHOD_RESET_LINK)
            ->call('create');

        $user = User::where('email', 'reset@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_create_rejects_duplicate_email(): void
    {
        $this->actingAsUserWithPermissions(['users.view', 'users.edit']);
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::test(UserCreate::class)
            ->set('name', 'Whoever')
            ->set('email', 'taken@example.com')
            ->set('passwordMethod', UserCreate::PW_METHOD_RESET_LINK)
            ->call('create')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_create_duplicate_from_copies_roles_programs_housing_flag(): void
    {
        $this->actingAsUserWithPermissions(['users.view', 'users.edit']);

        $role = Role::create(['name' => 'role-'.Str::uuid(), 'display_name' => 'Source Role']);
        $program = Program::factory()->create();

        $delegate = User::factory()->create(['active' => true]);
        $source = User::factory()->create([
            'is_housing_team' => true,
            'approval_delegate_id' => $delegate->id,
        ]);
        $source->roles()->attach($role);
        $source->programs()->attach($program);

        $component = Livewire::test(UserCreate::class, ['duplicate_from' => $source->id]);

        $component->assertSet('isHousingTeam', true)
            ->assertSet('selectedRoles', [$role->id])
            ->assertSet('selectedPrograms', [$program->id])
            ->assertSet('name', '')
            ->assertSet('email', '');
    }

public function test_create_duplicate_from_actually_creates_with_copied_settings(): void
    {
        Notification::fake();
        $this->actingAsUserWithPermissions(['users.view', 'users.edit']);

        $role = Role::create(['name' => 'role-'.Str::uuid(), 'display_name' => 'Source Role']);
        $program = Program::factory()->create();

        $source = User::factory()->create(['is_housing_team' => true]);
        $source->roles()->attach($role);
        $source->programs()->attach($program);

        Livewire::test(UserCreate::class, ['duplicate_from' => $source->id])
            ->set('name', 'New Clone')
            ->set('email', 'clone@example.com')
            ->set('passwordMethod', UserCreate::PW_METHOD_RESET_LINK)
            ->call('create');

        $new = User::where('email', 'clone@example.com')->first();
        $this->assertNotNull($new);
        $this->assertTrue((bool) $new->is_housing_team);
        $this->assertSame([$role->id], $new->roles->pluck('id')->all());
        $this->assertSame([$program->id], $new->programs->pluck('id')->all());
        // Delegate is intentionally NOT copied
        $this->assertNull($new->approval_delegate_id);
    }

    // -------------------------------------------------------------------
    // UserBulkOperations
    // -------------------------------------------------------------------

    public function test_bulk_assignment_merges_new_roles_without_duplicates(): void
    {
        $admin = $this->actingAsUserWithPermissions(['users.view', 'users.edit']);
        $users = User::factory()->count(2)->create();
        $role = Role::create([
            'name' => 'role-'.Str::uuid(),
            'display_name' => 'Test Role',
        ]);
        $role->permissions()->sync($this->syncPermissions(['users.view']));

        Livewire::actingAs($admin)
            ->test(UserBulkOperations::class)
            ->set('selectedUsers', [$users[0]->id, (string) $users[1]->id, $users[0]->id])
            ->set('bulkRoles', [$role->id, (string) $role->id])
            ->call('applyAssignment');

        $this->assertSame([$role->id], $users[0]->fresh()->roles->pluck('id')->all());
        $this->assertSame([$role->id], $users[1]->fresh()->roles->pluck('id')->all());
    }

    public function test_bulk_deactivate_excludes_self(): void
    {
        $admin = $this->actingAsUserWithPermissions(['users.view', 'users.edit']);
        $admin->forceFill(['active' => true])->save();
        $other = User::factory()->create(['active' => true]);

        Livewire::actingAs($admin)
            ->test(UserBulkOperations::class)
            ->set('selectedUsers', [$admin->id, $other->id])
            ->call('applyDeactivate');

        $this->assertTrue($admin->fresh()->active);
        $this->assertFalse($other->fresh()->active);
    }

    public function test_bulk_activate_reactivates_users(): void
    {
        $this->actingAsUserWithPermissions(['users.view', 'users.edit']);
        $a = User::factory()->create(['active' => false]);
        $b = User::factory()->create(['active' => false]);

        Livewire::test(UserBulkOperations::class)
            ->set('selectedUsers', [$a->id, $b->id])
            ->call('applyActivate');

        $this->assertTrue($a->fresh()->active);
        $this->assertTrue($b->fresh()->active);
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    private function actingAsUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $role = Role::create([
            'name' => 'role-'.Str::uuid(),
            'display_name' => 'Automation Role',
        ]);

        $role->permissions()->sync($this->syncPermissions($permissions));
        $user->roles()->attach($role);

        $this->actingAs($user);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, int>
     */
    private function syncPermissions(array $permissions): array
    {
        return collect($permissions)
            ->map(function (string $permission) {
                return Permission::firstOrCreate(
                    ['name' => $permission],
                    [
                        'display_name' => Str::headline($permission),
                        'description' => 'Autogenerated for testing',
                        'category' => 'testing',
                    ]
                )->id;
            })
            ->all();
    }
}
