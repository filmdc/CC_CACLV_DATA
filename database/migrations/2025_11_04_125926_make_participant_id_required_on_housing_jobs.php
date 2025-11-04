<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check for any null participant_id records before making it required
        $nullCount = DB::table('housing_jobs')->whereNull('participant_id')->count();

        if ($nullCount > 0) {
            throw new \Exception("Cannot make participant_id required: {$nullCount} housing_jobs have null participant_id. Please fix these records first.");
        }

        Schema::table('housing_jobs', function (Blueprint $table) {
            // Make participant_id NOT NULL
            $table->unsignedBigInteger('participant_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            // Revert to nullable
            $table->unsignedBigInteger('participant_id')->nullable()->change();
        });
    }
};
