<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Service;
use App\Models\Contract;
use App\Models\WorkPlan;
use App\Models\User;
use App\Models\SiteSetting;
use App\Models\ProgramEnrollment;
use App\Models\ServiceEnrollment;
use App\Models\PotentialDuplicate;
use App\Models\CustomImportLog;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminPanel extends Component
{
    use WithPagination, WithFileUploads;

    public $activeTab = 'overview';
    public $search = '';
    public $showCreateForm = false;
    public $showCreateUserForm = false;
    public $reportType = 'overview';
    
    // Bulk Delete Properties
    public $bulkDeleteModalVisible = false;
    public $bulkDeleteType = '';
    public $deleteConfirmation = '';
    public $deleteTestDataOnly = true;
    public $selectedDateRange = '30'; // days
    public $selectedPrograms = [];
    public $selectedSources = [];
    
    // Program Form
    public $program_name = '';
    public $program_description = '';
    public $program_start_date = '';
    public $program_end_date = '';
    public $active = true;

    // User Form
    public $user_name = '';
    public $user_email = '';
    public $user_password = '';
    public $user_password_confirmation = '';

    // Site Settings
    public $site_name = '';
    public $site_logo = null;
    public $site_favicon = null;
    public $current_logo_path = null;
    public $current_favicon_path = null;

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function showCreateProgram()
    {
        $this->showCreateForm = true;
        $this->showCreateUserForm = false;
        $this->resetProgramForm();
    }

    public function showCreateUser()
    {
        $this->showCreateUserForm = true;
        $this->showCreateForm = false;
        $this->resetUserForm();
    }

    public function resetProgramForm()
    {
        $this->program_name = '';
        $this->program_description = '';
        $this->program_start_date = '';
        $this->program_end_date = '';
        $this->active = true;
    }

    public function resetUserForm()
    {
        $this->user_name = '';
        $this->user_email = '';
        $this->user_password = '';
        $this->user_password_confirmation = '';
    }

    public function updatedProgramStartDate()
    {
        // Normalize the date input to handle keyboard entries
        $this->normalizeDate('program_start_date');
        
        $this->validateOnly('program_start_date', [
            'program_start_date' => 'required|date',
        ]);
    }

    public function updatedProgramEndDate()
    {
        // Normalize the date input to handle keyboard entries
        $this->normalizeDate('program_end_date');
        
        $this->validateOnly('program_end_date', [
            'program_end_date' => 'nullable|date|after:program_start_date',
        ]);
    }

    private function normalizeDate($property)
    {
        if (empty($this->$property)) {
            return;
        }

        $date = trim($this->$property);
        
        // Skip if already in correct format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return;
        }
        
        try {
            // Handle common keyboard input formats
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
                // MM/DD/YYYY format
                $this->$property = sprintf('%04d-%02d-%02d', $matches[3], $matches[1], $matches[2]);
            } elseif (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $date, $matches)) {
                // MM-DD-YYYY format
                $this->$property = sprintf('%04d-%02d-%02d', $matches[3], $matches[1], $matches[2]);
            } elseif (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date, $matches)) {
                // YYYY/MM/DD format
                $this->$property = sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
            } elseif (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $date, $matches)) {
                // DD.MM.YYYY format (European)
                $this->$property = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            } else {
                // Try to parse with Carbon for other formats
                $carbonDate = \Carbon\Carbon::parse($date);
                $this->$property = $carbonDate->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // If parsing fails, leave as is and let validation handle it
        }
    }

    public function createProgram()
    {
        $this->validate([
            'program_name' => 'required|string|max:255',
            'program_description' => 'nullable|string',
            'program_start_date' => 'required|date',
            'program_end_date' => 'nullable|date|after:program_start_date',
        ]);

        try {
            Program::create([
                'name' => $this->program_name,
                'description' => $this->program_description,
                'start_date' => $this->program_start_date,
                'end_date' => $this->program_end_date,
                'active' => $this->active,
            ]);

            session()->flash('success', 'Program created successfully.');
            $this->showCreateForm = false;
            $this->resetProgramForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function createUser()
    {
        $this->validate([
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|string|email|max:255|unique:users,email',
            'user_password' => 'required|string|min:8',
            'user_password_confirmation' => 'required|same:user_password',
        ]);

        try {
            User::create([
                'name' => $this->user_name,
                'email' => $this->user_email,
                'password' => bcrypt($this->user_password),
                'email_verified_at' => now(),
            ]);

            session()->flash('success', 'User created successfully.');
            $this->showCreateUserForm = false;
            $this->resetUserForm();
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function toggleProgramStatus($programId)
    {
        $program = Program::find($programId);
        if ($program) {
            $program->update(['active' => !$program->active]);
            session()->flash('success', 'Program status updated successfully.');
        }
    }

    public function mount()
    {
        $this->site_name = SiteSetting::get('site_name', 'CAP TRACK');
        $this->current_logo_path = SiteSetting::get('site_logo');
        $this->current_favicon_path = SiteSetting::get('site_favicon');
    }

    public function updateSiteSettings()
    {
        $this->validate([
            'site_name' => 'required|string|max:255',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'site_favicon' => 'nullable|image|mimes:ico,png,jpg|max:512',
        ]);

        try {
            // Update site name
            SiteSetting::set('site_name', $this->site_name);

            // Handle logo upload
            if ($this->site_logo) {
                $logoPath = $this->site_logo->store('site', 'public');
                SiteSetting::set('site_logo', $logoPath, 'file');
                $this->current_logo_path = $logoPath;
            }

            // Handle favicon upload
            if ($this->site_favicon) {
                $faviconPath = $this->site_favicon->store('site', 'public');
                SiteSetting::set('site_favicon', $faviconPath, 'file');
                $this->current_favicon_path = $faviconPath;
            }

            // Clear cache to ensure changes are reflected immediately
            SiteSetting::clearCache();

            session()->flash('success', 'Site settings updated successfully.');
            
            // Reset file inputs
            $this->site_logo = null;
            $this->site_favicon = null;
            
        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    // Bulk Delete Methods
    public function showBulkDeleteModal($type)
    {
        $this->bulkDeleteType = $type;
        $this->bulkDeleteModalVisible = true;
        $this->deleteConfirmation = '';
        $this->resetBulkDeleteOptions();
    }

    public function closeBulkDeleteModal()
    {
        $this->bulkDeleteModalVisible = false;
        $this->bulkDeleteType = '';
        $this->deleteConfirmation = '';
        $this->resetBulkDeleteOptions();
    }

    private function resetBulkDeleteOptions()
    {
        $this->deleteTestDataOnly = true;
        $this->selectedDateRange = '30';
        $this->selectedPrograms = [];
        $this->selectedSources = [];
    }

    public function getBulkDeletePreview()
    {
        $query = $this->buildBulkDeleteQuery();
        
        if ($this->bulkDeleteType === 'participants') {
            return $query->count();
        } elseif ($this->bulkDeleteType === 'households') {
            return $query->count();
        } elseif ($this->bulkDeleteType === 'test_data') {
            return [
                'participants' => $this->buildBulkDeleteQuery('participants')->count(),
                'households' => $this->buildBulkDeleteQuery('households')->count(),
                'duplicates' => $this->buildBulkDeleteQuery('duplicates')->count(),
                'import_logs' => $this->buildBulkDeleteQuery('import_logs')->count(),
            ];
        }
        
        return 0;
    }

    public function buildBulkDeleteQuery($type = null)
    {
        $targetType = $type ?: $this->bulkDeleteType;
        
        switch ($targetType) {
            case 'participants':
                $query = Participant::query();
                
                if ($this->deleteTestDataOnly) {
                    // Only delete test/import data, not manually entered participants
                    $query->where(function ($q) {
                        $q->whereHas('programEnrollments', function ($pe) {
                            $pe->whereIn('program_id', $this->selectedPrograms ?: [1]); // Default to Housing program
                        })->orWhere('notes', 'like', '%import%');
                    });
                }
                
                if ($this->selectedDateRange && $this->selectedDateRange !== 'all') {
                    $query->where('created_at', '>=', now()->subDays($this->selectedDateRange));
                }
                
                return $query;
                
            case 'households':
                $query = Household::query();
                
                if ($this->deleteTestDataOnly) {
                    // Only delete households that have no participants or only test participants
                    $query->where(function ($q) {
                        $q->whereDoesntHave('participants')
                          ->orWhereHas('participants', function ($p) {
                              $p->where('notes', 'like', '%import%');
                          });
                    });
                }
                
                if ($this->selectedDateRange && $this->selectedDateRange !== 'all') {
                    $query->where('created_at', '>=', now()->subDays($this->selectedDateRange));
                }
                
                return $query;
                
            case 'duplicates':
                $query = PotentialDuplicate::query();
                
                if ($this->selectedSources) {
                    $query->whereIn('source_type', $this->selectedSources);
                } else {
                    $query->whereIn('source_type', ['rx_office_import', 'hmis_import', 'general_import']);
                }
                
                if ($this->selectedDateRange && $this->selectedDateRange !== 'all') {
                    $query->where('created_at', '>=', now()->subDays($this->selectedDateRange));
                }
                
                return $query;
                
            case 'import_logs':
                $query = CustomImportLog::query();
                
                if ($this->selectedDateRange && $this->selectedDateRange !== 'all') {
                    $query->where('created_at', '>=', now()->subDays($this->selectedDateRange));
                }
                
                return $query;
                
            default:
                return collect();
        }
    }

    public function executeBulkDelete()
    {
        // Validate confirmation
        $expectedConfirmation = strtoupper($this->bulkDeleteType === 'test_data' ? 'DELETE TEST DATA' : 'DELETE ' . strtoupper($this->bulkDeleteType));
        
        if (strtoupper($this->deleteConfirmation) !== $expectedConfirmation) {
            session()->flash('error', 'Confirmation text does not match. Please type exactly: ' . $expectedConfirmation);
            return;
        }

        try {
            DB::beginTransaction();

            $deletedCounts = [];

            if ($this->bulkDeleteType === 'test_data') {
                // Delete in correct order to avoid foreign key constraints
                
                // 1. Delete service enrollments first
                $serviceEnrollments = ServiceEnrollment::whereHas('participant', function ($q) {
                    $participantQuery = $this->buildBulkDeleteQuery('participants');
                    $q->whereIn('id', $participantQuery->pluck('id'));
                })->get();
                
                foreach ($serviceEnrollments as $enrollment) {
                    $enrollment->delete();
                }
                $deletedCounts['service_enrollments'] = $serviceEnrollments->count();
                
                // 2. Delete program enrollments
                $programEnrollments = ProgramEnrollment::whereHas('participant', function ($q) {
                    $participantQuery = $this->buildBulkDeleteQuery('participants');
                    $q->whereIn('id', $participantQuery->pluck('id'));
                })->get();
                
                foreach ($programEnrollments as $enrollment) {
                    $enrollment->delete();
                }
                $deletedCounts['program_enrollments'] = $programEnrollments->count();
                
                // 3. Delete potential duplicates
                $duplicates = $this->buildBulkDeleteQuery('duplicates')->get();
                foreach ($duplicates as $duplicate) {
                    $duplicate->delete();
                }
                $deletedCounts['duplicates'] = $duplicates->count();
                
                // 4. Delete participants
                $participants = $this->buildBulkDeleteQuery('participants')->get();
                foreach ($participants as $participant) {
                    $participant->delete();
                }
                $deletedCounts['participants'] = $participants->count();
                
                // 5. Delete empty households
                $households = $this->buildBulkDeleteQuery('households')->get();
                foreach ($households as $household) {
                    $household->delete();
                }
                $deletedCounts['households'] = $households->count();
                
                // 6. Delete import logs
                $importLogs = $this->buildBulkDeleteQuery('import_logs')->get();
                foreach ($importLogs as $log) {
                    $log->delete();
                }
                $deletedCounts['import_logs'] = $importLogs->count();
                
            } else {
                // Single type deletion
                $items = $this->buildBulkDeleteQuery()->get();
                
                foreach ($items as $item) {
                    if ($this->bulkDeleteType === 'participants') {
                        // Delete related enrollments first
                        $item->serviceEnrollments()->delete();
                        $item->programEnrollments()->delete();
                    }
                    $item->delete();
                }
                
                $deletedCounts[$this->bulkDeleteType] = $items->count();
            }

            DB::commit();

            // Log the bulk delete operation
            Log::info('Bulk delete operation completed', [
                'type' => $this->bulkDeleteType,
                'user_id' => auth()->id(),
                'deleted_counts' => $deletedCounts,
                'options' => [
                    'test_data_only' => $this->deleteTestDataOnly,
                    'date_range' => $this->selectedDateRange,
                    'programs' => $this->selectedPrograms,
                    'sources' => $this->selectedSources,
                ]
            ]);

            $totalDeleted = array_sum($deletedCounts);
            $summary = collect($deletedCounts)->map(function ($count, $type) {
                return "{$count} {$type}";
            })->implode(', ');

            session()->flash('success', "Bulk delete completed successfully. Deleted: {$summary}");
            $this->closeBulkDeleteModal();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete operation failed', [
                'type' => $this->bulkDeleteType,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            
            session()->flash('error', 'Bulk delete failed: ' . $e->getMessage());
        }
    }

    public function getAvailablePrograms()
    {
        return Program::orderBy('name')->get();
    }

    public function getAvailableSources()
    {
        return [
            'rx_office_import' => 'RX Office Import',
            'hmis_import' => 'HMIS Import', 
            'general_import' => 'General Import',
            'manual' => 'Manual Entry'
        ];
    }

    private function getCSBGData()
    {
        $data = [];
        $totalParticipants = Participant::count();
        $totalHouseholds = Household::count();

        // Helper function to safely get array from JSON field
        $getArrayField = function($field) {
            if (is_array($field)) return $field;
            if (is_string($field)) return json_decode($field, true) ?? [];
            return [];
        };

        // A. & B. Totals
        $data['total_individuals'] = $totalParticipants;
        $data['total_households'] = $totalHouseholds;

        // 1. Gender data
        $data['gender'] = [
            'Male' => Participant::where('gender', 'Male')->count(),
            'Female' => Participant::where('gender', 'Female')->count(),
            'Other' => Participant::where('gender', 'Other')->count(),
            'Unknown/not reported' => Participant::whereNull('gender')->orWhere('gender', 'Unknown/not reported')->count(),
        ];
        $data['gender']['total'] = array_sum($data['gender']);

        // 2. Age groups using calculated age
        $participants = Participant::get();
        $data['age_groups'] = [
            '0-5' => $participants->filter(fn($p) => $p->age >= 0 && $p->age <= 5)->count(),
            '6-13' => $participants->filter(fn($p) => $p->age >= 6 && $p->age <= 13)->count(),
            '14-17' => $participants->filter(fn($p) => $p->age >= 14 && $p->age <= 17)->count(),
            '18-24' => $participants->filter(fn($p) => $p->age >= 18 && $p->age <= 24)->count(),
            '25-44' => $participants->filter(fn($p) => $p->age >= 25 && $p->age <= 44)->count(),
            '45-54' => $participants->filter(fn($p) => $p->age >= 45 && $p->age <= 54)->count(),
            '55-59' => $participants->filter(fn($p) => $p->age >= 55 && $p->age <= 59)->count(),
            '60-64' => $participants->filter(fn($p) => $p->age >= 60 && $p->age <= 64)->count(),
            '65-74' => $participants->filter(fn($p) => $p->age >= 65 && $p->age <= 74)->count(),
            '75+' => $participants->filter(fn($p) => $p->age >= 75)->count(),
            'Unknown/not reported' => $participants->filter(fn($p) => !$p->date_of_birth)->count(),
        ];
        $data['age']['total'] = array_sum($data['age_groups']);

        // 3. Education Levels
        $data['education'] = [
            'Grades 0-8' => Participant::where('education_level', 'Grades 0-8')->count(),
            'Grades 9-12/Non-Graduate' => Participant::where('education_level', 'Grades 9-12/Non-Graduate')->count(),
            'High School Graduate' => Participant::where('education_level', 'High School Graduate')->count(),
            'GED/Equivalency Diploma' => Participant::where('education_level', 'GED/Equivalency Diploma')->count(),
            '12 grade + Some Post-Secondary' => Participant::where('education_level', '12 grade + Some Post-Secondary')->count(),
            '2 or 4 years College Graduate' => Participant::where('education_level', '2 or 4 years College Graduate')->count(),
            'Graduate of other post-secondary school' => Participant::where('education_level', 'Graduate of other post-secondary school')->count(),
            'Unknown/not reported' => Participant::whereNull('education_level')->orWhere('education_level', 'Unknown/not reported')->count(),
        ];
        $data['education']['total'] = array_sum($data['education']);

        // 4. Disconnected Youth
        $data['disconnected_youth'] = [
            'Youth ages 14-24 who are neither working or in school' => $participants->filter(function($p) {
                return $p->age >= 14 && $p->age <= 24 && 
                       (!in_array('Employed Full-Time', $p->work_status ?? []) && 
                        !in_array('Employed Part-Time', $p->work_status ?? []) &&
                        !in_array('In School', $p->education_level ?? []));
            })->count(),
        ];
        $data['disconnected_youth']['total'] = array_sum($data['disconnected_youth']);

        // 5. Health
        $data['health'] = [
            'disabling_condition' => [
                'Yes' => Participant::where('disability_status', 'Yes')->count(),
                'No' => Participant::where('disability_status', 'No')->count(),
                'Unknown' => Participant::whereNull('disability_status')->orWhere('disability_status', 'Unknown')->count(),
            ],
            'health_insurance' => [
                'Yes' => Participant::where('health_insurance_status', 'Yes')->count(),
                'No' => Participant::where('health_insurance_status', 'No')->count(),
                'Unknown' => Participant::whereNull('health_insurance_status')->orWhere('health_insurance_status', 'Unknown')->count(),
            ]
        ];

        // Health Insurance Sources
        $data['health_insurance_sources'] = [
            'Medicaid' => $participants->filter(fn($p) => in_array('Medicaid', $p->health_insurance_sources ?? []))->count(),
            'Medicare' => $participants->filter(fn($p) => in_array('Medicare', $p->health_insurance_sources ?? []))->count(),
            'State Children\'s Health Insurance Program' => $participants->filter(fn($p) => in_array('State Children\'s Health Insurance Program', $p->health_insurance_sources ?? []))->count(),
            'State Health Insurance for Adults' => $participants->filter(fn($p) => in_array('State Health Insurance for Adults', $p->health_insurance_sources ?? []))->count(),
            'Military Health Care' => $participants->filter(fn($p) => in_array('Military Health Care', $p->health_insurance_sources ?? []))->count(),
            'Direct-Purchase' => $participants->filter(fn($p) => in_array('Direct-Purchase', $p->health_insurance_sources ?? []))->count(),
            'Employment Based' => $participants->filter(fn($p) => in_array('Employment Based', $p->health_insurance_sources ?? []))->count(),
            'Unknown/not reported' => $participants->filter(fn($p) => empty($p->health_insurance_sources))->count(),
        ];
        $data['health_insurance_sources']['total'] = array_sum($data['health_insurance_sources']);

        // 6. Ethnicity/Race
        $data['ethnicity'] = [
            'Hispanic, Latino or Spanish Origins' => Participant::where('ethnicity', 'Hispanic, Latino or Spanish Origins')->count(),
            'Not Hispanic, Latino or Spanish Origins' => Participant::where('ethnicity', 'Not Hispanic, Latino or Spanish Origins')->count(),
            'Unknown/not reported' => Participant::whereNull('ethnicity')->orWhere('ethnicity', 'Unknown/not reported')->count(),
        ];
        $data['ethnicity']['total'] = array_sum($data['ethnicity']);

        // Race (stored as JSON array)
        $data['race'] = [
            'American Indian or Alaska Native' => $participants->filter(fn($p) => in_array('American Indian or Alaska Native', $getArrayField($p->race)))->count(),
            'Asian' => $participants->filter(fn($p) => in_array('Asian', $getArrayField($p->race)))->count(),
            'Black or African American' => $participants->filter(fn($p) => in_array('Black or African American', $getArrayField($p->race)))->count(),
            'Native Hawaiian and Other Pacific Islander' => $participants->filter(fn($p) => in_array('Native Hawaiian and Other Pacific Islander', $getArrayField($p->race)))->count(),
            'White' => $participants->filter(fn($p) => in_array('White', $getArrayField($p->race)))->count(),
            'Other' => $participants->filter(fn($p) => in_array('Other', $getArrayField($p->race)))->count(),
            'Multi-race (two or more of the above)' => $participants->filter(fn($p) => count($getArrayField($p->race)) > 1)->count(),
            'Unknown/not reported' => $participants->filter(function($p) use ($getArrayField) {
                $race = $getArrayField($p->race);
                return empty($race) || in_array('Unknown/not reported', $race);
            })->count(),
        ];
        $data['race']['total'] = $totalParticipants;

        // 7. Military Status
        $data['military_status'] = [
            'Veteran' => Participant::where('military_status', 'Veteran')->count(),
            'Active Military' => Participant::where('military_status', 'Active Military')->count(),
            'Never Served in the Military' => Participant::where('military_status', 'Never Served in the Military')->count(),
            'Unknown/not reported' => Participant::whereNull('military_status')->orWhere('military_status', 'Unknown/not reported')->count(),
        ];
        $data['military_status']['total'] = array_sum($data['military_status']);

        // 8. Work Status (Individuals 18+)
        $adults = $participants->filter(fn($p) => $p->age >= 18);
        $data['work_status'] = [
            'Employed Full-Time' => $adults->filter(fn($p) => $p->work_status === 'Employed Full-Time')->count(),
            'Employed Part-Time' => $adults->filter(fn($p) => $p->work_status === 'Employed Part-Time')->count(),
            'Migrant Seasonal Farm Worker' => $adults->filter(fn($p) => $p->work_status === 'Migrant Seasonal Farm Worker')->count(),
            'Unemployed (Short-Term, 6 months or less)' => $adults->filter(fn($p) => $p->work_status === 'Unemployed (Short-Term, 6 months or less)')->count(),
            'Unemployed (Long-Term, more than 6 months)' => $adults->filter(fn($p) => $p->work_status === 'Unemployed (Long-Term, more than 6 months)')->count(),
            'Unemployed (Not in Labor Force)' => $adults->filter(fn($p) => $p->work_status === 'Unemployed (Not in Labor Force)')->count(),
            'Retired' => $adults->filter(fn($p) => $p->work_status === 'Retired')->count(),
            'Unknown/not reported' => $adults->filter(fn($p) => !$p->work_status || $p->work_status === 'Unknown/not reported')->count(),
        ];
        $data['work_status']['total'] = $adults->count();

        // HOUSEHOLD LEVEL CHARACTERISTICS

        // 9. Household Type
        $data['household_type'] = [
            'Single Person' => Household::where('household_type', 'Single Person')->count(),
            'Two Adults NO Children' => Household::where('household_type', 'Two Adults NO Children')->count(),
            'Single Parent Female' => Household::where('household_type', 'Single Parent Female')->count(),
            'Single Parent Male' => Household::where('household_type', 'Single Parent Male')->count(),
            'Two Parent Household' => Household::where('household_type', 'Two Parent Household')->count(),
            'Non-related Adults with Children' => Household::where('household_type', 'Non-related Adults with Children')->count(),
            'Multigenerational Household' => Household::where('household_type', 'Multigenerational Household')->count(),
            'Other' => Household::where('household_type', 'Other')->count(),
            'Unknown/not reported' => Household::whereNull('household_type')->orWhere('household_type', 'Unknown/not reported')->count(),
        ];
        $data['household_type']['total'] = array_sum($data['household_type']);

        // 10. Household Size
        $data['household_size'] = [
            'Single Person' => Household::where('household_size', 1)->count(),
            'Two' => Household::where('household_size', 2)->count(),
            'Three' => Household::where('household_size', 3)->count(),
            'Four' => Household::where('household_size', 4)->count(),
            'Five' => Household::where('household_size', 5)->count(),
            'Six or more' => Household::where('household_size', '>=', 6)->count(),
            'Unknown/not reported' => Household::whereNull('household_size')->count(),
        ];
        $data['household_size']['total'] = array_sum($data['household_size']);

        // 11. Housing
        $data['housing'] = [
            'Own' => Household::where('housing_status', 'Own')->count(),
            'Rent' => Household::where('housing_status', 'Rent')->count(),
            'Other permanent housing' => Household::where('housing_status', 'Other permanent housing')->count(),
            'Homeless' => Household::where('housing_status', 'Homeless')->count(),
            'Other' => Household::where('housing_status', 'Other')->count(),
            'Unknown/not reported' => Household::whereNull('housing_status')->orWhere('housing_status', 'Unknown/not reported')->count(),
        ];
        $data['housing']['total'] = array_sum($data['housing']);

        // 12. Level of Household Income (% of HHS Guideline)
        $data['income_level'] = [
            'Up to 50%' => Household::where('hhs_poverty_percentage', '<=', 50)->count(),
            '51% to 75%' => Household::whereBetween('hhs_poverty_percentage', [51, 75])->count(),
            '76% to 100%' => Household::whereBetween('hhs_poverty_percentage', [76, 100])->count(),
            '101% to 125%' => Household::whereBetween('hhs_poverty_percentage', [101, 125])->count(),
            '126% to 150%' => Household::whereBetween('hhs_poverty_percentage', [126, 150])->count(),
            '151% to 175%' => Household::whereBetween('hhs_poverty_percentage', [151, 175])->count(),
            '176% to 200%' => Household::whereBetween('hhs_poverty_percentage', [176, 200])->count(),
            '201% to 250%' => Household::whereBetween('hhs_poverty_percentage', [201, 250])->count(),
            '250% and over' => Household::where('hhs_poverty_percentage', '>', 250)->count(),
            'Unknown/not reported' => Household::whereNull('hhs_poverty_percentage')->count(),
        ];
        $data['income_level']['total'] = array_sum($data['income_level']);

        // 13. Sources of Household Income (from participants' income_sources)
        $households = Household::with('participants')->get();
        $data['income_sources'] = [
            'Income from Employment Only' => $households->filter(function($h) use ($getArrayField) {
                return $h->participants->some(function($p) use ($getArrayField) {
                    $income = $getArrayField($p->income_sources);
                    return in_array('Employment', $income) && count($income) == 1;
                });
            })->count(),
            'Income from Employment and Other Income Source' => $households->filter(function($h) use ($getArrayField) {
                return $h->participants->some(function($p) use ($getArrayField) {
                    $income = $getArrayField($p->income_sources);
                    $other = $getArrayField($p->other_income_sources);
                    return in_array('Employment', $income) && !empty($other);
                });
            })->count(),
            'Income from Employment, Other Income Source, and Non-Cash Benefits' => $households->filter(function($h) use ($getArrayField) {
                return $h->participants->some(function($p) use ($getArrayField) {
                    $income = $getArrayField($p->income_sources);
                    $other = $getArrayField($p->other_income_sources);
                    $benefits = $getArrayField($p->non_cash_benefits);
                    return in_array('Employment', $income) && !empty($other) && !empty($benefits);
                });
            })->count(),
            'Income from Employment and Non-Cash Benefits' => $households->filter(function($h) use ($getArrayField) {
                return $h->participants->some(function($p) use ($getArrayField) {
                    $income = $getArrayField($p->income_sources);
                    $benefits = $getArrayField($p->non_cash_benefits);
                    return in_array('Employment', $income) && !empty($benefits);
                });
            })->count(),
            'Other Income Source Only' => $households->filter(function($h) use ($getArrayField) {
                return $h->participants->some(function($p) use ($getArrayField) {
                    $income = $getArrayField($p->income_sources);
                    $other = $getArrayField($p->other_income_sources);
                    return !empty($other) && !in_array('Employment', $income);
                });
            })->count(),
            'Other Income Source and Non-Cash Benefits' => $households->filter(function($h) use ($getArrayField) {
                return $h->participants->some(function($p) use ($getArrayField) {
                    $other = $getArrayField($p->other_income_sources);
                    $benefits = $getArrayField($p->non_cash_benefits);
                    return !empty($other) && !empty($benefits);
                });
            })->count(),
            'No Income' => $households->filter(function($h) use ($getArrayField) {
                return $h->participants->every(function($p) use ($getArrayField) {
                    $income = $getArrayField($p->income_sources);
                    $other = $getArrayField($p->other_income_sources);
                    return empty($income) && empty($other);
                });
            })->count(),
            'Non-Cash Benefits Only' => $households->filter(function($h) use ($getArrayField) {
                return $h->participants->some(function($p) use ($getArrayField) {
                    $income = $getArrayField($p->income_sources);
                    $other = $getArrayField($p->other_income_sources);
                    $benefits = $getArrayField($p->non_cash_benefits);
                    return !empty($benefits) && empty($income) && empty($other);
                });
            })->count(),
            'Unknown/not reported' => $households->filter(function($h) use ($getArrayField) {
                return $h->participants->every(function($p) use ($getArrayField) {
                    $income = $getArrayField($p->income_sources);
                    $other = $getArrayField($p->other_income_sources);
                    $benefits = $getArrayField($p->non_cash_benefits);
                    return empty($income) && empty($other) && empty($benefits);
                });
            })->count(),
        ];
        $data['income_sources']['total'] = array_sum($data['income_sources']);

        // 14. Other Income Source
        $data['other_income_sources'] = [
            'TANF' => $participants->filter(fn($p) => in_array('TANF', $getArrayField($p->other_income_sources)))->count(),
            'Supplemental Security Income (SSI)' => $participants->filter(fn($p) => in_array('Supplemental Security Income (SSI)', $getArrayField($p->other_income_sources)))->count(),
            'Social Security Disability Income (SSDI)' => $participants->filter(fn($p) => in_array('Social Security Disability Income (SSDI)', $getArrayField($p->other_income_sources)))->count(),
            'VA Service-Connected Disability Compensation' => $participants->filter(fn($p) => in_array('VA Service-Connected Disability Compensation', $getArrayField($p->other_income_sources)))->count(),
            'VA Non-Service Connected Disability Pension' => $participants->filter(fn($p) => in_array('VA Non-Service Connected Disability Pension', $getArrayField($p->other_income_sources)))->count(),
            'Private Disability Insurance' => $participants->filter(fn($p) => in_array('Private Disability Insurance', $getArrayField($p->other_income_sources)))->count(),
            'Worker\'s Compensation' => $participants->filter(fn($p) => in_array('Worker\'s Compensation', $getArrayField($p->other_income_sources)))->count(),
            'Retirement Income from Social Security' => $participants->filter(fn($p) => in_array('Retirement Income from Social Security', $getArrayField($p->other_income_sources)))->count(),
            'Pension' => $participants->filter(fn($p) => in_array('Pension', $getArrayField($p->other_income_sources)))->count(),
            'Child Support' => $participants->filter(fn($p) => in_array('Child Support', $getArrayField($p->other_income_sources)))->count(),
            'Alimony or other Spousal Support' => $participants->filter(fn($p) => in_array('Alimony or other Spousal Support', $getArrayField($p->other_income_sources)))->count(),
            'Unemployment Insurance' => $participants->filter(fn($p) => in_array('Unemployment Insurance', $getArrayField($p->other_income_sources)))->count(),
            'EITC' => $participants->filter(fn($p) => in_array('EITC', $getArrayField($p->other_income_sources)))->count(),
            'Other' => $participants->filter(fn($p) => in_array('Other', $getArrayField($p->other_income_sources)))->count(),
            'Unknown/not reported' => $participants->filter(fn($p) => empty($getArrayField($p->other_income_sources)))->count(),
        ];

        // 15. Non-Cash Benefits
        $data['non_cash_benefits'] = [
            'SNAP' => $participants->filter(fn($p) => in_array('SNAP', $getArrayField($p->non_cash_benefits)))->count(),
            'WIC' => $participants->filter(fn($p) => in_array('WIC', $getArrayField($p->non_cash_benefits)))->count(),
            'LIHEAP' => $participants->filter(fn($p) => in_array('LIHEAP', $getArrayField($p->non_cash_benefits)))->count(),
            'Housing Choice Voucher' => $participants->filter(fn($p) => in_array('Housing Choice Voucher', $getArrayField($p->non_cash_benefits)))->count(),
            'Public Housing' => $participants->filter(fn($p) => in_array('Public Housing', $getArrayField($p->non_cash_benefits)))->count(),
            'Permanent Supportive Housing' => $participants->filter(fn($p) => in_array('Permanent Supportive Housing', $getArrayField($p->non_cash_benefits)))->count(),
            'HUD-VASH' => $participants->filter(fn($p) => in_array('HUD-VASH', $getArrayField($p->non_cash_benefits)))->count(),
            'Childcare Voucher' => $participants->filter(fn($p) => in_array('Childcare Voucher', $getArrayField($p->non_cash_benefits)))->count(),
            'Affordable Care Act Subsidy' => $participants->filter(fn($p) => in_array('Affordable Care Act Subsidy', $getArrayField($p->non_cash_benefits)))->count(),
            'Other' => $participants->filter(fn($p) => in_array('Other', $getArrayField($p->non_cash_benefits)))->count(),
            'Unknown/not reported' => $participants->filter(fn($p) => empty($getArrayField($p->non_cash_benefits)))->count(),
        ];

        return $data;
    }

    public function exportCSBGReport()
    {
        // TODO: Implement CSV/PDF export functionality
        session()->flash('success', 'CSBG Report export functionality coming soon.');
    }

    public function render()
    {
        $data = [];

        switch ($this->activeTab) {
            case 'overview':
                $data['systemStats'] = [
                    'total_participants' => Participant::count(),
                    'total_households' => Household::count(),
                    'total_programs' => Program::count(),
                    'active_programs' => Program::where('active', true)->count(),
                    'total_services' => Service::count(),
                    'total_contracts' => Contract::count(),
                    'total_work_plans' => WorkPlan::count(),
                    'total_users' => User::count(),
                ];
                break;

            case 'programs':
                $data['programs'] = Program::when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('description', 'like', '%' . $this->search . '%');
                })
                ->withCount(['services', 'workPlans'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
                break;

            case 'users':
                $data['users'] = User::when($this->search, function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%');
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);
                break;

            case 'reports':
                $data['reportData'] = [
                    'participants_by_month' => Participant::selectRaw('strftime("%Y-%m", created_at) as month, COUNT(*) as count')
                        ->groupBy('month')
                        ->orderBy('month', 'desc')
                        ->limit(12)
                        ->get(),
                    'households_by_poverty_level' => Household::whereNotNull('hhs_poverty_percentage')
                        ->selectRaw('
                            CASE 
                                WHEN hhs_poverty_percentage <= 50 THEN "0-50%"
                                WHEN hhs_poverty_percentage <= 100 THEN "51-100%"
                                WHEN hhs_poverty_percentage <= 150 THEN "101-150%"
                                WHEN hhs_poverty_percentage <= 200 THEN "151-200%"
                                ELSE "200%+"
                            END as poverty_range,
                            COUNT(*) as count
                        ')
                        ->groupBy('poverty_range')
                        ->get(),
                ];
                
                // Add CSBG data if report type is CSBG
                if ($this->reportType === 'csbg') {
                    $data['csbgData'] = $this->getCSBGData();
                }
                break;

            case 'settings':
                // Site settings data is already loaded in mount()
                break;
                
            case 'data_management':
                $data['dataStats'] = [
                    'total_participants' => Participant::count(),
                    'total_households' => Household::count(),
                    'imported_participants' => Participant::where('notes', 'like', '%import%')->count(),
                    'potential_duplicates' => PotentialDuplicate::count(),
                    'pending_duplicates' => PotentialDuplicate::pending()->count(),
                    'import_logs' => CustomImportLog::count(),
                    'recent_imports' => CustomImportLog::with('template')
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get(),
                ];
                break;
        }

        return view('livewire.admin-panel', $data)->layout('components.layouts.app');
    }
}
