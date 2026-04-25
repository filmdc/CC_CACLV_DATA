<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P-19: stamp the moment a milestone enters in_progress.
     *
     * Existing rows stay NULL on purpose — the UI shows "Started: —"
     * until the next state transition. Backfilling from updated_at would
     * fabricate timestamps and lie to operators. The next time a
     * milestone is moved into in_progress (or auto-advanced via
     * markAsCompleted), the stamp lands.
     */
    public function up(): void
    {
        Schema::table('project_milestones', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('completed_date');
        });
    }

    public function down(): void
    {
        Schema::table('project_milestones', function (Blueprint $table) {
            $table->dropColumn('started_at');
        });
    }
};
