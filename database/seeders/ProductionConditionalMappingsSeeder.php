<?php

namespace Database\Seeders;

use App\Models\ConditionalCodeMapping;
use Illuminate\Database\Seeder;

class ProductionConditionalMappingsSeeder extends Seeder
{
    /**
     * Seed production conditional code mappings.
     *
     * Conditional mappings define location-specific code rules for Sage Intacct sync.
     * These specify which solicitation and sub-solicitation codes are allowed for
     * each Sage Intacct location (program).
     *
     * Generated from production database on: 2025-11-21
     * Total mappings: 79
     */
    public function run(): void
    {
        $mappings = [
            ['intacct_location_code' => 'P1000', 'field_type' => 'solicit_code', 'dp_code' => 'ANNLFUND', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Admin - Annual Fund', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'solicit_code', 'dp_code' => 'EVENTS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Admin - Events', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'solicit_code', 'dp_code' => 'WEBDONATIONS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Admin - Web Donations', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'solicit_code', 'dp_code' => 'ANNUFUND', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Admin - Annual Fund (alternate)', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'CALVHMAP', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'CALVHMAPP', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'CAUSEMARK', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'CORPBUSTGRT', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service - Corporate Grant', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'CORPMATCGFT', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service - Corp Matching', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'FNDTRUSTGRT', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service - Foundation Grant', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'ONLINEFALLNEWSLETTER', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'UNSOLICIT', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service - Unsolicited', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'USOLFAITHBCONTB', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'USOLFNDCONTRIB', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'UWEEGIVNG', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service - United Way', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'AMO', 'intacct_code' => 'S10160', 'priority' => 0, 'notes' => 'ADMIN160-Annual Meeting', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'ANLMTGDO', 'intacct_code' => 'S10160', 'priority' => 0, 'notes' => 'ADMIN160-Annual Meeting - Donations', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'ANLMTGSPONS', 'intacct_code' => 'S10160', 'priority' => 0, 'notes' => 'ADMIN160-Annual Meeting - Sponsorship', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'ANLMTGTIX', 'intacct_code' => 'S10160', 'priority' => 0, 'notes' => 'ADMIN160-Annual Meeting - Tickets', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'ANNLFUND', 'intacct_code' => 'S10160', 'priority' => 0, 'notes' => 'ADMIN160-Annual Meeting - Annual Fund', 'is_active' => true],
            ['intacct_location_code' => 'P1000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'ALE', 'intacct_code' => 'S10999', 'priority' => 0, 'notes' => 'ADMIN999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1100', 'field_type' => 'solicit_code', 'dp_code' => 'ANNLFUND', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Housing Counseling - Annual Fund', 'is_active' => true],
            ['intacct_location_code' => 'P1100', 'field_type' => 'solicit_code', 'dp_code' => 'WEBDONATIONS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Housing Counseling - Web Donations', 'is_active' => true],
            ['intacct_location_code' => 'P1300', 'field_type' => 'solicit_code', 'dp_code' => 'ANNLFUND', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'REJ - Annual Fund', 'is_active' => true],
            ['intacct_location_code' => 'P1300', 'field_type' => 'solicit_code', 'dp_code' => 'EVENTS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'REJ - Events', 'is_active' => true],
            ['intacct_location_code' => 'P1300', 'field_type' => 'solicit_code', 'dp_code' => 'WEBDONATIONS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'REJ - Web Donations', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'solicit_code', 'dp_code' => 'ANNUFUND', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Food Bank - Annual Fund (alternate)', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'solicit_code', 'dp_code' => 'TSF', 'intacct_code' => 'F8040', 'priority' => 0, 'notes' => null, 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'solicit_code', 'dp_code' => 'ANNLFUND', 'intacct_code' => 'F8000', 'priority' => 99, 'notes' => 'Food Bank - Annual Fund', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'solicit_code', 'dp_code' => 'EVENTS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Food Bank - Events', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'solicit_code', 'dp_code' => 'EVENTSPONSORSHIP', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Food Bank - Event Sponsorship', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'solicit_code', 'dp_code' => 'NEWSLETTER', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Food Bank - Newsletter', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'solicit_code', 'dp_code' => 'WEBDONATIONS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Food Bank - Web Donations', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'solicit_code', 'dp_code' => 'SHFB_WEBDONATIONS_FY22-23', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Food Bank - Web Donations FY22-23', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => 'TAKACTDONS', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service - Donations', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24HADM06', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24HADM05', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24HADM03', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24HAAQBN', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24FLDI', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24CHDI', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24BSDMDI', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service - 2024 Direct Mail', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => 'TAKACTTIXS', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service - Tickets', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24MMDI', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => 'TAKACSPONS', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service - Sponsorship', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => 'SHFBHPAPPL', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => 'FBSUNEWS', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24HADMDI', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24HNDM05', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24NDDM04', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24NDDM05', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24NDDM06', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => 'UNSOLICIT', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => null, 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24PADI', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '24THDM04', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => 'FBPPAPPL', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => 'FBHPAPPL', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1400', 'field_type' => 'sub_solicit_code', 'dp_code' => '25MMDI', 'intacct_code' => 'S14999', 'priority' => 0, 'notes' => 'SHFB999-Undesignated Service', 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'solicit_code', 'dp_code' => 'ANNLFUND', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Shelter - Annual Fund', 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'solicit_code', 'dp_code' => 'NEWSLETTER', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Shelter - Newsletter', 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'solicit_code', 'dp_code' => 'EVENTS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Shelter - Events', 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'solicit_code', 'dp_code' => 'SSS_UW_FY22-23', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Shelter - United Way FY22-23', 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'solicit_code', 'dp_code' => 'SSSWEB_FY23-24', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Shelter - Web FY23-24', 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'solicit_code', 'dp_code' => 'SSSWEB_FY22-23', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Shelter - Web FY22-23', 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'solicit_code', 'dp_code' => 'WEBDONATIONS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Shelter - Web Donations', 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'solicit_code', 'dp_code' => 'ANNUFUND', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Shelter - Annual Fund (alternate)', 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'sub_solicit_code', 'dp_code' => 'UNSOLICIT', 'intacct_code' => 'S16999', 'priority' => 0, 'notes' => null, 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'sub_solicit_code', 'dp_code' => 'SSSWEBPAGE', 'intacct_code' => 'S16999', 'priority' => 0, 'notes' => 'SSS999-Undesignated Service - Web Page', 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'sub_solicit_code', 'dp_code' => 'SSSPNGNWSL', 'intacct_code' => 'S16999', 'priority' => 0, 'notes' => 'SSS999-Undesignated Service - Spring Newsletter', 'is_active' => true],
            ['intacct_location_code' => 'P1600', 'field_type' => 'sub_solicit_code', 'dp_code' => 'SSSFALLNWSL', 'intacct_code' => 'S16999', 'priority' => 0, 'notes' => 'SSS999-Undesignated Service - Fall Newsletter', 'is_active' => true],
            ['intacct_location_code' => 'P2000', 'field_type' => 'solicit_code', 'dp_code' => 'WEBDONATIONS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'CADCA - Web Donations', 'is_active' => true],
            ['intacct_location_code' => 'P2000', 'field_type' => 'solicit_code', 'dp_code' => 'EVENTS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'CADCA - Events', 'is_active' => true],
            ['intacct_location_code' => 'P2000', 'field_type' => 'solicit_code', 'dp_code' => 'ANNLFUND', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'CADCA - Annual Fund', 'is_active' => true],
            ['intacct_location_code' => 'P2000', 'field_type' => 'sub_solicit_code', 'dp_code' => 'CABSTMREVNT', 'intacct_code' => 'S20999', 'priority' => 0, 'notes' => 'CADCA999-Undesignated Service - Best Move Event', 'is_active' => true],
            ['intacct_location_code' => 'P3000', 'field_type' => 'solicit_code', 'dp_code' => 'EVENTS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'CADCB - Events', 'is_active' => true],
            ['intacct_location_code' => 'P3000', 'field_type' => 'solicit_code', 'dp_code' => 'WEBDONATIONS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'CADCB - Web Donations', 'is_active' => true],
            ['intacct_location_code' => 'P3000', 'field_type' => 'solicit_code', 'dp_code' => 'ANNLFUND', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'CADCB - Annual Fund', 'is_active' => true],
            ['intacct_location_code' => 'P5000', 'field_type' => 'solicit_code', 'dp_code' => 'ANNLFUND', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Rising Tide - Annual Fund', 'is_active' => true],
            ['intacct_location_code' => 'P5000', 'field_type' => 'solicit_code', 'dp_code' => 'WEBDONATIONS', 'intacct_code' => 'F8000', 'priority' => 0, 'notes' => 'Rising Tide - Web Donations', 'is_active' => true],
        ];

        $this->command->info("Seeding " . count($mappings) . " conditional code mappings...");

        foreach ($mappings as $mapping) {
            ConditionalCodeMapping::updateOrCreate(
                [
                    'intacct_location_code' => $mapping['intacct_location_code'],
                    'field_type' => $mapping['field_type'],
                    'dp_code' => $mapping['dp_code'],
                ],
                [
                    'intacct_code' => $mapping['intacct_code'],
                    'priority' => $mapping['priority'],
                    'notes' => $mapping['notes'],
                    'is_active' => $mapping['is_active'],
                ]
            );
        }

        $this->command->info("✓ Conditional mappings seeded successfully");

        // Show breakdown by location
        $byLocation = array_reduce($mappings, function ($carry, $item) {
            $loc = $item['intacct_location_code'];
            $carry[$loc] = ($carry[$loc] ?? 0) + 1;
            return $carry;
        }, []);
        foreach ($byLocation as $location => $count) {
            $this->command->info("  - Location {$location}: {$count} rules");
        }
    }
}
