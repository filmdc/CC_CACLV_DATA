<?php

namespace App\Traits;

trait WithPermissionChecks
{
    /**
     * Check if the current user has a permission.
     */
    public function can(string $permission): bool
    {
        return auth()->user() && auth()->user()->hasPermission($permission);
    }

    /**
     * Check if the current user has any of the given permissions.
     */
    public function canAny(array $permissions): bool
    {
        return auth()->user() && auth()->user()->hasAnyPermission($permissions);
    }

    /**
     * Check if the current user has all of the given permissions.
     */
    public function canAll(array $permissions): bool
    {
        return auth()->user() && auth()->user()->hasAllPermissions($permissions);
    }

    /**
     * Check if the current user has a role.
     */
    public function hasRole(string $role): bool
    {
        return auth()->user() && auth()->user()->hasRole($role);
    }

    /**
     * Check if the current user is an admin.
     */
    public function isAdmin(): bool
    {
        return auth()->user() && auth()->user()->isAdmin();
    }

    /**
     * Authorize an action based on permission or abort with 403.
     */
    public function authorizeAction(string $permission, ?string $message = null): void
    {
        if (!$this->can($permission)) {
            abort(403, $message ?? "You do not have permission to perform this action.");
        }
    }
}