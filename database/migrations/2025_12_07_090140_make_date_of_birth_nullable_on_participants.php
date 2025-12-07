<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make date_of_birth nullable to support quick participant creation
        // from communications log and other simplified intake workflows
        DB::statement('ALTER TABLE participants MODIFY COLUMN date_of_birth DATE NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This will fail if there are NULL values in date_of_birth
        DB::statement('ALTER TABLE participants MODIFY COLUMN date_of_birth DATE NOT NULL');
    }
};
