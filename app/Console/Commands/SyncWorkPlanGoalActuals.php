<?php

namespace App\Console\Commands;

use App\Models\WorkPlanGoal;
use App\Models\WorkPlan;
use Illuminate\Console\Command;

class SyncWorkPlanGoalActuals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workplan:sync-actuals 
                            {--work-plan-id= : Sync goals for a specific work plan}
                            {--program-id= : Sync goals for all work plans in a program}
                            {--active-only : Only sync goals for active work plans}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize work plan goal actual numbers with current enrollment data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting work plan goal synchronization...');

        $query = WorkPlanGoal::query();

        // Filter by work plan ID if provided
        if ($workPlanId = $this->option('work-plan-id')) {
            $query->where('work_plan_id', $workPlanId);
            $this->info("Filtering by work plan ID: {$workPlanId}");
        }

        // Filter by program ID if provided
        if ($programId = $this->option('program-id')) {
            $query->whereHas('workPlan', function ($q) use ($programId) {
                $q->where('program_id', $programId);
            });
            $this->info("Filtering by program ID: {$programId}");
        }

        // Filter by active work plans if requested
        if ($this->option('active-only')) {
            $query->whereHas('workPlan', function ($q) {
                $q->where('active', true);
            });
            $this->info("Filtering by active work plans only");
        }

        $goals = $query->with(['workPlan.program', 'service'])->get();
        $totalGoals = $goals->count();

        if ($totalGoals === 0) {
            $this->warn('No work plan goals found matching the criteria.');
            return 0;
        }

        $this->info("Found {$totalGoals} goals to sync.");
        $progressBar = $this->output->createProgressBar($totalGoals);
        $progressBar->start();

        $updatedCount = 0;
        $errorCount = 0;

        foreach ($goals as $goal) {
            try {
                $oldActual = $goal->actual_number;
                $goal->updateActualFromServiceData();
                
                if ($oldActual !== $goal->actual_number) {
                    $updatedCount++;
                    $this->line(''); // New line for clean output
                    $this->info(sprintf(
                        'Updated Goal "%s" (ID: %d): %d → %d',
                        $goal->goal_name,
                        $goal->id,
                        $oldActual,
                        $goal->actual_number
                    ));
                }
                
                $progressBar->advance();
            } catch (\Exception $e) {
                $errorCount++;
                $this->line(''); // New line for clean output
                $this->error(sprintf(
                    'Error updating Goal "%s" (ID: %d): %s',
                    $goal->goal_name,
                    $goal->id,
                    $e->getMessage()
                ));
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->line(''); // New line after progress bar
        
        $this->info('Synchronization complete!');
        $this->info("Total goals processed: {$totalGoals}");
        $this->info("Goals updated: {$updatedCount}");
        
        if ($errorCount > 0) {
            $this->warn("Errors encountered: {$errorCount}");
        }

        // Show summary of work plans affected
        $affectedWorkPlans = $goals->pluck('workPlan')->unique('id');
        $this->info("\nAffected work plans:");
        foreach ($affectedWorkPlans as $workPlan) {
            $this->line(sprintf(
                '- %s (%s - %d)',
                $workPlan->name,
                $workPlan->program->name,
                $workPlan->year
            ));
        }

        return 0;
    }
}