<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Console\Command;

class GrantContractPermission extends Command
{
    protected $signature = 'user:grant-contract-permission {email}';
    
    protected $description = 'Grant contracts.manage permission to a user';
    
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return Command::FAILURE;
        }
        
        // Find or create the permission
        $permission = Permission::firstOrCreate(
            ['name' => 'contracts.manage'],
            [
                'display_name' => 'Manage Contracts',
                'description' => 'Manage contracts and notifications',
                'group' => 'Contracts'
            ]
        );
        
        // Check if user already has this permission through roles
        if ($user->hasPermission('contracts.manage')) {
            $this->info("User already has 'contracts.manage' permission through their roles");
            return Command::SUCCESS;
        }
        
        // Find or create a role for contract managers
        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'contract_manager'],
            [
                'display_name' => 'Contract Manager',
                'description' => 'Can manage contracts and notifications'
            ]
        );
        
        // Attach permission to role
        if (!$role->permissions->contains($permission)) {
            $role->permissions()->attach($permission);
        }
        
        // Attach role to user
        if (!$user->roles->contains($role)) {
            $user->roles()->attach($role);
            $this->info("Role 'contract_manager' with 'contracts.manage' permission granted to {$user->name} ({$email})");
        }
        
        // Also check if user is admin
        if ($user->hasRole('admin')) {
            $this->info("User is also an admin - they should have full access");
        }
        
        return Command::SUCCESS;
    }
}