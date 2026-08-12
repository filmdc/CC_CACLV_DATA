<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records each run of the automatic legacy contract sync so drift, errors
     * and contracts deleted upstream are auditable after the fact.
     */
    public function up(): void
    {
        Schema::create('legacy_contract_sync_runs', function (Blueprint $table) {
            $table->id();

            $table->string('status')->default('running'); // running|success|partial|failed|skipped
            $table->string('triggered_by')->default('scheduled'); // scheduled|manual|command
            $table->boolean('dry_run')->default(false);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // What the comparison against the legacy database found
            $table->unsignedInteger('detected_new')->default(0);
            $table->unsignedInteger('detected_updated')->default(0);
            $table->unsignedInteger('detected_deleted')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);

            // What was actually applied
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('due_dates_created')->default(0);
            $table->unsignedInteger('due_dates_updated')->default(0);
            $table->unsignedInteger('media_synced')->default(0);
            $table->unsignedInteger('funders_created')->default(0);
            $table->unsignedInteger('deferred_count')->default(0);

            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();

            // Legacy ids soft-deleted upstream that still exist here, so the
            // team can decide case by case what to do about them.
            $table->json('deleted_legacy_ids')->nullable();

            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();

            $table->foreignId('executed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'started_at']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_contract_sync_runs');
    }
};
