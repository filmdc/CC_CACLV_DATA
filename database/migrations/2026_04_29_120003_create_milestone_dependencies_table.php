<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3A scope addition: project-level milestone dependencies.
     *
     * A milestone can depend on multiple other milestones; a milestone
     * can block multiple others. Dependencies live on the project's
     * actual project_milestones rows, not on milestone templates.
     * Cross-project edges are rejected at the validator layer; same-
     * project scope is enforced there too because a foreign key alone
     * cannot express "same housing_job_id."
     *
     * Composite primary key on the pair prevents duplicate edges. Both
     * FKs cascade so a milestone's deletion cleans up both inbound
     * and outbound dependency rows automatically — independent of the
     * Step 7 milestone-delete keep/delete-requirements prompt, which
     * concerns Requirement rows rather than dependency edges.
     *
     * Marking a milestone as "ready" or "blocked" is computed (not
     * stored) on dashboard render via a precomputed blocked-set query.
     */
    public function up(): void
    {
        Schema::create('milestone_dependencies', function (Blueprint $table) {
            $table->foreignId('milestone_id')
                ->constrained('project_milestones')
                ->cascadeOnDelete();
            $table->foreignId('depends_on_milestone_id')
                ->constrained('project_milestones')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['milestone_id', 'depends_on_milestone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_dependencies');
    }
};
