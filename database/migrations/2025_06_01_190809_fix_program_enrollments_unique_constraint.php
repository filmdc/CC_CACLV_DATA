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
        Schema::table('program_enrollments', function (Blueprint $table) {
            // Drop the problematic unique constraint
            $table->dropUnique('unique_active_enrollment');
            
            // Add a regular index instead for performance
            $table->index(['participant_id', 'program_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_enrollments', function (Blueprint $table) {
            // Remove the index
            $table->dropIndex(['participant_id', 'program_id', 'status']);
            
            // Re-add the unique constraint
            $table->unique(['participant_id', 'program_id', 'status'], 'unique_active_enrollment');
        });
    }
};