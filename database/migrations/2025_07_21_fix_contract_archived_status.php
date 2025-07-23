<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Contract;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get list of contract IDs that should be archived from original database
        $archivedContractTitles = [
            '2020HCP_LCCDBG_CV_ Foreclosure Mitigation Counseling',
            '2021CADCB_BethlehemCDBG_SouthsideFacades',
            '2021CADCB_BethlehemCDBG_TrailAmenities',
            '2021HCP_LCAHTF',
            '2022-2023CADA_AllentownCDBG',
            '2022HRP_NCPHARE/RTTEnergyRoofHVAC_West Ward',
            '2022HRP_NCPHARE/RTTLEAD_ East Bangor',
            '2022CABH_PHARERTT_EnergyEfficiancy and Mobility',
            // Add more titles as needed
        ];
        
        // Archive contracts based on title match
        foreach ($archivedContractTitles as $title) {
            Contract::where('title', 'LIKE', '%' . $title . '%')
                ->update([
                    'archived' => true,
                    'date_archived' => now(),
                ]);
        }
        
        // Also archive contracts that have expired more than 6 months ago
        // This is a reasonable assumption for contracts that should be archived
        $sixMonthsAgo = now()->subMonths(6);
        Contract::where('archived', false)
            ->where('end_date', '<', $sixMonthsAgo)
            ->whereNotNull('end_date')
            ->update([
                'archived' => true,
                'date_archived' => now(),
            ]);
        
        echo "Archived contracts updated.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset all archived flags
        Contract::where('archived', true)
            ->update([
                'archived' => false,
                'date_archived' => null,
            ]);
    }
};