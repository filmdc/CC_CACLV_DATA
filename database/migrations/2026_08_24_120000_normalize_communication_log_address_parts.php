<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize the free-typed address parts on communication_logs:
     * trim stray whitespace, uppercase the state, and null-out empty
     * strings — so "Allentown " and "Allentown" are one city in the
     * portal's city filter and in reports.
     */
    public function up(): void
    {
        DB::table('communication_logs')->update([
            'contact_address' => DB::raw("NULLIF(TRIM(contact_address), '')"),
            'contact_city' => DB::raw("NULLIF(TRIM(contact_city), '')"),
            'contact_state' => DB::raw("NULLIF(UPPER(TRIM(contact_state)), '')"),
            'contact_zip' => DB::raw("NULLIF(TRIM(contact_zip), '')"),
        ]);
    }

    /**
     * Data cleanup only — nothing to reverse.
     */
    public function down(): void
    {
        //
    }
};
