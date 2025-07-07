<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Participant;
use App\Models\Household;
use App\Models\ServiceEnrollment;
use App\Models\ProgramEnrollment;
use App\Models\PotentialDuplicate;
use App\Models\CustomImportLog;

class ClearTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:clear-test {--type=all : Type of data to clear (all, participants, households, duplicates, imports)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear test data from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        
        if (!$this->confirm("⚠️  This will PERMANENTLY delete data. Are you sure you want to continue?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        try {
            DB::beginTransaction();
            
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            switch ($type) {
                case 'participants':
                    $this->clearParticipants();
                    break;
                    
                case 'households':
                    $this->clearHouseholds();
                    break;
                    
                case 'duplicates':
                    $this->clearDuplicates();
                    break;
                    
                case 'imports':
                    $this->clearImportLogs();
                    break;
                    
                case 'all':
                default:
                    $this->clearAll();
                    break;
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            DB::commit();
            $this->info("✅ Test data cleared successfully!");
            
        } catch (\Exception $e) {
            // Re-enable foreign key checks even if there's an error
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            DB::rollBack();
            $this->error("❌ Error clearing data: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function clearParticipants()
    {
        $count = Participant::count();
        
        // Delete in correct order to avoid foreign key constraints
        ServiceEnrollment::truncate();
        ProgramEnrollment::truncate();
        Participant::truncate();
        
        $this->info("Deleted {$count} participants and their enrollments");
    }

    protected function clearHouseholds()
    {
        $participantCount = Participant::count();
        $householdCount = Household::count();
        
        // Delete in correct order
        ServiceEnrollment::truncate();
        ProgramEnrollment::truncate();
        Participant::truncate();
        Household::truncate();
        
        $this->info("Deleted {$householdCount} households and {$participantCount} participants");
    }

    protected function clearDuplicates()
    {
        $count = PotentialDuplicate::count();
        PotentialDuplicate::truncate();
        $this->info("Deleted {$count} potential duplicates");
    }

    protected function clearImportLogs()
    {
        $count = CustomImportLog::count();
        CustomImportLog::truncate();
        $this->info("Deleted {$count} import logs");
    }

    protected function clearAll()
    {
        $stats = [
            'participants' => Participant::count(),
            'households' => Household::count(),
            'duplicates' => PotentialDuplicate::count(),
            'import_logs' => CustomImportLog::count(),
        ];
        
        // Clear in order to avoid foreign key issues
        ServiceEnrollment::truncate();
        ProgramEnrollment::truncate();
        Participant::truncate();
        Household::truncate();
        PotentialDuplicate::truncate();
        CustomImportLog::truncate();
        
        $this->info("Deleted all test data:");
        $this->info("  - {$stats['participants']} participants");
        $this->info("  - {$stats['households']} households");
        $this->info("  - {$stats['duplicates']} potential duplicates");
        $this->info("  - {$stats['import_logs']} import logs");
    }
}
