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
        Schema::table('housing_jobs', function (Blueprint $table) {
            // Make these fields nullable to support templates
            // Templates don't have specific participants, properties, or programs
            $table->foreignId('participant_id')->nullable()->change();
            $table->foreignId('property_id')->nullable()->change();
            $table->foreignId('program_id')->nullable()->change();
            $table->date('request_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('housing_jobs', function (Blueprint $table) {
            // Note: This down migration might fail if there are existing templates with NULL values
            // In production, you may want to handle this differently
            $table->foreignId('participant_id')->nullable(false)->change();
            $table->foreignId('property_id')->nullable(false)->change();
            $table->foreignId('program_id')->nullable(false)->change();
            $table->date('request_date')->nullable(false)->change();
        });
    }
};
