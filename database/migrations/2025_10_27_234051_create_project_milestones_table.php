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
        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_job_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sequence_order');
            $table->enum('status', ['not_started', 'in_progress', 'completed'])->default('not_started');
            $table->date('target_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->string('icon')->default('Circle'); // Icon name from Lucide React
            $table->string('color')->default('#6366f1'); // Hex color code
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Index for performance
            $table->index(['housing_job_id', 'sequence_order']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_milestones');
    }
};
