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
        Schema::table('participants', function (Blueprint $table) {
            // Add composite index for duplicate detection (first_name + last_name + date_of_birth)
            $table->index(['first_name', 'last_name', 'date_of_birth'], 'idx_participants_duplicate_check');

            // Add index for email-based duplicate detection
            $table->index('email', 'idx_participants_email');

            // Add index for telephone-based duplicate detection
            $table->index('telephone', 'idx_participants_telephone');
        });

        Schema::table('households', function (Blueprint $table) {
            // Add index for household_code lookups
            if (!Schema::hasColumn('households', 'household_code')) {
                // Index likely already exists, but add if missing
                $table->index('household_code', 'idx_households_code');
            }
        });

        // Add indexes to potential_duplicates table for performance
        Schema::table('potential_duplicates', function (Blueprint $table) {
            // Index for filtering by status
            $table->index('status', 'idx_potential_duplicates_status');

            // Index for filtering by source type
            $table->index('source_type', 'idx_potential_duplicates_source_type');

            // Index for filtering by matched participant
            $table->index('matched_participant_id', 'idx_potential_duplicates_matched');

            // Index for filtering by confidence score
            $table->index('confidence_score', 'idx_potential_duplicates_confidence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropIndex('idx_participants_duplicate_check');
            $table->dropIndex('idx_participants_email');
            $table->dropIndex('idx_participants_telephone');
        });

        Schema::table('households', function (Blueprint $table) {
            if (Schema::hasIndex('households', 'idx_households_code')) {
                $table->dropIndex('idx_households_code');
            }
        });

        Schema::table('potential_duplicates', function (Blueprint $table) {
            $table->dropIndex('idx_potential_duplicates_status');
            $table->dropIndex('idx_potential_duplicates_source_type');
            $table->dropIndex('idx_potential_duplicates_matched');
            $table->dropIndex('idx_potential_duplicates_confidence');
        });
    }
};
