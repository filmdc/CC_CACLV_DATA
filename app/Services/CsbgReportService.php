<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\ReferenceDataSet;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CsbgReportService
{
    /**
     * Generate CSBG Module 4 Section C report data
     */
    public function generateReport(array $filters = []): array
    {
        // Generate cache key based on filters
        $cacheKey = $this->generateCacheKey($filters);
        
        // Check if report is cached and still valid
        $cachedReport = Cache::get($cacheKey);
        if ($cachedReport) {
            Log::info('CSBG report served from cache', ['cache_key' => $cacheKey]);
            return $cachedReport;
        }
        
        // Generate fresh report
        Log::info('Generating fresh CSBG report', ['filters' => $filters]);
        $participants = $this->getParticipants($filters);
        
        $reportData = [
            'report_metadata' => $this->getReportMetadata($filters),
            'participant_count' => $participants->count(),
            'demographics' => $this->generateDemographicsReport($participants),
            'characteristics' => $this->generateCharacteristicsReport($participants),
            'employment_income' => $this->generateEmploymentIncomeReport($participants),
            'health_housing' => $this->generateHealthHousingReport($participants),
        ];
        
        // Cache the report for 1 hour (or longer for older data)
        $cacheMinutes = $this->calculateCacheDuration($filters);
        Cache::put($cacheKey, $reportData, now()->addMinutes($cacheMinutes));
        
        Log::info('CSBG report cached', [
            'cache_key' => $cacheKey,
            'cache_minutes' => $cacheMinutes,
            'participant_count' => $reportData['participant_count']
        ]);
        
        return $reportData;
    }

    /**
     * Get filtered participants for the report with deduplication
     */
    protected function getParticipants(array $filters): Collection
    {
        $query = Participant::with(['household', 'programEnrollments.program', 'serviceEnrollments.service']);

        // Filter by date range - only include participants with active enrollments during the reporting period
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;

        // Log the date range being used
        Log::info('CSBG Report Date Range', [
            'start_date' => $startDate?->format('Y-m-d H:i:s'),
            'end_date' => $endDate?->format('Y-m-d H:i:s'),
            'filters' => $filters
        ]);

        if ($startDate && $endDate) {
            $query->where(function($q) use ($startDate, $endDate) {
                // Only include participants with active enrollments during the reporting period
                
                // Program enrollments active during the period
                $q->whereHas('programEnrollments', function($enrollQuery) use ($startDate, $endDate) {
                    $enrollQuery->where(function($q) use ($startDate, $endDate) {
                        // Enrollment started before or during the period
                        $q->where('enrollment_date', '<=', $endDate)
                          // AND (no exit date OR exited after period started)
                          ->where(function($q2) use ($startDate) {
                              $q2->whereNull('exit_date')
                                 ->orWhere('exit_date', '>=', $startDate);
                          });
                    });
                    
                    // Log the enrollment query for debugging
                    Log::debug('Program Enrollment Query', [
                        'sql' => $enrollQuery->toSql(),
                        'bindings' => $enrollQuery->getBindings()
                    ]);
                })
                // OR Service enrollments active during the period
                ->orWhereHas('serviceEnrollments', function($serviceQuery) use ($startDate, $endDate) {
                    $serviceQuery->where(function($q) use ($startDate, $endDate) {
                        // Enrollment started before or during the period
                        $q->where('enrollment_date', '<=', $endDate)
                          // AND (no completion date OR completed after period started)
                          ->where(function($q2) use ($startDate) {
                              $q2->whereNull('completion_date')
                                 ->orWhere('completion_date', '>=', $startDate);
                          });
                    });
                    
                    // Log the service enrollment query for debugging
                    Log::debug('Service Enrollment Query', [
                        'sql' => $serviceQuery->toSql(),
                        'bindings' => $serviceQuery->getBindings()
                    ]);
                });
            });
        }

        // Filter by reference data set if specified
        // Note: This filter is optional and should only be applied when explicitly needed
        // Most participants may not have a reference_data_set_id assigned
        if (isset($filters['reference_data_set_id']) && !empty($filters['reference_data_set_id'])) {
            // Check if we should apply strict filtering or not
            $strictReferenceFiltering = $filters['strict_reference_filtering'] ?? false;
            
            if ($strictReferenceFiltering) {
                // Strict mode: Only include participants with the specified reference_data_set_id
                $query->where('reference_data_set_id', $filters['reference_data_set_id']);
                Log::info('CSBG Report: Applying strict reference data set filter', [
                    'reference_data_set_id' => $filters['reference_data_set_id']
                ]);
            } else {
                // Default mode: Include participants with the specified reference_data_set_id OR no reference_data_set_id
                $query->where(function($q) use ($filters) {
                    $q->where('reference_data_set_id', $filters['reference_data_set_id'])
                      ->orWhereNull('reference_data_set_id');
                });
                Log::info('CSBG Report: Applying inclusive reference data set filter', [
                    'reference_data_set_id' => $filters['reference_data_set_id']
                ]);
            }
        }

        // Filter by program if specified
        if (isset($filters['program_id']) && !empty($filters['program_id'])) {
            $query->whereHas('programEnrollments', function($q) use ($filters, $startDate, $endDate) {
                $q->where('program_id', $filters['program_id']);
                // Also check that the enrollment was active during the reporting period
                if ($startDate && $endDate) {
                    $q->where('enrollment_date', '<=', $endDate)
                      ->where(function($q2) use ($startDate) {
                          $q2->whereNull('exit_date')
                             ->orWhere('exit_date', '>=', $startDate);
                      });
                }
            });
            Log::info('CSBG Report: Applying program filter', [
                'program_id' => $filters['program_id']
            ]);
        }

        // Only count heads of household for CSBG reporting (avoids double counting families)
        if ($filters['heads_of_household_only'] ?? true) {
            $query->where('is_head_of_household', true);
        }

        // Advanced filters
        if (!empty($filters['gender'])) {
            $query->whereIn('gender', $filters['gender']);
        }

        if (!empty($filters['ethnicity'])) {
            $query->whereIn('ethnicity', $filters['ethnicity']);
        }

        if (!empty($filters['education_level'])) {
            $query->whereIn('education_level', $filters['education_level']);
        }

        if (!empty($filters['work_status'])) {
            $query->whereIn('work_status', $filters['work_status']);
        }

        // Age range filtering
        if (isset($filters['age_min']) || isset($filters['age_max'])) {
            $query->where(function($q) use ($filters) {
                if (isset($filters['age_min'])) {
                    $maxBirthDate = Carbon::now()->subYears($filters['age_min'])->format('Y-m-d');
                    $q->where('date_of_birth', '<=', $maxBirthDate);
                }
                if (isset($filters['age_max'])) {
                    $minBirthDate = Carbon::now()->subYears($filters['age_max'] + 1)->addDay()->format('Y-m-d');
                    $q->where('date_of_birth', '>=', $minBirthDate);
                }
            });
        }

        // Household type filtering
        if (!empty($filters['household_type'])) {
            $query->whereHas('household', function($q) use ($filters) {
                $q->whereIn('household_type', $filters['household_type']);
            });
        }

        // Log the final query for debugging
        Log::debug('CSBG Report Final Query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        // Get participants and ensure deduplication by participant ID
        $participants = $query->get()->unique('id');
        
        // Enhanced logging for debugging
        Log::info('CSBG Report Participants Found', [
            'total_found' => $participants->count(),
            'date_range' => [
                'start' => $startDate?->format('Y-m-d'),
                'end' => $endDate?->format('Y-m-d')
            ],
            'filters_applied' => array_keys(array_filter($filters)),
            'heads_of_household_only' => $filters['heads_of_household_only'] ?? true,
            'program_id' => $filters['program_id'] ?? 'All Programs',
            'reference_data_set_id' => $filters['reference_data_set_id'] ?? 'Not specified'
        ]);
        
        // Log sample participant data for verification
        if ($participants->count() > 0) {
            $sampleParticipants = $participants->take(5)->map(function($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->full_name,
                    'household_id' => $p->household_id,
                    'is_head' => $p->is_head_of_household,
                    'created_at' => $p->created_at->format('Y-m-d'),
                    'program_enrollments_count' => $p->programEnrollments->count(),
                    'service_enrollments_count' => $p->serviceEnrollments->count(),
                    'active_program_enrollments' => $p->programEnrollments->filter(function($e) {
                        return $e->status === 'active' || (!$e->exit_date || $e->exit_date->isFuture());
                    })->count(),
                    'active_service_enrollments' => $p->serviceEnrollments->filter(function($e) {
                        return in_array($e->status, ['enrolled', 'in_progress']) || 
                               (!$e->completion_date || $e->completion_date->isFuture());
                    })->count()
                ];
            });
            
            Log::debug('CSBG Report Sample Participants', [
                'sample_size' => $sampleParticipants->count(),
                'participants' => $sampleParticipants->toArray()
            ]);
        } else {
            Log::warning('CSBG Report: No participants found with current filters');
        }
        
        return $participants;
    }

    /**
     * Generate report metadata
     */
    protected function getReportMetadata(array $filters): array
    {
        $referenceDataSet = null;
        if (isset($filters['reference_data_set_id'])) {
            $referenceDataSet = ReferenceDataSet::find($filters['reference_data_set_id']);
        } else {
            $referenceDataSet = ReferenceDataSet::getActive();
        }

        // Get program name if program filter is applied
        $programFilter = 'All Programs';
        if (isset($filters['program_id'])) {
            $program = \App\Models\Program::find($filters['program_id']);
            $programFilter = $program ? $program->name : 'Program ID: ' . $filters['program_id'];
        }

        return [
            'generated_at' => now(),
            'period_start' => $filters['start_date'] ?? null,
            'period_end' => $filters['end_date'] ?? null,
            'reference_data_set' => $referenceDataSet ? [
                'name' => $referenceDataSet->name,
                'version' => $referenceDataSet->version,
                'id' => $referenceDataSet->id
            ] : null,
            'program_filter' => $programFilter,
            'heads_of_household_only' => $filters['heads_of_household_only'] ?? true,
            'advanced_filters' => [
                'gender' => $filters['gender'] ?? [],
                'ethnicity' => $filters['ethnicity'] ?? [],
                'education_level' => $filters['education_level'] ?? [],
                'work_status' => $filters['work_status'] ?? [],
                'age_min' => $filters['age_min'] ?? null,
                'age_max' => $filters['age_max'] ?? null,
                'household_type' => $filters['household_type'] ?? [],
            ],
        ];
    }

    /**
     * Generate demographics section of CSBG Module 4 Section C
     */
    protected function generateDemographicsReport(Collection $participants): array
    {
        return [
            'gender' => $this->aggregateByField($participants, 'gender'),
            'ethnicity' => $this->aggregateByField($participants, 'ethnicity'),
            'race' => $this->aggregateMultiSelectField($participants, 'race'),
            'education_level' => $this->aggregateByField($participants, 'education_level'),
        ];
    }

    /**
     * Generate characteristics section of CSBG Module 4 Section C
     */
    protected function generateCharacteristicsReport(Collection $participants): array
    {
        return [
            'military_status' => $this->aggregateByField($participants, 'military_status'),
            'disability_status' => $this->aggregateByField($participants, 'disability_status'),
            'age_groups' => $this->aggregateByAgeGroups($participants),
        ];
    }

    /**
     * Generate employment and income section of CSBG Module 4 Section C
     */
    protected function generateEmploymentIncomeReport(Collection $participants): array
    {
        return [
            'work_status' => $this->aggregateByField($participants, 'work_status'),
            'income_sources' => $this->aggregateMultiSelectField($participants, 'income_sources'),
            'other_income_sources' => $this->aggregateMultiSelectField($participants, 'other_income_sources'),
            'non_cash_benefits' => $this->aggregateMultiSelectField($participants, 'non_cash_benefits'),
            'poverty_levels' => $this->aggregateByPovertyLevels($participants),
        ];
    }

    /**
     * Generate health and housing section of CSBG Module 4 Section C
     */
    protected function generateHealthHousingReport(Collection $participants): array
    {
        return [
            'health_insurance_status' => $this->aggregateByField($participants, 'health_insurance_status'),
            'health_insurance_sources' => $this->aggregateMultiSelectField($participants, 'health_insurance_sources'),
            'housing_status' => $this->aggregateHouseholdField($participants, 'housing_status'),
            'household_type' => $this->aggregateHouseholdField($participants, 'household_type'),
        ];
    }

    /**
     * Aggregate participants by a single field value
     */
    protected function aggregateByField(Collection $participants, string $field): array
    {
        $counts = [];
        
        // Group by field value, but handle null/empty specially
        $grouped = $participants->groupBy(function ($participant) use ($field) {
            $value = $participant->$field;
            
            // Convert null and empty strings to a special marker
            if ($value === null || $value === '') {
                return '__UNKNOWN__';
            }
            
            return $value;
        });
        
        foreach ($grouped as $value => $group) {
            if ($value === '__UNKNOWN__') {
                $counts['Unknown/not reported'] = $group->count();
            } else {
                $counts[$value] = $group->count();
            }
        }

        return $counts;
    }

    /**
     * Aggregate participants by multi-select field (JSON arrays)
     */
    protected function aggregateMultiSelectField(Collection $participants, string $field): array
    {
        $counts = [];
        $hasDataCount = 0;

        foreach ($participants as $participant) {
            $values = $participant->$field;
            
            if (is_array($values) && !empty($values)) {
                $hasDataCount++;
                foreach ($values as $value) {
                    $counts[$value] = ($counts[$value] ?? 0) + 1;
                }
            }
        }

        // Count participants with no data
        $noDataCount = $participants->count() - $hasDataCount;
        if ($noDataCount > 0) {
            $counts['Unknown/not reported'] = $noDataCount;
        }

        return $counts;
    }

    /**
     * Aggregate participants by household field (from household relationship)
     */
    protected function aggregateHouseholdField(Collection $participants, string $field): array
    {
        $counts = $participants->groupBy(function ($participant) use ($field) {
            return $participant->household->$field ?? 'Unknown/not reported';
        })->map(function ($group) {
            return $group->count();
        })->toArray();

        return $counts;
    }

    /**
     * Aggregate participants by age groups
     */
    protected function aggregateByAgeGroups(Collection $participants): array
    {
        $ageGroups = [
            '0-5' => 0,
            '6-11' => 0,
            '12-17' => 0,
            '18-24' => 0,
            '25-34' => 0,
            '35-44' => 0,
            '45-54' => 0,
            '55-64' => 0,
            '65+' => 0,
            'Unknown/not reported' => 0,
        ];

        foreach ($participants as $participant) {
            if (!$participant->date_of_birth) {
                $ageGroups['Unknown/not reported']++;
                continue;
            }

            $age = Carbon::parse($participant->date_of_birth)->age;

            if ($age <= 5) {
                $ageGroups['0-5']++;
            } elseif ($age <= 11) {
                $ageGroups['6-11']++;
            } elseif ($age <= 17) {
                $ageGroups['12-17']++;
            } elseif ($age <= 24) {
                $ageGroups['18-24']++;
            } elseif ($age <= 34) {
                $ageGroups['25-34']++;
            } elseif ($age <= 44) {
                $ageGroups['35-44']++;
            } elseif ($age <= 54) {
                $ageGroups['45-54']++;
            } elseif ($age <= 64) {
                $ageGroups['55-64']++;
            } else {
                $ageGroups['65+']++;
            }
        }

        return $ageGroups;
    }

    /**
     * Aggregate participants by poverty levels (using household data)
     */
    protected function aggregateByPovertyLevels(Collection $participants): array
    {
        $povertyLevels = [
            '0-50% FPL' => 0,
            '51-100% FPL' => 0,
            '101-125% FPL' => 0,
            '126-150% FPL' => 0,
            '151-200% FPL' => 0,
            'Over 200% FPL' => 0,
            'Unknown/not reported' => 0,
        ];

        foreach ($participants as $participant) {
            $povertyPercentage = $participant->household->hhs_poverty_percentage ?? null;

            if ($povertyPercentage === null) {
                $povertyLevels['Unknown/not reported']++;
                continue;
            }

            if ($povertyPercentage <= 50) {
                $povertyLevels['0-50% FPL']++;
            } elseif ($povertyPercentage <= 100) {
                $povertyLevels['51-100% FPL']++;
            } elseif ($povertyPercentage <= 125) {
                $povertyLevels['101-125% FPL']++;
            } elseif ($povertyPercentage <= 150) {
                $povertyLevels['126-150% FPL']++;
            } elseif ($povertyPercentage <= 200) {
                $povertyLevels['151-200% FPL']++;
            } else {
                $povertyLevels['Over 200% FPL']++;
            }
        }

        return $povertyLevels;
    }

    /**
     * Export report data to CSV format
     */
    public function exportToCsv(array $reportData): string
    {
        $csv = [];
        
        // Header
        $csv[] = ['CSBG Module 4 Section C Report'];
        $csv[] = ['Generated: ' . $reportData['report_metadata']['generated_at']];
        $csv[] = ['Total Participants: ' . $reportData['participant_count']];
        $csv[] = [''];
        
        // Filter information
        $csv[] = ['REPORT FILTERS'];
        $metadata = $reportData['report_metadata'];
        $csv[] = ['Date Range: ' . ($metadata['period_start'] ? date('M j, Y', strtotime($metadata['period_start'])) : 'Not specified') . ' to ' . ($metadata['period_end'] ? date('M j, Y', strtotime($metadata['period_end'])) : 'Not specified')];
        
        if (isset($metadata['reference_data_set']) && $metadata['reference_data_set']) {
            $csv[] = ['Reference Data Set: ' . $metadata['reference_data_set']['name'] . ' (v' . $metadata['reference_data_set']['version'] . ')'];
        }
        
        $csv[] = ['Program Filter: ' . $metadata['program_filter']];
        $csv[] = ['Heads of Household Only: ' . ($metadata['heads_of_household_only'] ? 'Yes' : 'No')];
        
        // Add advanced filters if they exist
        if (isset($metadata['advanced_filters'])) {
            $advancedFilters = $metadata['advanced_filters'];
            if (!empty($advancedFilters['gender'])) {
                $csv[] = ['Gender Filter: ' . implode(', ', $advancedFilters['gender'])];
            }
            if (!empty($advancedFilters['ethnicity'])) {
                $csv[] = ['Ethnicity Filter: ' . implode(', ', $advancedFilters['ethnicity'])];
            }
            if (!empty($advancedFilters['education_level'])) {
                $csv[] = ['Education Level Filter: ' . implode(', ', $advancedFilters['education_level'])];
            }
            if (!empty($advancedFilters['work_status'])) {
                $csv[] = ['Work Status Filter: ' . implode(', ', $advancedFilters['work_status'])];
            }
            if ($advancedFilters['age_min'] || $advancedFilters['age_max']) {
                $ageRange = '';
                if ($advancedFilters['age_min']) $ageRange .= 'Min: ' . $advancedFilters['age_min'];
                if ($advancedFilters['age_max']) $ageRange .= ($ageRange ? ', ' : '') . 'Max: ' . $advancedFilters['age_max'];
                $csv[] = ['Age Range Filter: ' . $ageRange];
            }
            if (!empty($advancedFilters['household_type'])) {
                $csv[] = ['Household Type Filter: ' . implode(', ', $advancedFilters['household_type'])];
            }
        }
        
        $csv[] = [''];
        $csv[] = [''];

        // Demographics section
        $csv[] = ['DEMOGRAPHICS'];
        foreach ($reportData['demographics'] as $category => $data) {
            $csv[] = [ucfirst(str_replace('_', ' ', $category))];
            foreach ($data as $value => $count) {
                $csv[] = ['', $value, $count];
            }
            $csv[] = [''];
        }

        // Characteristics section
        $csv[] = ['CHARACTERISTICS'];
        foreach ($reportData['characteristics'] as $category => $data) {
            $csv[] = [ucfirst(str_replace('_', ' ', $category))];
            foreach ($data as $value => $count) {
                $csv[] = ['', $value, $count];
            }
            $csv[] = [''];
        }

        // Employment & Income section
        $csv[] = ['EMPLOYMENT & INCOME'];
        foreach ($reportData['employment_income'] as $category => $data) {
            $csv[] = [ucfirst(str_replace('_', ' ', $category))];
            foreach ($data as $value => $count) {
                $csv[] = ['', $value, $count];
            }
            $csv[] = [''];
        }

        // Health & Housing section
        $csv[] = ['HEALTH & HOUSING'];
        foreach ($reportData['health_housing'] as $category => $data) {
            $csv[] = [ucfirst(str_replace('_', ' ', $category))];
            foreach ($data as $value => $count) {
                $csv[] = ['', $value, $count];
            }
            $csv[] = [''];
        }

        // Convert to CSV string
        $output = '';
        foreach ($csv as $line) {
            $output .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $line)) . "\n";
        }

        return $output;
    }

    /**
     * Export report data to PDF format
     */
    public function exportToPdf(array $reportData): string
    {
        $pdf = Pdf::loadView('reports.csbg-pdf-template', [
            'reportData' => $reportData,
            'generatedAt' => Carbon::parse($reportData['report_metadata']['generated_at'])->format('F j, Y g:i A'),
            'periodStart' => Carbon::parse($reportData['report_metadata']['period_start'])->format('F j, Y'),
            'periodEnd' => Carbon::parse($reportData['report_metadata']['period_end'])->format('F j, Y'),
        ])
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont' => 'sans-serif',
            'isRemoteEnabled' => false,
            'chroot' => public_path(),
        ]);
        
        return $pdf->output();
    }

    /**
     * Export report data to Excel format matching official CSBG Module 4 Section C
     */
    public function exportToExcel(array $reportData): string
    {
        return $this->exportToOfficialCsbgExcel($reportData);
    }

    /**
     * Create comprehensive Excel export with all report data on a single sheet
     */
    private function exportToOfficialCsbgExcel(array $reportData): string
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle('CSBG Report');
        
        // Set up the page format
        $worksheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $worksheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LETTER);
        
        $row = 1;
        
        // Main Header
        $worksheet->mergeCells('A1:D1');
        $worksheet->setCellValue('A1', 'CSBG Module 4 Section C Report');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        $worksheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');
        $row += 2;
        
        // Report Summary
        $worksheet->setCellValue("A{$row}", 'Total Participants:');
        $worksheet->setCellValue("B{$row}", $reportData['participant_count']);
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        
        $worksheet->setCellValue("A{$row}", 'Generated:');
        $worksheet->setCellValue("B{$row}", $reportData['report_metadata']['generated_at']->format('M j, Y g:i A'));
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row += 2;
        
        // Add filter information
        $this->addFilterInformationToExcel($worksheet, $row, $reportData);
        $row += $this->getFilterInfoRowCount($reportData) + 2;
        
        // Demographics Section
        $row = $this->addComprehensiveExcelSection($worksheet, $row, 'DEMOGRAPHICS', $reportData['demographics']);
        
        // Characteristics Section
        $row = $this->addComprehensiveExcelSection($worksheet, $row, 'CHARACTERISTICS', $reportData['characteristics']);
        
        // Employment & Income Section
        $row = $this->addComprehensiveExcelSection($worksheet, $row, 'EMPLOYMENT & INCOME', $reportData['employment_income']);
        
        // Health & Housing Section
        $row = $this->addComprehensiveExcelSection($worksheet, $row, 'HEALTH & HOUSING', $reportData['health_housing']);
        
        // Auto-size columns
        foreach (range('A', 'D') as $column) {
            $worksheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Create writer and return content
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }


    /**
     * Generate cache key based on filters
     */
    private function generateCacheKey(array $filters): string
    {
        // Sort filters to ensure consistent cache keys
        ksort($filters);
        
        // Remove null/empty values
        $cleanFilters = array_filter($filters, function($value) {
            return $value !== null && $value !== '' && (!is_array($value) || !empty($value));
        });
        
        $keyData = [
            'filters' => $cleanFilters,
            'version' => '1.0' // Increment when report structure changes
        ];
        
        return 'csbg_report_' . md5(json_encode($keyData));
    }

    /**
     * Calculate cache duration based on data age
     */
    private function calculateCacheDuration(array $filters): int
    {
        $endDate = Carbon::parse($filters['end_date'] ?? now());
        $daysSinceEndDate = $endDate->diffInDays(now());
        
        // Cache longer for older data
        if ($daysSinceEndDate > 365) {
            return 1440; // 24 hours for data older than 1 year
        } elseif ($daysSinceEndDate > 90) {
            return 480; // 8 hours for data older than 90 days
        } elseif ($daysSinceEndDate > 30) {
            return 240; // 4 hours for data older than 30 days
        } elseif ($daysSinceEndDate > 7) {
            return 120; // 2 hours for data older than 7 days
        } else {
            return 60; // 1 hour for recent data
        }
    }

    /**
     * Clear cache for CSBG reports
     */
    public function clearCache(array $filters = []): void
    {
        if (empty($filters)) {
            // Clear all CSBG report caches
            Cache::forget('csbg_report_*');
            Log::info('All CSBG report caches cleared');
        } else {
            // Clear specific cache
            $cacheKey = $this->generateCacheKey($filters);
            Cache::forget($cacheKey);
            Log::info('CSBG report cache cleared', ['cache_key' => $cacheKey]);
        }
    }

    /**
     * Get detailed participant data for drill-down functionality
     */
    public function getDrillDownData(array $filters, string $category, string $value): Collection
    {
        $participants = $this->getParticipants($filters);
        
        // Filter participants based on the specific category and value
        $filteredParticipants = $participants->filter(function ($participant) use ($category, $value) {
            return $this->matchesCategory($participant, $category, $value);
        });
        
        // Load additional relationships for display
        $filteredParticipants->load([
            'household',
            'programEnrollments.program',
            'serviceEnrollments.service'
        ]);
        
        return $filteredParticipants;
    }

    /**
     * Check if a participant matches the drill-down category and value
     */
    private function matchesCategory($participant, string $category, string $value): bool
    {
        switch ($category) {
            case 'gender':
                return $participant->gender === $value || 
                       ($value === 'Unknown/not reported' && ($participant->gender === null || $participant->gender === ''));
                       
            case 'ethnicity':
                return $participant->ethnicity === $value || 
                       ($value === 'Unknown/not reported' && ($participant->ethnicity === null || $participant->ethnicity === ''));
                       
            case 'education_level':
                return $participant->education_level === $value || 
                       ($value === 'Unknown/not reported' && ($participant->education_level === null || $participant->education_level === ''));
                       
            case 'work_status':
                return $participant->work_status === $value || 
                       ($value === 'Unknown/not reported' && ($participant->work_status === null || $participant->work_status === ''));
                       
            case 'age_groups':
                return $this->matchesAgeGroup($participant, $value);
                
            case 'poverty_levels':
                return $this->matchesPovertyLevel($participant, $value);
                
            case 'housing_status':
                $housingStatus = $participant->household->housing_status ?? null;
                return $housingStatus === $value || 
                       ($value === 'Unknown/not reported' && ($housingStatus === null || $housingStatus === ''));
                       
            case 'household_type':
                $householdType = $participant->household->household_type ?? null;
                return $householdType === $value || 
                       ($value === 'Unknown/not reported' && ($householdType === null || $householdType === ''));
                       
            case 'health_insurance_status':
                return $participant->health_insurance_status === $value || 
                       ($value === 'Unknown/not reported' && ($participant->health_insurance_status === null || $participant->health_insurance_status === ''));
                       
            case 'health_insurance_sources':
                // Handle multi-select field
                $sources = $participant->health_insurance_sources ?? [];
                if (!is_array($sources)) {
                    $sources = json_decode($sources, true) ?? [];
                }
                return in_array($value, $sources) || 
                       ($value === 'Unknown/not reported' && empty($sources));
                       
            case 'military_status':
                return $participant->military_status === $value || 
                       ($value === 'Unknown/not reported' && ($participant->military_status === null || $participant->military_status === ''));
                       
            case 'disability_status':
                return $participant->disability_status === $value || 
                       ($value === 'Unknown/not reported' && ($participant->disability_status === null || $participant->disability_status === ''));
                       
            case 'income_sources':
                // Handle multi-select field
                $sources = $participant->income_sources ?? [];
                if (!is_array($sources)) {
                    $sources = json_decode($sources, true) ?? [];
                }
                return in_array($value, $sources) || 
                       ($value === 'Unknown/not reported' && empty($sources));
                       
            case 'other_income_sources':
                // Handle multi-select field
                $sources = $participant->other_income_sources ?? [];
                if (!is_array($sources)) {
                    $sources = json_decode($sources, true) ?? [];
                }
                return in_array($value, $sources) || 
                       ($value === 'Unknown/not reported' && empty($sources));
                       
            case 'non_cash_benefits':
                // Handle multi-select field
                $benefits = $participant->non_cash_benefits ?? [];
                if (!is_array($benefits)) {
                    $benefits = json_decode($benefits, true) ?? [];
                }
                return in_array($value, $benefits) || 
                       ($value === 'Unknown/not reported' && empty($benefits));
                       
            default:
                return false;
        }
    }

    /**
     * Check if participant matches age group
     */
    private function matchesAgeGroup($participant, string $ageGroup): bool
    {
        if ($ageGroup === 'Unknown/not reported') {
            return empty($participant->date_of_birth);
        }
        
        if (empty($participant->date_of_birth)) {
            return false;
        }
        
        $age = Carbon::parse($participant->date_of_birth)->age;
        
        return match($ageGroup) {
            '0-5' => $age <= 5,
            '6-11' => $age >= 6 && $age <= 11,
            '12-17' => $age >= 12 && $age <= 17,
            '18-24' => $age >= 18 && $age <= 24,
            '25-34' => $age >= 25 && $age <= 34,
            '35-44' => $age >= 35 && $age <= 44,
            '45-54' => $age >= 45 && $age <= 54,
            '55-64' => $age >= 55 && $age <= 64,
            '65+' => $age >= 65,
            default => false,
        };
    }

    /**
     * Check if participant matches poverty level
     */
    private function matchesPovertyLevel($participant, string $povertyLevel): bool
    {
        $povertyPercentage = $participant->household->hhs_poverty_percentage ?? null;
        
        if ($povertyLevel === 'Unknown/not reported') {
            return $povertyPercentage === null;
        }
        
        if ($povertyPercentage === null) {
            return false;
        }
        
        return match($povertyLevel) {
            '0-50% FPL' => $povertyPercentage <= 50,
            '51-100% FPL' => $povertyPercentage > 50 && $povertyPercentage <= 100,
            '101-125% FPL' => $povertyPercentage > 100 && $povertyPercentage <= 125,
            '126-150% FPL' => $povertyPercentage > 125 && $povertyPercentage <= 150,
            '151-200% FPL' => $povertyPercentage > 150 && $povertyPercentage <= 200,
            'Over 200% FPL' => $povertyPercentage > 200,
            default => false,
        };
    }

    /**
     * Add comprehensive Excel section with proper formatting
     */
    private function addComprehensiveExcelSection($worksheet, int $startRow, string $sectionTitle, array $sectionData): int
    {
        $row = $startRow;
        
        // Section header
        $worksheet->mergeCells("A{$row}:D{$row}");
        $worksheet->setCellValue("A{$row}", $sectionTitle);
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $worksheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $row += 2;
        
        // Table headers
        $worksheet->setCellValue("A{$row}", 'Category');
        $worksheet->setCellValue("B{$row}", 'Value');
        $worksheet->setCellValue("C{$row}", 'Count');
        $worksheet->setCellValue("D{$row}", 'Percentage');
        
        $headerStyle = $worksheet->getStyle("A{$row}:D{$row}");
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row++;
        
        // Data rows
        foreach ($sectionData as $category => $data) {
            $categoryStart = $row;
            $totalCount = array_sum($data);
            
            foreach ($data as $value => $count) {
                $worksheet->setCellValue("A{$row}", ucfirst(str_replace('_', ' ', $category)));
                $worksheet->setCellValue("B{$row}", $value);
                $worksheet->setCellValue("C{$row}", number_format($count));
                $worksheet->setCellValue("D{$row}", $totalCount > 0 ? number_format(($count / $totalCount) * 100, 1) . '%' : '0%');
                
                // Add borders and alternating row colors
                $rowStyle = $worksheet->getStyle("A{$row}:D{$row}");
                $rowStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                if ($row % 2 == 0) {
                    $rowStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
                }
                $row++;
            }
            
            // Add spacing between categories
            if ($row < $categoryStart + count($data)) {
                $row++;
            }
        }
        
        return $row + 2; // Add extra spacing between sections
    }

    /**
     * Add filter information section to Excel worksheet
     */
    private function addFilterInformationToExcel($worksheet, int $startRow, array $reportData): void
    {
        $row = $startRow;
        $metadata = $reportData['report_metadata'];
        
        // Filter section header
        $worksheet->mergeCells("A{$row}:D{$row}");
        $worksheet->setCellValue("A{$row}", 'REPORT FILTERS');
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $worksheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $worksheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFE6CC');
        $row++;
        
        // Date range
        $dateRange = ($metadata['period_start'] ? date('M j, Y', strtotime($metadata['period_start'])) : 'Not specified') . 
                    ' to ' . 
                    ($metadata['period_end'] ? date('M j, Y', strtotime($metadata['period_end'])) : 'Not specified');
        $worksheet->setCellValue("A{$row}", 'Date Range:');
        $worksheet->setCellValue("B{$row}", $dateRange);
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        
        // Reference data set
        if (isset($metadata['reference_data_set']) && $metadata['reference_data_set']) {
            $worksheet->setCellValue("A{$row}", 'Reference Data Set:');
            $worksheet->setCellValue("B{$row}", $metadata['reference_data_set']['name'] . ' (v' . $metadata['reference_data_set']['version'] . ')');
            $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }
        
        // Program filter
        $worksheet->setCellValue("A{$row}", 'Program Filter:');
        $worksheet->setCellValue("B{$row}", $metadata['program_filter']);
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        
        // Heads of household only
        $worksheet->setCellValue("A{$row}", 'Heads of Household Only:');
        $worksheet->setCellValue("B{$row}", $metadata['heads_of_household_only'] ? 'Yes' : 'No');
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        
        // Advanced filters
        if (isset($metadata['advanced_filters'])) {
            $advancedFilters = $metadata['advanced_filters'];
            
            if (!empty($advancedFilters['gender'])) {
                $worksheet->setCellValue("A{$row}", 'Gender Filter:');
                $worksheet->setCellValue("B{$row}", implode(', ', $advancedFilters['gender']));
                $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;
            }
            
            if (!empty($advancedFilters['ethnicity'])) {
                $worksheet->setCellValue("A{$row}", 'Ethnicity Filter:');
                $worksheet->setCellValue("B{$row}", implode(', ', $advancedFilters['ethnicity']));
                $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;
            }
            
            if (!empty($advancedFilters['education_level'])) {
                $worksheet->setCellValue("A{$row}", 'Education Level Filter:');
                $worksheet->setCellValue("B{$row}", implode(', ', $advancedFilters['education_level']));
                $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;
            }
            
            if (!empty($advancedFilters['work_status'])) {
                $worksheet->setCellValue("A{$row}", 'Work Status Filter:');
                $worksheet->setCellValue("B{$row}", implode(', ', $advancedFilters['work_status']));
                $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;
            }
            
            if ($advancedFilters['age_min'] || $advancedFilters['age_max']) {
                $ageRange = '';
                if ($advancedFilters['age_min']) $ageRange .= 'Min: ' . $advancedFilters['age_min'];
                if ($advancedFilters['age_max']) $ageRange .= ($ageRange ? ', ' : '') . 'Max: ' . $advancedFilters['age_max'];
                $worksheet->setCellValue("A{$row}", 'Age Range Filter:');
                $worksheet->setCellValue("B{$row}", $ageRange);
                $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;
            }
            
            if (!empty($advancedFilters['household_type'])) {
                $worksheet->setCellValue("A{$row}", 'Household Type Filter:');
                $worksheet->setCellValue("B{$row}", implode(', ', $advancedFilters['household_type']));
                $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;
            }
        }
    }

    /**
     * Calculate how many rows the filter information will take
     */
    private function getFilterInfoRowCount(array $reportData): int
    {
        $count = 5; // Header + date range + reference data set + program filter + heads of household
        
        if (isset($reportData['report_metadata']['advanced_filters'])) {
            $advancedFilters = $reportData['report_metadata']['advanced_filters'];
            
            if (!empty($advancedFilters['gender'])) $count++;
            if (!empty($advancedFilters['ethnicity'])) $count++;
            if (!empty($advancedFilters['education_level'])) $count++;
            if (!empty($advancedFilters['work_status'])) $count++;
            if ($advancedFilters['age_min'] || $advancedFilters['age_max']) $count++;
            if (!empty($advancedFilters['household_type'])) $count++;
        }
        
        return $count;
    }
}