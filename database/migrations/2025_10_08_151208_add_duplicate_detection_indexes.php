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
            if (!$this->hasIndex('participants', 'idx_participants_duplicate_check')) {
                $table->index(['first_name', 'last_name', 'date_of_birth'], 'idx_participants_duplicate_check');
            }

            // Add index for email-based duplicate detection
            if (!$this->hasIndex('participants', 'idx_participants_email')) {
                $table->index('email', 'idx_participants_email');
            }

            // Add index for telephone-based duplicate detection
            if (!$this->hasIndex('participants', 'idx_participants_telephone')) {
                $table->index('telephone', 'idx_participants_telephone');
            }
        });

        Schema::table('households', function (Blueprint $table) {
            // Add index for household_code lookups
            if (Schema::hasColumn('households', 'household_code') && !$this->hasIndex('households', 'idx_households_code')) {
                $table->index('household_code', 'idx_households_code');
            }
        });

        // Add indexes to potential_duplicates table for performance
        Schema::table('potential_duplicates', function (Blueprint $table) {
            // Index for filtering by status
            if (!$this->hasIndex('potential_duplicates', 'idx_potential_duplicates_status')) {
                $table->index('status', 'idx_potential_duplicates_status');
            }

            // Index for filtering by source type
            if (!$this->hasIndex('potential_duplicates', 'idx_potential_duplicates_source_type')) {
                $table->index('source_type', 'idx_potential_duplicates_source_type');
            }

            // Index for filtering by matched participant
            if (!$this->hasIndex('potential_duplicates', 'idx_potential_duplicates_matched')) {
                $table->index('matched_participant_id', 'idx_potential_duplicates_matched');
            }

            // Index for filtering by confidence score
            if (!$this->hasIndex('potential_duplicates', 'idx_potential_duplicates_confidence')) {
                $table->index('confidence_score', 'idx_potential_duplicates_confidence');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            foreach ([
                'idx_participants_duplicate_check',
                'idx_participants_email',
                'idx_participants_telephone',
            ] as $index) {
                if ($this->hasIndex('participants', $index)) {
                    $table->dropIndex($index);
                }
            }
        });

        Schema::table('households', function (Blueprint $table) {
            if ($this->hasIndex('households', 'idx_households_code')) {
                $table->dropIndex('idx_households_code');
            }
        });

        Schema::table('potential_duplicates', function (Blueprint $table) {
            foreach ([
                'idx_potential_duplicates_status',
                'idx_potential_duplicates_source_type',
                'idx_potential_duplicates_matched',
                'idx_potential_duplicates_confidence',
            ] as $index) {
                if ($this->hasIndex('potential_duplicates', $index)) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $tableName = $connection->getTablePrefix() . $table;
        $results = $connection->select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName]);

        return !empty($results);
    }
};
