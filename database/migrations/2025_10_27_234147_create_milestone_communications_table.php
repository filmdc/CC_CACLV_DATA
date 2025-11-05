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
        Schema::create('milestone_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained('project_milestones')->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Index for performance
            $table->index('milestone_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestone_communications');
    }
};
