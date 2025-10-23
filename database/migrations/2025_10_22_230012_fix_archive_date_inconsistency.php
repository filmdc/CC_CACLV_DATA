<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration fixes data inconsistency where contracts are marked as
     * active (archived = false) but still have a date_archived value set.
     *
     * For affected contracts, we clear the date_archived field to maintain
     * data integrity.
     */
    public function up(): void
    {
        // Find all active contracts that incorrectly have date_archived set
        $affectedContracts = DB::table('contracts')
            ->where('archived', false)
            ->whereNotNull('date_archived')
            ->get();

        $fixedCount = 0;
        $logEntries = [];

        foreach ($affectedContracts as $contract) {
            // Store the original date for logging
            $originalDate = $contract->date_archived;

            // Clear the date_archived field
            DB::table('contracts')
                ->where('id', $contract->id)
                ->update([
                    'date_archived' => null,
                    'updated_at' => now(),
                ]);

            $logEntries[] = [
                'id' => $contract->id,
                'title' => $contract->title,
                'cleared_date_archived' => $originalDate,
            ];

            $fixedCount++;
        }

        // Log the changes
        if ($fixedCount > 0) {
            \Log::info("Fixed {$fixedCount} active contracts with inconsistent date_archived", [
                'affected_contracts' => $logEntries
            ]);

            echo "\n✓ Fixed {$fixedCount} active contracts with date_archived set\n";
            echo "  Details have been logged to storage/logs/laravel.log\n\n";
        } else {
            echo "\n✓ No archive date inconsistencies found\n\n";
        }
    }

    /**
     * Reverse the migrations.
     *
     * Note: This migration clears invalid data. Reversal would require
     * restoring the incorrect state, which is not recommended.
     */
    public function down(): void
    {
        echo "\n⚠️  WARNING: This migration fixed data inconsistencies and is not reversible.\n";
        echo "   The original date_archived values have been logged to storage/logs/laravel.log\n";
        echo "   if restoration is needed for specific records.\n\n";
    }
};
