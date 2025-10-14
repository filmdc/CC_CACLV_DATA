<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Contract;
use App\Models\ContractReportDueDate;
use App\Models\DueDateEmail;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the imported data
        require base_path('report_due_dates_with_emails.php');
        
        // Build contract mapping based on contract numbers and titles from original database
        $contractMapping = [
            77 => ['number' => 'C000082084', 'title' => '2022-2026CALV_CSBG'],
            78 => ['number' => null, 'title' => '2020HCP_LCCDBG_CV_ Foreclosure Mitigation Counseling'],
            102 => ['number' => null, 'title' => '2022CALV_LarosFoundation'],
            109 => ['number' => null, 'title' => '2022REJ_JustBorn_COTL'],
            117 => ['number' => '22-1066', 'title' => '2023SHFB_NCTEFAP'],
            118 => ['number' => null, 'title' => '2023_2026RTCLF_Truist_SBLF Pilot'],
            119 => ['number' => null, 'title' => '2023-2026REJ_UnitedWay_SHE_ Gen Next'],
            120 => ['number' => null, 'title' => '2023CALV_LVCFSynergyFund'],
            153 => ['number' => null, 'title' => '2023HCP_PNCFoundation'],
            158 => ['number' => null, 'title' => '2023HRP_NCCDBG_HomeImprovementProgram'],
            159 => ['number' => null, 'title' => '2023SSS_SylviaPerkinsCharitableTrust'],
            160 => ['number' => 'C000084605', 'title' => '2023HRP_PADCED_ARPACOVID19_WholeHomeRepairs'],
            300 => ['number' => 'C000086369', 'title' => '2024RTCLF_PACDFI_ARPA_HDBA_SBGII'],
            301 => ['number' => null, 'title' => '2024REJ_ Nature Nurture Center_ CBO _ Create Connections: Linking a Vision of Resilience to Action'],
            303 => ['number' => null, 'title' => '2024SHFB_FA_StarbucksGlobalMonthofGood'],
            304 => ['number' => null, 'title' => '2021_2026SHFB_LehighCountyTEFAP'],
            306 => ['number' => '306', 'title' => '2021_SHFB_CSFP_SeniorFoodBoxes'],
            310 => ['number' => null, 'title' => '2023HCP_NCCDBG_Housing Counseling Program'],
            319 => ['number' => null, 'title' => '2024HCPHRP_Wells Fargo'],
            320 => ['number' => null, 'title' => '2024CADA_Just Born'],
            322 => ['number' => null, 'title' => '2024HCP_Univest Financial'],
            327 => ['number' => '327', 'title' => '2023-2025SHFB_NCCDBG_FreshFoodDistribution'],
            328 => ['number' => null, 'title' => '2024HCP_ PNC'],
            330 => ['number' => null, 'title' => '2024SSS_TrexlerTrust'],
            335 => ['number' => '335', 'title' => '2024SHFB_StarbucksFoodShareCapacity'],
            336 => ['number' => '700-01-9923-0404-40', 'title' => '2024SSS_CityofAllentownESG_Rapid Rehousing'],
            338 => ['number' => null, 'title' => '2024-2025SHFB_PikeCDBG'],
            339 => ['number' => null, 'title' => '2024SSS_TJX Foundation'],
            348 => ['number' => null, 'title' => '2024CAH_PHFA PHARE_ Healthy Homes'],
            349 => ['number' => '349', 'title' => '2024SHFB_AldiRetailRecovery'],
            354 => ['number' => '354', 'title' => '2024SHFB_Sanofi_Monroe County'],
            355 => ['number' => '355', 'title' => '2024SHFB_VernoCanova The Seed Farm'],
            362 => ['number' => null, 'title' => '2024-2025SHFB_Feeding PA_PASS_MOU'],
            363 => ['number' => null, 'title' => '2024SHFB_NCCDBG_Pantry Improvement'],
            364 => ['number' => null, 'title' => '2024CADB_ M&T'],
            366 => ['number' => null, 'title' => '2024HCP_SSS_Santander'],
            367 => ['number' => '24-AGNG-184', 'title' => '2024CALV_LC Aging and Adult Services'],
            370 => ['number' => '370', 'title' => '2024SHFB_FeedingAmerica Mobile Food Pantry Cooler Capacity Grant'],
            372 => ['number' => null, 'title' => '2024RTCLF_M&T'],
            376 => ['number' => null, 'title' => '2024SSS_Provident Bank Foundation'],
            377 => ['number' => null, 'title' => '2024SHFB_Capital BlueCross Foundation'],
            378 => ['number' => null, 'title' => '2024RTCLF_TD Bank'],
            379 => ['number' => '379', 'title' => '2024SHFB_ShopRite Partners in Caring'],
            383 => ['number' => '383', 'title' => '2024SHFB_HorizonFoundation'],
            384 => ['number' => '384', 'title' => '2024SHFB_EW Kaufmann'],
        ];
        
        // Try to import each due date
        $imported = 0;
        $skipped = 0;
        
        foreach ($reportDueDatesWithEmails as $originalDueDate) {
            // Try to find the contract
            $contract = null;
            
            // First try by mapping
            if (isset($contractMapping[$originalDueDate['contract_id']])) {
                $mapping = $contractMapping[$originalDueDate['contract_id']];
                
                // Try by contract number first
                if ($mapping['number']) {
                    $contract = Contract::where('contract_number', $mapping['number'])->first();
                }
                
                // If not found by number, try by title
                if (!$contract && $mapping['title']) {
                    $contract = Contract::where('title', 'LIKE', '%' . $mapping['title'] . '%')->first();
                }
            }
            
            // If not found, try by ID directly (in case some IDs match)
            if (!$contract) {
                $contract = Contract::find($originalDueDate['contract_id']);
            }
            
            if (!$contract) {
                $skipped++;
                continue; // Skip if contract doesn't exist
            }
            
            // Determine report type from description
            $reportType = $this->determineReportType($originalDueDate['description']);
            
            // Convert status
            $status = $originalDueDate['status'] === 'incomplete' ? 'pending' : 'completed';
            
            // Create the due date record
            $lookup = [
                'contract_id' => $contract->id,
                'due_date' => $originalDueDate['due_date'],
                'description' => $originalDueDate['description'],
            ];

            $dueDate = ContractReportDueDate::firstOrCreate(
                $lookup,
                array_merge($lookup, [
                    'status' => $status,
                    'report_type' => $reportType,
                    'reminder_intervals' => [30, 15, 7, 3, 1],
                    'active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ])
            );

            // Attach emails
            foreach ($originalDueDate['email_addresses'] as $email) {
                $dueDateEmail = DueDateEmail::where('email', $email)->first();
                if ($dueDateEmail) {
                    $dueDate->emails()->syncWithoutDetaching([
                        $dueDateEmail->id => [
                            'reminder_intervals' => json_encode([30, 15, 7, 3, 1]),
                            'active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ]);
                }
            }

            $imported++;
        }
        
        echo "Import complete: $imported imported, $skipped skipped\n";
    }
    
    /**
     * Determine report type from description
     */
    private function determineReportType($description)
    {
        if (!$description) {
            return null;
        }
        
        $desc = strtolower(strip_tags($description));
        
        if (strpos($desc, 'quarterly') !== false || strpos($desc, 'quarter') !== false) {
            return 'quarterly';
        }
        if (strpos($desc, 'annual') !== false || strpos($desc, 'yearly') !== false) {
            return 'annual';
        }
        if (strpos($desc, 'semi-annual') !== false || strpos($desc, 'semi annual') !== false) {
            return 'semi-annual';
        }
        if (strpos($desc, 'final') !== false) {
            return 'final';
        }
        if (strpos($desc, 'monthly') !== false) {
            return 'monthly';
        }
        
        return 'other';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible - would need to track which records were imported
    }
};
