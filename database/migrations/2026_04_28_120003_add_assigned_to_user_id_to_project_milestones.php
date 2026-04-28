<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P-18 (Phase 3A): per the new requirements model, every milestone
     * has a single assignee whose user becomes the default owner of any
     * milestone-generated requirement. Granular per-task and per-document
     * `assigned_to` columns on the legacy milestone_tasks /
     * milestone_documents tables remain for legacy data; new requirements
     * inherit the milestone's assignee unless explicitly reassigned.
     *
     * Existing rows stay NULL — no fabrication. The UI prompts for an
     * assignee on every new milestone going forward, and milestone edit
     * surfaces a reassign control for existing rows.
     */
    public function up(): void
    {
        Schema::table('project_milestones', function (Blueprint $table) {
            $table->foreignId('assigned_to_user_id')
                ->nullable()
                ->after('color')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_milestones', function (Blueprint $table) {
            $table->dropForeign(['assigned_to_user_id']);
            $table->dropColumn('assigned_to_user_id');
        });
    }
};
