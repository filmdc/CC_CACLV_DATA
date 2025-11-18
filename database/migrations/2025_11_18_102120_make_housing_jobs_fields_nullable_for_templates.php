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
            // Templates are reusable patterns not tied to specific participants/properties/programs
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
            // Restore NOT NULL constraints
            // Note: This will fail if there are templates with NULL values
            $table->foreignId('participant_id')->nullable(false)->change();
            $table->foreignId('property_id')->nullable(false)->change();
            $table->foreignId('program_id')->nullable(false)->change();
            $table->date('request_date')->nullable(false)->change();
        });
    }
};
