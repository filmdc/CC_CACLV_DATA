<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing address data from households table to household_addresses table
        $households = DB::table('households')
            ->whereNotNull('street_address')
            ->orWhereNotNull('city')
            ->orWhereNotNull('state')
            ->orWhereNotNull('zip_code')
            ->get();

        foreach ($households as $household) {
            // Only create address record if there's at least some address data
            if ($household->street_address || $household->city || $household->state || $household->zip_code) {
                $alreadyMigrated = DB::table('household_addresses')
                    ->where('household_id', $household->id)
                    ->where('reason_for_change', 'Initial address from system migration')
                    ->exists();

                if (!$alreadyMigrated) {
                    DB::table('household_addresses')->insert([
                        'household_id' => $household->id,
                        'address' => $household->street_address,
                        'city' => $household->city,
                        'state' => $household->state,
                        'zip_code' => $household->zip_code,
                        'county' => null, // These fields didn't exist before
                        'area' => null,
                        'primary_phone' => null,
                        'is_current' => true,
                        'effective_date' => $household->created_at,
                        'end_date' => null,
                        'created_by' => null, // System migration
                        'reason_for_change' => 'Initial address from system migration',
                        'created_at' => $household->created_at,
                        'updated_at' => $household->updated_at,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migrated address records
        DB::table('household_addresses')
            ->where('reason_for_change', 'Initial address from system migration')
            ->delete();
    }
};
