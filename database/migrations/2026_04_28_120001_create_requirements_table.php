<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P-18 (Phase 3A): unified per-project requirements table.
     *
     * Every requirement on a project is one row here. The `type` column
     * distinguishes documents (file expected, with override-reason audit
     * path) from tasks (mark-complete with a note). The `source_type` enum
     * + `source_id` polymorphic-style pointer records which milestone /
     * fund code / ad-hoc origin produced the row, so lifecycle controls
     * (detach prompts, milestone-delete prompts) can find their progeny.
     *
     * Templates copy on instantiate — `source_label_snapshot` retains the
     * upstream template label at the moment of instantiation, even if a
     * future admin edit renames the template. No FK on `source_id` because
     * it is polymorphic; no FK on `source_template_id` because a template
     * may be deleted without cascading the project requirement (lifecycle
     * decision left to the global-delete prompt).
     *
     * No approval columns. The override fields cover audit needs for
     * document-type completion without an upload; Tony/Peter do not log
     * in to approve. Phase 5 may layer a proper approval flow on top.
     */
    public function up(): void
    {
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('housing_job_id')
                ->constrained('housing_jobs')
                ->cascadeOnDelete();

            $table->enum('type', ['document', 'task']);

            $table->string('title', 255);
            $table->text('description')->nullable();

            $table->enum('status', ['outstanding', 'complete', 'not_applicable'])
                ->default('outstanding');

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('source_type', ['milestone', 'fund_code', 'ad_hoc']);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('source_template_id')->nullable();
            $table->string('source_label_snapshot', 255)->nullable();

            $table->text('milestone_gate_text')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('completion_note')->nullable();

            $table->text('override_reason')->nullable();
            $table->foreignId('overridden_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['owner_user_id', 'status'], 'requirements_owner_status_idx');
            $table->index(['housing_job_id', 'status'], 'requirements_job_status_idx');
            $table->index(['source_type', 'source_id'], 'requirements_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirements');
    }
};
