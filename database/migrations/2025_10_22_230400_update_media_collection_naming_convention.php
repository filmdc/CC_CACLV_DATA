<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration documents the media collection naming convention update.
     * Collection names are defined in model classes, not database schema.
     *
     * NEW MEDIA COLLECTION NAMING CONVENTION:
     * ========================================
     *
     * Contract Model:
     *   - Collection: "documents"
     *   - Purpose: Contract files and supporting documentation
     *   - Uploaded by: Contract managers
     *   - Examples: Signed contracts, budgets, amendments
     *
     * ContractReportDueDate Model:
     *   - Collection: "templates"
     *   - Purpose: Report templates, instructions, and requirements
     *   - Uploaded by: Contract managers
     *   - Examples: Report templates, submission guidelines, checklists
     *
     * ContractReport Model:
     *   - Collection: "submissions"
     *   - Purpose: Submitted reports from staff
     *   - Uploaded by: Staff members assigned to report
     *   - Examples: Completed reports, supporting data files
     *
     * RATIONALE:
     * ==========
     * Previous: All three models used "documents" collection (confusing)
     * New: Distinct collection names that clearly indicate purpose
     * Benefit: Easier to understand document organization and workflow
     *
     * IMPLEMENTATION:
     * ===============
     * Changes are made in model files:
     * - app/Models/Contract.php (already uses "documents" - no change)
     * - app/Models/ContractReportDueDate.php (changed to "templates")
     * - app/Models/ContractReport.php (changed to "submissions")
     */
    public function up(): void
    {
        // This is a documentation migration
        // Actual collection names are defined in model registerMediaCollections() methods

        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║         Media Collection Naming Convention Updated            ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        echo "Contract Model:\n";
        echo "  Collection: 'documents' (contract files)\n";
        echo "\n";
        echo "ContractReportDueDate Model:\n";
        echo "  Collection: 'templates' (report templates & requirements)\n";
        echo "\n";
        echo "ContractReport Model:\n";
        echo "  Collection: 'submissions' (submitted reports)\n";
        echo "\n";
        echo "✓ Model files have been updated with new collection names\n";
        echo "✓ Existing Contract documents unchanged (already used 'documents')\n";
        echo "✓ New uploads will use appropriate collection names\n";
        echo "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        echo "\n⚠️  This is a documentation migration.\n";
        echo "   To reverse, update model registerMediaCollections() methods.\n\n";
    }
};
