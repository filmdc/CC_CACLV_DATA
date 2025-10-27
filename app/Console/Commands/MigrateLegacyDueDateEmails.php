<?php

namespace App\Console\Commands;

use App\Models\DueDateEmail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyDueDateEmails extends Command
{
    protected $signature = 'migrate:legacy-due-date-emails {--dry-run}';

    protected $description = 'Migrate due date emails with user mapping';

    public function handle()
    {
        $this->info('======================================');
        $this->info('  Due Date Emails Migration');
        $this->info('======================================');
        $this->newLine();

        $legacyEmails = DB::connection('legacy_contracts')->table('due_date_emails')->get();
        $this->info("Found {$legacyEmails->count()} due date emails");
        $this->newLine();

        $bar = $this->output->createProgressBar($legacyEmails->count());
        $bar->start();

        $migrated = 0;
        $withUser = 0;
        $withoutUser = 0;

        foreach ($legacyEmails as $legacyEmail) {
            if (DueDateEmail::where('email', $legacyEmail->email)->exists()) {
                $bar->advance();

                continue;
            }

            // Try to find matching user
            $user = User::where('email', $legacyEmail->email)->first();

            // Generate display name
            $name = $this->generateDisplayName($legacyEmail->email, $user);

            if (! $this->option('dry-run')) {
                DueDateEmail::create([
                    'email' => $legacyEmail->email,
                    'user_id' => $user?->id,
                    'name' => $name,
                    'active' => true,
                    'preferences' => [
                        'reminder_intervals' => [30, 15, 7, 3, 1],
                        'receive_overdue_notifications' => true,
                        'receive_completion_notifications' => false,
                    ],
                    'created_at' => $legacyEmail->created_at,
                    'updated_at' => $legacyEmail->updated_at,
                ]);
            }

            $migrated++;
            if ($user) {
                $withUser++;
            } else {
                $withoutUser++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Migrated: {$migrated}");
        $this->info("Linked to users: {$withUser}");
        $this->warn("Distribution/role emails (no user): {$withoutUser}");

        return 0;
    }

    protected function generateDisplayName(string $email, ?User $user): string
    {
        if ($user) {
            return $user->name ?? $email;
        }

        // Handle planner-* emails
        if (preg_match('/planner-(.+)@/', $email, $matches)) {
            return strtoupper($matches[1]).' Planner';
        }

        // Handle _AllPlanningStaff
        if ($email === '_AllPlanningStaff@caclv.org') {
            return 'All Planning Staff';
        }

        return $email;
    }
}
