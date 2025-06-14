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
        Schema::create('program_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->onDelete('cascade');
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->date('enrollment_date');
            $table->date('exit_date')->nullable();
            $table->enum('status', ['active', 'completed', 'withdrawn', 'transferred'])->default('active');
            $table->enum('exit_reason', [
                'completed_successfully',
                'moved_out_of_area',
                'no_longer_eligible',
                'non_compliance',
                'participant_choice',
                'other'
            ])->nullable();
            $table->text('exit_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Add index for performance
            $table->index(['participant_id', 'program_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_enrollments');
    }
};