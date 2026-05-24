<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P-39b: intake-phase requirements rooted on CommunicationLog.
 *
 * Mirrors the structure of `requirements` (Phase 3A, P-18) but with
 * intake-phase semantics:
 *   - statuses are outstanding/received/waived (not complete/N-A);
 *   - the soft-delete-on-detach reconciliation is implemented via
 *     is_orphaned rather than a dedicated table (OQ-1, orphan-and-keep);
 *   - no owner_user_id — Kaitlyn is the sole intake worker in v1 and the
 *     scope explicitly defers notifications (OQ-6).
 *
 * No FK on source_template_id by design (matches the Phase 3A decision
 * documented in the requirements migration): templates may be deleted
 * without cascading the intake instance, and the global-delete prompt
 * surfaces the lifecycle question to the operator.
 *
 * No deleted_at — hard delete matches the rest of the housing stack
 * (OQ-7). Communication-log deletion cascades intake requirements via
 * the FK; that is the intentional posture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_requirements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('communication_log_id')
                ->constrained('communication_logs')
                ->cascadeOnDelete();

            $table->enum('type', ['document', 'task']);

            $table->string('label', 255);

            $table->enum('status', ['outstanding', 'received', 'waived'])
                ->default('outstanding');

            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('source_type', ['baseline', 'fund_code', 'ad_hoc']);
            $table->unsignedBigInteger('source_template_id')->nullable();
            $table->string('source_label_snapshot', 255)->nullable();

            $table->boolean('is_orphaned')->default(false);

            $table->text('notes')->nullable();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(
                ['communication_log_id', 'status', 'is_orphaned'],
                'intake_req_log_status_orphan_idx'
            );
            $table->index(
                ['source_type', 'source_template_id'],
                'intake_req_source_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_requirements');
    }
};
