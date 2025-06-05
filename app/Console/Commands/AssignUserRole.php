<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;

class AssignUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-role {email} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign a role to a user by email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $this->error("Role {$roleName} not found.");
            return 1;
        }

        if ($user->roles()->where('role_id', $role->id)->exists()) {
            $this->info("User {$email} already has the {$roleName} role.");
            return 0;
        }

        $user->roles()->attach($role->id);
        $this->info("Successfully assigned {$roleName} role to {$email}.");
        
        return 0;
    }
}