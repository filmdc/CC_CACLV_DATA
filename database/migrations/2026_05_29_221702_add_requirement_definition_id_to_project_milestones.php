<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Milestone redesign (Phase 1): mark a milestone as a "promoted" Phase 2
 * requirement by linking it back to the RequirementDefinition library
 * atom it materializes. When set, the milestone's name + canonical
 * gating requirement are sourced from the atom; the UI treats it as a
 * compliance gate rather than a PM-composed milestone.
 *
 * Nullable: PM-created composite milestones leave this null.
 *
 * onDelete('set null'): we don't want a library atom soft-delete (or
 * eventual hard-delete) to wipe the project's milestone history. The
 * milestone keeps its name/state; the link to the atom just goes blank.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_milestones', function (Blueprint $table) {
            $table->foreignId('requirement_definition_id')
                ->nullable()
                ->after('housing_job_id')
                ->constrained('requirement_definitions')
                ->nullOnDelete();

            $table->index(
                ['housing_job_id', 'requirement_definition_id'],
                'project_milestones_job_def_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('project_milestones', function (Blueprint $table) {
            $table->dropIndex('project_milestones_job_def_idx');
            $table->dropConstrainedForeignId('requirement_definition_id');
        });
    }
};
