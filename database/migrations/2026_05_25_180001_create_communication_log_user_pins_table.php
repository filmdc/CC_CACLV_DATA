<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12.3: per-user "pin" relationship on Communication Logs.
 *
 * Lets the worker keep a handful of logs front-and-center on the
 * Housing Dashboard — e.g. active applicants she's currently chasing,
 * VIP cases, or anything she doesn't want to lose in the long list.
 *
 * Per-user (not global) because different users may care about
 * different cases. Cascades on both sides so deleting a log or a user
 * cleans up the pin rows without leaving orphans.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_log_user_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('communication_log_id')
                ->constrained('communication_logs', 'id', 'fk_cl_user_pins_log_id')
                ->cascadeOnDelete();
            $table->timestamp('pinned_at');
            $table->timestamps();

            $table->unique(['user_id', 'communication_log_id'], 'uq_cl_user_pins');
            $table->index('communication_log_id', 'ix_cl_user_pins_log_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_log_user_pins');
    }
};
