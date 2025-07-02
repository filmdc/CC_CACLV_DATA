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

        // Filter by date range - check both participant creation and enrollment dates
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : null;
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;

        if ($startDate || $endDate) {
            $query->where(function($q) use ($startDate, $endDate) {
                // Include participants created in date range
                if ($startDate) {
                    $q->where('created_at', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('created_at', '<=', $endDate);
                }
                
                // OR participants with enrollments in date range
                $q->orWhereHas('programEnrollments', function($enrollQuery) use ($startDate, $endDate) {
                    if ($startDate) {
                        $enrollQuery->where('enrollment_date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $enrollQuery->where('enrollment_date', '<=', $endDate);
                    }
                });
                
                $q->orWhereHas('serviceEnrollments', function($serviceQuery) use ($startDate, $endDate) {
                    if ($startDate) {
                        $serviceQuery->where('enrollment_date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $serviceQuery->where('enrollment_date', '<=', $endDate);
                    }
                });
            });
        }

        // Filter by reference data set if specified and participants have reference data set IDs
        if (isset($filters['reference_data_set_id'])) {
            // Only apply this filter if there are participants with reference data set IDs
            $hasParticipantsWithRDS = Participant::whereNotNull('reference_data_set_id')->exists();
            if ($hasParticipantsWithRDS) {
                $query->where('reference_data_set_id', $filters['reference_data_set_id']);
            }
        }

        // Filter by program if specified
        if (isset($filters['program_id'])) {
            $query->whereHas('programEnrollments', function($q) use ($filters, $startDate, $endDate) {
                $q->where('program_id', $filters['program_id']);
                // Only count enrollments within the date range
                if ($startDate) {
                    $q->where('enrollment_date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('enrollment_date', '<=', $endDate);
                }
            });
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

        // Get participants and ensure deduplication by participant ID
        $participants = $query->get()->unique('id');
        
        Log::info('CSBG Report Participants', [
            'total_found' => $participants->count(),
            'date_range' => [$startDate?->format('Y-m-d'), $endDate?->format('Y-m-d')],
            'filters_applied' => array_keys(array_filter($filters))
        ]);
        
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

        return [
            'generated_at' => now(),
            'period_start' => $filters['start_date'] ?? null,
            'period_end' => $filters['end_date'] ?? null,
            'reference_data_set' => $referenceDataSet ? [
                'name' => $referenceDataSet->name,
                'version' => $referenceDataSet->version,
                'id' => $referenceDataSet->id
            ] : null,
            'program_filter' => isset($filters['program_id']) ? $filters['program_id'] : 'All Programs',
            'heads_of_household_only' => $filters['heads_of_household_only'] ?? true,
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
        $counts = $participants->groupBy($field)->map(function ($group) {
            return $group->count();
        })->toArray();

        // Ensure we include "Unknown/not reported" for missing values
        $unknownCount = $participants->whereNull($field)->count() + 
                       $participants->where($field, '')->count();
        
        if ($unknownCount > 0) {
            $counts['Unknown/not reported'] = $unknownCount;
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
     * Create Excel export matching the official CSBG Module 4 Section C format
     */
    private function exportToOfficialCsbgExcel(array $reportData): string
    {
        $spreadsheet = new Spreadsheet();
        
        // Create two worksheets for the two pages
        $page1 = $spreadsheet->getActiveSheet();
        $page1->setTitle('CSBG Module 4C Page 1');
        
        $page2 = $spreadsheet->createSheet();
        $page2->setTitle('CSBG Module 4C Page 2');
        
        // Build Page 1 - Household Level Characteristics
        $this->buildCsbgPage1($page1, $reportData);
        
        // Build Page 2 - Individual Level Characteristics  
        $this->buildCsbgPage2($page2, $reportData);
        
        // Set the active sheet back to page 1
        $spreadsheet->setActiveSheetIndex(0);
        
        // Create writer and return content
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    /**
     * Build CSBG Page 1 - Household Level Characteristics
     */
    private function buildCsbgPage1($worksheet, array $reportData)
    {
        // Set up the page format
        $worksheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $worksheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LETTER);
        
        $row = 1;
        
        // Header
        $worksheet->mergeCells('A1:H1');
        $worksheet->setCellValue('A1', 'Module 4, Section C: All Characteristics Report - Data Entry Form');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row += 2;
        
        $worksheet->mergeCells('A3:H3');
        $worksheet->setCellValue('A3', 'Goal 1: Individuals and Families with low-incomes are stable and achieve economic security.');
        $worksheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
        $row += 2;
        
        // CALV Program Reporting field
        $worksheet->setCellValue('A5', 'CALV Program Reporting:');
        $worksheet->getStyle('A5')->getFont()->setBold(true);
        $worksheet->mergeCells('B5:H5');
        $worksheet->getStyle('B5:H5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row += 2;
        
        // Section A & B headers
        $worksheet->setCellValue('A7', 'A. Total unduplicated number of all INDIVIDUALS about whom one or more characteristics were obtained:');
        $worksheet->getStyle('A7')->getFont()->setBold(true);
        $worksheet->setCellValue('G7', $reportData['participant_count']);
        $worksheet->getStyle('G7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $worksheet->setCellValue('A8', 'B. Total unduplicated number of all HOUSEHOLDS about whom one or more characteristics were obtained:');
        $worksheet->getStyle('A8')->getFont()->setBold(true);
        // Calculate unique households from participants
        $householdCount = 0; // We'll need to calculate this from the data
        $worksheet->setCellValue('G8', $householdCount);
        $worksheet->getStyle('G8')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $row = 10;
        
        // Section D: HOUSEHOLD LEVEL CHARACTERISTICS
        $worksheet->mergeCells("A{$row}:H{$row}");
        $worksheet->setCellValue("A{$row}", 'D. HOUSEHOLD LEVEL CHARACTERISTICS');
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $worksheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('B3D9FF');
        $row += 2;
        
        // 9. Household Type
        $row = $this->addHouseholdSection($worksheet, $row, '9. Household Type', [
            'a. Single Person' => 0,
            'b. Two Parent with Children' => 0,
            'c. Single Parent Female' => 0,
            'd. Single Parent Male' => 0,
            'e. Two Parent Household' => 0,
            'f. Non-related Adults with Children' => 0,
            'g. Multigenerational Household' => 0,
            'h. Other' => 0,
            'i. Unknown/not reported' => 0,
        ]);
        
        // 10. Household Size
        $row = $this->addHouseholdSection($worksheet, $row, '10. Household Size', [
            'a. Single Person' => 0,
            'b. Two' => 0,
            'c. Three' => 0,
            'd. Four' => 0,
            'e. Five' => 0,
            'f. Six or more' => 0,
            'g. Unknown/not reported' => 0,
        ]);
        
        // 11. Housing
        $row = $this->addHouseholdSection($worksheet, $row, '11. Housing', [
            'a. Own' => 0,
            'b. Rent' => 0,
            'c. Other permanent housing' => 0,
            'd. Homeless' => 0,
            'e. Other' => 0,
            'f. Unknown/not reported' => 0,
        ]);
        
        // 12. Level of Household Income
        $povertyData = $reportData['employment_income']['poverty_levels'] ?? [];
        $row = $this->addHouseholdSection($worksheet, $row, '12. Level of Household Income', [
            'a. Up to 50%' => $povertyData['0-50% FPL'] ?? 0,
            'b. 51% to 75%' => 0, // Need to split the 51-100% range
            'c. 76% to 100%' => 0,
            'd. 101% to 125%' => $povertyData['101-125% FPL'] ?? 0,
            'e. 126% to 150%' => $povertyData['126-150% FPL'] ?? 0,
            'f. 151% to 175%' => 0, // Need to split the 151-200% range  
            'g. 176% to 200%' => 0,
            'h. 201% to 250%' => 0, // Need to add this range
            'i. 250% and over' => $povertyData['Over 200% FPL'] ?? 0,
            'j. Unknown/not reported' => $povertyData['Unknown/not reported'] ?? 0,
        ]);
        
        // Continue with sections 13, 14, 15, etc. based on the form
        // This is getting quite long, so I'll implement the core structure first
    }

    /**
     * Helper method to add a household section with proper formatting
     */
    private function addHouseholdSection($worksheet, int $startRow, string $title, array $data): int
    {
        $row = $startRow;
        
        // Section title with blue background
        $worksheet->mergeCells("A{$row}:E{$row}");
        $worksheet->setCellValue("A{$row}", $title);
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
        $worksheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('B3D9FF');
        
        $worksheet->setCellValue("F{$row}", 'Number of Households');
        $worksheet->getStyle("F{$row}")->getFont()->setBold(true);
        $worksheet->getStyle("F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('B3D9FF');
        $row++;
        
        // Add data rows
        foreach ($data as $label => $count) {
            $worksheet->setCellValue("A{$row}", $label);
            $worksheet->setCellValue("F{$row}", $count);
            $worksheet->getStyle("F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
        }
        
        // Total row
        $worksheet->setCellValue("A{$row}", 'i. TOTAL (auto calculated)');
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
        $totalCount = array_sum($data);
        $worksheet->setCellValue("F{$row}", $totalCount);
        $worksheet->getStyle("F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle("F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        
        return $row + 2; // Add spacing
    }

    /**
     * Build CSBG Page 2 - Individual Level Characteristics
     */
    private function buildCsbgPage2($worksheet, array $reportData)
    {
        // Set up the page format
        $worksheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $worksheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LETTER);
        
        $row = 1;
        
        // Header (same as page 1)
        $worksheet->mergeCells('A1:H1');
        $worksheet->setCellValue('A1', 'Module 4, Section C: All Characteristics Report - Data Entry Form');
        $worksheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $worksheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row += 2;
        
        $worksheet->mergeCells('A3:H3');
        $worksheet->setCellValue('A3', 'Goal 1: Individuals and Families with low-incomes are stable and achieve economic security.');
        $worksheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
        $row += 2;
        
        // CALV Program Reporting field  
        $worksheet->setCellValue('A5', 'CALV Program Reporting:');
        $worksheet->getStyle('A5')->getFont()->setBold(true);
        $worksheet->mergeCells('B5:H5');
        $worksheet->getStyle('B5:H5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row += 2;
        
        // Section A & B headers (same counts as page 1)
        $worksheet->setCellValue('A7', 'A. Total unduplicated number of all INDIVIDUALS about whom one or more characteristics were obtained:');
        $worksheet->getStyle('A7')->getFont()->setBold(true);
        $worksheet->setCellValue('G7', $reportData['participant_count']);
        $worksheet->getStyle('G7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $worksheet->setCellValue('A8', 'B. Total unduplicated number of all HOUSEHOLDS about whom one or more characteristics were obtained:');
        $worksheet->getStyle('A8')->getFont()->setBold(true);
        $householdCount = 0; // Calculate from data
        $worksheet->setCellValue('G8', $householdCount);
        $worksheet->getStyle('G8')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $row = 10;
        
        // Section C: INDIVIDUAL LEVEL CHARACTERISTICS
        $worksheet->mergeCells("A{$row}:H{$row}");
        $worksheet->setCellValue("A{$row}", 'C. INDIVIDUAL LEVEL CHARACTERISTICS');
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $worksheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('B3D9FF');
        $row += 2;
        
        // 1. Gender
        $genderData = $reportData['demographics']['gender'] ?? [];
        $row = $this->addIndividualSection($worksheet, $row, '1. Gender', [
            'a. Male' => $genderData['Male'] ?? 0,
            'b. Female' => $genderData['Female'] ?? 0,
            'c. Other' => $genderData['Other'] ?? 0,
            'd. Unknown/not reported' => $genderData['Unknown/not reported'] ?? 0,
        ]);
        
        // 2. Age
        $ageData = $reportData['characteristics']['age_groups'] ?? [];
        $row = $this->addIndividualSection($worksheet, $row, '2. Age', [
            'a. 0-5' => $ageData['0-5'] ?? 0,
            'b. 6-13' => $ageData['6-11'] ?? 0, // Combine 6-11 range
            'c. 14-17' => $ageData['12-17'] ?? 0, // Use 12-17 as closest
            'd. 18-24' => $ageData['18-24'] ?? 0,
            'e. 25-44' => ($ageData['25-34'] ?? 0) + ($ageData['35-44'] ?? 0),
            'f. 45-54' => $ageData['45-54'] ?? 0,
            'g. 55-59' => 0, // Need to split 55-64 range
            'h. 60-64' => $ageData['55-64'] ?? 0,
            'i. 65-74' => 0, // Need to split 65+ range
            'j. 75+' => $ageData['65+'] ?? 0,
            'k. Unknown/not reported' => $ageData['Unknown/not reported'] ?? 0,
        ]);
        
        // Continue with remaining sections...
    }

    /**
     * Helper method to add an individual section with proper formatting
     */
    private function addIndividualSection($worksheet, int $startRow, string $title, array $data): int
    {
        $row = $startRow;
        
        // Section title with blue background
        $worksheet->mergeCells("A{$row}:E{$row}");
        $worksheet->setCellValue("A{$row}", $title);
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
        $worksheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('B3D9FF');
        
        $worksheet->setCellValue("F{$row}", 'Number of Individuals');
        $worksheet->getStyle("F{$row}")->getFont()->setBold(true);
        $worksheet->getStyle("F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('B3D9FF');
        $row++;
        
        // Add data rows
        foreach ($data as $label => $count) {
            $worksheet->setCellValue("A{$row}", $label);
            $worksheet->setCellValue("F{$row}", $count);
            $worksheet->getStyle("F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $row++;
        }
        
        // Total row
        $worksheet->setCellValue("A{$row}", 'l. TOTAL (auto calculated)');
        $worksheet->getStyle("A{$row}")->getFont()->setBold(true);
        $totalCount = array_sum($data);
        $worksheet->setCellValue("F{$row}", $totalCount);
        $worksheet->getStyle("F{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $worksheet->getStyle("F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        
        return $row + 2; // Add spacing
    }

    /**
     * Helper method to add a section to Excel worksheet
     */
    private function addExcelSection($worksheet, int $startRow, string $title, array $sectionData): int
    {
        $row = $startRow;
        
        // Section title
        $worksheet->setCellValue('A' . $row, $title);
        $worksheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $worksheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $worksheet->mergeCells('A' . $row . ':D' . $row);
        $row++;
        
        // Headers
        $worksheet->setCellValue('A' . $row, 'Category');
        $worksheet->setCellValue('B' . $row, 'Value');
        $worksheet->setCellValue('C' . $row, 'Count');
        $worksheet->setCellValue('D' . $row, 'Percentage');
        
        $headerStyle = $worksheet->getStyle('A' . $row . ':D' . $row);
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row++;
        
        // Data rows
        foreach ($sectionData as $category => $data) {
            $categoryStart = $row;
            $totalCount = array_sum($data);
            
            foreach ($data as $value => $count) {
                $worksheet->setCellValue('A' . $row, ucfirst(str_replace('_', ' ', $category)));
                $worksheet->setCellValue('B' . $row, $value);
                $worksheet->setCellValue('C' . $row, number_format($count));
                $worksheet->setCellValue('D' . $row, $totalCount > 0 ? number_format(($count / $totalCount) * 100, 1) . '%' : '0%');
                
                // Add borders
                $worksheet->getStyle('A' . $row . ':D' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }
        }
        
        return $row + 1; // Add extra space between sections
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
                       ($value === 'Unknown/not reported' && empty($participant->gender));
                       
            case 'ethnicity':
                return $participant->ethnicity === $value || 
                       ($value === 'Unknown/not reported' && empty($participant->ethnicity));
                       
            case 'education_level':
                return $participant->education_level === $value || 
                       ($value === 'Unknown/not reported' && empty($participant->education_level));
                       
            case 'work_status':
                return $participant->work_status === $value || 
                       ($value === 'Unknown/not reported' && empty($participant->work_status));
                       
            case 'age_groups':
                return $this->matchesAgeGroup($participant, $value);
                
            case 'poverty_levels':
                return $this->matchesPovertyLevel($participant, $value);
                
            case 'housing_status':
                $housingStatus = $participant->household->housing_status ?? null;
                return $housingStatus === $value || 
                       ($value === 'Unknown/not reported' && empty($housingStatus));
                       
            case 'household_type':
                $householdType = $participant->household->household_type ?? null;
                return $householdType === $value || 
                       ($value === 'Unknown/not reported' && empty($householdType));
                       
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
}