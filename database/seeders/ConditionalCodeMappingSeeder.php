<?php

namespace Database\Seeders;

use App\Models\ConditionalCodeMapping;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConditionalCodeMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates conditional code mapping rules to enforce dimensional
     * compatibility in Sage Intacct. Rules ensure that campaigns only use
     * compatible solicitation and sub-solicitation codes.
     */
    public function run(): void
    {
        // Clear existing rules
        ConditionalCodeMapping::truncate();

        // =========================================================================
        // FOOD BANK CAMPAIGNS (FB, SF, SHFB_FY22-23)
        // Location: P1400 (Second Harvest Food Bank)
        // Only allow: Service codes S14xxx, Fund F8000
        // =========================================================================

        $foodBankCampaigns = ['FB', 'SF', 'SHFB_FY22-23'];

        foreach ($foodBankCampaigns as $campaign) {
            // Solicit Codes → Fund F8000 (all Food Bank campaigns use general operating fund)
            $this->createMapping($campaign, 'solicit_code', 'ANNLFUND', 'F8000', 'Food Bank - Annual Fund');
            $this->createMapping($campaign, 'solicit_code', 'ANNUFUND', 'F8000', 'Food Bank - Annual Fund (alternate)');
            $this->createMapping($campaign, 'solicit_code', 'EVENTS', 'F8000', 'Food Bank - Events');
            $this->createMapping($campaign, 'solicit_code', 'EVENTSPONSORSHIP', 'F8000', 'Food Bank - Event Sponsorship');
            $this->createMapping($campaign, 'solicit_code', 'NEWSLETTER', 'F8000', 'Food Bank - Newsletter');
            $this->createMapping($campaign, 'solicit_code', 'WEBDONATIONS', 'F8000', 'Food Bank - Web Donations');
            $this->createMapping($campaign, 'solicit_code', 'SHFB_WEBDONATIONS_FY22-23', 'F8000', 'Food Bank - Web Donations FY22-23');

            // Sub-Solicit Codes → Service S14999 (Food Bank Undesignated)
            $this->createMapping($campaign, 'sub_solicit_code', 'FBHPAPPL', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', 'FBPPAPPL', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', 'FBSUNEWS', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', 'SHFBHPAPPL', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', 'TAKACSPONS', 'S14999', 'SHFB999-Undesignated Service - Sponsorship');
            $this->createMapping($campaign, 'sub_solicit_code', 'TAKACTDONS', 'S14999', 'SHFB999-Undesignated Service - Donations');
            $this->createMapping($campaign, 'sub_solicit_code', 'TAKACTTIXS', 'S14999', 'SHFB999-Undesignated Service - Tickets');

            // Additional Food Bank sub-solicitation codes
            $this->createMapping($campaign, 'sub_solicit_code', '24BSDMDI', 'S14999', 'SHFB999-Undesignated Service - 2024 Direct Mail');
            $this->createMapping($campaign, 'sub_solicit_code', '24CHDI', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24FLDI', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24HAAQBN', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24HADM03', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24HADM05', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24HADM06', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24HADMDI', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24HNDM05', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24MMDI', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24NDDM04', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24NDDM05', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24NDDM06', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24PADI', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '24THDM04', 'S14999', 'SHFB999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', '25MMDI', 'S14999', 'SHFB999-Undesignated Service');
        }

        // =========================================================================
        // SHELTER CAMPAIGNS (SS, SSS_FY22-23, SSS_FY23-24)
        // Location: P1600 (Sixth Street Shelter)
        // Only allow: Service codes S16xxx, Fund F8000
        // =========================================================================

        $shelterCampaigns = ['SS', 'SSS_FY22-23', 'SSS_FY23-24'];

        foreach ($shelterCampaigns as $campaign) {
            // Solicit Codes → Fund F8000
            $this->createMapping($campaign, 'solicit_code', 'ANNLFUND', 'F8000', 'Shelter - Annual Fund');
            $this->createMapping($campaign, 'solicit_code', 'ANNUFUND', 'F8000', 'Shelter - Annual Fund (alternate)');
            $this->createMapping($campaign, 'solicit_code', 'WEBDONATIONS', 'F8000', 'Shelter - Web Donations');
            $this->createMapping($campaign, 'solicit_code', 'SSSWEB_FY22-23', 'F8000', 'Shelter - Web FY22-23');
            $this->createMapping($campaign, 'solicit_code', 'SSSWEB_FY23-24', 'F8000', 'Shelter - Web FY23-24');
            $this->createMapping($campaign, 'solicit_code', 'SSS_UW_FY22-23', 'F8000', 'Shelter - United Way FY22-23');
            $this->createMapping($campaign, 'solicit_code', 'EVENTS', 'F8000', 'Shelter - Events');
            $this->createMapping($campaign, 'solicit_code', 'NEWSLETTER', 'F8000', 'Shelter - Newsletter');

            // Sub-Solicit Codes → Service S16999 (Shelter Undesignated)
            $this->createMapping($campaign, 'sub_solicit_code', 'SSSFALLNWSL', 'S16999', 'SSS999-Undesignated Service - Fall Newsletter');
            $this->createMapping($campaign, 'sub_solicit_code', 'SSSPNGNWSL', 'S16999', 'SSS999-Undesignated Service - Spring Newsletter');
            $this->createMapping($campaign, 'sub_solicit_code', 'SSSWEBPAGE', 'S16999', 'SSS999-Undesignated Service - Web Page');
        }

        // =========================================================================
        // ADMIN CAMPAIGNS (AD)
        // Location: P1000 (ADMIN-Administration)
        // Allow: Service codes S10xxx, Fund F8000
        // =========================================================================

        $adminCampaigns = ['AD'];

        foreach ($adminCampaigns as $campaign) {
            // Solicit Codes → Fund F8000
            $this->createMapping($campaign, 'solicit_code', 'ANNLFUND', 'F8000', 'Admin - Annual Fund');
            $this->createMapping($campaign, 'solicit_code', 'ANNUFUND', 'F8000', 'Admin - Annual Fund (alternate)');
            $this->createMapping($campaign, 'solicit_code', 'WEBDONATIONS', 'F8000', 'Admin - Web Donations');
            $this->createMapping($campaign, 'solicit_code', 'EVENTS', 'F8000', 'Admin - Events');

            // Sub-Solicit Codes → Service S10999 or S10160
            $this->createMapping($campaign, 'sub_solicit_code', 'ALE', 'S10999', 'ADMIN999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', 'CALVHMAP', 'S10999', 'ADMIN999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', 'CALVHMAPP', 'S10999', 'ADMIN999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', 'CAUSEMARK', 'S10999', 'ADMIN999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', 'CORPBUSTGRT', 'S10999', 'ADMIN999-Undesignated Service - Corporate Grant');
            $this->createMapping($campaign, 'sub_solicit_code', 'CORPMATCGFT', 'S10999', 'ADMIN999-Undesignated Service - Corp Matching');
            $this->createMapping($campaign, 'sub_solicit_code', 'FNDTRUSTGRT', 'S10999', 'ADMIN999-Undesignated Service - Foundation Grant');
            $this->createMapping($campaign, 'sub_solicit_code', 'ONLINEFALLNEWSLETTER', 'S10999', 'ADMIN999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', 'UNSOLICIT', 'S10999', 'ADMIN999-Undesignated Service - Unsolicited');
            $this->createMapping($campaign, 'sub_solicit_code', 'USOLFAITHBCONTB', 'S10999', 'ADMIN999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', 'USOLFNDCONTRIB', 'S10999', 'ADMIN999-Undesignated Service');
            $this->createMapping($campaign, 'sub_solicit_code', 'UWEEGIVNG', 'S10999', 'ADMIN999-Undesignated Service - United Way');

            // Annual Meeting specific service
            $this->createMapping($campaign, 'sub_solicit_code', 'AMO', 'S10160', 'ADMIN160-Annual Meeting');
            $this->createMapping($campaign, 'sub_solicit_code', 'ANLMTGDO', 'S10160', 'ADMIN160-Annual Meeting - Donations');
            $this->createMapping($campaign, 'sub_solicit_code', 'ANLMTGSPONS', 'S10160', 'ADMIN160-Annual Meeting - Sponsorship');
            $this->createMapping($campaign, 'sub_solicit_code', 'ANLMTGTIX', 'S10160', 'ADMIN160-Annual Meeting - Tickets');
            $this->createMapping($campaign, 'sub_solicit_code', 'ANNLFUND', 'S10160', 'ADMIN160-Annual Meeting - Annual Fund');
        }

        // =========================================================================
        // CAD ALLENTOWN CAMPAIGNS (CA)
        // Location: P2000 (CADCA-Allentown)
        // Allow: Service codes S20xxx, Fund F8000
        // =========================================================================

        $cadcaCampaigns = ['CA'];

        foreach ($cadcaCampaigns as $campaign) {
            // Solicit Codes → Fund F8000
            $this->createMapping($campaign, 'solicit_code', 'ANNLFUND', 'F8000', 'CADCA - Annual Fund');
            $this->createMapping($campaign, 'solicit_code', 'EVENTS', 'F8000', 'CADCA - Events');
            $this->createMapping($campaign, 'solicit_code', 'WEBDONATIONS', 'F8000', 'CADCA - Web Donations');

            // Sub-Solicit Codes → Service S20999
            $this->createMapping($campaign, 'sub_solicit_code', 'CABSTMREVNT', 'S20999', 'CADCA999-Undesignated Service - Best Move Event');
        }

        // =========================================================================
        // CAD BETHLEHEM CAMPAIGNS (CB)
        // Location: P3000 (CADCB-Bethlehem)
        // Allow: Service codes S30xxx, Fund F8000
        // =========================================================================

        $cadcbCampaigns = ['CB'];

        foreach ($cadcbCampaigns as $campaign) {
            // Solicit Codes → Fund F8000
            $this->createMapping($campaign, 'solicit_code', 'ANNLFUND', 'F8000', 'CADCB - Annual Fund');
            $this->createMapping($campaign, 'solicit_code', 'EVENTS', 'F8000', 'CADCB - Events');
            $this->createMapping($campaign, 'solicit_code', 'WEBDONATIONS', 'F8000', 'CADCB - Web Donations');

            // Add sub-solicit codes as needed (S30xxx services)
            // Currently no specific sub-solicit codes documented for CADCB
        }

        // =========================================================================
        // RACIAL & ETHNIC JUSTICE CAMPAIGNS (GN, RJ)
        // Location: P1300 (REJ-Racial and Ethnic Justice)
        // Allow: Service codes S13xxx, Fund F8000
        // =========================================================================

        $rejCampaigns = ['GN', 'RJ'];

        foreach ($rejCampaigns as $campaign) {
            // Solicit Codes → Fund F8000
            $this->createMapping($campaign, 'solicit_code', 'ANNLFUND', 'F8000', 'REJ - Annual Fund');
            $this->createMapping($campaign, 'solicit_code', 'WEBDONATIONS', 'F8000', 'REJ - Web Donations');
            $this->createMapping($campaign, 'solicit_code', 'EVENTS', 'F8000', 'REJ - Events');

            // Add sub-solicit codes as needed (S13xxx services)
        }

        // =========================================================================
        // HOUSING COUNSELING CAMPAIGNS (HC)
        // Location: P1100 (CAFS-Housing Counseling)
        // Allow: Service codes S11xxx, Fund F8000
        // =========================================================================

        $hcCampaigns = ['HC'];

        foreach ($hcCampaigns as $campaign) {
            // Solicit Codes → Fund F8000
            $this->createMapping($campaign, 'solicit_code', 'ANNLFUND', 'F8000', 'Housing Counseling - Annual Fund');
            $this->createMapping($campaign, 'solicit_code', 'WEBDONATIONS', 'F8000', 'Housing Counseling - Web Donations');

            // Add sub-solicit codes as needed (S11xxx services)
        }

        // =========================================================================
        // RISING TIDE CAMPAIGNS (RT)
        // Location: P5000 (RTCLF-Rising Tide)
        // Allow: Service codes S50xxx, Fund F8000
        // =========================================================================

        $rtCampaigns = ['RT'];

        foreach ($rtCampaigns as $campaign) {
            // Solicit Codes → Fund F8000
            $this->createMapping($campaign, 'solicit_code', 'ANNLFUND', 'F8000', 'Rising Tide - Annual Fund');
            $this->createMapping($campaign, 'solicit_code', 'WEBDONATIONS', 'F8000', 'Rising Tide - Web Donations');

            // Add sub-solicit codes as needed (S50xxx services)
        }

        $this->command->info('Conditional code mappings seeded successfully!');
        $this->command->info('Total mappings created: ' . ConditionalCodeMapping::count());
    }

    /**
     * Helper method to create a mapping
     */
    private function createMapping(string $campaign, string $fieldType, string $dpCode, string $intacctCode, string $notes): void
    {
        ConditionalCodeMapping::create([
            'campaign_code' => $campaign,
            'field_type' => $fieldType,
            'dp_code' => $dpCode,
            'intacct_code' => $intacctCode,
            'is_active' => true,
            'priority' => 0,
            'notes' => $notes,
        ]);
    }
}
