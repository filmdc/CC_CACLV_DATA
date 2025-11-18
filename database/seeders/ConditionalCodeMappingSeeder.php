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
     * compatibility in Sage Intacct. Rules ensure that Intacct locations only use
     * compatible solicitation and sub-solicitation codes.
     *
     * NOTE: Rules are keyed by Sage Intacct location codes (P1000, P1400, etc.),
     * not DonorPerfect campaign codes. This is because dimensional compatibility
     * is a Sage Intacct concept, and multiple DP campaigns can map to the same
     * Intacct location.
     */
    public function run(): void
    {
        // Clear existing rules
        ConditionalCodeMapping::truncate();

        // =========================================================================
        // FOOD BANK LOCATION: P1400 (Second Harvest Food Bank)
        // DonorPerfect campaigns that map here: FB, SF, SHFB_FY22-23
        // Only allow: Service codes S14xxx, Fund F8000
        // =========================================================================

        $location = 'P1400';

        // Solicit Codes → Fund F8000 (all Food Bank campaigns use general operating fund)
        $this->createMapping($location, 'solicit_code', 'ANNLFUND', 'F8000', 'Food Bank - Annual Fund');
        $this->createMapping($location, 'solicit_code', 'ANNUFUND', 'F8000', 'Food Bank - Annual Fund (alternate)');
        $this->createMapping($location, 'solicit_code', 'EVENTS', 'F8000', 'Food Bank - Events');
        $this->createMapping($location, 'solicit_code', 'EVENTSPONSORSHIP', 'F8000', 'Food Bank - Event Sponsorship');
        $this->createMapping($location, 'solicit_code', 'NEWSLETTER', 'F8000', 'Food Bank - Newsletter');
        $this->createMapping($location, 'solicit_code', 'WEBDONATIONS', 'F8000', 'Food Bank - Web Donations');
        $this->createMapping($location, 'solicit_code', 'SHFB_WEBDONATIONS_FY22-23', 'F8000', 'Food Bank - Web Donations FY22-23');

        // Sub-Solicit Codes → Service S14999 (Food Bank Undesignated)
        $this->createMapping($location, 'sub_solicit_code', 'FBHPAPPL', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', 'FBPPAPPL', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', 'FBSUNEWS', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', 'SHFBHPAPPL', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', 'TAKACSPONS', 'S14999', 'SHFB999-Undesignated Service - Sponsorship');
        $this->createMapping($location, 'sub_solicit_code', 'TAKACTDONS', 'S14999', 'SHFB999-Undesignated Service - Donations');
        $this->createMapping($location, 'sub_solicit_code', 'TAKACTTIXS', 'S14999', 'SHFB999-Undesignated Service - Tickets');

        // Additional Food Bank sub-solicitation codes
        $this->createMapping($location, 'sub_solicit_code', '24BSDMDI', 'S14999', 'SHFB999-Undesignated Service - 2024 Direct Mail');
        $this->createMapping($location, 'sub_solicit_code', '24CHDI', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24FLDI', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24HAAQBN', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24HADM03', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24HADM05', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24HADM06', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24HADMDI', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24HNDM05', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24MMDI', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24NDDM04', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24NDDM05', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24NDDM06', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24PADI', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '24THDM04', 'S14999', 'SHFB999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', '25MMDI', 'S14999', 'SHFB999-Undesignated Service');

        // =========================================================================
        // SHELTER LOCATION: P1600 (Sixth Street Shelter)
        // DonorPerfect campaigns that map here: SS, SSS_FY22-23, SSS_FY23-24
        // Only allow: Service codes S16xxx, Fund F8000
        // =========================================================================

        $location = 'P1600';

        // Solicit Codes → Fund F8000
        $this->createMapping($location, 'solicit_code', 'ANNLFUND', 'F8000', 'Shelter - Annual Fund');
        $this->createMapping($location, 'solicit_code', 'ANNUFUND', 'F8000', 'Shelter - Annual Fund (alternate)');
        $this->createMapping($location, 'solicit_code', 'WEBDONATIONS', 'F8000', 'Shelter - Web Donations');
        $this->createMapping($location, 'solicit_code', 'SSSWEB_FY22-23', 'F8000', 'Shelter - Web FY22-23');
        $this->createMapping($location, 'solicit_code', 'SSSWEB_FY23-24', 'F8000', 'Shelter - Web FY23-24');
        $this->createMapping($location, 'solicit_code', 'SSS_UW_FY22-23', 'F8000', 'Shelter - United Way FY22-23');
        $this->createMapping($location, 'solicit_code', 'EVENTS', 'F8000', 'Shelter - Events');
        $this->createMapping($location, 'solicit_code', 'NEWSLETTER', 'F8000', 'Shelter - Newsletter');

        // Sub-Solicit Codes → Service S16999 (Shelter Undesignated)
        $this->createMapping($location, 'sub_solicit_code', 'SSSFALLNWSL', 'S16999', 'SSS999-Undesignated Service - Fall Newsletter');
        $this->createMapping($location, 'sub_solicit_code', 'SSSPNGNWSL', 'S16999', 'SSS999-Undesignated Service - Spring Newsletter');
        $this->createMapping($location, 'sub_solicit_code', 'SSSWEBPAGE', 'S16999', 'SSS999-Undesignated Service - Web Page');

        // =========================================================================
        // ADMIN LOCATION: P1000 (ADMIN-Administration)
        // DonorPerfect campaigns that map here: AD
        // Allow: Service codes S10xxx, Fund F8000
        // =========================================================================

        $location = 'P1000';

        // Solicit Codes → Fund F8000
        $this->createMapping($location, 'solicit_code', 'ANNLFUND', 'F8000', 'Admin - Annual Fund');
        $this->createMapping($location, 'solicit_code', 'ANNUFUND', 'F8000', 'Admin - Annual Fund (alternate)');
        $this->createMapping($location, 'solicit_code', 'WEBDONATIONS', 'F8000', 'Admin - Web Donations');
        $this->createMapping($location, 'solicit_code', 'EVENTS', 'F8000', 'Admin - Events');

        // Sub-Solicit Codes → Service S10999 or S10160
        $this->createMapping($location, 'sub_solicit_code', 'ALE', 'S10999', 'ADMIN999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', 'CALVHMAP', 'S10999', 'ADMIN999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', 'CALVHMAPP', 'S10999', 'ADMIN999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', 'CAUSEMARK', 'S10999', 'ADMIN999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', 'CORPBUSTGRT', 'S10999', 'ADMIN999-Undesignated Service - Corporate Grant');
        $this->createMapping($location, 'sub_solicit_code', 'CORPMATCGFT', 'S10999', 'ADMIN999-Undesignated Service - Corp Matching');
        $this->createMapping($location, 'sub_solicit_code', 'FNDTRUSTGRT', 'S10999', 'ADMIN999-Undesignated Service - Foundation Grant');
        $this->createMapping($location, 'sub_solicit_code', 'ONLINEFALLNEWSLETTER', 'S10999', 'ADMIN999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', 'UNSOLICIT', 'S10999', 'ADMIN999-Undesignated Service - Unsolicited');
        $this->createMapping($location, 'sub_solicit_code', 'USOLFAITHBCONTB', 'S10999', 'ADMIN999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', 'USOLFNDCONTRIB', 'S10999', 'ADMIN999-Undesignated Service');
        $this->createMapping($location, 'sub_solicit_code', 'UWEEGIVNG', 'S10999', 'ADMIN999-Undesignated Service - United Way');

        // Annual Meeting specific service
        $this->createMapping($location, 'sub_solicit_code', 'AMO', 'S10160', 'ADMIN160-Annual Meeting');
        $this->createMapping($location, 'sub_solicit_code', 'ANLMTGDO', 'S10160', 'ADMIN160-Annual Meeting - Donations');
        $this->createMapping($location, 'sub_solicit_code', 'ANLMTGSPONS', 'S10160', 'ADMIN160-Annual Meeting - Sponsorship');
        $this->createMapping($location, 'sub_solicit_code', 'ANLMTGTIX', 'S10160', 'ADMIN160-Annual Meeting - Tickets');
        $this->createMapping($location, 'sub_solicit_code', 'ANNLFUND', 'S10160', 'ADMIN160-Annual Meeting - Annual Fund');

        // =========================================================================
        // CADCA LOCATION: P2000 (CADCA-Allentown)
        // DonorPerfect campaigns that map here: CA
        // Allow: Service codes S20xxx, Fund F8000
        // =========================================================================

        $location = 'P2000';

        // Solicit Codes → Fund F8000
        $this->createMapping($location, 'solicit_code', 'ANNLFUND', 'F8000', 'CADCA - Annual Fund');
        $this->createMapping($location, 'solicit_code', 'EVENTS', 'F8000', 'CADCA - Events');
        $this->createMapping($location, 'solicit_code', 'WEBDONATIONS', 'F8000', 'CADCA - Web Donations');

        // Sub-Solicit Codes → Service S20999
        $this->createMapping($location, 'sub_solicit_code', 'CABSTMREVNT', 'S20999', 'CADCA999-Undesignated Service - Best Move Event');

        // =========================================================================
        // CADCB LOCATION: P3000 (CADCB-Bethlehem)
        // DonorPerfect campaigns that map here: CB
        // Allow: Service codes S30xxx, Fund F8000
        // =========================================================================

        $location = 'P3000';

        // Solicit Codes → Fund F8000
        $this->createMapping($location, 'solicit_code', 'ANNLFUND', 'F8000', 'CADCB - Annual Fund');
        $this->createMapping($location, 'solicit_code', 'EVENTS', 'F8000', 'CADCB - Events');
        $this->createMapping($location, 'solicit_code', 'WEBDONATIONS', 'F8000', 'CADCB - Web Donations');

        // Add sub-solicit codes as needed (S30xxx services)
        // Currently no specific sub-solicit codes documented for CADCB

        // =========================================================================
        // REJ LOCATION: P1300 (REJ-Racial and Ethnic Justice)
        // DonorPerfect campaigns that map here: GN, RJ
        // Allow: Service codes S13xxx, Fund F8000
        // =========================================================================

        $location = 'P1300';

        // Solicit Codes → Fund F8000
        $this->createMapping($location, 'solicit_code', 'ANNLFUND', 'F8000', 'REJ - Annual Fund');
        $this->createMapping($location, 'solicit_code', 'WEBDONATIONS', 'F8000', 'REJ - Web Donations');
        $this->createMapping($location, 'solicit_code', 'EVENTS', 'F8000', 'REJ - Events');

        // Add sub-solicit codes as needed (S13xxx services)

        // =========================================================================
        // HOUSING COUNSELING LOCATION: P1100 (CAFS-Housing Counseling)
        // DonorPerfect campaigns that map here: HC
        // Allow: Service codes S11xxx, Fund F8000
        // =========================================================================

        $location = 'P1100';

        // Solicit Codes → Fund F8000
        $this->createMapping($location, 'solicit_code', 'ANNLFUND', 'F8000', 'Housing Counseling - Annual Fund');
        $this->createMapping($location, 'solicit_code', 'WEBDONATIONS', 'F8000', 'Housing Counseling - Web Donations');

        // Add sub-solicit codes as needed (S11xxx services)

        // =========================================================================
        // RISING TIDE LOCATION: P5000 (RTCLF-Rising Tide)
        // DonorPerfect campaigns that map here: RT
        // Allow: Service codes S50xxx, Fund F8000
        // =========================================================================

        $location = 'P5000';

        // Solicit Codes → Fund F8000
        $this->createMapping($location, 'solicit_code', 'ANNLFUND', 'F8000', 'Rising Tide - Annual Fund');
        $this->createMapping($location, 'solicit_code', 'WEBDONATIONS', 'F8000', 'Rising Tide - Web Donations');

        // Add sub-solicit codes as needed (S50xxx services)

        $this->command->info('Conditional code mappings seeded successfully!');
        $this->command->info('Total mappings created: ' . ConditionalCodeMapping::count());
    }

    /**
     * Helper method to create a mapping
     */
    private function createMapping(string $intacctLocationCode, string $fieldType, string $dpCode, string $intacctCode, string $notes): void
    {
        ConditionalCodeMapping::create([
            'intacct_location_code' => $intacctLocationCode,
            'field_type' => $fieldType,
            'dp_code' => $dpCode,
            'intacct_code' => $intacctCode,
            'is_active' => true,
            'priority' => 0,
            'notes' => $notes,
        ]);
    }
}
