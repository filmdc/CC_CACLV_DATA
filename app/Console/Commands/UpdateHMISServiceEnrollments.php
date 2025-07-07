<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Participant;
use App\Models\ProgramEnrollment;
use App\Models\Service;
use App\Models\ServiceEnrollment;
use App\Services\DataTransformationService;
use League\Csv\Reader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateHMISServiceEnrollments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hmis:update-service-enrollments 
                            {file : Path to the original HMIS CSV file}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing HMIS imported participants with proper service enrollments based on original import file';

    const SIXTH_STREET_SHELTER_PROGRAM_ID = 4;

    protected DataTransformationService $transformer;

    public function __construct(DataTransformationService $transformer)
    {
        parent::__construct();
        $this->transformer = $transformer;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $isDryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Starting HMIS service enrollment update from: {$filePath}");
        if ($isDryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        try {
            $results = $this->processFile($filePath, $isDryRun);
            $this->displayResults($results);
            return 0;
        } catch (\Exception $e) {
            $this->error("Error processing file: " . $e->getMessage());
            return 1;
        }
    }

    protected function processFile(string $filePath, bool $isDryRun): array
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        
        $headers = $csv->getHeader();
        
        // Clean corrupted headers - fix the specific ". The " corruption
        $cleanedHeaders = array_map(function($header) {
            if (strpos($header, '. The ') === 0) {
                return 'OrganizationName';
            }
            return trim($header);
        }, $headers);
        
        // Set the cleaned headers
        $records = iterator_to_array($csv->getRecords());
        
        $results = [
            'total_records' => count($records),
            'participants_found' => 0,
            'services_created' => 0,
            'enrollments_created' => 0,
            'enrollments_updated' => 0,
            'errors' => [],
            'warnings' => [],
            'created_services' => []
        ];

        $progressBar = $this->output->createProgressBar(count($records));
        $progressBar->start();

        foreach ($records as $record) {
            try {
                $result = $this->processRecord($record, $isDryRun);
                $results['participants_found'] += $result['participant_found'] ? 1 : 0;
                $results['services_created'] += $result['services_created'];
                $results['enrollments_created'] += $result['enrollments_created'];
                $results['enrollments_updated'] += $result['enrollments_updated'];
                $results['warnings'] = array_merge($results['warnings'], $result['warnings']);
                $results['created_services'] = array_merge($results['created_services'], $result['created_services']);
            } catch (\Exception $e) {
                $results['errors'][] = "Row with ClientID {$record['ClientID']}: " . $e->getMessage();
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $results;
    }

    protected function processRecord(array $record, bool $isDryRun): array
    {
        $result = [
            'participant_found' => false,
            'services_created' => 0,
            'enrollments_created' => 0,
            'enrollments_updated' => 0,
            'warnings' => [],
            'created_services' => []
        ];

        // Skip if no ProgramName or ClientID
        if (empty($record['ProgramName']) || empty($record['ClientID'])) {
            return $result;
        }

        // Find participant by ClientID in notes field
        $participant = Participant::where('notes', 'LIKE', '%ClientID:' . $record['ClientID'] . '%')
            ->first();

        if (!$participant) {
            return $result;
        }

        $result['participant_found'] = true;

        // Find participant's program enrollment for program ID 4
        $programEnrollment = ProgramEnrollment::where('participant_id', $participant->id)
            ->where('program_id', self::SIXTH_STREET_SHELTER_PROGRAM_ID)
            ->first();

        if (!$programEnrollment) {
            $result['warnings'][] = "No program enrollment found for participant {$participant->full_name} (ClientID: {$record['ClientID']})";
            return $result;
        }

        // Find or create service
        $service = Service::where('program_id', self::SIXTH_STREET_SHELTER_PROGRAM_ID)
            ->where('name', $record['ProgramName'])
            ->first();

        if (!$service) {
            if (!$isDryRun) {
                $service = Service::create([
                    'program_id' => self::SIXTH_STREET_SHELTER_PROGRAM_ID,
                    'name' => $record['ProgramName'],
                    'description' => "Service created from HMIS import: {$record['ProgramName']}",
                    'service_type' => 'Housing',
                    'active' => true,
                    'created_by' => 1,
                ]);
            }
            $result['services_created'] = 1;
            $result['created_services'][] = $record['ProgramName'];
        }

        if ($service || $isDryRun) {
            // Parse enrollment and exit dates
            $enrollmentDate = null;
            if (!empty($record['EnrollDate'])) {
                $enrollmentDate = $this->transformer->parseDate($record['EnrollDate'], 'm/d/Y H:i:s A');
            }
            
            $exitDate = null;
            if (!empty($record['ExitDate'])) {
                $exitDate = $this->transformer->parseDate($record['ExitDate'], 'm/d/Y H:i:s A');
            }

            if (!$isDryRun) {
                // Check if service enrollment already exists
                $serviceEnrollment = ServiceEnrollment::where('participant_id', $participant->id)
                    ->where('service_id', $service->id)
                    ->where('program_enrollment_id', $programEnrollment->id)
                    ->first();

                if (!$serviceEnrollment) {
                    ServiceEnrollment::create([
                        'participant_id' => $participant->id,
                        'service_id' => $service->id,
                        'program_enrollment_id' => $programEnrollment->id,
                        'enrollment_date' => $enrollmentDate ? $enrollmentDate->format('Y-m-d') : $programEnrollment->enrollment_date,
                        'completion_date' => $exitDate ? $exitDate->format('Y-m-d') : null,
                        'status' => $exitDate ? 'completed' : 'enrolled',
                        'notes' => "HMIS EnrollID:" . ($record['EnrollID'] ?? 'N/A') . " (Updated via command)",
                    ]);
                    
                    $result['enrollments_created'] = 1;
                } else {
                    // Update existing enrollment if needed
                    $updates = [];
                    if ($exitDate && !$serviceEnrollment->completion_date) {
                        $updates['completion_date'] = $exitDate->format('Y-m-d');
                        $updates['status'] = 'completed';
                    }
                    
                    if (!empty($updates)) {
                        $serviceEnrollment->update($updates);
                        $result['enrollments_updated'] = 1;
                    }
                }
            } else {
                // Dry run - just count what would be done
                $serviceEnrollment = ServiceEnrollment::where('participant_id', $participant->id)
                    ->where('service_id', $service ? $service->id : 0)
                    ->where('program_enrollment_id', $programEnrollment->id)
                    ->first();

                if (!$serviceEnrollment) {
                    $result['enrollments_created'] = 1;
                } else if ($exitDate && !$serviceEnrollment->completion_date) {
                    $result['enrollments_updated'] = 1;
                }
            }
        }

        return $result;
    }

    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info("=== HMIS Service Enrollment Update Results ===");
        $this->line("Total records processed: {$results['total_records']}");
        $this->line("Participants found: {$results['participants_found']}");
        $this->line("Services created: {$results['services_created']}");
        $this->line("Service enrollments created: {$results['enrollments_created']}");
        $this->line("Service enrollments updated: {$results['enrollments_updated']}");

        if (!empty($results['created_services'])) {
            $this->newLine();
            $this->info("New services created:");
            foreach (array_unique($results['created_services']) as $serviceName) {
                $this->line("  - {$serviceName}");
            }
        }

        if (!empty($results['warnings'])) {
            $this->newLine();
            $this->warn("Warnings:");
            foreach ($results['warnings'] as $warning) {
                $this->line("  - {$warning}");
            }
        }

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error("Errors:");
            foreach ($results['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }
    }
}
