<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comm Log v2 / dashboard split: per-user project pins.
 *
 * Mirrors communication_log_user_pins (intake-side) so the Projects
 * dashboard can float a user's pinned housing jobs to the top of the
 * per-project list, independent of the chosen sort.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('housing_job_user_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('housing_job_id')
                ->constrained('housing_jobs', 'id', 'fk_hj_user_pins_job_id')
                ->cascadeOnDelete();
            $table->timestamp('pinned_at');
            $table->timestamps();

            $table->unique(['user_id', 'housing_job_id'], 'uq_hj_user_pins');
            $table->index('housing_job_id', 'ix_hj_user_pins_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('housing_job_user_pins');
    }
};
