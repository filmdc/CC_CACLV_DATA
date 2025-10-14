<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('service_enrollments')) {
            Schema::create('service_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('participant_id')->constrained()->onDelete('cascade');
                $table->foreignId('service_id')->constrained()->onDelete('cascade');
                $table->foreignId('program_enrollment_id')->constrained()->onDelete('cascade');
                $table->date('enrollment_date');
                $table->date('completion_date')->nullable();
                $table->enum('status', ['enrolled', 'in_progress', 'completed', 'withdrawn'])->default('enrolled');
                $table->json('outcomes')->nullable(); // Service-specific outcomes and measurements
                $table->decimal('hours_received', 8, 2)->nullable(); // Hours of service received
                $table->integer('sessions_attended')->nullable(); // Number of sessions attended
                $table->text('completion_notes')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                // Index for performance
                $table->index(['participant_id', 'service_id']);
                $table->index(['program_enrollment_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_enrollments');
    }
};
