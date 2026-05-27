<?php

namespace Tests\Support;

use App\Models\FundCode;
use App\Models\Permission;
use App\Models\RequirementDefinition;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class RegressionHelpers
{
    /**
     * Comm Log v2: link $count project-phase library items
     * (category=service_delivery_compliance) to a fund code so that
     * RequirementInstantiator::forFundCode instantiates them onto a
     * project. Replaces the legacy FundCodeRequirement(phase=project)
     * template path.
     *
     * @return Collection<int, RequirementDefinition>
     */
    public static function linkProjectRequirements(FundCode $code, int $count = 1): Collection
    {
        $defs = RequirementDefinition::factory()
            ->count($count)
            ->category(RequirementDefinition::CATEGORY_SERVICE_DELIVERY)
            ->create();

        $code->requirementDefinitions()->attach($defs->pluck('id')->all(), ['created_at' => now()]);

        return $defs;
    }

    /**
     * Create a User with the `admin` role attached and every existing
     * permission granted to that role.
     *
     * Assumes RegressionTestSeeder (or the relevant permission seeders) has
     * already run, so housing-module permissions exist in the DB. Idempotent
     * — safe to call from multiple beforeEach hooks.
     */
    public static function adminUser(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrator', 'description' => 'Regression test admin role.']
        );

        $role->permissions()->syncWithoutDetaching(Permission::pluck('id')->toArray());

        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }
}
