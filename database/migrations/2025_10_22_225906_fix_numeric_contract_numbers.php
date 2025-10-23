<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Contract;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration fixes contracts where the contract_number field contains
     * numeric-only values that are likely legacy database IDs rather than
     * actual contract numbers from funders.
     *
     * For affected contracts, we:
     * 1. Add a note documenting the original numeric value
     * 2. Clear the contract_number field (set to NULL)
     * 3. Log the changes for audit purposes
     */
    public function up(): void
    {
        // Find all contracts with numeric-only contract numbers
        $affectedContracts = DB::table('contracts')
            ->whereNotNull('contract_number')
            ->whereRaw('contract_number REGEXP \'^[0-9]+$\'')
            ->get();

        $fixedCount = 0;
        $logEntries = [];

        foreach ($affectedContracts as $contract) {
            // Get current notes
            $currentNotes = $contract->notes ?? '';

            // Prepare the legacy note
            $legacyNote = "\n\n[MIGRATION NOTE - " . date('Y-m-d') . "]: Original contract_number '{$contract->contract_number}' was identified as a likely legacy database ID and has been cleared. If this was a valid contract number, please re-enter it.";

            // Append to existing notes
            $updatedNotes = trim($currentNotes . $legacyNote);

            // Update the contract
            DB::table('contracts')
                ->where('id', $contract->id)
                ->update([
                    'contract_number' => null,
                    'notes' => $updatedNotes,
                    'updated_at' => now(),
                ]);

            $logEntries[] = [
                'id' => $contract->id,
                'title' => $contract->title,
                'old_contract_number' => $contract->contract_number,
            ];

            $fixedCount++;
        }

        // Log the changes to Laravel log
        if ($fixedCount > 0) {
            \Log::info("Fixed {$fixedCount} contracts with numeric-only contract numbers", [
                'affected_contracts' => $logEntries
            ]);

            echo "\n✓ Fixed {$fixedCount} contracts with numeric-only contract numbers\n";
            echo "  Details have been logged to storage/logs/laravel.log\n\n";
        } else {
            echo "\n✓ No contracts found with numeric-only contract numbers\n\n";
        }
    }

    /**
     * Reverse the migrations.
     *
     * Note: This is a data-quality migration and is NOT fully reversible.
     * The original contract_number values have been documented in the notes field.
     */
    public function down(): void
    {
        echo "\n⚠️  WARNING: This migration is not fully reversible.\n";
        echo "   Original contract_number values have been documented in each contract's notes field.\n";
        echo "   Manual review is required to restore the original values if needed.\n\n";
    }
};
